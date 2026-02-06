<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvinceWithBoundarySeeder extends Seeder
{
    /**
     * Seed provinces with boundary data from Nominatim API
     *
     * This seeder demonstrates how to use the ProvinceFactory to fetch
     * GeoJSON boundary data from OpenStreetMap Nominatim API
     */
    public function run(): void
    {
        // Get the Casablanca-Settat region
        $region = Region::where('code', 'CS')->first();

        if (!$region) {
            $this->command->error('Region Casablanca-Settat not found. Please run CasablancaSettatGeoSeeder first.');
            return;
        }

        DB::beginTransaction();

        try {
            // Example: Fetch boundary data for Casablanca province
            $this->command->info('Fetching boundary data for provinces...');

            // List of provinces with their search queries
            $provinces = [
                [
                    'name_fr' => 'Casablanca',
                    'name_ar' => 'الدار البيضاء',
                    'code' => 'CAS',
                    'latitude' => 33.5731,
                    'longitude' => -7.5898,
                    // NOTE: Using "Préfecture" helps Nominatim return the administrative boundary
                    'query' => 'Préfecture de Casablanca, Maroc',
                ],
                [
                    'name_fr' => 'Mohammedia',
                    'name_ar' => 'المحمدية',
                    'code' => 'MOH',
                    'latitude' => 33.6864,
                    'longitude' => -7.3833,
                    'query' => 'Préfecture de Mohammedia, Maroc',
                ],
                [
                    'name_fr' => 'Benslimane',
                    'name_ar' => 'بن سليمان',
                    'code' => 'BEN',
                    'latitude' => 33.6186,
                    'longitude' => -7.1211,
                    'query' => 'Province de Benslimane, Maroc',
                ],
                [
                    'name_fr' => 'El Jadida',
                    'name_ar' => 'الجديدة',
                    'code' => 'JDI',
                    'latitude' => 33.2316,
                    'longitude' => -8.5007,
                    'query' => 'Province d\'El Jadida, Maroc',
                ],
                [
                    'name_fr' => 'Nouaceur',
                    'name_ar' => 'النواصر',
                    'code' => 'NOU',
                    'latitude' => 33.3667,
                    'longitude' => -7.5833,
                    'query' => 'Province de Nouaceur, Maroc',
                ],
                [
                    'name_fr' => 'Settat',
                    'name_ar' => 'سطات',
                    'code' => 'SET',
                    'latitude' => 33.0008,
                    'longitude' => -7.6164,
                    'query' => 'Province de Settat, Maroc',
                ],
                [
                    'name_fr' => 'Berrechid',
                    'name_ar' => 'برشيد',
                    'code' => 'BER',
                    'latitude' => 33.2650,
                    'longitude' => -7.5869,
                    'query' => 'Province de Berrechid, Maroc',
                ],
                [
                    'name_fr' => 'Sidi Bennour',
                    'name_ar' => 'سيدي بنور',
                    'code' => 'SBN',
                    'latitude' => 32.6486,
                    'longitude' => -8.4264,
                    'query' => 'Province de Sidi Bennour, Maroc',
                ],
                [
                    'name_fr' => 'Médiouna',
                    'name_ar' => 'مديونة',
                    'code' => 'MED',
                    'latitude' => 33.4539,
                    'longitude' => -7.5019,
                    'query' => 'Province de Médiouna, Maroc',
                ],
            ];

            foreach ($provinces as $provinceData) {
                $this->command->info("Processing {$provinceData['name_fr']}...");

                // Check if province already exists
                $existingProvince = Province::where('code', $provinceData['code'])->first();

                if ($existingProvince) {
                    // Update existing province with boundary data
                    $this->command->info("  → Province exists, updating with boundary data...");

                    // Fetch boundary data using factory method
                    $factory = Province::factory()->withBoundaryData($provinceData['query']);
                    $boundaryData = $factory->make()->only(['properties', 'bbox', 'geometry']);

                    $existingProvince->update($boundaryData);

                    $this->command->info("  ✓ Updated {$provinceData['name_fr']}");
                } else {
                    // Create new province with boundary data
                    $this->command->info("  → Creating province with boundary data...");

                    Province::factory()
                        ->withBoundaryData($provinceData['query'])
                        ->create([
                            'region_id' => $region->id,
                            'name_fr' => $provinceData['name_fr'],
                            'name_ar' => $provinceData['name_ar'],
                            'code' => $provinceData['code'],
                            'latitude' => $provinceData['latitude'],
                            'longitude' => $provinceData['longitude'],
                        ]);

                    $this->command->info("  ✓ Created {$provinceData['name_fr']}");
                }

                // Add delay to respect Nominatim API rate limits (1 request per second)
                sleep(1);
            }

            DB::commit();

            $this->command->info('');
            $this->command->info('✅ All provinces updated with boundary data!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
