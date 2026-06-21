<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OwnerTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'uuid',
        'type',
        'sub_type',
        'amount',
        'description',
        'date_time',
    ];

    protected $casts = [
        'date_time' => 'datetime',
    ];
}
