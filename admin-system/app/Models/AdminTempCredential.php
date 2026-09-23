<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminTempCredential extends Model
{
    protected $fillable = [
        'user_id',
        'password',
    ];
}
