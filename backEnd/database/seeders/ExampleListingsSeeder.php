<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ExampleListingsSeeder extends Seeder
{
    private function squarePolygon(float $lat, float $lng, float $delta = 0.0012): array
    {
        return [
            'type' => 'Polygon',
            'coordinates' => [[
                [$lng - $delta, $lat - $delta],
                [$lng - $delta, $lat + $delta],
                [$lng + $delta, $lat + $delta],
                [$lng + $delta, $lat - $delta],
                [$lng - $delta, $lat - $delta],
            ]],
        ];
    }

    public function run(): void
    {
        if (Commune::query()->count() === 0) {
            $this->command?->error('❌ Aucune commune trouvée. Lancez d’abord: php artisan db:seed --class=CasablancaSettatGeoSeeder');
            return;
        }

        $owner = User::query()->where('role', 'vendeur')->first();
        if (!$owner) {
            $owner = User::query()->create([
                'email' => 'demo-vendeur@ardocco.com',
                'password' => Hash::make('password'),
                'role' => 'vendeur',
                'first_name' => 'Demo',
                'last_name' => 'Vendeur',
                'is_verified' => true,
                'is_active' => true,
                'account_status' => 'active',
            ]);
        }

        $agent = User::query()->where('role', 'agent')->first();
        if (!$agent) {
            $agent = User::query()->create([
                'email' => 'demo-agent@ardocco.com',
                'password' => Hash::make('password'),
                'role' => 'agent',
                'first_name' => 'Demo',
                'last_name' => 'Agent',
                'is_verified' => true,
                'is_active' => true,
                'account_status' => 'active',
            ]);
        }

        $communesByName = Commune::query()
            ->whereIn('name_fr', ['Anfa', 'Casablanca-Anfa', 'Dar Bouazza', 'Bouskoura', 'Mohammedia'])
            ->get()
            ->keyBy('name_fr');

        $fallbackCommune = Commune::query()->orderBy('name_fr')->first();

        $definitions = [
            [
                'reference' => 'ARD-EX-0001',
                'title' => 'Terrain résidentiel - Demo (Anfa)',
                'type_terrain' => 'residentiel',
                'status' => 'publie',
                'visibility' => 'public',
                'superficie' => 520.0,
                'prix_demande' => 7200000.0,
                'quartier' => 'Anfa',
                'address' => 'Anfa, Casablanca',
                'commune_name' => 'Anfa',
                'has_polygon' => true,
            ],
            [
                'reference' => 'ARD-EX-0002',
                'title' => 'Terrain commercial - Demo (Dar Bouazza)',
                'type_terrain' => 'commercial',
                'status' => 'publie',
                'visibility' => 'public',
                'superficie' => 300.0,
                'prix_demande' => 5400000.0,
                'quartier' => 'Dar Bouazza',
                'address' => 'Dar Bouazza, Casablanca',
                'commune_name' => 'Dar Bouazza',
                'has_polygon' => true,
            ],
            [
                'reference' => 'ARD-EX-0003',
                'title' => 'Terrain industriel - Demo (Bouskoura)',
                'type_terrain' => 'industriel',
                'status' => 'publie',
                'visibility' => 'public',
                'superficie' => 1300.0,
                'prix_demande' => 9800000.0,
                'quartier' => 'Bouskoura',
                'address' => 'Bouskoura',
                'commune_name' => 'Bouskoura',
                'has_polygon' => false,
            ],
        ];

        foreach ($definitions as $definition) {
            $commune = $definition['commune_name']
                ? $communesByName->get($definition['commune_name'])
                : null;

            if (!$commune) $commune = $fallbackCommune;
            if (!$commune) continue;

            $lat = $commune->latitude !== null ? (float) $commune->latitude : 33.5731;
            $lng = $commune->longitude !== null ? (float) $commune->longitude : -7.5898;

            Listing::query()->updateOrCreate(
                ['reference' => $definition['reference']],
                [
                    'owner_id' => $owner->id,
                    'agent_id' => $agent->id,
                    'title' => $definition['title'],
                    'description' => 'Annonce de démonstration avec (ou sans) polygone GeoJSON.',
                    'commune_id' => $commune->id,
                    'quartier' => $definition['quartier'],
                    'address' => $definition['address'],
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'geojson_polygon' => $definition['has_polygon'] ? $this->squarePolygon($lat, $lng) : null,
                    'superficie' => $definition['superficie'],
                    'prix_demande' => $definition['prix_demande'],
                    'prix_par_m2' => $definition['superficie'] > 0 ? round($definition['prix_demande'] / $definition['superficie'], 2) : null,
                    'type_terrain' => $definition['type_terrain'],
                    'status' => $definition['status'],
                    'visibility' => $definition['visibility'],
                    'is_exclusive' => false,
                    'is_urgent' => false,
                    'views_count' => 0,
                    'submitted_at' => null,
                    'validated_at' => now(),
                    'published_at' => now(),
                ]
            );
        }

        $this->command?->info('✅ Example listings seeded.');
    }
}

