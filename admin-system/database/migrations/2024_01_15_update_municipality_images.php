<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        // Get all municipality image files
        $files = Storage::disk('public')->files('municipality-images');
        
        if (empty($files)) {
            return;
        }

        // Get all municipalities
        $municipalities = DB::table('municipalities')->get();
        
        // Assign images to municipalities
        foreach ($municipalities as $index => $municipality) {
            if (isset($files[$index])) {
                $imageUrl = '/storage/' . $files[$index];
                DB::table('municipalities')
                    ->where('id', $municipality->id)
                    ->update(['image_url' => $imageUrl]);
            }
        }
    }

    public function down(): void
    {
        // Clear image URLs
        DB::table('municipalities')->update(['image_url' => null]);
    }
};
