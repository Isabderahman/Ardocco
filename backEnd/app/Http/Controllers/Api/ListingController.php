<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commune;
use App\Models\FicheFinanciere;
use App\Models\FicheJuridique;
use App\Models\FicheTechnique;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Listing::query()
            ->with([
                'commune.province.region',
                'owner',
                'agent',
            ])
            ->orderByDesc('created_at');

        if ($user->role === 'vendeur') {
            $query->where('owner_id', $user->id);
        }

        if ($request->filled('status')) {
            $statuses = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('status')))));
            $query->whereIn('status', $statuses);
        }

        if ($request->filled('type_terrain')) {
            $query->where('type_terrain', $request->input('type_terrain'));
        }

        if ($request->filled('commune_id')) {
            $query->where('commune_id', $request->input('commune_id'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            if ($term !== '') {
                $like = '%' . mb_strtolower($term) . '%';

                $query->where(function ($subQuery) use ($like) {
                    $subQuery
                        ->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(reference) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(address) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(quartier) LIKE ?', [$like])
                        ->orWhereHas('commune', function ($communeQuery) use ($like) {
                            $communeQuery
                                ->whereRaw('LOWER(name_fr) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(name_ar) LIKE ?', [$like]);
                        });
                });
            }
        }

        $listings = $query->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'vendeur') {
            return response()->json([
                'success' => false,
                'message' => 'Only vendeur can create listings.',
            ], 403);
        }

        $validated = $request->validate([
            'reference' => 'nullable|string|max:50|unique:listings,reference',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'commune_id' => 'required|uuid|exists:communes,id',
            'quartier' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'superficie' => 'required|numeric|min:0.01',
            'prix_demande' => 'required|numeric|min:0',
            'type_terrain' => 'required|in:residentiel,commercial,industriel,agricole,mixte',
            'visibility' => 'nullable|in:public,private,restricted',
            'is_exclusive' => 'sometimes|boolean',
            'is_urgent' => 'sometimes|boolean',
        ]);

        $commune = Commune::query()->find($validated['commune_id']);

        $superficie = (float) $validated['superficie'];
        $prixDemande = (float) $validated['prix_demande'];
        $prixParM2 = $superficie > 0 ? round($prixDemande / $superficie, 2) : null;

        $listing = Listing::create([
            'owner_id' => $user->id,
            'agent_id' => null,
            'reference' => $validated['reference'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'commune_id' => $validated['commune_id'],
            'quartier' => $validated['quartier'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? $commune?->latitude,
            'longitude' => $validated['longitude'] ?? $commune?->longitude,
            'superficie' => $superficie,
            'prix_demande' => $prixDemande,
            'prix_par_m2' => $prixParM2,
            'type_terrain' => $validated['type_terrain'],
            'status' => 'brouillon',
            'visibility' => $validated['visibility'] ?? 'public',
            'is_exclusive' => (bool) ($validated['is_exclusive'] ?? false),
            'is_urgent' => (bool) ($validated['is_urgent'] ?? false),
            'views_count' => 0,
        ]);

        $listing->load(['commune.province.region', 'owner', 'agent']);

        return response()->json([
            'success' => true,
            'data' => $listing,
        ], 201);
    }

    public function show(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!$this->canView($user, $listing)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $listing->load([
            'commune.province.region',
            'owner',
            'agent',
            'ficheTechnique',
            'ficheFinanciere',
            'ficheJuridique',
            'documents',
        ]);

        return response()->json([
            'success' => true,
            'data' => $listing,
        ]);
    }

    public function update(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!$listing->canBeEditedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'commune_id' => 'sometimes|required|uuid|exists:communes,id',
            'quartier' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'superficie' => 'sometimes|required|numeric|min:0.01',
            'prix_demande' => 'sometimes|required|numeric|min:0',
            'prix_estime' => 'nullable|numeric|min:0',
            'type_terrain' => 'sometimes|required|in:residentiel,commercial,industriel,agricole,mixte',
            'titre_foncier' => 'nullable|string|max:100',
            'forme_terrain' => 'nullable|string|max:50',
            'topographie' => 'nullable|string|max:50',
            'viabilisation' => 'nullable|array',
            'zonage' => 'nullable|string|max:100',
            'coefficient_occupation' => 'nullable|numeric|min:0|max:99',
            'hauteur_max' => 'nullable|integer|min:0|max:999',
            'visibility' => 'nullable|in:public,private,restricted',
            'is_exclusive' => 'sometimes|boolean',
            'is_urgent' => 'sometimes|boolean',
        ]);

        if (array_key_exists('commune_id', $validated)) {
            $commune = Commune::query()->find($validated['commune_id']);
            if (!array_key_exists('latitude', $validated) && $commune?->latitude !== null) {
                $validated['latitude'] = $commune->latitude;
            }
            if (!array_key_exists('longitude', $validated) && $commune?->longitude !== null) {
                $validated['longitude'] = $commune->longitude;
            }
        }

        $listing->fill(Arr::except($validated, ['superficie', 'prix_demande']));

        $superficie = array_key_exists('superficie', $validated) ? (float) $validated['superficie'] : (float) $listing->superficie;
        $prixDemande = array_key_exists('prix_demande', $validated) ? (float) $validated['prix_demande'] : (float) $listing->prix_demande;

        if (array_key_exists('superficie', $validated)) {
            $listing->superficie = $superficie;
        }
        if (array_key_exists('prix_demande', $validated)) {
            $listing->prix_demande = $prixDemande;
        }

        if (array_key_exists('superficie', $validated) || array_key_exists('prix_demande', $validated)) {
            $listing->prix_par_m2 = $superficie > 0 ? round($prixDemande / $superficie, 2) : null;
        }

        $listing->save();

        $listing->load(['commune.province.region', 'owner', 'agent']);

        return response()->json([
            'success' => true,
            'data' => $listing,
        ]);
    }

    public function submit(Request $request, Listing $listing)
    {
        $user = $request->user();

        if ($user->role !== 'vendeur' || $listing->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        if (!in_array($listing->status, ['brouillon', 'refuse', 'en_revision'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Listing cannot be submitted in current status.',
            ], 422);
        }

        $missing = $this->missingRequiredData($listing);
        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => 'Listing is not complete.',
                'missing' => $missing,
            ], 422);
        }

        $listing->forceFill([
            'status' => 'soumis',
            'submitted_at' => now(),
            'validated_at' => null,
            'published_at' => null,
        ])->save();

        $this->notifyAgentsListingSubmitted($listing, $user);

        return response()->json([
            'success' => true,
            'message' => 'Listing submitted.',
            'data' => $listing,
        ]);
    }

    public function upsertFicheTechnique(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!$listing->canBeEditedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'accessibilite' => 'nullable|array',
            'voisinage' => 'nullable|array',
            'contraintes_techniques' => 'nullable|array',
            'opportunites' => 'nullable|array',
            'equipements' => 'nullable|array',
            'photos_analyse' => 'nullable|array',
        ]);

        $data = [
            ...$validated,
            'generated_by_ai' => false,
        ];

        if (!$user->isAdmin() && !$user->isAgent()) {
            $data['validated_by'] = null;
            $data['validated_at'] = null;
        }

        $fiche = FicheTechnique::query()->updateOrCreate(
            ['listing_id' => $listing->id],
            $data
        );

        return response()->json([
            'success' => true,
            'data' => $fiche,
        ]);
    }

    public function upsertFicheFinanciere(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!$listing->canBeEditedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'prix_marche_estime' => 'nullable|numeric|min:0',
            'comparables' => 'nullable|array',
            'hypotheses_valorisation' => 'nullable|array',
            'couts_viabilisation' => 'nullable|numeric|min:0',
            'couts_amenagement' => 'nullable|numeric|min:0',
            'taxes_estimees' => 'nullable|array',
            'rentabilite_potentielle' => 'nullable|array',
        ]);

        $data = [
            ...$validated,
            'generated_by_ai' => false,
        ];

        if (!$user->isAdmin() && !$user->isAgent()) {
            $data['validated_by'] = null;
            $data['validated_at'] = null;
        }

        $fiche = FicheFinanciere::query()->updateOrCreate(
            ['listing_id' => $listing->id],
            $data
        );

        return response()->json([
            'success' => true,
            'data' => $fiche,
        ]);
    }

    public function upsertFicheJuridique(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!$listing->canBeEditedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'statut_foncier' => 'nullable|in:titre_foncier,melk,collectif,habous,domaine_public',
            'numero_titre' => 'nullable|string|max:100',
            'proprietaire_legal' => 'nullable|string',
            'servitudes' => 'nullable|array',
            'restrictions' => 'nullable|array',
            'litiges' => 'nullable|array',
            'documents_manquants' => 'nullable|array',
            'points_vigilance' => 'nullable|array',
            'conformite_urbanisme' => 'nullable|boolean',
        ]);

        $data = [
            ...$validated,
            'generated_by_ai' => false,
        ];

        if (!$user->isAdmin() && !$user->isAgent()) {
            $data['validated_by'] = null;
            $data['validated_at'] = null;
        }

        $fiche = FicheJuridique::query()->updateOrCreate(
            ['listing_id' => $listing->id],
            $data
        );

        return response()->json([
            'success' => true,
            'data' => $fiche,
        ]);
    }

    private function canView(User $user, Listing $listing): bool
    {
        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }

        return $listing->owner_id === $user->id;
    }

    /**
     * @return array<int, string>
     */
    private function missingRequiredData(Listing $listing): array
    {
        $missing = [];

        if (!$listing->ficheTechnique()->exists()) {
            $missing[] = 'fiche_technique';
        }
        if (!$listing->ficheFinanciere()->exists()) {
            $missing[] = 'fiche_financiere';
        }
        if (!$listing->ficheJuridique()->exists()) {
            $missing[] = 'fiche_juridique';
        }

        return $missing;
    }

    private function notifyAgentsListingSubmitted(Listing $listing, User $owner): void
    {
        $agents = User::query()
            ->where('role', 'agent')
            ->where('is_active', true)
            ->get(['id']);

        foreach ($agents as $agent) {
            Notification::create([
                'user_id' => $agent->id,
                'type' => 'listing_submitted',
                'title' => 'Nouvelle annonce à valider',
                'message' => "Annonce {$listing->reference} soumise par {$owner->first_name} {$owner->last_name}.",
                'link' => "/agent/listings/{$listing->id}",
                'is_read' => false,
            ]);
        }
    }
}
