<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $statusType = DB::select("SHOW COLUMNS FROM tourist_spots LIKE 'status'")[0]->Type ?? '';
        if (str_contains($statusType, 'under_maintenance')) {
            return;
        }

        // Expand enum to allow new values first.
        DB::statement(
            "ALTER TABLE tourist_spots MODIFY COLUMN status ENUM('active','inactive','open','closed') NOT NULL DEFAULT 'open'"
        );

        // Migrate existing values.
        DB::statement("UPDATE tourist_spots SET status = 'open' WHERE status = 'active'");
        DB::statement("UPDATE tourist_spots SET status = 'closed' WHERE status = 'inactive'");

        // Restrict enum to new values only.
        DB::statement(
            "ALTER TABLE tourist_spots MODIFY COLUMN status ENUM('open','closed') NOT NULL DEFAULT 'open'"
        );
    }

    public function down(): void
    {
        // Expand enum to allow legacy values.
        DB::statement(
            "ALTER TABLE tourist_spots MODIFY COLUMN status ENUM('active','inactive','open','closed') NOT NULL DEFAULT 'active'"
        );

        // Revert values.
        DB::statement("UPDATE tourist_spots SET status = 'active' WHERE status = 'open'");
        DB::statement("UPDATE tourist_spots SET status = 'inactive' WHERE status = 'closed'");

        // Restrict enum back to legacy values only.
        DB::statement(
            "ALTER TABLE tourist_spots MODIFY COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'"
        );
    }
};
