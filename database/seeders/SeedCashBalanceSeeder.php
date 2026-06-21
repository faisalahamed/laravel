<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\CashTransaction;
use App\Models\DailyCashSummary;
use Illuminate\Support\Facades\DB;

class SeedCashBalanceSeeder extends Seeder
{
    public function run(): void
    {
        // Truncate existing summaries to start fresh
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DailyCashSummary::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Disable model events during seeding to prevent observer double-run
        CashTransaction::unsetEventDispatcher();

        $users = User::all();

        foreach ($users as $user) {
            $userId = $user->id;

            // 1. Calculate and update user's live running balance
            $totalIn = CashTransaction::where('user_id', $userId)
                ->where('type', 'in')
                ->sum('amount');

            $totalOut = CashTransaction::where('user_id', $userId)
                ->where('type', 'out')
                ->sum('amount');

            $user->cash_balance = (double) ($totalIn - $totalOut);
            $user->save();

            // 2. Generate daily summaries for this user from historical transactions
            $historicalSummaries = CashTransaction::where('user_id', $userId)
                ->selectRaw('DATE(date_time) as date')
                ->selectRaw("SUM(CASE WHEN type = 'in' THEN amount ELSE 0 END) as cash_in")
                ->selectRaw("SUM(CASE WHEN type = 'out' THEN amount ELSE 0 END) as cash_out")
                ->groupBy('date')
                ->get();

            foreach ($historicalSummaries as $summary) {
                if (!$summary->date) {
                    continue;
                }
                DailyCashSummary::create([
                    'user_id' => $userId,
                    'date' => $summary->date,
                    'cash_in' => (double) $summary->cash_in,
                    'cash_out' => (double) $summary->cash_out,
                ]);
            }

            // 3. Recalculate each customer's total_due
            $customers = \App\Models\Customer::where('user_id', $userId)->get();
            foreach ($customers as $customer) {
                $salesDue = \App\Models\Sale::where('customer_id', $customer->id)->sum('due_amount');
                
                // Active loans given to customer
                $loansQuery = \App\Models\CashTransaction::where('user_id', $userId)
                    ->where('transactable_type', \App\Models\Customer::class)
                    ->where('transactable_id', $customer->id)
                    ->where('type', 'out')
                    ->where('category', 'customer_loan');
                $loansAmount = $loansQuery->sum('amount');
                $loanIds = $loansQuery->pluck('id');
                $paymentsOnLoans = \App\Models\DuePayment::where('payable_type', \App\Models\CashTransaction::class)
                    ->whereIn('payable_id', $loanIds)
                    ->sum('amount');
                $loansDue = $loansAmount - $paymentsOnLoans;

                // Active payments/advances from customer
                $paymentsQuery = \App\Models\CashTransaction::where('user_id', $userId)
                    ->where('transactable_type', \App\Models\Customer::class)
                    ->where('transactable_id', $customer->id)
                    ->where('type', 'in')
                    ->whereIn('category', ['customer_payment', 'customer_advance']);
                $paymentsAmount = $paymentsQuery->sum('amount');
                $paymentIds = $paymentsQuery->pluck('id');
                $allocatedPayments = \App\Models\DuePayment::whereIn('cash_transaction_id', $paymentIds)->sum('amount');
                $unallocatedPayments = $paymentsAmount - $allocatedPayments;

                $customer->total_due = (double) ($salesDue + $loansDue - $unallocatedPayments);
                $customer->save();
            }

            // 4. Recalculate each supplier's total_due
            $suppliers = \App\Models\Supplier::where('user_id', $userId)->get();
            foreach ($suppliers as $supplier) {
                $purchasesDue = \App\Models\Purchase::where('supplier_id', $supplier->id)->sum('due_amount');
                
                // Active loans (borrowed) from supplier
                $loansQuery = \App\Models\CashTransaction::where('user_id', $userId)
                    ->where('transactable_type', \App\Models\Supplier::class)
                    ->where('transactable_id', $supplier->id)
                    ->where('type', 'in')
                    ->where('category', 'supplier_loan');
                $loansAmount = $loansQuery->sum('amount');
                $loanIds = $loansQuery->pluck('id');
                $paymentsOnLoans = \App\Models\DuePayment::where('payable_type', \App\Models\CashTransaction::class)
                    ->whereIn('payable_id', $loanIds)
                    ->sum('amount');
                $loansDue = $loansAmount - $paymentsOnLoans;

                // Active payments/advances to supplier
                $paymentsQuery = \App\Models\CashTransaction::where('user_id', $userId)
                    ->where('transactable_type', \App\Models\Supplier::class)
                    ->where('transactable_id', $supplier->id)
                    ->where('type', 'out')
                    ->whereIn('category', ['supplier_payment', 'supplier_advance']);
                $paymentsAmount = $paymentsQuery->sum('amount');
                $paymentIds = $paymentsQuery->pluck('id');
                $allocatedPayments = \App\Models\DuePayment::whereIn('cash_transaction_id', $paymentIds)->sum('amount');
                $unallocatedPayments = $paymentsAmount - $allocatedPayments;

                $supplier->total_due = (double) ($purchasesDue + $loansDue - $unallocatedPayments);
                $supplier->save();
            }
        }
    }
}
