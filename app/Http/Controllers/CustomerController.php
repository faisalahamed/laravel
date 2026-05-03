<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
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
            'customers' => Customer::query()
                ->withTrashed()
                ->where('shop_id', $data['shop_id'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'uuid'],
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'created_at' => ['nullable', 'date'],
            'updated_at' => ['nullable', 'date'],
            'deleted_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        $phone = isset($data['phone']) && trim((string) $data['phone']) !== ''
            ? trim((string) $data['phone'])
            : null;

        $existingByPhone = $phone === null
            ? null
            : Customer::withTrashed()
                ->where('shop_id', $data['shop_id'])
                ->where('phone', $phone)
                ->first();

        $customer = $existingByPhone ?? Customer::withTrashed()->firstOrNew([
            'id' => $data['id'],
        ]);

        $customer->fill([
            'id' => $customer->exists ? $customer->id : $data['id'],
            'shop_id' => $data['shop_id'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $phone,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_at' => $customer->exists ? $customer->created_at : ($data['created_at'] ?? now()),
            'updated_at' => $data['updated_at'] ?? now(),
            'deleted_at' => $data['deleted_at'] ?? null,
        ]);
        $customer->save();

        if (isset($data['deleted_at'])) {
            $customer->deleted_at = $data['deleted_at'];
            $customer->save();
        } elseif ($customer->trashed()) {
            $customer->restore();
        }

        return response()->json([
            'customer' => $customer,
        ], 201);
    }
}
