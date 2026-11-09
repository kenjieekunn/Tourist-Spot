<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'profile_image_path',
        'email',
        'username',
        'password',
        'role',
        'permissions',
        'max_staff_accounts',
        'municipality_id',
        'is_active',
        'last_login_at',
        'auth_provider',
        'provider_id',
        'api_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'max_staff_accounts' => 'integer',
        'last_login_at' => 'datetime',
    ];

    /**
     * Provide a ready-to-use profile image URL for the admin UI.
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if (empty($this->profile_image_path)) {
            return null;
        }

        if (filter_var($this->profile_image_path, FILTER_VALIDATE_URL)) {
            return $this->profile_image_path;
        }

        return Storage::disk('public')->url($this->profile_image_path);
    }

    /**
     * Get the municipality this admin belongs to
     */
    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function favoriteTouristSpots()
    {
        return $this->belongsToMany(TouristSpot::class, 'tourist_spot_favorites')
            ->withTimestamps();
    }

    /**
     * Get the reviews written by this user.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin()
    {
        return $this->role === 'super-admin';
    }

    /**
     * Check if user is a municipality admin
     */
    public function isMunicipalityAdmin()
    {
        return $this->role === 'municipality-admin';
    }

    public function isMunicipalityStaff(): bool
    {
        return $this->role === 'municipality-staff';
    }

    public function belongsToMunicipalityTeam(): bool
    {
        return $this->isMunicipalityAdmin() || $this->isMunicipalityStaff();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->belongsToMunicipalityTeam()) {
            return false;
        }

        // Existing municipality admins remain fully capable until configured by the super admin.
        return $this->permissions === null || (bool) ($this->permissions[$permission] ?? false);
    }

    /**
     * Check if user is any type of admin
     */
    public function isAdmin()
    {
        return $this->isSuperAdmin() || $this->belongsToMunicipalityTeam();
    }
}
