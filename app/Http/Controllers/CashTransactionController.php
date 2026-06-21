<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CashTransactionController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = CashTransaction::with(['transactable', 'duePayments.payable'])->orderBy('date_time', 'desc')
            ->where('user_id', $userId);

        if ($request->has('transactable_type') && $request->has('transactable_id')) {
            $query->where('transactable_type', $request->transactable_type)
                  ->where('transactable_id', $request->transactable_id);
        }

        // Optional filtering by type ('in' or 'out')
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Optional filtering by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Optional filtering by payment_method
        if ($request->get('payment_method') === 'cash') {
            $query->where(function($q) {
                $q->whereNull('payment_method')
                  ->orWhere('payment_method', 'cash');
            });
        } elseif ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        } elseif ($request->has('payment_methods')) {
            $methods = is_array($request->payment_methods)
                ? $request->payment_methods
                : explode(',', $request->payment_methods);
            $query->whereIn('payment_method', $methods);
        }

        // Optional filtering by date period
        $startDate = null;
        if ($request->has('period') && $request->has('date')) {
            $date = Carbon::parse($request->date);
            $period = $request->period;

            if ($period === 'day') {
                $startDate = $date->copy()->startOfDay();
                $query->whereDate('date_time', $date->toDateString());
            } elseif ($period === 'month') {
                $startDate = $date->copy()->startOfMonth();
                $query->whereMonth('date_time', $date->month)
                      ->whereYear('date_time', $date->year);
            } elseif ($period === 'year') {
                $startDate = $date->copy()->startOfYear();
                $query->whereYear('date_time', $date->year);
            }
        }

        if ($request->has('page')) {
            $perPage = $request->get('per_page', 50);

            // Calculate filtered sums using query builder clone BEFORE paginating
            $filteredCashIn = (double) (clone $query)->where('type', 'in')->sum('amount');
            $filteredCashOut = (double) (clone $query)->where('type', 'out')->sum('amount');

            $isOnlineQuery = ($request->has('payment_method') && $request->payment_method !== 'cash') ||
                             ($request->has('payment_methods') && !in_array('cash', is_array($request->payment_methods) ? $request->payment_methods : explode(',', $request->payment_methods)));

            // 1. Current overall balance
            $currentBalance = 0.0;
            if ($isOnlineQuery) {
                $currentBalance = (double) CashTransaction::where('user_id', $userId)
                    ->whereIn('payment_method', ['bkash_nagad', 'bank'])
                    ->sum('amount');
            } else {
                if ($userId) {
                    $user = \App\Models\User::find($userId);
                    if ($user) {
                        $currentBalance = (double) $user->cash_balance;
                    }
                }
            }

            // 2. Opening Balance for the queried period
            $openingBalance = 0.0;
            if ($userId && $startDate) {
                if ($isOnlineQuery) {
                    $flowAfter = (double) CashTransaction::where('user_id', $userId)
                        ->whereIn('payment_method', ['bkash_nagad', 'bank'])
                        ->where('date_time', '>=', $startDate->copy()->addDay()->startOfDay())
                        ->sum('amount');
                    $openingBalance = $currentBalance - $flowAfter - $filteredCashIn;
                } else {
                    $netFlowFromStartDateOnward = \App\Models\DailyCashSummary::where('user_id', $userId)
                        ->where('date', '>=', $startDate->toDateString())
                        ->selectRaw('SUM(cash_in - cash_out) as net')
                        ->value('net');
                    $openingBalance = $currentBalance - (double) $netFlowFromStartDateOnward;
                }
            }

            $closingBalance = $openingBalance + $filteredCashIn - $filteredCashOut;

            $paginated = $query->paginate($perPage);

            return response()->json([
                'transactions' => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'summary' => [
                    'total_cash_in' => (double)$filteredCashIn,
                    'total_cash_out' => (double)$filteredCashOut,
                    'current_balance' => $currentBalance,
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $closingBalance,
                    'filtered_cash_in' => (double)$filteredCashIn,
                    'filtered_cash_out' => (double)$filteredCashOut,
                ]
            ]);
        }

        $transactions = $query->get();

        $isOnlineQuery = ($request->has('payment_method') && $request->payment_method !== 'cash') ||
                         ($request->has('payment_methods') && !in_array('cash', is_array($request->payment_methods) ? $request->payment_methods : explode(',', $request->payment_methods)));

        // 1. Current overall balance
        $currentBalance = 0.0;
        if ($isOnlineQuery) {
            $currentBalance = (double) CashTransaction::where('user_id', $userId)
                ->whereIn('payment_method', ['bkash_nagad', 'bank'])
                ->sum('amount');
        } else {
            if ($userId) {
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $currentBalance = (double) $user->cash_balance;
                }
            }
        }

        // 2. Opening Balance for the queried period
        $openingBalance = 0.0;
        if ($userId && $startDate) {
            if ($isOnlineQuery) {
                // Determine filtered cash in for opening balance math
                $filteredCashInForMath = $transactions->where('type', 'in')->sum('amount');
                $flowAfter = (double) CashTransaction::where('user_id', $userId)
                    ->whereIn('payment_method', ['bkash_nagad', 'bank'])
                    ->where('date_time', '>=', $startDate->copy()->addDay()->startOfDay())
                    ->sum('amount');
                $openingBalance = $currentBalance - $flowAfter - $filteredCashInForMath;
            } else {
                $netFlowFromStartDateOnward = \App\Models\DailyCashSummary::where('user_id', $userId)
                    ->where('date', '>=', $startDate->toDateString())
                    ->selectRaw('SUM(cash_in - cash_out) as net')
                    ->value('net');
                $openingBalance = $currentBalance - (double) $netFlowFromStartDateOnward;
            }
        }

        // Summaries for the currently filtered set
        $filteredCashIn = $transactions->where('type', 'in')->sum('amount');
        $filteredCashOut = $transactions->where('type', 'out')->sum('amount');

        $closingBalance = $openingBalance + $filteredCashIn - $filteredCashOut;

        return response()->json([
            'transactions' => $transactions,
            'summary' => [
                'total_cash_in' => (double)$filteredCashIn,
                'total_cash_out' => (double)$filteredCashOut,
                'current_balance' => $currentBalance,
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'filtered_cash_in' => (double)$filteredCashIn,
                'filtered_cash_out' => (double)$filteredCashOut,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid|unique:cash_transactions,uuid',
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'date_time' => 'nullable|date',
            'transactable_type' => 'nullable|string',
            'transactable_id' => 'nullable|integer',
            'payment_method' => 'nullable|string',
        ]);

        $userId = $request->user()->id;
        $validated['user_id'] = $userId;

        $transaction = DB::transaction(function () use ($validated) {
            $tx = CashTransaction::create($validated);

            $isCustomerPayment = ($tx->type === 'in' && 
                                  ($tx->transactable_type === 'App\\Models\\Customer' || 
                                   $tx->transactable_type === 'App\Models\Customer'));

            $isSupplierPayment = ($tx->type === 'out' && 
                                  ($tx->transactable_type === 'App\\Models\\Supplier' || 
                                   $tx->transactable_type === 'App\Models\Supplier'));

            $isCustomerLoan = ($tx->type === 'out' && 
                               $tx->category === 'customer_loan' &&
                               ($tx->transactable_type === 'App\\Models\\Customer' || 
                                $tx->transactable_type === 'App\Models\Customer'));

            $isSupplierLoan = ($tx->type === 'in' && 
                               $tx->category === 'supplier_loan' &&
                               ($tx->transactable_type === 'App\\Models\\Supplier' || 
                                $tx->transactable_type === 'App\Models\Supplier'));

            if ($isCustomerPayment && $tx->transactable_id) {
                $customerClasses = [\App\Models\Customer::class, 'App\\Models\\Customer', 'App\Models\Customer'];
                $unpaidItems = [];

                // Find all unpaid sales for customer
                $sales = \App\Models\Sale::with('duePayments')
                    ->where('customer_id', $tx->transactable_id)
                    ->where('due_amount', '>', 0.01)
                    ->orderBy('date_time', 'asc')
                    ->get();

                foreach ($sales as $sale) {
                    $allocatedSum = $sale->duePayments->sum('amount');
                    $remainingDue = (double)$sale->due_amount - (double)$allocatedSum;
                    if ($remainingDue > 0.01) {
                        $unpaidItems[] = [
                            'type' => 'sale',
                            'model' => $sale,
                            'due_amount' => $remainingDue,
                            'date_time' => $sale->date_time,
                        ];
                    }
                }

                // Find all unpaid customer loans
                $loans = \App\Models\CashTransaction::where('user_id', $tx->user_id)
                    ->whereIn('transactable_type', $customerClasses)
                    ->where('transactable_id', $tx->transactable_id)
                    ->where('type', 'out')
                    ->where('category', 'customer_loan')
                    ->orderBy('date_time', 'asc')
                    ->get();

                foreach ($loans as $loan) {
                    $allocatedSum = \App\Models\DuePayment::where('payable_type', \App\Models\CashTransaction::class)
                        ->where('payable_id', $loan->id)
                        ->sum('amount');
                    $dueOnLoan = (double) $loan->amount - (double) $allocatedSum;
                    if ($dueOnLoan > 0.01) {
                        $unpaidItems[] = [
                            'type' => 'loan',
                            'model' => $loan,
                            'due_amount' => $dueOnLoan,
                            'date_time' => $loan->date_time,
                        ];
                    }
                }

                // Sort chronologically by date_time
                usort($unpaidItems, function ($a, $b) {
                    $dtA = \Carbon\Carbon::parse($a['date_time']);
                    $dtB = \Carbon\Carbon::parse($b['date_time']);
                    return $dtA->timestamp <=> $dtB->timestamp;
                });

                $amountToAllocate = (double) $tx->amount;
                foreach ($unpaidItems as $item) {
                    if ($amountToAllocate <= 0) break;
                    $allocated = min($amountToAllocate, $item['due_amount']);

                    if ($item['type'] === 'sale') {
                        $sale = $item['model'];

                        \App\Models\DuePayment::create([
                            'cash_transaction_id' => $tx->id,
                            'payable_type' => \App\Models\Sale::class,
                            'payable_id' => $sale->id,
                            'amount' => $allocated,
                        ]);
                    } else {
                        $loan = $item['model'];
                        \App\Models\DuePayment::create([
                            'cash_transaction_id' => $tx->id,
                            'payable_type' => \App\Models\CashTransaction::class,
                            'payable_id' => $loan->id,
                            'amount' => $allocated,
                        ]);
                    }

                    $amountToAllocate -= $allocated;
                }

                $customer = \App\Models\Customer::withTrashed()->find($tx->transactable_id);
                if ($customer) {
                    $customer->decrement('total_due', $tx->amount);
                }
            } elseif ($isCustomerLoan && $tx->transactable_id) {
                $customer = \App\Models\Customer::withTrashed()->find($tx->transactable_id);
                if ($customer) {
                    $customer->increment('total_due', $tx->amount);
                }
            } elseif ($isSupplierLoan && $tx->transactable_id) {
                $supplier = \App\Models\Supplier::withTrashed()->find($tx->transactable_id);
                if ($supplier) {
                    $supplier->increment('total_due', $tx->amount);
                }
            } elseif ($isSupplierPayment && $tx->transactable_id) {
                $supplierClasses = [\App\Models\Supplier::class, 'App\\Models\\Supplier', 'App\Models\Supplier'];
                $unpaidItems = [];

                // Find all unpaid purchases for supplier
                $purchases = \App\Models\Purchase::with('duePayments')
                    ->where('supplier_id', $tx->transactable_id)
                    ->where('due_amount', '>', 0.01)
                    ->orderBy('date_time', 'asc')
                    ->get();

                foreach ($purchases as $purchase) {
                    $allocatedSum = $purchase->duePayments->sum('amount');
                    $remainingDue = (double)$purchase->due_amount - (double)$allocatedSum;
                    if ($remainingDue > 0.01) {
                        $unpaidItems[] = [
                            'type' => 'purchase',
                            'model' => $purchase,
                            'due_amount' => $remainingDue,
                            'date_time' => $purchase->date_time,
                        ];
                    }
                }

                // Find all unpaid supplier loans
                $loans = \App\Models\CashTransaction::where('user_id', $tx->user_id)
                    ->whereIn('transactable_type', $supplierClasses)
                    ->where('transactable_id', $tx->transactable_id)
                    ->where('type', 'in')
                    ->where('category', 'supplier_loan')
                    ->orderBy('date_time', 'asc')
                    ->get();

                foreach ($loans as $loan) {
                    $allocatedSum = \App\Models\DuePayment::where('payable_type', \App\Models\CashTransaction::class)
                        ->where('payable_id', $loan->id)
                        ->sum('amount');
                    $dueOnLoan = (double) $loan->amount - (double) $allocatedSum;
                    if ($dueOnLoan > 0.01) {
                        $unpaidItems[] = [
                            'type' => 'loan',
                            'model' => $loan,
                            'due_amount' => $dueOnLoan,
                            'date_time' => $loan->date_time,
                        ];
                    }
                }

                // Sort chronologically by date_time
                usort($unpaidItems, function ($a, $b) {
                    $dtA = \Carbon\Carbon::parse($a['date_time']);
                    $dtB = \Carbon\Carbon::parse($b['date_time']);
                    return $dtA->timestamp <=> $dtB->timestamp;
                });

                $amountToAllocate = (double) $tx->amount;
                foreach ($unpaidItems as $item) {
                    if ($amountToAllocate <= 0) break;
                    $allocated = min($amountToAllocate, $item['due_amount']);

                    if ($item['type'] === 'purchase') {
                        $purchase = $item['model'];

                        \App\Models\DuePayment::create([
                            'cash_transaction_id' => $tx->id,
                            'payable_type' => \App\Models\Purchase::class,
                            'payable_id' => $purchase->id,
                            'amount' => $allocated,
                        ]);
                    } else {
                        $loan = $item['model'];
                        \App\Models\DuePayment::create([
                            'cash_transaction_id' => $tx->id,
                            'payable_type' => \App\Models\CashTransaction::class,
                            'payable_id' => $loan->id,
                            'amount' => $allocated,
                        ]);
                    }

                    $amountToAllocate -= $allocated;
                }

                $supplier = \App\Models\Supplier::withTrashed()->find($tx->transactable_id);
                if ($supplier) {
                    $supplier->decrement('total_due', $tx->amount);
                }
            }

            return $tx;
        });

        return response()->json($transaction->load(['transactable', 'duePayments.payable']), 201);
    }

    public function destroy(CashTransaction $cashTransaction)
    {
        DB::transaction(function () use ($cashTransaction) {
            // Reversing associated due payments is handled automatically because Sale/Purchase fields are not mutated.

            // Explicitly delete due payments associated with the transaction (since soft deletes bypass DB cascade)
            \App\Models\DuePayment::where('cash_transaction_id', $cashTransaction->id)->delete();

            // Restore partner total_due
            $isCustomerPayment = ($cashTransaction->type === 'in' && 
                                  ($cashTransaction->transactable_type === 'App\\Models\\Customer' || 
                                   $cashTransaction->transactable_type === 'App\Models\Customer'));

            $isSupplierPayment = ($cashTransaction->type === 'out' && 
                                  ($cashTransaction->transactable_type === 'App\\Models\\Supplier' || 
                                   $cashTransaction->transactable_type === 'App\Models\Supplier'));

            $isCustomerLoan = ($cashTransaction->type === 'out' && 
                               $cashTransaction->category === 'customer_loan' &&
                               ($cashTransaction->transactable_type === 'App\\Models\\Customer' || 
                                $cashTransaction->transactable_type === 'App\Models\Customer'));

            $isSupplierLoan = ($cashTransaction->type === 'in' && 
                               $cashTransaction->category === 'supplier_loan' &&
                               ($cashTransaction->transactable_type === 'App\\Models\\Supplier' || 
                                $cashTransaction->transactable_type === 'App\Models\Supplier'));

            if ($isCustomerPayment && $cashTransaction->transactable_id) {
                $customer = \App\Models\Customer::withTrashed()->find($cashTransaction->transactable_id);
                if ($customer) {
                    $customer->increment('total_due', $cashTransaction->amount);
                }
            } elseif ($isCustomerLoan && $cashTransaction->transactable_id) {
                $customer = \App\Models\Customer::withTrashed()->find($cashTransaction->transactable_id);
                if ($customer) {
                    $customer->decrement('total_due', $cashTransaction->amount);
                }
            } elseif ($isSupplierLoan && $cashTransaction->transactable_id) {
                $supplier = \App\Models\Supplier::withTrashed()->find($cashTransaction->transactable_id);
                if ($supplier) {
                    $supplier->decrement('total_due', $cashTransaction->amount);
                }
            } elseif ($isSupplierPayment && $cashTransaction->transactable_id) {
                $supplier = \App\Models\Supplier::withTrashed()->find($cashTransaction->transactable_id);
                if ($supplier) {
                    $supplier->increment('total_due', $cashTransaction->amount);
                }
            }

            // If deleting a loan, clean up any payments allocated to this loan
            if ($isCustomerLoan || $isSupplierLoan) {
                \App\Models\DuePayment::where('payable_type', \App\Models\CashTransaction::class)
                    ->where('payable_id', $cashTransaction->id)
                    ->delete();
            }

            $cashTransaction->delete();
        });

        return response()->json(['message' => 'Cash transaction deleted successfully']);
    }
}
