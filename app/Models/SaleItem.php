<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['id', 'shop_id', 'order_id', 'product_id', 'buy_price', 'sale_price', 'quantity', 'price', 'created_at', 'updated_at', 'deleted_at'])]
class SaleItem extends Model
{
    use HasUuids;
    use SoftDeletes;

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'order_id');
    }
}
