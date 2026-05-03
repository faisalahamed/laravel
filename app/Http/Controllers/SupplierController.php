<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
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

        $suppliers = Supplier::query()
            ->withTrashed()
            ->where('shop_id', $data['shop_id'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'uuid'],
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:2048'],
            'mobile' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'created_at' => ['nullable', 'date'],
            'updated_at' => ['nullable', 'date'],
            'deleted_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        $supplier = Supplier::withTrashed()->updateOrCreate(
            ['id' => $data['id']],
            [
                'id' => $data['id'],
                'shop_id' => $data['shop_id'],
                'name' => $data['name'],
                'image' => $data['image'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'address' => $data['address'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
                'updated_at' => $data['updated_at'] ?? now(),
                'deleted_at' => $data['deleted_at'] ?? null,
            ],
        );

        if (isset($data['deleted_at'])) {
            $supplier->deleted_at = $data['deleted_at'];
            $supplier->save();
        } elseif ($supplier->trashed()) {
            $supplier->restore();
        }

        return response()->json([
            'supplier' => $supplier,
        ], 201);
    }
}
