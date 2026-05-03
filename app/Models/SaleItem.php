<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'shop_id', 'order_id', 'product_id', 'buy_price', 'sale_price', 'quantity', 'price', 'created_at', 'updated_at'])]
class SaleItem extends Model
{
    use HasUuids;

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'order_id');
    }
}
