<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('app:about', function () {
    $this->info(config('app.name'));
})->purpose('Display the application name.');

// Register scheduled tasks here when the application adds any.
Schedule::call(static function (): void {
    // Intentionally empty: keeps the scheduler route available for deployment.
})->name('application-placeholder')->dailyAt('00:00')->withoutOverlapping();
