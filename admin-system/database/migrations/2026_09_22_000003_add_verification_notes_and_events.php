<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tourist_spots', 'rejection_reason')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable()->after('verification_status');
            });
        }

        if (!Schema::hasTable('tourist_spot_verification_events')) {
            Schema::create('tourist_spot_verification_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tourist_spot_id')->constrained('tourist_spots')->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 40);
                $table->text('note')->nullable();
                $table->timestamps();
                $table->index(['tourist_spot_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tourist_spot_verification_events');
        if (Schema::hasColumn('tourist_spots', 'rejection_reason')) {
            Schema::table('tourist_spots', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }
    }
};
