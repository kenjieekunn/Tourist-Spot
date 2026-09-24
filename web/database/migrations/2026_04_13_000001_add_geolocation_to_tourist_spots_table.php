<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            // Only add if columns don't exist
            if (!Schema::hasColumn('tourist_spots', 'latitude')) {
                // Store coordinates as DECIMAL with 10 digits total, 8 decimal places for precise geolocation
                $table->decimal('latitude', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('tourist_spots', 'longitude')) {
                $table->decimal('longitude', 10, 8)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
