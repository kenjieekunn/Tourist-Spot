<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tourist_spots', 'status_reason')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->string('status_reason')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('tourist_spots', 'edited_by')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->foreignId('edited_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            });
        }

        DB::statement("ALTER TABLE `tourist_spots` MODIFY COLUMN `status` ENUM('active', 'inactive', 'open', 'closed', 'under_maintenance', 'seasonal') NOT NULL DEFAULT 'active'");
        DB::statement("ALTER TABLE `tourist_spots` MODIFY COLUMN `category` ENUM('beach', 'parks', 'falls', 'nature', 'resort', 'historical', 'cultural', 'religious') NOT NULL DEFAULT 'nature'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `tourist_spots` MODIFY COLUMN `status` ENUM('active', 'inactive', 'open', 'closed') NOT NULL DEFAULT 'active'");
        DB::statement("ALTER TABLE `tourist_spots` MODIFY COLUMN `category` ENUM('beach', 'parks', 'falls', 'nature', 'resort') NOT NULL DEFAULT 'nature'");

        if (Schema::hasColumn('tourist_spots', 'edited_by')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->dropForeign(['edited_by']);
                $table->dropColumn('edited_by');
            });
        }
        if (Schema::hasColumn('tourist_spots', 'status_reason')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->dropColumn('status_reason');
            });
        }
    }
};
