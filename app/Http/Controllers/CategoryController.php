<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'type' => ['nullable', Rule::in(['product', 'expense', 'commission', 'income'])],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        $categories = Category::query()
            ->withTrashed()
            ->where('shop_id', $data['shop_id'])
            ->when(
                isset($data['type']),
                fn ($query) => $query->where('type', $data['type']),
            )
            ->orderBy('name')
            ->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'uuid'],
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['product', 'expense', 'commission', 'income'])],
            'details' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'created_at' => ['nullable', 'date'],
            'updated_at' => ['nullable', 'date'],
            'deleted_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        $category = Category::withTrashed()->updateOrCreate(
            ['id' => $data['id']],
            [
                'id' => $data['id'],
                'shop_id' => $data['shop_id'],
                'name' => $data['name'],
                'type' => $data['type'],
                'details' => $data['details'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
                'updated_at' => $data['updated_at'] ?? now(),
                'deleted_at' => $data['deleted_at'] ?? null,
            ],
        );

        if (isset($data['deleted_at'])) {
            $category->deleted_at = $data['deleted_at'];
            $category->save();
        } elseif ($category->trashed()) {
            $category->restore();
        }

        return response()->json([
            'category' => $category,
        ], 201);
    }
}
