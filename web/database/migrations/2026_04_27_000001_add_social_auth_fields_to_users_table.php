<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('auth_provider')->nullable()->after('profile_image_path');
            $table->string('provider_id')->nullable()->after('auth_provider');
            $table->string('api_token', 80)->unique()->nullable()->after('provider_id');

            $table->index(['auth_provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['auth_provider', 'provider_id']);
            $table->dropColumn(['auth_provider', 'provider_id', 'api_token']);
        });
    }
};

