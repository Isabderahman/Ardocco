<?php

namespace Database\Factories;

use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Province>
 */
class ProvinceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_fr' => $this->faker->city(),
            'name_ar' => $this->faker->city(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'latitude' => $this->faker->latitude(28, 36),
            'longitude' => $this->faker->longitude(-13, -1),
            'properties' => null,
            'bbox' => null,
            'geometry' => null,
        ];
    }

    /**
     * Fetch boundary data from Nominatim API and populate GeoJSON fields
     *
     * @param string $query Search query for Nominatim (e.g., "Casablanca, Morocco")
     * @return static
     */
    public function withBoundaryData(string $query): static
    {
        return $this->state(function (array $attributes) use ($query) {
            $boundaryData = $this->fetchBoundary($query);

            if ($boundaryData) {
                return [
                    'properties' => $boundaryData['properties'] ?? null,
                    'bbox' => $boundaryData['bbox'] ?? null,
                    'geometry' => $boundaryData['geometry'] ?? null,
                ];
            }

            return [];
        });
    }

    /**
     * Fetch boundary data from OpenStreetMap Nominatim API
     *
     * @param string $query
     * @return array|null
     */
    protected function fetchBoundary(string $query): ?array
    {
        try {
            $url = 'https://nominatim.openstreetmap.org/search';

            $response = Http::withHeaders([
                'User-Agent' => 'CasablancaSettatMapApp/1.0'
            ])->get($url, [
                'q' => $query,
                'format' => 'geojson',
                'polygon_geojson' => 1,
                // Fetch multiple candidates and pick the best match.
                // Nominatim often returns city boundaries first for short queries like "Settat".
                'limit' => 10,
                // Restrict to Morocco to avoid false positives.
                'countrycodes' => 'ma',
                // Try to enrich properties (if supported by the output format).
                'extratags' => 1,
                'namedetails' => 1,
            ]);

            if (!$response->successful()) {
                Log::warning("Failed to fetch boundary data for: {$query}");
                return null;
            }

            $data = $response->json();

            $features = $data['features'] ?? [];

            if (empty($features)) {
                Log::warning("No features found for query: {$query}");
                return null;
            }

            // Prefer polygon/multipolygon boundaries
            $polygonFeatures = array_values(array_filter($features, function ($feature) {
                $geometryType = $feature['geometry']['type'] ?? null;
                return in_array($geometryType, ['Polygon', 'MultiPolygon'], true);
            }));

            $candidates = !empty($polygonFeatures) ? $polygonFeatures : $features;

            // Prefer administrative/boundary features if present
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

            // Choose the largest candidate by bbox area (best proxy for "full province" vs "city")
            $bestFeature = null;
            $bestArea = -1.0;

            foreach ($candidates as $candidate) {
                $area = $this->bboxArea($candidate['bbox'] ?? null);
                if ($area > $bestArea) {
                    $bestArea = $area;
                    $bestFeature = $candidate;
                }
            }

            $feature = $bestFeature ?: $candidates[0];

            return [
                'properties' => $feature['properties'] ?? null,
                'bbox' => $feature['bbox'] ?? null,
                'geometry' => $feature['geometry'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error("Error fetching boundary for '{$query}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compute an approximate area from a GeoJSON bbox.
     *
     * @param mixed $bbox
     */
    protected function bboxArea($bbox): float
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
