<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('email');
            });
        }

        $users = DB::table('users')->where(function ($query) {
            $query->whereNull('username')->orWhere('username', '');
        })->get();

        foreach ($users as $user) {
            $email = (string) ($user->email ?? 'user@example.com');
            $base = Str::slug(explode('@', $email)[0] ?? 'user', '_');
            $base = $base !== '' ? $base : 'user';

            $username = $base;
            $suffix = 1;

            while (
                DB::table('users')
                    ->where('username', $username)
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                $username = $base . '_' . $suffix;
                $suffix++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            });
        }
    }
};
