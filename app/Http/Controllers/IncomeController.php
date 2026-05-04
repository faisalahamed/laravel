<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Income;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IncomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
        ]);

        return response()->json([
            'incomes' => Income::query()
                ->where('shop_id', $validated['shop_id'])
                ->orderByDesc('created_at')
                ->get(),
            'cash_transactions' => CashTransaction::query()
                ->where('shop_id', $validated['shop_id'])
                ->where('type', 'income')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'incomes' => ['nullable', 'array'],
            'incomes.*.id' => ['required', 'uuid'],
            'incomes.*.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'incomes.*.category_id' => ['required', 'uuid', 'exists:categories,id'],
            'incomes.*.amount' => ['required', 'numeric', 'min:0'],
            'incomes.*.reason' => ['nullable', 'string', 'max:255'],
            'incomes.*.note' => ['nullable', 'string'],
            'incomes.*.receipt_url' => ['nullable', 'string'],
            'incomes.*.created_at' => ['nullable', 'date'],
            'incomes.*.updated_at' => ['nullable', 'date'],

            'cash_transactions' => ['nullable', 'array'],
            'cash_transactions.*.id' => ['required', 'uuid'],
            'cash_transactions.*.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'cash_transactions.*.type' => ['required', Rule::in(['income'])],
            'cash_transactions.*.direction' => ['required', Rule::in(['in'])],
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
            foreach ($data['incomes'] ?? [] as $income) {
                Income::query()->updateOrCreate(
                    ['id' => $income['id']],
                    [
                        'id' => $income['id'],
                        'shop_id' => $income['shop_id'],
                        'category_id' => $income['category_id'],
                        'amount' => $income['amount'],
                        'total' => $income['amount'], // Mapping amount to total if needed for consistency
                        'reason' => $income['reason'] ?? null,
                        'note' => $income['note'] ?? null,
                        'receipt_url' => $income['receipt_url'] ?? null,
                        'created_at' => $income['created_at'] ?? now(),
                        'updated_at' => $income['updated_at'] ?? now(),
                    ],
                );
            }

            foreach ($data['cash_transactions'] ?? [] as $transaction) {
                CashTransaction::query()->updateOrCreate(
                    ['id' => $transaction['id']],
                    [
                        'id' => $transaction['id'],
                        'shop_id' => $transaction['shop_id'],
                        'type' => 'income',
                        'direction' => 'in',
                        'amount' => $transaction['amount'],
                        'reference_id' => $transaction['reference_id'] ?? null,
                        'reference_type' => $transaction['reference_type'] ?? 'income',
                        'method' => $transaction['method'] ?? null,
                        'note' => $transaction['note'] ?? null,
                        'created_at' => $transaction['created_at'] ?? now(),
                        'updated_at' => $transaction['updated_at'] ?? now(),
                    ],
                );
            }
        });

        return response()->json([
            'incomes' => Income::query()
                ->whereIn('id', collect($data['incomes'] ?? [])->pluck('id'))
                ->get(),
            'cash_transactions' => CashTransaction::query()
                ->whereIn('id', collect($data['cash_transactions'] ?? [])->pluck('id'))
                ->get(),
        ], 201);
    }
}
