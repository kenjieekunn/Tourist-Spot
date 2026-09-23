<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'tourist_spot_id',
        'user_id',
        'user_name',
        'rating',
        'comment',
        'status',
        'image_path',
        'images',
        'media',
    ];

    protected $casts = [
        'rating' => 'integer',
        'image_path' => 'string',
        'images' => 'array',
        'media' => 'array',
    ];

    public function touristSpot()
    {
        return $this->belongsTo(TouristSpot::class);
    }

    /**
     * Get the user who wrote this review.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
