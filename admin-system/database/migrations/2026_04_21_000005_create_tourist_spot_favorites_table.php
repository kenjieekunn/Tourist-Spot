<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tourist_spot_favorites')) {
            Schema::create('tourist_spot_favorites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('tourist_spot_id')->constrained('tourist_spots')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'tourist_spot_id']);
                $table->index('tourist_spot_id');
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tourist_spot_favorites');
    }
};
