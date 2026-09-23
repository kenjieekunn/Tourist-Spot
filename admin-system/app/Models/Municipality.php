<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipality extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'latitude',
        'longitude',
        'image_url',
        'is_active',
    ];

    public function touristSpots()
    {
        return $this->hasMany(TouristSpot::class);
    }

    public function admins()
    {
        return $this->hasMany(User::class)->where('role', 'municipality-admin');
    }

    public function staffAccounts()
    {
        return $this->hasMany(User::class)->where('role', 'municipality-staff');
    }
}
