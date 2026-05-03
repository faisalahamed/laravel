<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'shop_id', 'type', 'direction', 'amount', 'reference_id', 'reference_type', 'method', 'note', 'created_at', 'updated_at'])]
class CashTransaction extends Model
{
    use HasUuids;
}
