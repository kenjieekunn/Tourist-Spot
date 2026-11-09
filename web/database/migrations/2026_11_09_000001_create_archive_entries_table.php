<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('archive_entries')) {
            return;
        }

        Schema::create('archive_entries', function (Blueprint $table) {
            $table->id();
            $table->string('record_type', 40)->index();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('record_name');
            $table->string('municipality_name')->nullable();
            $table->string('archived_by_name')->nullable();
            $table->timestamp('archived_at')->index();
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_entries');
    }
};