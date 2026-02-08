<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CasablancaSettatBoundariesSeeder extends Seeder
{
    /**
     * Fetch & persist province boundary data (GeoJSON) for Casablanca-Settat region.
     *
     * Requirements:
     * - Region CS + provinces must exist (run CasablancaSettatGeoSeeder first).
     * - Outbound internet access (Nominatim).
     */
    public function run(): void
    {
        $region = Region::query()->where('code', 'CS')->first();

        if (!$region) {
            $this->command?->error('❌ Région CS introuvable. Lancez d’abord: php artisan db:seed --class=CasablancaSettatGeoSeeder');
            return;
        }

        $this->command?->info('Fetching boundary data for Casablanca-Settat provinces (Nominatim)...');

        $provinces = [
            [
                'name_fr' => 'Casablanca',
                'name_ar' => 'الدار البيضاء',
                'code' => 'CAS',
                'latitude' => 33.5731,
                'longitude' => -7.5898,
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
                'name_fr' => 'Settat',
                'name_ar' => 'سطات',
                'code' => 'SET',
                'latitude' => 33.0008,
                'longitude' => -7.6164,
                'query' => 'Province de Settat, Maroc',
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
                'name_fr' => 'Berrechid',
                'name_ar' => 'برشيد',
                'code' => 'BER',
                'latitude' => 33.2650,
                'longitude' => -7.5869,
                'query' => 'Province de Berrechid, Maroc',
            ],
            [
                'name_fr' => 'Médiouna',
                'name_ar' => 'مديونة',
                'code' => 'MED',
                'latitude' => 33.4539,
                'longitude' => -7.5019,
                'query' => 'Province de Médiouna, Maroc',
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
                'name_fr' => 'Sidi Bennour',
                'name_ar' => 'سيدي بنور',
                'code' => 'SBN',
                'latitude' => 32.6486,
                'longitude' => -8.4264,
                'query' => 'Province de Sidi Bennour, Maroc',
            ],
        ];

        foreach ($provinces as $provinceData) {
            $code = $provinceData['code'];

            $this->command?->info("Processing {$provinceData['name_fr']} ({$code})...");

            $province = Province::query()->updateOrCreate(
                ['code' => $code],
                [
                    'region_id' => $region->id,
                    'name_fr' => $provinceData['name_fr'],
                    'name_ar' => $provinceData['name_ar'],
                    'latitude' => $provinceData['latitude'],
                    'longitude' => $provinceData['longitude'],
                ]
            );

            $boundary = $this->fetchBoundary($provinceData['query']);
            if (!$boundary) {
                $this->command?->warn("⚠️ No boundary found for {$code}");
                continue;
            }

            $province->forceFill([
                'properties' => $boundary['properties'] ?? null,
                'bbox' => $boundary['bbox'] ?? null,
                'geometry' => $boundary['geometry'] ?? null,
            ])->save();

            sleep(1);
        }

        $this->command?->info('✅ Province boundaries updated.');
    }

    private function fetchBoundary(string $query): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Ardocco/1.0',
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'geojson',
                'polygon_geojson' => 1,
                'limit' => 10,
                'countrycodes' => 'ma',
                'extratags' => 1,
                'namedetails' => 1,
            ]);

            if (!$response->successful()) {
                Log::warning("Failed to fetch boundary data for: {$query}");
                return null;
            }

            $data = $response->json();
            $features = is_array($data) ? ($data['features'] ?? []) : [];

            if (!is_array($features) || empty($features)) {
                Log::warning("No features found for query: {$query}");
                return null;
            }

            $polygonFeatures = array_values(array_filter($features, function ($feature) {
                $geometryType = $feature['geometry']['type'] ?? null;
                return in_array($geometryType, ['Polygon', 'MultiPolygon'], true);
            }));

            $candidates = !empty($polygonFeatures) ? $polygonFeatures : $features;

            $adminCandidates = array_values(array_filter($candidates, function ($feature) {
                $props = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
                $geocoding = is_array($props['geocoding'] ?? null) ? $props['geocoding'] : [];

                $category = $props['category'] ?? ($geocoding['category'] ?? null);
                $type = $props['type'] ?? ($geocoding['type'] ?? null);
                $class = $props['class'] ?? null;

                return $category === 'boundary' || $class === 'boundary' || $type === 'administrative';
            }));

            if (!empty($adminCandidates)) {
                $candidates = $adminCandidates;
            }

            $best = null;
            $bestArea = -1.0;

            foreach ($candidates as $candidate) {
                $area = $this->bboxArea($candidate['bbox'] ?? null);
                if ($area > $bestArea) {
                    $bestArea = $area;
                    $best = $candidate;
                }
            }

            $feature = $best ?: $candidates[0];

            return [
                'properties' => $feature['properties'] ?? null,
                'bbox' => $feature['bbox'] ?? null,
                'geometry' => $feature['geometry'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error("Error fetching boundary for '{$query}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compute an approximate area from a GeoJSON bbox.
     *
     * @param mixed $bbox
     */
    private function bboxArea($bbox): float
    {
        if (!is_array($bbox) || count($bbox) !== 4) {
            return 0.0;
        }

        [$minLon, $minLat, $maxLon, $maxLat] = array_map('floatval', $bbox);
        $width = abs($maxLon - $minLon);
        $height = abs($maxLat - $minLat);

        return $width * $height;
    }
}

