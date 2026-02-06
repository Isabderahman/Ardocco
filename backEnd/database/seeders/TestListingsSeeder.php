<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\FicheFinanciere;
use App\Models\FicheJuridique;
use App\Models\FicheTechnique;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class TestListingsSeeder extends Seeder
{
    /**
     * Seed des annonces (listings) de test.
     *
     * Dépendances:
     * - Communes (id) : exécuter d'abord CasablancaSettatGeoSeeder (ou DatabaseSeeder).
     * - Users : ce seeder appelle TestUsersSeeder automatiquement.
     */
    public function run(): void
    {
        $this->call([
            TestUsersSeeder::class,
        ]);

        if (Commune::query()->count() === 0) {
            $this->command?->error('❌ Aucune commune trouvée. Lancez d’abord: php artisan db:seed --class=CasablancaSettatGeoSeeder');
            return;
        }

        $agentsByEmail = User::query()
            ->where('role', 'agent')
            ->whereIn('email', ['agent1@ardocco.test', 'agent2@ardocco.test'])
            ->get()
            ->keyBy('email');

        $ownersByEmail = User::query()
            ->whereIn('email', [
                'vendeur1@ardocco.test',
                'vendeur2@ardocco.test',
                'promoteur1@ardocco.test',
                'promoteur2@ardocco.test',
            ])
            ->get()
            ->keyBy('email');

        $admin = User::query()->where('email', 'admin@ardocco.test')->first();

        if ($agentsByEmail->isEmpty() || $ownersByEmail->isEmpty()) {
            $this->command?->error('❌ Utilisateurs manquants. Lancez: php artisan db:seed --class=TestUsersSeeder');
            return;
        }

        $preferredCommuneNames = [
            'Casablanca-Anfa',
            'Anfa',
            'Hay Hassani',
            'Aïn Chock',
            'Dar Bouazza',
            'Bouskoura',
            'Mohammedia',
            'Berrechid',
            'El Jadida',
            'Settat',
        ];

        $communesByName = Commune::query()
            ->whereIn('name_fr', $preferredCommuneNames)
            ->get()
            ->keyBy('name_fr');

        $fallbackCommune = Commune::query()->orderBy('name_fr')->first();

        $definitions = [
            [
                'reference' => 'ARD-TST-0001',
                'title' => 'Terrain résidentiel - Anfa',
                'type_terrain' => 'residentiel',
                'status' => 'publie',
                'visibility' => 'public',
                'superficie' => 450.00,
                'prix_demande' => 6500000.00,
                'quartier' => 'Anfa',
                'address' => 'Boulevard d’Anfa, Casablanca',
                'commune_name' => 'Anfa',
                'owner_email' => 'vendeur1@ardocco.test',
                'agent_email' => 'agent1@ardocco.test',
                'is_exclusive' => true,
                'is_urgent' => false,
            ],
            [
                'reference' => 'ARD-TST-0002',
                'title' => 'Terrain commercial - Casablanca-Anfa',
                'type_terrain' => 'commercial',
                'status' => 'valide',
                'visibility' => 'restricted',
                'superficie' => 300.00,
                'prix_demande' => 5200000.00,
                'quartier' => 'Centre',
                'address' => 'Centre-ville, Casablanca',
                'commune_name' => 'Casablanca-Anfa',
                'owner_email' => 'promoteur1@ardocco.test',
                'agent_email' => 'agent2@ardocco.test',
                'is_exclusive' => false,
                'is_urgent' => true,
            ],
            [
                'reference' => 'ARD-TST-0003',
                'title' => 'Terrain industriel - Berrechid',
                'type_terrain' => 'industriel',
                'status' => 'soumis',
                'visibility' => 'private',
                'superficie' => 1200.00,
                'prix_demande' => 7800000.00,
                'quartier' => 'Zone industrielle',
                'address' => 'ZI, Berrechid',
                'commune_name' => 'Berrechid',
                'owner_email' => 'promoteur2@ardocco.test',
                'agent_email' => 'agent2@ardocco.test',
                'is_exclusive' => false,
                'is_urgent' => false,
            ],
            [
                'reference' => 'ARD-TST-0004',
                'title' => 'Terrain agricole - Settat',
                'type_terrain' => 'agricole',
                'status' => 'brouillon',
                'visibility' => 'private',
                'superficie' => 5000.00,
                'prix_demande' => 2500000.00,
                'quartier' => null,
                'address' => 'Route de Settat',
                'commune_name' => 'Settat',
                'owner_email' => 'vendeur2@ardocco.test',
                'agent_email' => null,
                'is_exclusive' => false,
                'is_urgent' => false,
            ],
            [
                'reference' => 'ARD-TST-0005',
                'title' => 'Terrain mixte proche mer - Dar Bouazza',
                'type_terrain' => 'mixte',
                'status' => 'publie',
                'visibility' => 'public',
                'superficie' => 800.00,
                'prix_demande' => 9300000.00,
                'quartier' => 'Plage',
                'address' => 'Dar Bouazza, Casablanca',
                'commune_name' => 'Dar Bouazza',
                'owner_email' => 'vendeur1@ardocco.test',
                'agent_email' => 'agent1@ardocco.test',
                'is_exclusive' => true,
                'is_urgent' => true,
            ],
            [
                'reference' => 'ARD-TST-0006',
                'title' => 'Terrain résidentiel - Bouskoura',
                'type_terrain' => 'residentiel',
                'status' => 'en_revision',
                'visibility' => 'restricted',
                'superficie' => 600.00,
                'prix_demande' => 4100000.00,
                'quartier' => 'Forêt',
                'address' => 'Bouskoura',
                'commune_name' => 'Bouskoura',
                'owner_email' => 'promoteur1@ardocco.test',
                'agent_email' => 'agent2@ardocco.test',
                'is_exclusive' => false,
                'is_urgent' => false,
            ],
            [
                'reference' => 'ARD-TST-0007',
                'title' => 'Terrain commercial - Mohammedia',
                'type_terrain' => 'commercial',
                'status' => 'refuse',
                'visibility' => 'private',
                'superficie' => 220.00,
                'prix_demande' => 1900000.00,
                'quartier' => 'Centre',
                'address' => 'Mohammedia',
                'commune_name' => 'Mohammedia',
                'owner_email' => 'vendeur2@ardocco.test',
                'agent_email' => 'agent1@ardocco.test',
                'is_exclusive' => false,
                'is_urgent' => false,
            ],
            [
                'reference' => 'ARD-TST-0008',
                'title' => 'Terrain résidentiel - Hay Hassani',
                'type_terrain' => 'residentiel',
                'status' => 'vendu',
                'visibility' => 'public',
                'superficie' => 350.00,
                'prix_demande' => 3200000.00,
                'quartier' => 'Hay Hassani',
                'address' => 'Hay Hassani, Casablanca',
                'commune_name' => 'Hay Hassani',
                'owner_email' => 'vendeur1@ardocco.test',
                'agent_email' => 'agent1@ardocco.test',
                'is_exclusive' => false,
                'is_urgent' => false,
            ],
            [
                'reference' => 'ARD-TST-0009',
                'title' => 'Terrain industriel - zone périphérique',
                'type_terrain' => 'industriel',
                'status' => 'valide',
                'visibility' => 'restricted',
                'superficie' => 2000.00,
                'prix_demande' => 11000000.00,
                'quartier' => 'Périphérie',
                'address' => 'Périphérie',
                'commune_name' => 'Aïn Chock',
                'owner_email' => 'promoteur2@ardocco.test',
                'agent_email' => 'agent2@ardocco.test',
                'is_exclusive' => true,
                'is_urgent' => false,
            ],
            [
                'reference' => 'ARD-TST-0010',
                'title' => 'Terrain mixte - El Jadida',
                'type_terrain' => 'mixte',
                'status' => 'publie',
                'visibility' => 'public',
                'superficie' => 1000.00,
                'prix_demande' => 6000000.00,
                'quartier' => 'Corniche',
                'address' => 'El Jadida',
                'commune_name' => 'El Jadida',
                'owner_email' => 'promoteur1@ardocco.test',
                'agent_email' => 'agent1@ardocco.test',
                'is_exclusive' => false,
                'is_urgent' => true,
            ],
            [
                'reference' => 'ARD-TST-0011',
                'title' => 'Terrain agricole - périphérie',
                'type_terrain' => 'agricole',
                'status' => 'soumis',
                'visibility' => 'restricted',
                'superficie' => 7500.00,
                'prix_demande' => 3500000.00,
                'quartier' => null,
                'address' => 'Périphérie',
                'commune_name' => null,
                'owner_email' => 'vendeur1@ardocco.test',
                'agent_email' => 'agent2@ardocco.test',
                'is_exclusive' => false,
                'is_urgent' => false,
            ],
            [
                'reference' => 'ARD-TST-0012',
                'title' => 'Terrain résidentiel - opportunité',
                'type_terrain' => 'residentiel',
                'status' => 'en_revision',
                'visibility' => 'private',
                'superficie' => 280.00,
                'prix_demande' => 2400000.00,
                'quartier' => 'Quartier calme',
                'address' => 'Adresse à confirmer',
                'commune_name' => null,
                'owner_email' => 'vendeur2@ardocco.test',
                'agent_email' => 'agent1@ardocco.test',
                'is_exclusive' => false,
                'is_urgent' => true,
            ],
        ];

        $createdOrUpdated = 0;

        foreach ($definitions as $definition) {
            $commune = $definition['commune_name']
                ? $communesByName->get($definition['commune_name'])
                : null;

            if (!$commune) {
                $commune = $fallbackCommune;
            }

            if (!$commune) {
                $this->command?->error('❌ Impossible de sélectionner une commune (table communes vide).');
                return;
            }

            $owner = $ownersByEmail->get($definition['owner_email']);
            if (!$owner) {
                $this->command?->error("❌ Owner introuvable: {$definition['owner_email']}");
                return;
            }

            $agentId = null;
            if (!empty($definition['agent_email'])) {
                $agent = $agentsByEmail->get($definition['agent_email']);
                if (!$agent) {
                    $this->command?->error("❌ Agent introuvable: {$definition['agent_email']}");
                    return;
                }
                $agentId = $agent->id;
            }

            $status = $definition['status'];
            $timestamps = $this->timestampsForStatus($status);

            $superficie = (float) $definition['superficie'];
            $prixDemande = (float) $definition['prix_demande'];
            $prixParM2 = $superficie > 0 ? round($prixDemande / $superficie, 2) : null;
            $prixEstime = round($prixDemande * Arr::random([0.92, 0.97, 1.03, 1.08]), 2);

            $viabilisation = [
                'eau' => (bool) Arr::random([true, true, false]),
                'electricite' => (bool) Arr::random([true, true, false]),
                'assainissement' => (bool) Arr::random([true, false]),
                'voirie' => (bool) Arr::random([true, true, false]),
            ];

            $listing = Listing::withTrashed()
                ->firstOrNew(['reference' => $definition['reference']]);

            $listing->fill([
                'owner_id' => $owner->id,
                'agent_id' => $agentId,
                'title' => $definition['title'],
                'description' => "Annonce de test ({$definition['reference']}).",
                'commune_id' => $commune->id,
                'quartier' => $definition['quartier'],
                'address' => $definition['address'],
                'latitude' => $commune->latitude,
                'longitude' => $commune->longitude,
                'superficie' => $superficie,
                'prix_demande' => $prixDemande,
                'prix_estime' => $prixEstime,
                'prix_par_m2' => $prixParM2,
                'type_terrain' => $definition['type_terrain'],
                'status' => $status,
                'titre_foncier' => Arr::random([null, 'TF-' . $definition['reference'], 'TF-' . random_int(10000, 99999)]),
                'forme_terrain' => Arr::random([null, 'rectangulaire', 'irrégulière', 'trapèze']),
                'topographie' => Arr::random([null, 'plat', 'en pente', 'mixte']),
                'viabilisation' => $viabilisation,
                'zonage' => Arr::random([null, 'R+2', 'R+5', 'Zone villa', 'ZI', 'Zone agricole']),
                'coefficient_occupation' => Arr::random([null, 0.60, 0.80, 1.20]),
                'hauteur_max' => Arr::random([null, 8, 12, 16, 20]),
                'is_exclusive' => (bool) $definition['is_exclusive'],
                'is_urgent' => (bool) $definition['is_urgent'],
                'visibility' => $definition['visibility'],
                'views_count' => (int) Arr::random([0, 3, 12, 45, 120, 350]),
                'submitted_at' => $timestamps['submitted_at'],
                'validated_at' => $timestamps['validated_at'],
                'published_at' => $timestamps['published_at'],
            ]);

            $listing->save();
            if ($listing->trashed()) {
                $listing->restore();
            }

            if (in_array($status, ['valide', 'publie', 'vendu'], true)) {
                $validatorId = $admin?->id ?? $agentId;

                FicheTechnique::query()->updateOrCreate(
                    ['listing_id' => $listing->id],
                    [
                        'accessibilite' => ['route' => 'bonne', 'transport' => 'proche'],
                        'voisinage' => ['environnement' => Arr::random(['résidentiel', 'mixte', 'commercial'])],
                        'contraintes_techniques' => ['contraintes' => []],
                        'opportunites' => ['points_forts' => ['emplacement', 'potentiel']],
                        'equipements' => ['eau' => $viabilisation['eau'], 'electricite' => $viabilisation['electricite']],
                        'photos_analyse' => [],
                        'generated_by_ai' => false,
                        'validated_by' => $validatorId,
                        'validated_at' => now(),
                    ]
                );

                FicheFinanciere::query()->updateOrCreate(
                    ['listing_id' => $listing->id],
                    [
                        'prix_marche_estime' => $prixEstime,
                        'comparables' => [],
                        'hypotheses_valorisation' => ['scenario' => 'standard'],
                        'couts_viabilisation' => Arr::random([0, 250000, 500000]),
                        'couts_amenagement' => Arr::random([0, 150000, 300000]),
                        'taxes_estimees' => ['tva' => null, 'frais' => null],
                        'rentabilite_potentielle' => ['roi_estime' => Arr::random([0.08, 0.12, 0.18])],
                        'generated_by_ai' => false,
                        'validated_by' => $validatorId,
                        'validated_at' => now(),
                    ]
                );

                FicheJuridique::query()->updateOrCreate(
                    ['listing_id' => $listing->id],
                    [
                        'statut_foncier' => Arr::random(['titre_foncier', 'melk', 'collectif']),
                        'numero_titre' => Arr::random([null, 'TJ-' . random_int(100000, 999999)]),
                        'proprietaire_legal' => $owner->first_name . ' ' . $owner->last_name,
                        'servitudes' => [],
                        'restrictions' => [],
                        'litiges' => [],
                        'documents_manquants' => [],
                        'points_vigilance' => [],
                        'conformite_urbanisme' => true,
                        'generated_by_ai' => false,
                        'validated_by' => $validatorId,
                        'validated_at' => now(),
                    ]
                );
            }

            $createdOrUpdated++;
        }

        $this->command?->info("✅ Listings de test créés/actualisés: {$createdOrUpdated}");
    }

    /**
     * Déduit les timestamps (submitted/validated/published) selon le status.
     *
     * @return array{submitted_at: \Illuminate\Support\Carbon|null, validated_at: \Illuminate\Support\Carbon|null, published_at: \Illuminate\Support\Carbon|null}
     */
    private function timestampsForStatus(string $status): array
    {
        $submittedStatuses = ['soumis', 'en_revision', 'valide', 'refuse', 'publie', 'vendu'];
        $validatedStatuses = ['valide', 'publie', 'vendu'];
        $publishedStatuses = ['publie', 'vendu'];

        $submittedAt = in_array($status, $submittedStatuses, true)
            ? now()->subDays(Arr::random([2, 5, 10, 15]))
            : null;

        $validatedAt = null;
        if (in_array($status, $validatedStatuses, true)) {
            $validatedAt = ($submittedAt ? $submittedAt->copy() : now())
                ->addDays(Arr::random([1, 2, 3]));
        }

        $publishedAt = null;
        if (in_array($status, $publishedStatuses, true)) {
            $publishedAt = ($validatedAt ? $validatedAt->copy() : now())
                ->addDays(Arr::random([1, 2]));
        }

        return [
            'submitted_at' => $submittedAt,
            'validated_at' => $validatedAt,
            'published_at' => $publishedAt,
        ];
    }
}
