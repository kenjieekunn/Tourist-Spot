<?php

namespace Database\Seeders;

use App\Models\Municipality;
use Illuminate\Database\Seeder;

class MunicipalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Municipality names in the 2nd district of Pangasinan with their coordinates
        $municipalities = [
            [
                'name' => 'Lingayen',
                'description' => 'The capital of Pangasinan',
                'latitude' => 16.0153,
                'longitude' => 120.2183,
                'image_url' => null,
            ],
            [
                'name' => 'Binmaley',
                'description' => 'Municipality in Pangasinan',
                'latitude' => 16.0667,
                'longitude' => 120.2667,
                'image_url' => null,
            ],
            [
                'name' => 'Urbiztondo',
                'description' => 'Municipality in Pangasinan',
                'latitude' => 16.1333,
                'longitude' => 120.2833,
                'image_url' => null,
            ],
            [
                'name' => 'Basista',
                'description' => 'Municipality in Pangasinan',
                'latitude' => 16.0833,
                'longitude' => 120.3667,
                'image_url' => null,
            ],
            [
                'name' => 'Labrador',
                'description' => 'Municipality in Pangasinan',
                'latitude' => 16.0333,
                'longitude' => 120.3167,
                'image_url' => null,
            ],
            [
                'name' => 'Bugallon',
                'description' => 'Municipality in Pangasinan',
                'latitude' => 15.9667,
                'longitude' => 120.3333,
                'image_url' => null,
            ],
            [
                'name' => 'Mangatarem',
                'description' => 'Municipality in Pangasinan',
                'latitude' => 15.9333,
                'longitude' => 120.2833,
                'image_url' => null,
            ],
            [
                'name' => 'Aguilar',
                'description' => 'Municipality in Pangasinan',
                'latitude' => 15.9167,
                'longitude' => 120.2667,
                'image_url' => null,
            ],
        ];

        // Create each municipality
        foreach ($municipalities as $municipalityData) {
            Municipality::updateOrCreate(
                ['name' => $municipalityData['name']],
                $municipalityData
            );
        }

        $this->command->info('8 municipalities in 2nd District Pangasinan created successfully!');
    }
}
