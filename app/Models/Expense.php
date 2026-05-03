<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['id', 'shop_id', 'category_id', 'amount', 'reason', 'note', 'total', 'created_at', 'updated_at'])]
class Expense extends Model
{
    use HasUuids;
}
