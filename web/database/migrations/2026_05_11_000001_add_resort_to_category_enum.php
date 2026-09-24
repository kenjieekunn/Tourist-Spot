<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `tourist_spots` MODIFY COLUMN `category` ENUM('beach', 'parks', 'falls', 'nature', 'resort') DEFAULT 'nature'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `tourist_spots` MODIFY COLUMN `category` ENUM('beach', 'parks', 'falls', 'nature') DEFAULT 'nature'");
    }
};
