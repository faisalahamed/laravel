<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
        ]);

        return response()->json([
            'sale_returns' => SaleReturn::query()
                ->where('shop_id', $validated['shop_id'])
                ->with(['items', 'cashTransactions'])
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sale_return' => ['required', 'array'],
            'sale_return.id' => ['required', 'uuid'],
            'sale_return.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'sale_return.sale_id' => ['required', 'uuid', 'exists:sales,id'],
            'sale_return.subtotal' => ['required', 'numeric', 'min:0'],
            'sale_return.restocking_fee' => ['nullable', 'numeric', 'min:0'],
            'sale_return.refund_total' => ['required', 'numeric', 'min:0'],
            'sale_return.note' => ['nullable', 'string'],
            'sale_return.created_at' => ['nullable', 'date'],
            'sale_return.updated_at' => ['nullable', 'date'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'uuid'],
            'items.*.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'items.*.return_id' => ['nullable', 'uuid'],
            'items.*.sale_item_id' => ['required', 'uuid', 'exists:sale_items,id'],
            'items.*.product_id' => ['required', 'uuid', 'exists:purchase_items,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.sale_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.reason' => ['nullable', 'string'],
            'items.*.created_at' => ['nullable', 'date'],
            'items.*.updated_at' => ['nullable', 'date'],

            'cash_transactions' => ['nullable', 'array'],
            'cash_transactions.*.id' => ['required', 'uuid'],
            'cash_transactions.*.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'cash_transactions.*.type' => ['required', Rule::in(['sales_return'])],
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
        $returnData = $data['sale_return'];

        DB::transaction(function () use ($data, $returnData): void {
            SaleReturn::withTrashed()->updateOrCreate(
                ['id' => $returnData['id']],
                [
                    'id' => $returnData['id'],
                    'shop_id' => $returnData['shop_id'],
                    'sale_id' => $returnData['sale_id'],
                    'subtotal' => $returnData['subtotal'],
                    'restocking_fee' => $returnData['restocking_fee'] ?? 0,
                    'refund_total' => $returnData['refund_total'],
                    'note' => $returnData['note'] ?? null,
                    'created_at' => $returnData['created_at'] ?? now(),
                    'updated_at' => $returnData['updated_at'] ?? now(),
                ],
            );

            foreach ($data['items'] as $item) {
                SaleReturnItem::withTrashed()->updateOrCreate(
                    ['id' => $item['id']],
                    [
                        'id' => $item['id'],
                        'shop_id' => $item['shop_id'],
                        'return_id' => $returnData['id'],
                        'sale_item_id' => $item['sale_item_id'],
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'sale_price' => $item['sale_price'],
                        'quantity' => $item['quantity'],
                        'reason' => $item['reason'] ?? null,
                        'created_at' => $item['created_at'] ?? now(),
                        'updated_at' => $item['updated_at'] ?? now(),
                    ],
                );
            }

            foreach ($data['cash_transactions'] ?? [] as $cashTransaction) {
                CashTransaction::withTrashed()->updateOrCreate(
                    ['id' => $cashTransaction['id']],
                    [
                        'id' => $cashTransaction['id'],
                        'shop_id' => $cashTransaction['shop_id'],
                        'type' => $cashTransaction['type'],
                        'direction' => $cashTransaction['direction'],
                        'amount' => $cashTransaction['amount'],
                        'reference_id' => $cashTransaction['reference_id'] ?? $returnData['id'],
                        'reference_type' => $cashTransaction['reference_type'] ?? 'sales_return',
                        'method' => $cashTransaction['method'] ?? null,
                        'note' => $cashTransaction['note'] ?? null,
                        'created_at' => $cashTransaction['created_at'] ?? now(),
                        'updated_at' => $cashTransaction['updated_at'] ?? now(),
                    ],
                );
            }
        });

        return response()->json([
            'sale_return' => SaleReturn::query()
                ->with(['items', 'cashTransactions'])
                ->find($returnData['id']),
        ], 201);
    }
}
