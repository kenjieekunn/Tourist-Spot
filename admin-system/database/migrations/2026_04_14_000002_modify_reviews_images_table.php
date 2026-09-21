<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Add images column as JSON before dropping image_path
            if (!Schema::hasColumn('reviews', 'images')) {
                $table->json('images')->nullable()->after('comment')->comment('Array of image paths for review');
            }
        });

        // Migrate data from image_path to images (if needed)
        DB::statement("UPDATE reviews SET images = JSON_ARRAY(image_path) WHERE image_path IS NOT NULL AND images IS NULL");

        // Drop the old image_path column
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Recreate image_path column
            $table->string('image_path')->nullable()->after('comment');
            
            // Restore data from images
            DB::statement("UPDATE reviews SET image_path = JSON_UNQUOTE(JSON_EXTRACT(images, '$[0]')) WHERE images IS NOT NULL");
            
            // Drop images column
            $table->dropColumn('images');
        });
    }
};
