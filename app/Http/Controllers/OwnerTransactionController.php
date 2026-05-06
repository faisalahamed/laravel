<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OwnerTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
        ]);

        return response()->json([
            'cash_transactions' => CashTransaction::query()
                ->where('shop_id', $validated['shop_id'])
                ->whereIn('type', ['owner_given', 'owner_taken'])
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cash_transactions' => ['nullable', 'array'],
            'cash_transactions.*.id' => ['required', 'uuid'],
            'cash_transactions.*.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'cash_transactions.*.type' => ['required', Rule::in(['owner_given', 'owner_taken'])],
            'cash_transactions.*.direction' => ['required', Rule::in(['in', 'out'])],
            'cash_transactions.*.amount' => ['required', 'numeric', 'min:0'],
            'cash_transactions.*.reference_id' => ['nullable', 'uuid'],
            'cash_transactions.*.reference_type' => ['nullable', 'string', 'max:255'],
            'cash_transactions.*.method' => ['nullable', 'string', 'max:255'],
            'cash_transactions.*.note' => ['nullable', 'string'],
            'cash_transactions.*.created_at' => ['nullable', 'date'],
            'cash_transactions.*.updated_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        DB::transaction(function () use ($data): void {
            foreach ($data['cash_transactions'] ?? [] as $transaction) {
                CashTransaction::withTrashed()->updateOrCreate(
                    ['id' => $transaction['id']],
                    [
                        'id' => $transaction['id'],
                        'shop_id' => $transaction['shop_id'],
                        'type' => $transaction['type'],
                        'direction' => $transaction['type'] === 'owner_given' ? 'in' : 'out',
                        'amount' => $transaction['amount'],
                        'reference_id' => $transaction['reference_id'] ?? null,
                        'reference_type' => $transaction['reference_type'] ?? 'owner',
                        'method' => $transaction['method'] ?? null,
                        'note' => $transaction['note'] ?? null,
                        'created_at' => $transaction['created_at'] ?? now(),
                        'updated_at' => $transaction['updated_at'] ?? now(),
                    ],
                );
            }
        });

        return response()->json([
            'cash_transactions' => CashTransaction::query()
                ->whereIn('id', collect($data['cash_transactions'] ?? [])->pluck('id'))
                ->get(),
        ], 201);
    }
}
