<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RecycleBinEntry extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'shop_id',
        'table_name',
        'record_id',
        'display_title',
        'display_subtitle',
        'deleted_data',
        'related_data',
        'deleted_by_user_id',
        'deleted_at',
        'restored_at',
        'restore_status',
        'restore_block_reason',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'deleted_data' => 'array',
        'related_data' => 'array',
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
    ];
}
