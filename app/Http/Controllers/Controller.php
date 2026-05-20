<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function validateShopAccess(Request $request, string $shopId): void
    {
        $user = $request->user();

        if (! $user) {
            throw ValidationException::withMessages([
                'user_id' => ['USER_REQUIRED: Sync requests must include an authenticated user token.'],
                'shop_id' => ['SELECTED_SHOP_INVALID: Selected shop could not be validated.'],
            ]);
        }

        $clientUserId = $request->input('user_id')
            ?? $request->query('user_id')
            ?? $request->header('X-User-Id');

        if ($clientUserId && $clientUserId !== $user->id) {
            throw ValidationException::withMessages([
                'user_id' => ['USER_MISMATCH: Payload user does not match authenticated token user.'],
            ]);
        }

        if ($user->shop_id !== $shopId || $user->deleted_at !== null) {
            throw ValidationException::withMessages([
                'shop_id' => ['SELECTED_SHOP_INVALID: User is not authorized for this shop.'],
            ]);
        }
    }

    protected function validateSameShop(string $shopId, array $rows, string $field = 'shop_id'): void
    {
        foreach ($rows as $row) {
            if (($row[$field] ?? null) !== $shopId) {
                throw ValidationException::withMessages([
                    $field => ['SHOP_MISMATCH: Payload contains rows for a different shop.'],
                ]);
            }
        }
    }

    protected function applyUpdatedAfter(mixed $query, ?string $updatedAfter): mixed
    {
        return $query->when(
            $updatedAfter,
            fn ($builder) => $builder->where('updated_at', '>', $updatedAfter),
        );
    }

    protected function applySyncWindow(mixed $query, ?string $updatedAfter, mixed $syncStartedAt): mixed
    {
        return $this->applyUpdatedAfter($query, $updatedAfter)
            ->where('updated_at', '<=', $syncStartedAt);
    }

    protected function syncServerTime(mixed $syncStartedAt = null): string
    {
        return ($syncStartedAt ?? now())->toISOString();
    }
}
