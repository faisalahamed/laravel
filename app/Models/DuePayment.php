<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_transaction_id',
        'payable_type',
        'payable_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'double',
    ];

    public function cashTransaction()
    {
        return $this->belongsTo(CashTransaction::class);
    }

    public function payable()
    {
        return $this->morphTo()->withTrashed();
    }
}
