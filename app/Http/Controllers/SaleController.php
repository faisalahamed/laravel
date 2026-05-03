<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\CustomerPayment;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
        ]);

        return response()->json([
            'sales' => Sale::query()
                ->where('shop_id', $validated['shop_id'])
                ->with(['customer', 'items', 'payments', 'cashTransactions'])
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sale' => ['required', 'array'],
            'sale.id' => ['required', 'uuid'],
            'sale.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'sale.customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'sale.subtotal' => ['nullable', 'numeric', 'min:0'],
            'sale.discount' => ['nullable', 'numeric', 'min:0'],
            'sale.vat' => ['nullable', 'numeric', 'min:0'],
            'sale.total' => ['required', 'numeric', 'min:0'],
            'sale.status' => ['required', Rule::in(['pending', 'completed'])],
            'sale.payment_method' => ['nullable', 'string', 'max:255'],
            'sale.created_at' => ['nullable', 'date'],
            'sale.updated_at' => ['nullable', 'date'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'uuid'],
            'items.*.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'items.*.order_id' => ['nullable', 'uuid'],
            'items.*.product_id' => ['required', 'uuid', 'exists:purchase_items,id'],
            'items.*.buy_price' => ['required', 'numeric', 'min:0'],
            'items.*.sale_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.created_at' => ['nullable', 'date'],
            'items.*.updated_at' => ['nullable', 'date'],

            'payments' => ['nullable', 'array'],
            'payments.*.id' => ['required', 'uuid'],
            'payments.*.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'payments.*.customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'payments.*.order_id' => ['nullable', 'uuid'],
            'payments.*.payments' => ['required', 'numeric', 'min:0'],
            'payments.*.description' => ['nullable', 'string'],
            'payments.*.created_at' => ['nullable', 'date'],
            'payments.*.updated_at' => ['nullable', 'date'],

            'cash_transactions' => ['nullable', 'array'],
            'cash_transactions.*.id' => ['required', 'uuid'],
            'cash_transactions.*.shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'cash_transactions.*.type' => ['required', Rule::in(['sale', 'customer_payment', 'purchase_payment', 'expense', 'owner_given', 'owner_taken'])],
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
        $saleData = $data['sale'];

        DB::transaction(function () use ($data, $saleData): void {
            Sale::query()->updateOrCreate(
                ['id' => $saleData['id']],
                [
                    'id' => $saleData['id'],
                    'shop_id' => $saleData['shop_id'],
                    'customer_id' => $saleData['customer_id'],
                    'subtotal' => $saleData['subtotal'] ?? $saleData['total'],
                    'discount' => $saleData['discount'] ?? 0,
                    'vat' => $saleData['vat'] ?? 0,
                    'total' => $saleData['total'],
                    'status' => $saleData['status'],
                    'payment_method' => $saleData['payment_method'] ?? null,
                    'created_at' => $saleData['created_at'] ?? now(),
                    'updated_at' => $saleData['updated_at'] ?? now(),
                ],
            );

            foreach ($data['items'] as $item) {
                SaleItem::query()->updateOrCreate(
                    ['id' => $item['id']],
                    [
                        'id' => $item['id'],
                        'shop_id' => $item['shop_id'],
                        'order_id' => $saleData['id'],
                        'product_id' => $item['product_id'],
                        'buy_price' => $item['buy_price'],
                        'sale_price' => $item['sale_price'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'created_at' => $item['created_at'] ?? now(),
                        'updated_at' => $item['updated_at'] ?? now(),
                    ],
                );
            }

            foreach ($data['payments'] ?? [] as $payment) {
                CustomerPayment::query()->updateOrCreate(
                    ['id' => $payment['id']],
                    [
                        'id' => $payment['id'],
                        'shop_id' => $payment['shop_id'],
                        'customer_id' => $payment['customer_id'],
                        'order_id' => $saleData['id'],
                        'payments' => $payment['payments'],
                        'description' => $payment['description'] ?? null,
                        'created_at' => $payment['created_at'] ?? now(),
                        'updated_at' => $payment['updated_at'] ?? now(),
                    ],
                );
            }

            foreach ($data['cash_transactions'] ?? [] as $cashTransaction) {
                CashTransaction::query()->updateOrCreate(
                    ['id' => $cashTransaction['id']],
                    [
                        'id' => $cashTransaction['id'],
                        'shop_id' => $cashTransaction['shop_id'],
                        'type' => $cashTransaction['type'],
                        'direction' => $cashTransaction['direction'],
                        'amount' => $cashTransaction['amount'],
                        'reference_id' => $cashTransaction['reference_id'] ?? $saleData['id'],
                        'reference_type' => $cashTransaction['reference_type'] ?? 'sale',
                        'method' => $cashTransaction['method'] ?? null,
                        'note' => $cashTransaction['note'] ?? null,
                        'created_at' => $cashTransaction['created_at'] ?? now(),
                        'updated_at' => $cashTransaction['updated_at'] ?? now(),
                    ],
                );
            }
        });

        return response()->json([
            'sale' => Sale::query()
                ->with(['customer', 'items', 'payments', 'cashTransactions'])
                ->find($saleData['id']),
        ], 201);
    }
}
