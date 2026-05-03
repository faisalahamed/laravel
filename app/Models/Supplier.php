<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['id', 'shop_id', 'name', 'image', 'mobile', 'address', 'created_at', 'updated_at', 'deleted_at'])]
class Supplier extends Model
{
    use HasUuids;
    use SoftDeletes;
}
