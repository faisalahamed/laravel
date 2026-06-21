<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = Purchase::with('supplier')->orderBy('date_time', 'desc')
            ->where('user_id', $userId);
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
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
            'uuid' => 'nullable|uuid|unique:purchases,uuid',
            'receipt_no' => 'required|string',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'total_amount' => 'required|numeric',
            'paid_amount' => 'required|numeric',
            'due_amount' => 'required|numeric',
            'payment_status' => 'required|string',
            'date_time' => 'nullable|date',
            'items' => 'nullable|array',
        ]);

        $userId = $request->user()->id;
        $validated['user_id'] = $userId;

        $purchase = Purchase::create($validated);

        // Update supplier total due
        if ($purchase->supplier_id && $purchase->due_amount > 0) {
            $supplier = Supplier::find($purchase->supplier_id);
            if ($supplier) {
                $supplier->increment('total_due', $purchase->due_amount);
            }
        }


        $cashboxAmount = $purchase->paid_amount;
        $ownerPocketAmount = 0.0;
        
        if (is_array($purchase->items)) {
            if (isset($purchase->items['cashbox_amount'])) {
                $cashboxAmount = (double) $purchase->items['cashbox_amount'];
            }
            if (isset($purchase->items['owner_pocket_amount'])) {
                $ownerPocketAmount = (double) $purchase->items['owner_pocket_amount'];
            }
        }

        // Record Cash Transaction
        if ($cashboxAmount > 0) {
            \App\Models\CashTransaction::create([
                'user_id' => $userId,
                'type' => 'out',
                'amount' => $cashboxAmount,
                'category' => 'purchase',
                'description' => 'ক্রয় (ক্যাশবক্স): ' . $purchase->receipt_no,
                'transactable_type' => Purchase::class,
                'transactable_id' => $purchase->id,
                'date_time' => $purchase->date_time ?? now(),
            ]);
        }

        // Record Owner Transaction (Pocket)
        if ($ownerPocketAmount > 0) {
            \App\Models\OwnerTransaction::create([
                'user_id' => $userId,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'give',
                'sub_type' => 'pocket',
                'amount' => $ownerPocketAmount,
                'description' => 'ক্রয় বাবদ মালিকের পকেট থেকে পরিশোধ: ' . $purchase->receipt_no,
                'date_time' => $purchase->date_time ?? now(),
            ]);
        }

        return response()->json($purchase->load('supplier'), 201);
    }

    public function show(Purchase $purchase)
    {
        return response()->json($purchase->load('supplier'));
    }

    public function destroy(Purchase $purchase)
    {
        // Decrement supplier total due if deleting
        if ($purchase->supplier_id && $purchase->due_amount > 0) {
            $supplier = Supplier::find($purchase->supplier_id);
            if ($supplier) {
                $supplier->decrement('total_due', min($purchase->due_amount, $supplier->total_due));
            }
        }

        // Delete associated Cash Transactions
        \App\Models\CashTransaction::where('transactable_type', Purchase::class)
            ->where('transactable_id', $purchase->id)
            ->get()
            ->each(fn($tx) => $tx->delete());

        // Delete associated Owner Transactions
        \App\Models\OwnerTransaction::where('description', 'ক্রয় বাবদ মালিকের পকেট থেকে পরিশোধ: ' . $purchase->receipt_no)
            ->delete();

        $purchase->delete();
        return response()->json(['message' => 'Purchase deleted successfully']);
    }
}
