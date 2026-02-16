<?php

namespace App\Jobs;

use App\Models\EtudeInvestissement;
use App\Models\Listing;
use App\Services\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateEtudeInvestissement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public Listing $listing,
        public string $createdBy
    ) {}

    public function handle(AIService $aiService): void
    {
        try {
            // Check if etude already exists for this listing
            $existingEtude = EtudeInvestissement::where('listing_id', $this->listing->id)
                ->where('generated_by_ai', true)
                ->first();

            if ($existingEtude) {
                Log::info('Etude already exists for listing', ['listing_id' => $this->listing->id]);
                return;
            }

            // Get AI suggestions based on terrain data
            $suggestions = $aiService->getInvestmentSuggestions([
                'superficie_terrain' => $this->listing->superficie,
                'localisation' => $this->listing->quartier ?? $this->listing->commune?->name_fr,
                'commune' => $this->listing->commune?->name_fr,
                'quartier' => $this->listing->quartier,
                'zonage' => $this->listing->zonage,
                'coefficient_occupation' => $this->listing->coefficient_occupation,
                'hauteur_max' => $this->listing->hauteur_max,
                'prix_terrain_m2' => $this->listing->prix_par_m2,
            ]);

            if (!$suggestions['success']) {
                Log::warning('Failed to get AI suggestions', [
                    'listing_id' => $this->listing->id,
                    'error' => $suggestions['error'] ?? 'Unknown error',
                ]);
                // Create a basic etude without AI suggestions
                $this->createBasicEtude();
                return;
            }

            $data = $suggestions['data'];

            // Create etude with AI suggestions
            $etude = new EtudeInvestissement([
                'listing_id' => $this->listing->id,
                'created_by' => $this->createdBy,
                'titre_projet' => $this->listing->title,
                'type_projet' => $data['type_projet_suggere'] ?? 'R+4',
                'nombre_sous_sols' => $data['nombre_sous_sols_recommande'] ?? 0,
                'nombre_etages' => $data['nombre_etages_recommande'] ?? 4,
                'localisation' => $this->listing->quartier ?? $this->listing->commune?->name_fr,
                'version' => date('M Y'),
                'superficie_terrain' => $this->listing->superficie,
                'prix_terrain_m2' => $this->listing->prix_par_m2 ?? 0,
                'taux_immatriculation' => 5.50,
                'surfaces_par_niveau' => $this->buildSurfacesParNiveau($data),
                'cout_gros_oeuvres_m2' => $data['couts_estimes']['gros_oeuvres_m2'] ?? 1300,
                'cout_finition_m2' => $data['couts_estimes']['finition_m2'] ?? 2700,
                'amenagement_divers' => $data['couts_estimes']['amenagement_divers'] ?? 350000,
                'frais_autorisation_eclatement' => 450000,
                'frais_lydec' => 270000,
                'surfaces_vendables' => $this->buildSurfacesVendables($data),
                'prix_vente_m2_commerce' => $data['prix_vente_estimes']['m2_commerce'] ?? null,
                'prix_vente_m2_appart' => $data['prix_vente_estimes']['m2_appart'] ?? 18000,
                'generated_by_ai' => true,
                'ai_extracted_data' => $data,
                'ai_notes' => implode("\n", array_merge(
                    $data['recommandations'] ?? [],
                    array_map(fn($r) => "⚠️ $r", $data['risques'] ?? [])
                )),
                'status' => 'draft',
            ]);

            $etude->calculate();
            $etude->save();

            Log::info('Etude generated successfully', [
                'listing_id' => $this->listing->id,
                'etude_id' => $etude->id,
                'ratio' => $etude->ratio,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate etude', [
                'listing_id' => $this->listing->id,
                'error' => $e->getMessage(),
            ]);

            // Create basic etude on failure
            $this->createBasicEtude();
        }
    }

    private function createBasicEtude(): void
    {
        $etude = new EtudeInvestissement([
            'listing_id' => $this->listing->id,
            'created_by' => $this->createdBy,
            'titre_projet' => $this->listing->title,
            'type_projet' => 'R+4',
            'nombre_sous_sols' => 0,
            'nombre_etages' => 4,
            'localisation' => $this->listing->quartier ?? $this->listing->commune?->name_fr,
            'version' => date('M Y'),
            'superficie_terrain' => $this->listing->superficie,
            'prix_terrain_m2' => $this->listing->prix_par_m2 ?? 0,
            'taux_immatriculation' => 5.50,
            'cout_gros_oeuvres_m2' => 1300,
            'cout_finition_m2' => 2700,
            'amenagement_divers' => 350000,
            'frais_autorisation_eclatement' => 450000,
            'frais_lydec' => 270000,
            'prix_vente_m2_appart' => 18000,
            'generated_by_ai' => true,
            'ai_notes' => 'Étude générée avec les valeurs par défaut. Veuillez ajuster les paramètres.',
            'status' => 'draft',
        ]);

        $etude->calculate();
        $etude->save();
    }

    private function buildSurfacesParNiveau(array $data): array
    {
        $surfaces = [];
        $nombreEtages = $data['nombre_etages_recommande'] ?? 4;
        $surfaceRdc = $data['surfaces_par_niveau']['rdc'] ?? ($this->listing->superficie * 0.8);
        $surfaceEtage = $data['surfaces_par_niveau']['etage_courant'] ?? $surfaceRdc;

        if (($data['nombre_sous_sols_recommande'] ?? 0) > 0) {
            $surfaces['sous_sol_1'] = $surfaceRdc;
        }

        $surfaces['rdc'] = $surfaceRdc;

        for ($i = 1; $i <= $nombreEtages; $i++) {
            $surfaces["etage_{$i}"] = $surfaceEtage;
        }

        return $surfaces;
    }

    private function buildSurfacesVendables(array $data): array
    {
        $hasCommerce = $data['repartition_suggeree']['commerce_rdc'] ?? false;
        $surfaceRdc = $data['surfaces_par_niveau']['rdc'] ?? ($this->listing->superficie * 0.8);
        $surfaceEtage = $data['surfaces_par_niveau']['etage_courant'] ?? $surfaceRdc;
        $nombreEtages = $data['nombre_etages_recommande'] ?? 4;

        return [
            'rdc' => [
                'usage' => $hasCommerce ? 'commerce' : 'apparts',
                'surface' => $surfaceRdc * 0.85, // 85% sellable
            ],
            'mezzanine' => [
                'usage' => null,
                'surface' => 0,
            ],
            'etages' => [
                'usage' => 'apparts',
                'surface' => $surfaceEtage * 0.85 * $nombreEtages,
            ],
        ];
    }
}
