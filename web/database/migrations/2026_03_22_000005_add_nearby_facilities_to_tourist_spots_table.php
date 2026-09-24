<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            if (!Schema::hasColumn('tourist_spots', 'nearby_dining')) {
                $table->text('nearby_dining')->nullable()->after('image_url');
            }
            if (!Schema::hasColumn('tourist_spots', 'nearby_gas_stations')) {
                $table->text('nearby_gas_stations')->nullable()->after('nearby_dining');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            $table->dropColumn(['nearby_dining', 'nearby_gas_stations']);
        });
    }
};
