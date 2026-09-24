<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            if (!Schema::hasColumn('tourist_spots', 'opening_days')) {
                $table->text('opening_days')->nullable();
            }
            if (!Schema::hasColumn('tourist_spots', 'opening_time')) {
                $table->string('opening_time', 5)->nullable();
            }
            if (!Schema::hasColumn('tourist_spots', 'closing_time')) {
                $table->string('closing_time', 5)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            $table->dropColumn(['opening_days', 'opening_time', 'closing_time']);
        });
    }
};
