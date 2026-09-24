<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            // Only add if it doesn't already exist
            if (!Schema::hasColumn('tourist_spots', 'opening_hours')) {
                $table->text('opening_hours')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            if (Schema::hasColumn('tourist_spots', 'opening_hours')) {
                $table->dropColumn('opening_hours');
            }
        });
    }
};

