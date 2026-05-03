<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SignupOtpController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:255'],
            'shop_name' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        $email = strtolower($data['email']);
        $throttleKey = 'signup-otp:send:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return response()->json([
                'message' => 'Please wait before requesting another OTP.',
                'retry_after' => RateLimiter::availableIn($throttleKey),
            ], 429);
        }

        RateLimiter::hit($throttleKey, 120);

        $otp = (string) random_int(1000, 9999);

        Cache::put(
            'signup-otp:'.$email,
            [
                'otp_hash' => hash('sha256', $otp),
                'shop_name' => $data['shop_name'] ?? null,
                'full_name' => $data['full_name'] ?? null,
            ],
            now()->addMinutes(10)
        );

        Mail::raw($this->messageBody($otp, $data), function ($message) use ($email): void {
            $message
                ->to($email)
                ->subject('Your Bonik signup OTP');
        });

        return response()->json([
            'message' => 'OTP sent successfully.',
            'expires_in' => 600,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'otp' => ['required', 'digits:4'],
            'shop_name' => ['required', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $data = $validator->validated();
        $email = strtolower($data['email']);
        $cachedOtp = Cache::get('signup-otp:'.$email);

        if (! $cachedOtp || ! hash_equals($cachedOtp['otp_hash'], hash('sha256', $data['otp']))) {
            return response()->json([
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        $result = DB::transaction(function () use ($data, $email): array {
            $shop = Shop::create([
                'shop_name' => $data['shop_name'],
                'email' => $email,
                'shop_mobile' => $data['phone'],
            ]);

            $user = User::create([
                'shop_id' => $shop->id,
                'name' => $data['full_name'],
                'email' => $email,
                'password' => $data['password'],
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            return [
                'shop' => $shop,
                'user' => $user,
            ];
        });

        Cache::forget('signup-otp:'.$email);

        return response()->json([
            'message' => 'Account verified and created successfully.',
            'shop' => $result['shop'],
            'user' => $result['user'],
        ], 201);
    }

    private function messageBody(string $otp, array $data): string
    {
        $name = trim((string) ($data['full_name'] ?? ''));
        $greeting = $name === '' ? 'Hello,' : 'Hello '.$name.',';

        return implode("\n", [
            $greeting,
            '',
            'Your Bonik signup verification code is: '.$otp,
            '',
            'This code will expire in 10 minutes.',
            'If you did not request this code, you can ignore this email.',
        ]);
    }
}
