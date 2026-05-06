<?php

namespace App\Http\Controllers;

use App\Models\RecycleBinEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecycleBinController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        return response()->json([
            'recycle_bin_entries' => RecycleBinEntry::query()
                ->where('shop_id', $data['shop_id'])
                ->orderByDesc('deleted_at')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'uuid'],
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'table_name' => ['required', 'string', Rule::in(array_keys($this->restorableTables()))],
            'record_id' => ['required', 'uuid'],
            'display_title' => ['required', 'string', 'max:255'],
            'display_subtitle' => ['nullable', 'string', 'max:255'],
            'deleted_data' => ['required'],
            'related_data' => ['nullable'],
            'deleted_by_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'deleted_at' => ['required', 'date'],
            'restored_at' => ['nullable', 'date'],
            'restore_status' => ['required', Rule::in(['deleted', 'restored', 'blocked', 'permanently_deleted'])],
            'restore_block_reason' => ['nullable', 'string'],
            'created_at' => ['nullable', 'date'],
            'updated_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        DB::transaction(function () use ($data): void {
            $entry = RecycleBinEntry::query()->updateOrCreate(
                ['id' => $data['id']],
                [
                    'id' => $data['id'],
                    'shop_id' => $data['shop_id'],
                    'table_name' => $data['table_name'],
                    'record_id' => $data['record_id'],
                    'display_title' => $data['display_title'],
                    'display_subtitle' => $data['display_subtitle'] ?? null,
                    'deleted_data' => $this->normalizeJsonValue($data['deleted_data']),
                    'related_data' => $this->normalizeJsonValue($data['related_data'] ?? null),
                    'deleted_by_user_id' => $data['deleted_by_user_id'] ?? null,
                    'deleted_at' => $data['deleted_at'],
                    'restored_at' => $data['restored_at'] ?? null,
                    'restore_status' => $data['restore_status'],
                    'restore_block_reason' => $data['restore_block_reason'] ?? null,
                    'created_at' => $data['created_at'] ?? now(),
                    'updated_at' => $data['updated_at'] ?? now(),
                ],
            );

            $this->applyRestoreStatus($entry);
        });

        return response()->json([
            'recycle_bin_entry' => RecycleBinEntry::query()->find($data['id']),
        ], 201);
    }

    /**
     * @return array<string, string>
     */
    private function restorableTables(): array
    {
        return [
            'shops' => 'shops',
            'users' => 'users',
            'categories' => 'categories',
            'suppliers' => 'suppliers',
            'purchases' => 'purchases',
            'purchase_items' => 'purchase_items',
            'purchase_payments' => 'purchase_payments',
            'customers' => 'customers',
            'sales' => 'sales',
            'sale_items' => 'sale_items',
            'sale_returns' => 'sale_returns',
            'sale_return_items' => 'sale_return_items',
            'customer_payments' => 'customer_payments',
            'cash_transactions' => 'cash_transactions',
            'expenses' => 'expenses',
            'incomes' => 'incomes',
            'notes' => 'notes',
        ];
    }

    private function normalizeJsonValue(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : ['value' => $value];
        }

        return $value;
    }

    private function applyRestoreStatus(RecycleBinEntry $entry): void
    {
        $table = $this->restorableTables()[$entry->table_name] ?? null;
        if ($table === null) {
            return;
        }

        if ($entry->restore_status === 'restored') {
            $query = DB::table($table)->where('id', $entry->record_id);
            if ($table === 'shops') {
                $query->where('id', $entry->shop_id);
            } else {
                $query->where('shop_id', $entry->shop_id);
            }
            $query->update([
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
            return;
        }

        if ($entry->restore_status === 'deleted') {
            $query = DB::table($table)->where('id', $entry->record_id);
            if ($table === 'shops') {
                $query->where('id', $entry->shop_id);
            } else {
                $query->where('shop_id', $entry->shop_id);
            }
            $query->update([
                'deleted_at' => $entry->deleted_at,
                'updated_at' => now(),
            ]);
            return;
        }

        if ($entry->restore_status === 'permanently_deleted') {
            $query = DB::table($table)->where('id', $entry->record_id);
            if ($table === 'shops') {
                $query->where('id', $entry->shop_id);
            } else {
                $query->where('shop_id', $entry->shop_id);
            }
            $query->delete();
        }
    }
}
