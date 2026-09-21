<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add foreign key constraint if it doesn't exist
            if (Schema::hasColumn('users', 'municipality_id')) {
                try {
                    $table->foreign('municipality_id')
                        ->references('id')
                        ->on('municipalities')
                        ->onDelete('set null');
                } catch (\Exception $e) {
                    // Foreign key might already exist, ignore
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key if it exists
            try {
                $table->dropForeign(['municipality_id']);
            } catch (\Exception $e) {
                // Foreign key doesn't exist, ignore
            }
        });
    }
};
