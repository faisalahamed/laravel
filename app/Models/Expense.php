<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['id', 'shop_id', 'category_id', 'amount', 'reason', 'note', 'total', 'created_at', 'updated_at', 'deleted_at'])]
class Expense extends Model
{
    use HasUuids;
    use SoftDeletes;
}
