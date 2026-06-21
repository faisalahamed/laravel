<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = Sale::with('customer')->orderBy('date_time', 'desc')
            ->where('user_id', $userId);
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->boolean('only_due')) {
            $query->where('due_amount', '>', 0.01);
        }
        if ($request->has('page')) {
            $perPage = $request->get('per_page', 50);
            return response()->json($query->paginate($perPage));
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid|unique:sales,uuid',
            'receipt_no' => 'required|string',
            'customer_id' => 'nullable|exists:customers,id',
            'total_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'due_amount' => 'required|numeric',
            'payment_status' => 'required|string',
            'date_time' => 'nullable|date',
            'items' => 'nullable|array',
            'payment_method' => 'nullable|string',
        ]);

        $userId = $request->user()->id;
        $validated['user_id'] = $userId;

        $sale = Sale::create($validated);

        // Update customer total due
        if ($sale->customer_id && $sale->due_amount > 0) {
            $customer = Customer::find($sale->customer_id);
            if ($customer) {
                $customer->increment('total_due', $sale->due_amount);
            }
        }
        // Record Cash Transaction
        if ($sale->paid_amount > 0) {
            \App\Models\CashTransaction::create([
                'user_id' => $userId,
                'type' => 'in',
                'amount' => $sale->paid_amount,
                'category' => 'sale',
                'description' => 'বিক্রি: ' . $sale->receipt_no,
                'transactable_type' => Sale::class,
                'transactable_id' => $sale->id,
                'date_time' => $sale->date_time ?? now(),
                'payment_method' => $sale->payment_method,
            ]);
        }

        return response()->json($sale->load('customer'), 201);
    }

    public function show(Sale $sale)
    {
        return response()->json($sale->load('customer'));
    }

    public function destroy(Sale $sale)
    {
        // Decrement customer total due if deleting
        if ($sale->customer_id && $sale->due_amount > 0) {
            $customer = Customer::find($sale->customer_id);
            $customer->decrement('total_due', min($sale->due_amount, $customer->total_due));
        }

        // Delete associated Cash Transactions
        \App\Models\CashTransaction::where('transactable_type', Sale::class)
            ->where('transactable_id', $sale->id)
            ->get()
            ->each(fn($tx) => $tx->delete());

        $sale->delete();
        return response()->json(['message' => 'Sale deleted successfully']);
    }
}
