<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tourist_spots', 'images')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->json('images')->nullable()->after('image_url')->comment('Array of image URLs for tourist spot');
            });
        }

        if (Schema::hasColumn('tourist_spots', 'image_url') && Schema::hasColumn('tourist_spots', 'images')) {
            DB::statement("UPDATE tourist_spots SET images = JSON_ARRAY(image_url) WHERE image_url IS NOT NULL AND images IS NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tourist_spots', 'image_url') && Schema::hasColumn('tourist_spots', 'images')) {
            DB::statement("UPDATE tourist_spots SET image_url = JSON_UNQUOTE(JSON_EXTRACT(images, '$[0]')) WHERE images IS NOT NULL");
        }

        if (Schema::hasColumn('tourist_spots', 'images')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->dropColumn('images');
            });
        }
    }
};
