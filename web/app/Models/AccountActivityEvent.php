<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class AccountActivityEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public static function recordFor(?User $actor, string $action, string $description): void
    {
        if (!$actor || !$actor->belongsToMunicipalityTeam()) {
            return;
        }

        try {
            self::query()->create([
                'user_id' => $actor->id,
                'action' => $action,
                'description' => $description,
                'occurred_at' => now(),
            ]);
        } catch (QueryException $exception) {
            Log::warning('Municipality account activity could not be recorded.', [
                'user_id' => $actor->id,
                'action' => $action,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}