<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'receipt_no',
        'supplier_id',
        'total_amount',
        'paid_amount',
        'due_amount',
        'payment_status',
        'date_time',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
        'date_time' => 'datetime',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function duePayments()
    {
        return $this->morphMany(DuePayment::class, 'payable');
    }
}
