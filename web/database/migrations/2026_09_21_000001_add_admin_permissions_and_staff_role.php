<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('permissions')->nullable()->after('role');
                $table->index('role');
            });
        }

        // Keep the existing municipality-admin values while allowing staff accounts.
        if (Schema::hasColumn('users', 'role')) {
            \DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super-admin', 'municipality-admin', 'municipality-staff', 'user') DEFAULT 'user'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            \DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super-admin', 'municipality-admin', 'user') DEFAULT 'user'");
        }

        if (Schema::hasColumn('users', 'permissions')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['role']);
                $table->dropColumn('permissions');
            });
        }
    }
};
