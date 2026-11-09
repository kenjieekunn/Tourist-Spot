<?php

namespace App\Support;

use App\Models\ArchiveEntry;
use App\Models\TouristSpot;
use App\Models\User;

class ArchiveRecord
{
    public static function touristSpot(TouristSpot $spot, ?User $actor): void
    {
        $spot->loadMissing('municipality');

        self::store(
            'tourist_spot',
            $spot->id,
            $spot->name,
            $spot->municipality?->name,
            $spot->attributesToArray(),
            $actor
        );
    }

    public static function account(User $account, string $recordType, ?User $actor): void
    {
        $account->loadMissing('municipality');

        self::store(
            $recordType,
            $account->id,
            $account->name,
            $account->municipality?->name,
            $account->only([
                'id',
                'name',
                'email',
                'username',
                'role',
                'municipality_id',
                'is_active',
                'max_staff_accounts',
                'permissions',
                'created_at',
                'updated_at',
            ]),
            $actor
        );
    }

    private static function store(
        string $recordType,
        int $sourceId,
        string $recordName,
        ?string $municipalityName,
        array $data,
        ?User $actor
    ): void {
        ArchiveEntry::create([
            'record_type' => $recordType,
            'source_id' => $sourceId,
            'record_name' => $recordName,
            'municipality_name' => $municipalityName,
            'archived_by_name' => $actor?->name,
            'archived_at' => now(),
            'data' => $data,
        ]);
    }
}