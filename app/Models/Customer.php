<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['id', 'shop_id', 'name', 'email', 'phone', 'address', 'notes', 'created_at', 'updated_at', 'deleted_at'])]
class Customer extends Model
{
    use HasUuids;
    use SoftDeletes;

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
