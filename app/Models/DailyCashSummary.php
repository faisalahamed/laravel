<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCashSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'cash_in',
        'cash_out',
    ];

    protected $casts = [
        'date' => 'date',
        'cash_in' => 'double',
        'cash_out' => 'double',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
