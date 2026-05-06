<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NoteController extends Controller
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
            'notes' => Note::withTrashed()
                ->where('shop_id', $data['shop_id'])
                ->orderByDesc('updated_at')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'uuid'],
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'title' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'is_archived' => ['nullable', 'boolean'],
            'archived_at' => ['nullable', 'date'],
            'created_at' => ['nullable', 'date'],
            'updated_at' => ['nullable', 'date'],
            'deleted_at' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();

        $note = Note::withTrashed()->updateOrCreate(
            ['id' => $data['id']],
            [
                'id' => $data['id'],
                'shop_id' => $data['shop_id'],
                'title' => $data['title'] ?? '',
                'body' => $data['body'] ?? '',
                'is_archived' => $data['is_archived'] ?? false,
                'archived_at' => $data['archived_at'] ?? null,
                'created_at' => $data['created_at'] ?? now(),
                'updated_at' => $data['updated_at'] ?? now(),
                'deleted_at' => $data['deleted_at'] ?? null,
            ],
        );

        if (isset($data['deleted_at'])) {
            $note->deleted_at = $data['deleted_at'];
            $note->save();
        } elseif ($note->trashed()) {
            $note->restore();
        }

        return response()->json([
            'note' => $note,
        ], 201);
    }
}
