<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TouristSpotFavorite extends Model
{
    use HasFactory;

    protected $table = 'tourist_spot_favorites';

    protected $fillable = [
        'user_id',
        'tourist_spot_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function touristSpot()
    {
        return $this->belongsTo(TouristSpot::class);
    }
}
