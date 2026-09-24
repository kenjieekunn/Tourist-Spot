<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tourist_spots', 'barangay')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->string('barangay')->nullable()->after('municipality_id');
                $table->index('barangay');
            });
        }
    }

    public function down(): void
    {
        Schema::table('tourist_spots', function (Blueprint $table) {
            $table->dropIndex(['barangay']);
            $table->dropColumn('barangay');
        });
    }
};
