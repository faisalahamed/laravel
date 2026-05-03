<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'shop_id', 'supplier_id', 'total', 'other_charge', 'description', 'buying_memo_url', 'status', 'created_at', 'updated_at'])]
class Purchase extends Model
{
    use HasUuids;

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }
}
