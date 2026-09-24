<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tourist_spots', 'category')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->enum('category', ['beach', 'parks', 'falls', 'nature'])
                    ->default('nature')
                    ->after('municipality_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tourist_spots', 'category')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
