<?php

namespace App\Support;

use App\Models\AccountActivityEvent;
use App\Models\TouristSpot;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountActivityTimeline
{
    private const PHILIPPINE_TIMEZONE = 'Asia/Manila';

    public static function formatPhilippineTime(?CarbonInterface $timestamp): ?string
    {
        return $timestamp?->copy()
            ->setTimezone(self::PHILIPPINE_TIMEZONE)
            ->format('M j, Y g:i A');
    }

    public static function forUser(User $user, string $loginEventTable): Collection
    {
        $loginEvents = Schema::hasTable($loginEventTable)
            ? DB::table($loginEventTable)
                ->where('user_id', $user->id)
                ->latest('logged_in_at')
                ->limit(20)
                ->get()
                ->map(function ($event) {
                    $occurredAt = Carbon::parse($event->logged_in_at, 'UTC')
                        ->setTimezone(self::PHILIPPINE_TIMEZONE);

                    return [
                        'sort_at' => $occurredAt->getTimestamp(),
                        'occurred_at' => $occurredAt,
                        'description' => 'Signed in',
                    ];
                })
            : collect();

        $accountEvents = Schema::hasTable('account_activity_events')
            ? AccountActivityEvent::query()
                ->where('user_id', $user->id)
                ->latest('occurred_at')
                ->limit(20)
                ->get()
                ->map(fn (AccountActivityEvent $event) => [
                    'sort_at' => $event->occurred_at->getTimestamp(),
                    'occurred_at' => $event->occurred_at->copy()->setTimezone(self::PHILIPPINE_TIMEZONE),
                    'description' => $event->description,
                ])
            : collect();

        return $loginEvents
            ->concat($accountEvents)
            ->sortByDesc('sort_at')
            ->take(20)
            ->map(fn (array $event) => [
                'occurred_at' => self::formatPhilippineTime($event['occurred_at']),
                'description' => $event['description'],
            ])
            ->values();
    }

    public static function historyForUser(User $user): Collection
    {
        $accountEvents = Schema::hasTable('account_activity_events')
            ? AccountActivityEvent::query()
                ->where('user_id', $user->id)
                ->where('action', '!=', 'tourist_spot.created')
                ->latest('occurred_at')
                ->limit(20)
                ->get()
                ->map(fn (AccountActivityEvent $event) => [
                    'sort_at' => $event->occurred_at->getTimestamp(),
                    'occurred_at' => $event->occurred_at->copy()->setTimezone(self::PHILIPPINE_TIMEZONE),
                    'description' => $event->description,
                    'type' => ucwords(str_replace(['.', '_'], ' ', $event->action)),
                    'status' => null,
                ])
            : collect();

        $addedSpots = TouristSpot::query()
            ->where('created_by', $user->id)
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(function (TouristSpot $spot) {
                $verificationStatus = strtolower((string) $spot->verification_status);
                $status = match ($verificationStatus) {
                    'approved' => 'Verified',
                    'pending' => 'Pending',
                    'rejected' => 'Rejected',
                    default => 'Not available',
                };

                return [
                    'sort_at' => $spot->created_at->getTimestamp(),
                    'occurred_at' => $spot->created_at->copy()->setTimezone(self::PHILIPPINE_TIMEZONE),
                    'description' => 'Added tourist spot: ' . $spot->name,
                    'type' => 'Tourist spot added',
                    'status' => $status,
                ];
            });

        return $accountEvents
            ->concat($addedSpots)
            ->sortByDesc('sort_at')
            ->take(20)
            ->map(fn (array $event) => [
                'occurred_at' => self::formatPhilippineTime($event['occurred_at']),
                'description' => $event['description'],
                'type' => $event['type'],
                'status' => $event['status'],
            ])
            ->values();
    }
}