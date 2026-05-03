<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
        ]);

        return response()->json([
            'expenses' => Expense::query()
                ->where('shop_id', $validated['shop_id'])
                ->orderByDesc('created_at')
                ->get(),
            'cash_transactions' => CashTransaction::query()
                ->where('shop_id', $validated['shop_id'])
                ->where('type', 'expense')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'expenses' => ['nullable', 'array'],
            'expenses.*.id' => ['required', 'uuid'],
            'expenses.*.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'expenses.*.category_id' => ['required', 'uuid', 'exists:categories,id'],
            'expenses.*.amount' => ['required', 'numeric', 'min:0'],
            'expenses.*.reason' => ['nullable', 'string', 'max:255'],
            'expenses.*.note' => ['nullable', 'string'],
            'expenses.*.created_at' => ['nullable', 'date'],
            'expenses.*.updated_at' => ['nullable', 'date'],

            'cash_transactions' => ['nullable', 'array'],
            'cash_transactions.*.id' => ['required', 'uuid'],
            'cash_transactions.*.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'cash_transactions.*.type' => ['required', Rule::in(['expense'])],
            'cash_transactions.*.direction' => ['required', Rule::in(['out'])],
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
            foreach ($data['expenses'] ?? [] as $expense) {
                Expense::query()->updateOrCreate(
                    ['id' => $expense['id']],
                    [
                        'id' => $expense['id'],
                        'shop_id' => $expense['shop_id'],
                        'category_id' => $expense['category_id'],
                        'amount' => $expense['amount'],
                        'total' => $expense['amount'],
                        'reason' => $expense['reason'] ?? null,
                        'note' => $expense['note'] ?? null,
                        'created_at' => $expense['created_at'] ?? now(),
                        'updated_at' => $expense['updated_at'] ?? now(),
                    ],
                );
            }

            foreach ($data['cash_transactions'] ?? [] as $transaction) {
                CashTransaction::query()->updateOrCreate(
                    ['id' => $transaction['id']],
                    [
                        'id' => $transaction['id'],
                        'shop_id' => $transaction['shop_id'],
                        'type' => 'expense',
                        'direction' => 'out',
                        'amount' => $transaction['amount'],
                        'reference_id' => $transaction['reference_id'] ?? null,
                        'reference_type' => $transaction['reference_type'] ?? 'expense',
                        'method' => $transaction['method'] ?? null,
                        'note' => $transaction['note'] ?? null,
                        'created_at' => $transaction['created_at'] ?? now(),
                        'updated_at' => $transaction['updated_at'] ?? now(),
                    ],
                );
            }
        });

        return response()->json([
            'expenses' => Expense::query()
                ->whereIn('id', collect($data['expenses'] ?? [])->pluck('id'))
                ->get(),
            'cash_transactions' => CashTransaction::query()
                ->whereIn('id', collect($data['cash_transactions'] ?? [])->pluck('id'))
                ->get(),
        ], 201);
    }
}
