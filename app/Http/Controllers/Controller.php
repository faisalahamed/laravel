<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected function validateShopAccess(Request $request, string $shopId): void
    {
        $userId = $request->input('user_id')
            ?? $request->query('user_id')
            ?? $request->header('X-User-Id');

        if (! $userId) {
            throw ValidationException::withMessages([
                'user_id' => ['USER_REQUIRED: Sync requests must include the authenticated local user id.'],
                'shop_id' => ['SELECTED_SHOP_INVALID: Selected shop could not be validated.'],
            ]);
        }

        $hasAccess = User::query()
            ->where('id', $userId)
            ->where('shop_id', $shopId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $hasAccess) {
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
