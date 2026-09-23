<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'max_staff_accounts')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('max_staff_accounts')->nullable()->after('permissions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'max_staff_accounts')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('max_staff_accounts');
            });
        }
    }
};
