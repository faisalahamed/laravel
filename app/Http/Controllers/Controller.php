<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
            return;
        }

        $hasAccess = User::query()
            ->where('id', $userId)
            ->where('shop_id', $shopId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $hasAccess) {
            throw ValidationException::withMessages([
                'shop_id' => ['SHOP_ACCESS_DENIED: User is not authorized for this shop.'],
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

    protected function applyUpdatedAfter(Builder $query, ?string $updatedAfter): Builder
    {
        return $query->when(
            $updatedAfter,
            fn (Builder $builder) => $builder->where('updated_at', '>', $updatedAfter),
        );
    }

    protected function syncServerTime(): string
    {
        return now()->toISOString();
    }
}
