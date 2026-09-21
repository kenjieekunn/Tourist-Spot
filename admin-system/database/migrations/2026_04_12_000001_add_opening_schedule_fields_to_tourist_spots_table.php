<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            // Store as JSON text for broad MySQL/MariaDB compatibility.
            $table->text('opening_days')->nullable();
            // 24h format: HH:MM
            $table->string('opening_time', 5)->nullable();
            $table->string('closing_time', 5)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            $table->dropColumn(['opening_days', 'opening_time', 'closing_time']);
        });
    }
};
