<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'images')) {
                $table->json('images')->nullable()->after('comment')->comment('Array of image paths for review');
            }
        });

        if (Schema::hasColumn('reviews', 'image_path') && Schema::hasColumn('reviews', 'images')) {
            DB::statement("UPDATE reviews SET images = JSON_ARRAY(image_path) WHERE image_path IS NOT NULL AND images IS NULL");

            Schema::table('reviews', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'image_path')) {
                $table->string('image_path')->nullable()->after('comment');
            }
        });

        if (Schema::hasColumn('reviews', 'images') && Schema::hasColumn('reviews', 'image_path')) {
            DB::statement("UPDATE reviews SET image_path = JSON_UNQUOTE(JSON_EXTRACT(images, '$[0]')) WHERE images IS NOT NULL");

            Schema::table('reviews', function (Blueprint $table) {
                $table->dropColumn('images');
            });
        }
    }
};
