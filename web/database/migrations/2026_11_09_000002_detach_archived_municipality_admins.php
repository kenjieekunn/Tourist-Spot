<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('archive_entries')
            || !Schema::hasTable('users')
            || !Schema::hasColumn('users', 'municipality_id')) {
            return;
        }

        $updates = ['municipality_id' => null];
        if (Schema::hasColumn('users', 'is_active')) {
            $updates['is_active'] = false;
        }
        if (Schema::hasColumn('users', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table('users')
            ->where('role', 'municipality-admin')
            ->whereIn('id', DB::table('archive_entries')
                ->select('source_id')
                ->where('record_type', 'municipality_admin')
                ->whereNotNull('source_id'))
            ->update($updates);
    }

    public function down(): void
    {
    }
};