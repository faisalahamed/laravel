<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identity' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        $identity = strtolower($data['identity']);

        $user = User::query()
            ->with('shop')
            ->whereRaw('LOWER(email) = ?', [$identity])
            ->orWhereHas('shop', function ($query) use ($identity): void {
                $query->where('shop_mobile', $identity);
            })
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials.',
            ], 422);
        }

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
            'shop' => $user->shop,
        ]);
    }
}
