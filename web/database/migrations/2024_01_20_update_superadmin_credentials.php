<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        // First, check if old superadmin exists and delete it if new one will be created
        $oldSuperAdmin = DB::table('users')
            ->where('email', 'superadmin@tourist-spots.com')
            ->first();

        $newSuperAdmin = DB::table('users')
            ->where('email', 'superadmin@gmail.com')
            ->first();

        // Preserve existing credentials; only bootstrap an account on an empty database.
        if (!$oldSuperAdmin && !$newSuperAdmin) {
            // If neither exists, create the new one
            $user = [
                'name' => 'Super Administrator',
                'email' => 'superadmin@gmail.com',
                'password' => Hash::make('superadmin@123'),
                'role' => 'super-admin',
                'municipality_id' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('users', 'username')) {
                $user['username'] = 'superadmin@gmail.com';
            }
            DB::table('users')->insert($user);
        }
        // If both exist, do nothing (new one takes precedence)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert superadmin credentials
        DB::table('users')
            ->where('email', 'superadmin@gmail.com')
            ->update([
                'email' => 'superadmin@tourist-spots.com',
                'username' => 'superadmin',
                'password' => Hash::make('SuperAdmin@123'),
                'updated_at' => now(),
            ]);
    }
};
