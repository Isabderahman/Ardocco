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
        $this->call([
            CasablancaSettatGeoSeeder::class,
        ]);

        if ((bool) env('SEED_EXAMPLE_LISTINGS', false)) {
            $this->call([
                ExampleListingsSeeder::class,
            ]);
        }

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                TestUsersSeeder::class,
                TestListingsSeeder::class,
            ]);
        }
    }
}
