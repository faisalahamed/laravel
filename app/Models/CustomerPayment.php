<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['id', 'shop_id', 'customer_id', 'order_id', 'payments', 'description', 'created_at', 'updated_at', 'deleted_at'])]
class CustomerPayment extends Model
{
    use HasUuids;
    use SoftDeletes;
}
