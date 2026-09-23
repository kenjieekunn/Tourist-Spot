<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TouristSpot extends Model
{
    use HasFactory;

    protected $table = 'tourist_spots';

    protected $fillable = [
        'municipality_id',
        'created_by',
        'edited_by',
        'barangay',
        'category',
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'phone',
        'website',
        'opening_hours',
        'opening_days',
        'opening_time',
        'closing_time',
        'entrance_fee',
        'image_url',
        'images',
        'nearby_dining',
        'nearby_gas_stations',
        'nearby_facilities',
        'status',
        'status_reason',
        'verification_status',
        'rejection_reason',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'entrance_fee' => 'float',
        'nearby_facilities' => 'array',
        'opening_days' => 'array',
        'images' => 'array',
    ];

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'tourist_spot_favorites')
            ->withTimestamps();
    }

    public function getAverageRating()
    {
        return $this->reviews()->average('rating') ?? 0;
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isPendingVerification(): bool
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Return every stored tourist-spot image as a displayable URL.
     */
    public function getImageUrlsAttribute(): array
    {
        $storedImages = $this->images;

        if (is_array($storedImages) && !empty($storedImages)) {
            return array_values(array_filter(array_map(function ($image) {
                return $this->normalizeImageUrl($image);
            }, $storedImages)));
        }

        if (!empty($this->image_url)) {
            return [$this->normalizeImageUrl($this->image_url)];
        }

        return [];
    }

    /**
     * Return the first tourist-spot image for cards and summaries.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        return $this->image_urls[0] ?? null;
    }

    private function normalizeImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return url($path);
    }
}
