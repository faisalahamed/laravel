<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'mobile_number' => 'required|string|unique:users,mobile_number',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'shop_name' => $request->shop_name,
            'name' => $request->name,
            'mobile_number' => $request->mobile_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $defaultCategories = ['বেতন', 'দোকান ভাড়া', 'বিদ্যুৎ বিল', 'দৈনিক খরচ', 'Service'];
        foreach ($defaultCategories as $catName) {
            \App\Models\ExpenseCategory::create([
                'user_id' => $user->id,
                'name' => $catName,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if login identifier is email or mobile number
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile_number';

        $user = User::where($loginField, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'প্রদত্ত তথ্য সঠিক নয়।'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }
}
