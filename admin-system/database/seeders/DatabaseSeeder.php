<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed municipalities first, then admins
        $this->call([
            MunicipalitySeeder::class,
            AdminSeeder::class,
        ]);
    }
}
