<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TouristSpotVerificationEvent extends Model
{
    protected $table = 'tourist_spot_verification_events';

    protected $fillable = [
        'tourist_spot_id',
        'actor_id',
        'action',
        'note',
    ];
}