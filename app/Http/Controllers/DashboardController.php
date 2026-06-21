<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard summary — built for 100 k users × millions of rows.
     *
     * Performance design:
     *  - All running totals (sales, purchases, expenses, dues) are cached
     *    per user for 5 minutes.  A write on any transaction clears the
     *    relevant cache key via model observers, so the data is never stale
     *    for more than 5 minutes in the worst case.
     *  - Cash balance is read from users.cash_balance (O(1) lookup) —
     *    maintained atomically by CashTransactionObserver on every write.
     *  - Today's figures are cached for 60 seconds only (they change often).
     *  - No query ever does a full-table COUNT or SUM without a user_id
     *    WHERE clause — the compound indexes ensure each query is O(log n).
     */
    public function getSummary(Request $request)
    {
        $userId = $request->user()->id;

        // ── Totals cache (5 min) ─────────────────────────────────────────────
        $totals = Cache::remember("dashboard_totals_{$userId}", 300, function () use ($userId) {
            // Total sales & paid
            $totalSales     = (double) Sale::where('user_id', $userId)->sum('total_amount');
            $totalSalesPaid = (double) Sale::where('user_id', $userId)->sum('paid_amount');

            // Online wallet: sum of paid via bkash/bank
            $totalSalesPaidOnline = (double) Sale::where('user_id', $userId)
                ->whereIn('payment_method', ['bkash_nagad', 'bank'])
                ->sum('paid_amount');

            // Total purchases & paid
            $totalPurchases     = (double) Purchase::where('user_id', $userId)->sum('total_amount');
            $totalPurchasesPaid = (double) Purchase::where('user_id', $userId)->sum('paid_amount');

            // Total expenses (all time)
            $totalExpenses = (double) Expense::where('user_id', $userId)->sum('amount');

            // Dues from denormalized columns — O(1) per row, indexed on user_id
            $totalCustomerDues  = (double) Customer::where('user_id', $userId)->where('total_due', '>', 0.01)->sum('total_due');
            $totalSupplierDues  = (double) Supplier::where('user_id', $userId)->where('total_due', '>', 0.01)->sum('total_due');

            return compact(
                'totalSales', 'totalSalesPaid', 'totalSalesPaidOnline',
                'totalPurchases', 'totalPurchasesPaid',
                'totalExpenses',
                'totalCustomerDues', 'totalSupplierDues'
            );
        });

        // ── Today's figures cache (60 s) ─────────────────────────────────────
        $today = Carbon::today()->toDateString();
        $todayData = Cache::remember("dashboard_today_{$userId}_{$today}", 60, function () use ($userId, $today) {
            $todaySales    = (double) Sale::where('user_id', $userId)
                ->whereDate('date_time', $today)
                ->sum('total_amount');

            $todayExpenses = (double) Expense::where('user_id', $userId)
                ->whereDate('date', $today)
                ->sum('amount');

            $todayOwnerGiven = (double) \App\Models\OwnerTransaction::where('user_id', $userId)
                ->where('type', 'give')
                ->whereDate('date_time', $today)
                ->sum('amount');

            $todayOwnerTaken = (double) \App\Models\OwnerTransaction::where('user_id', $userId)
                ->where('type', 'take')
                ->whereDate('date_time', $today)
                ->sum('amount');

            $todayDueGiven = (double) Sale::where('user_id', $userId)
                ->whereDate('date_time', $today)
                ->sum('due_amount');

            $todayDueReceived = (double) \App\Models\CashTransaction::where('user_id', $userId)
                ->where('type', 'in')
                ->where('category', 'customer_payment')
                ->whereDate('date_time', $today)
                ->sum('amount');

            $todayOnlineWallet = (double) \App\Models\CashTransaction::where('user_id', $userId)
                ->whereIn('payment_method', ['bkash_nagad', 'bank'])
                ->where('type', 'in')
                ->whereDate('date_time', $today)
                ->sum('amount');

            return compact(
                'todaySales',
                'todayExpenses',
                'todayOwnerGiven',
                'todayOwnerTaken',
                'todayDueGiven',
                'todayDueReceived',
                'todayOnlineWallet'
            );
        });

        // ── Cash balance — O(1) from pre-computed column ─────────────────────
        $user           = User::select('cash_balance')->find($userId);
        $cashboxBalance = $user ? (double) $user->cash_balance : 0.0;

        return response()->json([
            'cashbox_balance'       => max(0, $cashboxBalance),
            'online_wallet_balance' => $todayData['todayOnlineWallet'],
            'total_sales'           => $totals['totalSales'],
            'today_sales'           => $todayData['todaySales'],
            'total_expenses'        => $totals['totalExpenses'],
            'today_expenses'        => $todayData['todayExpenses'],
            'owner_taken'           => $todayData['todayOwnerTaken'],
            'owner_given'           => $todayData['todayOwnerGiven'],
            'today_due_given'       => $todayData['todayDueGiven'],
            'today_due_received'    => $todayData['todayDueReceived'],
            'customer_dues'         => $totals['totalCustomerDues'],
            'supplier_dues'         => $totals['totalSupplierDues'],
        ]);
    }
}
