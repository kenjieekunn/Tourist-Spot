<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // Check if the column already exists before modifying
            if (!Schema::hasColumn('users', 'municipality_id')) {
                // Change role to support super-admin and municipality-admin
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super-admin', 'municipality-admin', 'user') DEFAULT 'user'");
                
                // Add municipality_id to link municipality admins to their municipality
                $table->unsignedBigInteger('municipality_id')->nullable()->after('role');
                
                // Add index for faster queries
                $table->index(['role', 'municipality_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop index and column if they exist
            if (Schema::hasColumn('users', 'municipality_id')) {
                $table->dropIndex(['role', 'municipality_id']);
                $table->dropColumn('municipality_id');
                
                // Reset role to original
                DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user') DEFAULT 'admin'");
            }
        });
    }
};
