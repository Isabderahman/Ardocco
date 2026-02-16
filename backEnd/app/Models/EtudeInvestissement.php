<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtudeInvestissement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'etudes_investissement';

    protected $fillable = [
        'listing_id',
        'created_by',
        'titre_projet',
        'type_projet',
        'nombre_sous_sols',
        'nombre_etages',
        'localisation',
        'version',
        'superficie_terrain',
        'prix_terrain_m2',
        'taux_immatriculation',
        'surfaces_par_niveau',
        'surface_plancher_total',
        'cout_gros_oeuvres_m2',
        'cout_finition_m2',
        'amenagement_divers',
        'frais_groupement_etudes',
        'frais_autorisation_eclatement',
        'frais_lydec',
        'surfaces_vendables',
        'surface_vendable_commerce',
        'surface_vendable_appart',
        'prix_vente_m2_commerce',
        'prix_vente_m2_appart',
        'prix_terrain_total',
        'frais_immatriculation',
        'cout_total_travaux',
        'total_frais_construction',
        'total_investissement',
        'revenus_commerce',
        'revenus_appart',
        'total_revenues',
        'resultat_brute',
        'ratio',
        'plans',
        'generated_by_ai',
        'ai_extracted_data',
        'ai_notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected $casts = [
        'superficie_terrain' => 'decimal:2',
        'prix_terrain_m2' => 'decimal:2',
        'taux_immatriculation' => 'decimal:2',
        'surfaces_par_niveau' => 'array',
        'surface_plancher_total' => 'decimal:2',
        'cout_gros_oeuvres_m2' => 'decimal:2',
        'cout_finition_m2' => 'decimal:2',
        'amenagement_divers' => 'decimal:2',
        'frais_groupement_etudes' => 'decimal:2',
        'frais_autorisation_eclatement' => 'decimal:2',
        'frais_lydec' => 'decimal:2',
        'surfaces_vendables' => 'array',
        'surface_vendable_commerce' => 'decimal:2',
        'surface_vendable_appart' => 'decimal:2',
        'prix_vente_m2_commerce' => 'decimal:2',
        'prix_vente_m2_appart' => 'decimal:2',
        'prix_terrain_total' => 'decimal:2',
        'frais_immatriculation' => 'decimal:2',
        'cout_total_travaux' => 'decimal:2',
        'total_frais_construction' => 'decimal:2',
        'total_investissement' => 'decimal:2',
        'revenus_commerce' => 'decimal:2',
        'revenus_appart' => 'decimal:2',
        'total_revenues' => 'decimal:2',
        'resultat_brute' => 'decimal:2',
        'ratio' => 'decimal:2',
        'plans' => 'array',
        'generated_by_ai' => 'boolean',
        'ai_extracted_data' => 'array',
        'reviewed_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
        'nombre_sous_sols' => 'integer',
        'nombre_etages' => 'integer',
    ];

    // Relations
    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Calculate all derived values
    public function calculate(): self
    {
        // Prix terrain total
        $this->prix_terrain_total = $this->superficie_terrain * $this->prix_terrain_m2;

        // Frais immatriculation
        $this->frais_immatriculation = $this->prix_terrain_total * ($this->taux_immatriculation / 100);

        // Surface plancher total (from surfaces_par_niveau)
        if ($this->surfaces_par_niveau) {
            $this->surface_plancher_total = array_sum($this->surfaces_par_niveau);
        }

        // Cout total travaux
        $coutParM2 = $this->cout_gros_oeuvres_m2 + $this->cout_finition_m2;
        $this->cout_total_travaux = ($coutParM2 * $this->surface_plancher_total) + $this->amenagement_divers;

        // Frais groupement etudes (2.5% of cout travaux if not set)
        if (!$this->frais_groupement_etudes) {
            $this->frais_groupement_etudes = $this->cout_total_travaux * 0.025;
        }

        // Total frais construction
        $this->total_frais_construction = $this->cout_total_travaux
            + $this->frais_groupement_etudes
            + $this->frais_autorisation_eclatement
            + $this->frais_lydec;

        // Total investissement
        $this->total_investissement = $this->prix_terrain_total
            + $this->frais_immatriculation
            + $this->total_frais_construction;

        // Calculate surfaces vendables from JSON if provided
        if ($this->surfaces_vendables) {
            $commerce = 0;
            $appart = 0;
            foreach ($this->surfaces_vendables as $niveau => $data) {
                if (isset($data['usage']) && isset($data['surface'])) {
                    if ($data['usage'] === 'commerce') {
                        $commerce += $data['surface'];
                    } elseif ($data['usage'] === 'apparts') {
                        $appart += $data['surface'];
                    }
                }
            }
            $this->surface_vendable_commerce = $commerce;
            $this->surface_vendable_appart = $appart;
        }

        // Revenus
        $this->revenus_commerce = $this->surface_vendable_commerce * ($this->prix_vente_m2_commerce ?? 0);
        $this->revenus_appart = $this->surface_vendable_appart * $this->prix_vente_m2_appart;

        // Total revenues
        $this->total_revenues = $this->revenus_commerce + $this->revenus_appart;

        // Resultat brute
        $this->resultat_brute = $this->total_revenues - $this->total_investissement;

        // Ratio (%)
        if ($this->total_investissement > 0) {
            $this->ratio = ($this->resultat_brute / $this->total_investissement) * 100;
        }

        return $this;
    }

    // Get formatted data for display
    public function getFormattedDataAttribute(): array
    {
        return [
            'projet' => [
                'titre' => $this->titre_projet,
                'type' => $this->type_projet,
                'localisation' => $this->localisation,
                'version' => $this->version,
            ],
            'terrain' => [
                'superficie_m2' => $this->superficie_terrain,
                'prix_m2' => $this->prix_terrain_m2,
                'prix_total' => $this->prix_terrain_total,
                'frais_immatriculation' => $this->frais_immatriculation,
            ],
            'construction' => [
                'surfaces_par_niveau' => $this->surfaces_par_niveau,
                'surface_plancher_total' => $this->surface_plancher_total,
                'cout_gros_oeuvres_m2' => $this->cout_gros_oeuvres_m2,
                'cout_finition_m2' => $this->cout_finition_m2,
                'amenagement_divers' => $this->amenagement_divers,
                'cout_total_travaux' => $this->cout_total_travaux,
            ],
            'frais' => [
                'groupement_etudes' => $this->frais_groupement_etudes,
                'autorisation_eclatement' => $this->frais_autorisation_eclatement,
                'lydec' => $this->frais_lydec,
                'total_construction' => $this->total_frais_construction,
            ],
            'investissement' => [
                'total' => $this->total_investissement,
            ],
            'vente' => [
                'surfaces_vendables' => $this->surfaces_vendables,
                'surface_commerce' => $this->surface_vendable_commerce,
                'surface_appart' => $this->surface_vendable_appart,
                'prix_m2_commerce' => $this->prix_vente_m2_commerce,
                'prix_m2_appart' => $this->prix_vente_m2_appart,
            ],
            'resultat' => [
                'revenus_commerce' => $this->revenus_commerce,
                'revenus_appart' => $this->revenus_appart,
                'total_revenues' => $this->total_revenues,
                'resultat_brute' => $this->resultat_brute,
                'ratio' => $this->ratio,
            ],
            'status' => $this->status,
            'generated_by_ai' => $this->generated_by_ai,
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function canBeViewedBy(User $user): bool
    {
        // Admins and agents can always view
        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }

        // Creator can view their own
        if ($user->id === $this->created_by) {
            return true;
        }

        // Promoteurs can view approved etudes for their listings
        if ($user->isPromoteur() && $this->isApproved()) {
            return $this->listing->owner_id === $user->id;
        }

        return false;
    }
}
