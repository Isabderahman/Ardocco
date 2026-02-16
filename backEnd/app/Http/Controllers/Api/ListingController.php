<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commune;
use App\Models\Document;
use App\Models\FicheFinanciere;
use App\Models\FicheJuridique;
use App\Models\FicheTechnique;
use App\Models\Listing;
use App\Models\Notification;
use App\Models\User;
use App\Jobs\GenerateEtudeInvestissement;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['admin', 'agent', 'vendeur'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $query = Listing::query()
            ->with([
                'commune.province.region',
                'owner',
                'agent',
                'documents' => function ($q) {
                    $q
                        ->where('document_type', 'photos')
                        ->orderByDesc('created_at')
                        ->limit(3);
                },
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

        if (!in_array($user->role, ['vendeur', 'agent', 'admin'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $validated = $request->validate([
            'reference' => 'nullable|string|max:50|unique:listings,reference',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'commune_id' => 'required|uuid|exists:communes,id',
            'quartier' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'geojson_polygon' => 'nullable',
            'superficie' => 'nullable|numeric|min:0',
            'superficie_m2' => 'nullable|numeric|min:0',
            'superficie_unknown' => 'sometimes|boolean',
            'prix_demande' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'price_on_request' => 'sometimes|boolean',
            'price_per_m2' => 'sometimes|boolean',
            'negotiable' => 'sometimes|boolean',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'owner_attestation' => 'sometimes|accepted',
            'titre_foncier' => 'sometimes|boolean',
            'reference_tf' => 'nullable|string|max:100',
            'perimetre' => 'nullable|string|in:urbain,rural,periurbain,Urbain,Rural,Périurbain,Periurbain,peri-urbain,periurbain',
            'zonage' => 'nullable|string|max:100',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'file|mimes:jpg,jpeg,png|max:10240',
            'plan_situation' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'plan_cadastral' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'type_terrain' => 'required|in:residentiel,commercial,industriel,agricole,mixte',
            'visibility' => 'nullable|in:public,private,restricted',
            'is_exclusive' => 'sometimes|boolean',
            'is_urgent' => 'sometimes|boolean',
            'user_role' => 'nullable|string|in:proprietaire,agent',
            'cout_investissement' => 'nullable|numeric|min:0',
            'ratio' => 'nullable|numeric|min:0|max:100',
            'note_renseignement' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $geojsonPolygon = $validated['geojson_polygon'] ?? null;
        if (is_string($geojsonPolygon) && trim($geojsonPolygon) !== '') {
            $decoded = json_decode($geojsonPolygon, true);
            if (!is_array($decoded)) {
                throw ValidationException::withMessages([
                    'geojson_polygon' => ['GeoJSON polygon must be valid JSON.'],
                ]);
            }
            $geojsonPolygon = $decoded;
        }

        if ($geojsonPolygon !== null) {
            $type = is_array($geojsonPolygon) ? ($geojsonPolygon['type'] ?? null) : null;
            $coordinates = is_array($geojsonPolygon) ? ($geojsonPolygon['coordinates'] ?? null) : null;

            if ($type !== 'Polygon' || !is_array($coordinates)) {
                throw ValidationException::withMessages([
                    'geojson_polygon' => ['GeoJSON polygon must be an object with type=Polygon and coordinates.'],
                ]);
            }
        }

        $superficieUnknown = filter_var($validated['superficie_unknown'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $superficieValue = $validated['superficie'] ?? $validated['superficie_m2'] ?? null;
        $superficie = $superficieValue !== null ? (float) $superficieValue : null;

        if (!$superficieUnknown && (!$superficie || $superficie <= 0)) {
            throw ValidationException::withMessages([
                'superficie_m2' => ['Superficie is required unless it is unknown.'],
            ]);
        }

        $priceOnRequest = filter_var($validated['price_on_request'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $priceValue = $validated['prix_demande'] ?? $validated['price'] ?? null;
        $prixDemande = $priceValue !== null ? (float) $priceValue : null;

        if (!$priceOnRequest && ($prixDemande === null || $prixDemande <= 0)) {
            throw ValidationException::withMessages([
                'price' => ['Price is required unless it is on request.'],
            ]);
        }

        $hasTitreFoncier = filter_var($validated['titre_foncier'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $referenceTf = isset($validated['reference_tf']) ? trim((string) $validated['reference_tf']) : '';
        if ($hasTitreFoncier && $referenceTf === '') {
            throw ValidationException::withMessages([
                'reference_tf' => ['Reference TF is required when titre foncier is enabled.'],
            ]);
        }

        if (!$hasTitreFoncier && !$request->hasFile('plan_situation')) {
            throw ValidationException::withMessages([
                'plan_situation' => ['Plan de situation is required when titre foncier is not available.'],
            ]);
        }

        $commune = Commune::query()->find($validated['commune_id']);

        $superficieForCalc = $superficieUnknown ? 0.0 : (float) ($superficie ?? 0.0);
        $prixDemandeForCalc = $priceOnRequest ? 0.0 : (float) ($prixDemande ?? 0.0);
        $prixParM2 = $superficieForCalc > 0 && $prixDemandeForCalc > 0 ? round($prixDemandeForCalc / $superficieForCalc, 2) : null;

        $perimetre = null;
        if (array_key_exists('perimetre', $validated) && $validated['perimetre'] !== null) {
            $rawPerimetre = trim((string) $validated['perimetre']);
            $rawPerimetre = str_replace(['é', 'É'], 'e', $rawPerimetre);
            $rawPerimetre = str_replace('-', '', $rawPerimetre);
            $rawPerimetre = mb_strtolower($rawPerimetre);
            $perimetre = match ($rawPerimetre) {
                'urbain' => 'urbain',
                'rural' => 'rural',
                'periurbain' => 'periurbain',
                default => null,
            };
        }

        $listing = Listing::create([
            'owner_id' => $user->id,
            'agent_id' => null,
            'contact_phone' => $validated['phone'] ?? $user->phone,
            'contact_whatsapp' => $validated['whatsapp'] ?? null,
            'contact_email' => $validated['email'] ?? $user->email,
            'owner_attestation' => true,
            'user_role' => $validated['user_role'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'commune_id' => $validated['commune_id'],
            'quartier' => $validated['quartier'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? $commune?->latitude,
            'longitude' => $validated['longitude'] ?? $commune?->longitude,
            'geojson_polygon' => $geojsonPolygon,
            'superficie' => $superficieForCalc,
            'superficie_unknown' => $superficieUnknown,
            'prix_demande' => $prixDemandeForCalc,
            'price_on_request' => $priceOnRequest,
            'prix_par_m2' => $prixParM2,
            'show_price_per_m2' => filter_var($validated['price_per_m2'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'negotiable' => filter_var($validated['negotiable'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'type_terrain' => $validated['type_terrain'],
            'status' => 'brouillon',
            'titre_foncier' => $hasTitreFoncier ? $referenceTf : null,
            // New listings stay private until reviewed/published.
            'visibility' => 'private',
            'zonage' => $validated['zonage'] ?? null,
            'perimetre' => $perimetre,
            'is_exclusive' => filter_var($validated['is_exclusive'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'is_urgent' => filter_var($validated['is_urgent'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'cout_investissement' => isset($validated['cout_investissement']) ? (float) $validated['cout_investissement'] : null,
            'ratio' => isset($validated['ratio']) ? (float) $validated['ratio'] : null,
            'views_count' => 0,
        ]);

        Storage::disk('public')->makeDirectory("listings/{$listing->id}/images");
        Storage::disk('public')->makeDirectory("listings/{$listing->id}/documents");

        if ($request->hasFile('plan_situation')) {
            $file = $request->file('plan_situation');
            $path = $file->store("listings/{$listing->id}/documents", 'public');

            Document::create([
                'listing_id' => $listing->id,
                'uploaded_by' => $user->id,
                'document_type' => 'plan_cadastral',
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_public' => false,
                'processing_status' => 'completed',
            ]);
        }

        if ($request->hasFile('plan_cadastral')) {
            $file = $request->file('plan_cadastral');
            $path = $file->store("listings/{$listing->id}/documents", 'public');

            Document::create([
                'listing_id' => $listing->id,
                'uploaded_by' => $user->id,
                'document_type' => 'plan_cadastral',
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_public' => false,
                'processing_status' => 'completed',
            ]);
        }

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $path = $file->store("listings/{$listing->id}/images", 'public');

                Document::create([
                    'listing_id' => $listing->id,
                    'uploaded_by' => $user->id,
                    'document_type' => 'photos',
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'is_public' => true,
                    'processing_status' => 'completed',
                ]);
            }
        }

        if ($request->hasFile('note_renseignement')) {
            $file = $request->file('note_renseignement');
            $path = $file->store("listings/{$listing->id}/documents", 'public');

            Document::create([
                'listing_id' => $listing->id,
                'uploaded_by' => $user->id,
                'document_type' => 'note_renseignements',
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_public' => false,
                'processing_status' => 'completed',
            ]);
        }

        $listing->load(['commune.province.region', 'owner', 'agent']);

        // Dispatch job to generate AI investment study
        GenerateEtudeInvestissement::dispatch($listing, $user->id);

        return response()->json([
            'success' => true,
            'data' => $listing,
            'message' => 'Listing created. Investment study is being generated.',
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
            'etudesInvestissement' => fn($q) => $q->orderByDesc('created_at'),
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
            'geojson_polygon' => 'nullable|array',
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
            'cout_investissement' => 'nullable|numeric|min:0',
            'ratio' => 'nullable|numeric|min:0|max:100',
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

        // Vendeurs can only submit their own listings
        // Agents and admins can submit any listing
        $canSubmit = ($user->role === 'vendeur' && $listing->owner_id === $user->id)
            || $user->isAgent()
            || $user->isAdmin();

        if (!$canSubmit) {
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

        // Submission only requires the base listing info. Expertise fiches can be completed later by experts.
        $missing = $this->missingRequiredSubmissionData($listing);
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
            'visibility' => 'private',
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

    /**
     * @return array<int, string>
     */
    private function missingRequiredSubmissionData(Listing $listing): array
    {
        $missing = [];

        if (!is_string($listing->title) || trim($listing->title) === '') {
            $missing[] = 'title';
        }

        if (!$listing->commune_id) {
            $missing[] = 'commune_id';
        }

        if (!$listing->type_terrain) {
            $missing[] = 'type_terrain';
        }

        if (!$listing->contact_phone) {
            $missing[] = 'contact_phone';
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
