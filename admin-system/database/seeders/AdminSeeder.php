<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Municipality;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Administrator',
                'username' => 'superadmin@gmail.com',
                'password' => Hash::make('superadmin@123'),
                'role' => 'super-admin',
                'municipality_id' => null,
                'is_active' => true,
            ]
        );

        $municipalities = [
            'Lingayen',
            'Binmaley',
            'Urbiztondo',
            'Basista',
            'Labrador',
            'Bugallon',
            'Mangatarem',
            'Aguilar',
        ];

        $createdCount = 0;
        $missedCount = 0;

        foreach ($municipalities as $municipalityName) {
            $municipality = Municipality::where('name', $municipalityName)->first();

            if ($municipality) {
                $adminUsername = Str::slug($municipalityName . ' admin', '_');
                $adminEmail = $adminUsername . '@tourist-spots.com';

                User::updateOrCreate(
                    ['email' => $adminEmail],
                    [
                        'name' => $municipalityName . ' Admin',
                        'username' => $adminUsername,
                        'password' => Hash::make('MuniAdmin@123'),
                        'role' => 'municipality-admin',
                        'municipality_id' => $municipality->id,
                        'is_active' => true,
                    ]
                );

                $this->command->line("Created/Updated: {$adminEmail} for {$municipalityName}");
                $createdCount++;
            } else {
                $this->command->error("Municipality not found: {$municipalityName}");
                $missedCount++;
            }
        }

        $this->command->newLine();
        $this->command->info('Admin users created successfully!');
        $this->command->info("Total created: {$createdCount}/8");
        if ($missedCount > 0) {
            $this->command->warn("Missed: {$missedCount}");
        }
        $this->command->info('Super Admin: superadmin@gmail.com / superadmin@gmail.com / Password: superadmin@123');
        $this->command->info('Municipality Admins: [municipality]_admin / [municipality]_admin@tourist-spots.com / Password: MuniAdmin@123');
    }
}
