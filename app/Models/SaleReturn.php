<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['id', 'shop_id', 'sale_id', 'subtotal', 'restocking_fee', 'refund_total', 'note', 'created_at', 'updated_at', 'deleted_at'])]
class SaleReturn extends Model
{
    use HasUuids;
    use SoftDeletes;

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class, 'return_id');
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'reference_id')
            ->where('reference_type', 'sales_return')
            ->where('type', 'sales_return');
    }
}
