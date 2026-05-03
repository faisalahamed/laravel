<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['id', 'shop_id', 'customer_id', 'subtotal', 'discount', 'vat', 'total', 'status', 'payment_method', 'created_at', 'updated_at'])]
class Sale extends Model
{
    use HasUuids;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'order_id');
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'reference_id')
            ->where('reference_type', 'sale')
            ->where('type', 'sale');
    }
}
