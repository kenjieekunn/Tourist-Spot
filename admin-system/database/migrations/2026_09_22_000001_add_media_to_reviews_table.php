<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('reviews', 'media')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->json('media')->nullable()->after('comment')->comment('Review image and short video paths with media types');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('reviews', 'media')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropColumn('media');
            });
        }
    }
};
