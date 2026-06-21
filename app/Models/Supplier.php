<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'phone',
        'email',
        'address',
        'total_due',
        'status',
    ];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}
