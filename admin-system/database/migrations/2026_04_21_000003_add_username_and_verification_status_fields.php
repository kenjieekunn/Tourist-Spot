<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('email');
            });

            DB::table('users')
                ->orderBy('id')
                ->get()
                ->each(function ($user) {
                    if (!empty($user->username)) {
                        return;
                    }

                    $emailLocalPart = $user->email ? explode('@', $user->email)[0] : 'user';
                    $baseUsername = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $emailLocalPart));
                    $baseUsername = trim($baseUsername, '_') ?: 'user';

                    $username = $baseUsername;
                    $suffix = 1;

                    while (
                        DB::table('users')
                            ->where('username', $username)
                            ->where('id', '!=', $user->id)
                            ->exists()
                    ) {
                        $username = $baseUsername . '_' . $suffix;
                        $suffix++;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $username]);
                });
        }

        if (!Schema::hasColumn('tourist_spots', 'verification_status')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->enum('verification_status', ['pending', 'approved', 'rejected'])
                    ->default('pending')
                    ->after('status');
            });
        }

        DB::table('tourist_spots')->update(['verification_status' => 'approved']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('tourist_spots', 'verification_status')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->dropColumn('verification_status');
            });
        }

        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            });
        }
    }
};
