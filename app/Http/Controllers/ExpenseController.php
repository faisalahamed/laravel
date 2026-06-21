<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = Expense::with('employee')
            ->where('user_id', $userId);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        return response()->json($query->orderBy('date', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid|unique:expenses,uuid',
            'category' => 'required|string',
            'employee_id' => 'nullable|exists:employees,id',
            'amount' => 'required|numeric',
            'reason' => 'nullable|string',
            'attachment_path' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $userId = $request->user()->id;
        $validated['user_id'] = $userId;
        $expense = Expense::create($validated);

        // Record Cash Transaction
        \App\Models\CashTransaction::create([
            'user_id' => $userId,
            'type' => 'out',
            'amount' => $expense->amount,
            'category' => 'expense',
            'description' => 'খরচ (' . $expense->category . '): ' . ($expense->reason ?? ''),
            'transactable_type' => Expense::class,
            'transactable_id' => $expense->id,
            'date_time' => $expense->date ?? now(),
        ]);

        return response()->json($expense->load('employee'), 201);
    }

    public function show(Expense $expense)
    {
        return response()->json($expense->load('employee'));
    }

    public function destroy(Expense $expense)
    {
        // Delete associated Cash Transactions
        \App\Models\CashTransaction::where('transactable_type', Expense::class)
            ->where('transactable_id', $expense->id)
            ->get()
            ->each(fn($tx) => $tx->delete());

        $expense->delete();
        return response()->json(['message' => 'Expense deleted successfully']);
    }
}
