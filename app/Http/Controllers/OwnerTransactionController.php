<?php

namespace App\Http\Controllers;

use App\Models\OwnerTransaction;
use Illuminate\Http\Request;

class OwnerTransactionController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        
        $query = OwnerTransaction::query()->where('user_id', $userId);

        // Filtering by period & date
        $period = $request->query('period', 'all');
        $dateStr = $request->query('date');
        
        if ($dateStr) {
            try {
                $date = \Carbon\Carbon::parse($dateStr);
                if ($period === 'day') {
                    $query->whereDate('date_time', $date->toDateString());
                } elseif ($period === 'month') {
                    $query->whereYear('date_time', $date->year)
                          ->whereMonth('date_time', $date->month);
                } elseif ($period === 'year') {
                    $query->whereYear('date_time', $date->year);
                }
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }

        // Calculate aggregates before pagination
        $totalGive = (clone $query)->where('type', 'give')->sum('amount');
        $totalTake = (clone $query)->where('type', 'take')->sum('amount');

        // Pagination
        $limit = (int) $request->query('limit', 20);
        $paginator = $query->orderBy('date_time', 'desc')->paginate($limit);

        return response()->json([
            'transactions' => $paginator->items(),
            'total_give' => (double) $totalGive,
            'total_take' => (double) $totalTake,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total_count' => $paginator->total(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid|unique:owner_transactions,uuid',
            'type' => 'required|string|in:give,take',
            'sub_type' => 'nullable|string|in:cashbox,pocket',
            'amount' => 'required|numeric',
            'description' => 'nullable|string',
            'date_time' => 'nullable|date',
        ]);

        $userId = $request->user()->id;
        $validated['user_id'] = $userId;

        $transaction = OwnerTransaction::create($validated);

        // Record Cash Transaction (Only if not pocket transaction)
        if ($transaction->sub_type !== 'pocket') {
            \App\Models\CashTransaction::create([
                'user_id' => $userId,
                'type' => $transaction->type === 'give' ? 'in' : 'out',
                'amount' => $transaction->amount,
                'category' => $transaction->type === 'give' ? 'owner_give' : 'owner_take',
                'description' => $transaction->type === 'give' 
                    ? 'মালিক ক্যাশ বক্সে দিলো' . ($transaction->description ? ': ' . $transaction->description : '')
                    : 'মালিক ক্যাশ বক্স থেকে নিলো' . ($transaction->description ? ': ' . $transaction->description : ''),
                'transactable_type' => OwnerTransaction::class,
                'transactable_id' => $transaction->id,
                'date_time' => $transaction->date_time ?? now(),
            ]);
        }

        return response()->json($transaction, 201);
    }

    public function show(OwnerTransaction $ownerTransaction)
    {
        return response()->json($ownerTransaction);
    }

    public function destroy(OwnerTransaction $ownerTransaction)
    {
        // Delete associated Cash Transactions
        \App\Models\CashTransaction::where('transactable_type', OwnerTransaction::class)
            ->where('transactable_id', $ownerTransaction->id)
            ->get()
            ->each(fn($tx) => $tx->delete());

        $ownerTransaction->delete();
        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
