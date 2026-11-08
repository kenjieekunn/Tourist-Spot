<?php

namespace App\Support;

use App\Models\AccountActivityEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountActivityTimeline
{
    public static function forUser(User $user, string $loginEventTable): Collection
    {
        $loginEvents = Schema::hasTable($loginEventTable)
            ? DB::table($loginEventTable)
                ->where('user_id', $user->id)
                ->latest('logged_in_at')
                ->limit(20)
                ->get()
                ->map(function ($event) {
                    $occurredAt = Carbon::parse($event->logged_in_at);

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
                    'occurred_at' => $event->occurred_at,
                    'description' => $event->description,
                ])
            : collect();

        return $loginEvents
            ->concat($accountEvents)
            ->sortByDesc('sort_at')
            ->take(20)
            ->map(fn (array $event) => [
                'occurred_at' => $event['occurred_at']->format('M j, Y g:i A'),
                'description' => $event['description'],
            ])
            ->values();
    }
}