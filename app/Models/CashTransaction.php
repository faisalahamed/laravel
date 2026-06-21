<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted()
    {
        static::observe(\App\Observers\CashTransactionObserver::class);
    }

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'amount',
        'category',
        'description',
        'transactable_type',
        'transactable_id',
        'date_time',
        'payment_method',
    ];

    protected $casts = [
        'amount' => 'double',
        'date_time' => 'datetime',
    ];

    public function transactable()
    {
        return $this->morphTo();
    }

    public function duePayments()
    {
        return $this->hasMany(DuePayment::class);
    }
}
