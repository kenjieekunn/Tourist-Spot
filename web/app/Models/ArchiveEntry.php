<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveEntry extends Model
{
    protected $fillable = [
        'record_type',
        'source_id',
        'record_name',
        'municipality_name',
        'archived_by_name',
        'archived_at',
        'data',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'data' => 'array',
    ];
}