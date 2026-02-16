<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Geo data seeders (run in order - boundaries depend on base geo data)
        $this->call([
            CasablancaSettatGeoSeeder::class,
            CasablancaSettatBoundariesSeeder::class,
            ProvinceWithBoundarySeeder::class,
        ]);

        // Example listings (optional - set SEED_EXAMPLE_LISTINGS=true in .env)
        if ((bool) env('SEED_EXAMPLE_LISTINGS', false)) {
            $this->call([
                ExampleListingsSeeder::class,
            ]);
        }

        // Test data for local/testing environments
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                TestUsersSeeder::class,
                TestListingsSeeder::class,
            ]);
        }
    }
}
