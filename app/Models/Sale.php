<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'receipt_no',
        'customer_id',
        'total_amount',
        'paid_amount',
        'due_amount',
        'payment_status',
        'date_time',
        'items',
        'payment_method',
    ];

    protected $casts = [
        'items' => 'array',
        'date_time' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
