<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EtudeInvestissement;
use App\Models\Listing;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EtudeInvestissementController extends Controller
{
    protected AIService $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * List etudes for a listing
     */
    public function index(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!$this->canViewListing($user, $listing)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $query = EtudeInvestissement::where('listing_id', $listing->id)
            ->with(['creator', 'reviewer'])
            ->orderByDesc('created_at');

        // Non-admin/agent users can only see approved etudes or their own
        if (!$user->isAdmin() && !$user->isAgent()) {
            $query->where(function ($q) use ($user) {
                $q->where('status', 'approved')
                    ->orWhere('created_by', $user->id);
            });
        }

        $etudes = $query->get();

        return response()->json([
            'success' => true,
            'data' => $etudes,
        ]);
    }

    /**
     * Create a new etude d'investissement
     */
    public function store(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!in_array($user->role, ['admin', 'agent'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins and agents can create investment studies.',
            ], 403);
        }

        $validated = $request->validate([
            'titre_projet' => 'nullable|string|max:255',
            'type_projet' => 'nullable|string|max:50',
            'nombre_sous_sols' => 'nullable|integer|min:0|max:5',
            'nombre_etages' => 'nullable|integer|min:1|max:20',
            'localisation' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:50',
            'superficie_terrain' => 'required|numeric|min:0.01',
            'prix_terrain_m2' => 'required|numeric|min:0',
            'taux_immatriculation' => 'nullable|numeric|min:0|max:100',
            'surfaces_par_niveau' => 'nullable|array',
            'cout_gros_oeuvres_m2' => 'nullable|numeric|min:0',
            'cout_finition_m2' => 'nullable|numeric|min:0',
            'amenagement_divers' => 'nullable|numeric|min:0',
            'frais_groupement_etudes' => 'nullable|numeric|min:0',
            'frais_autorisation_eclatement' => 'nullable|numeric|min:0',
            'frais_lydec' => 'nullable|numeric|min:0',
            'surfaces_vendables' => 'nullable|array',
            'surface_vendable_commerce' => 'nullable|numeric|min:0',
            'surface_vendable_appart' => 'nullable|numeric|min:0',
            'prix_vente_m2_commerce' => 'nullable|numeric|min:0',
            'prix_vente_m2_appart' => 'nullable|numeric|min:0',
            'use_ai_suggestions' => 'nullable|boolean',
        ]);

        $etude = new EtudeInvestissement([
            'listing_id' => $listing->id,
            'created_by' => $user->id,
            'titre_projet' => $validated['titre_projet'] ?? $listing->title,
            'type_projet' => $validated['type_projet'] ?? null,
            'nombre_sous_sols' => $validated['nombre_sous_sols'] ?? 0,
            'nombre_etages' => $validated['nombre_etages'] ?? 4,
            'localisation' => $validated['localisation'] ?? $listing->quartier ?? $listing->commune?->name_fr,
            'version' => $validated['version'] ?? date('M Y'),
            'superficie_terrain' => $validated['superficie_terrain'],
            'prix_terrain_m2' => $validated['prix_terrain_m2'],
            'taux_immatriculation' => $validated['taux_immatriculation'] ?? 5.50,
            'surfaces_par_niveau' => $validated['surfaces_par_niveau'] ?? null,
            'cout_gros_oeuvres_m2' => $validated['cout_gros_oeuvres_m2'] ?? 1300,
            'cout_finition_m2' => $validated['cout_finition_m2'] ?? 2700,
            'amenagement_divers' => $validated['amenagement_divers'] ?? 350000,
            'frais_groupement_etudes' => $validated['frais_groupement_etudes'] ?? null,
            'frais_autorisation_eclatement' => $validated['frais_autorisation_eclatement'] ?? 450000,
            'frais_lydec' => $validated['frais_lydec'] ?? 270000,
            'surfaces_vendables' => $validated['surfaces_vendables'] ?? null,
            'surface_vendable_commerce' => $validated['surface_vendable_commerce'] ?? 0,
            'surface_vendable_appart' => $validated['surface_vendable_appart'] ?? 0,
            'prix_vente_m2_commerce' => $validated['prix_vente_m2_commerce'] ?? null,
            'prix_vente_m2_appart' => $validated['prix_vente_m2_appart'] ?? 18000,
            'status' => 'draft',
        ]);

        // Calculate all derived values
        $etude->calculate();
        $etude->save();

        return response()->json([
            'success' => true,
            'data' => $etude->load(['creator', 'listing']),
        ], 201);
    }

    /**
     * Show a specific etude
     */
    public function show(Request $request, Listing $listing, EtudeInvestissement $etude)
    {
        $user = $request->user();

        if ($etude->listing_id !== $listing->id) {
            return response()->json([
                'success' => false,
                'message' => 'Etude not found for this listing.',
            ], 404);
        }

        if (!$etude->canBeViewedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $etude->load(['creator', 'reviewer', 'listing.commune']);

        return response()->json([
            'success' => true,
            'data' => $etude,
            'formatted' => $etude->formatted_data,
        ]);
    }

    /**
     * Update an etude
     */
    public function update(Request $request, Listing $listing, EtudeInvestissement $etude)
    {
        $user = $request->user();

        if ($etude->listing_id !== $listing->id) {
            return response()->json([
                'success' => false,
                'message' => 'Etude not found for this listing.',
            ], 404);
        }

        if (!in_array($user->role, ['admin', 'agent'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        // Can't edit approved etudes unless admin
        if ($etude->status === 'approved' && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit approved etudes.',
            ], 403);
        }

        $validated = $request->validate([
            'titre_projet' => 'nullable|string|max:255',
            'type_projet' => 'nullable|string|max:50',
            'nombre_sous_sols' => 'nullable|integer|min:0|max:5',
            'nombre_etages' => 'nullable|integer|min:1|max:20',
            'localisation' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:50',
            'superficie_terrain' => 'sometimes|numeric|min:0.01',
            'prix_terrain_m2' => 'sometimes|numeric|min:0',
            'taux_immatriculation' => 'nullable|numeric|min:0|max:100',
            'surfaces_par_niveau' => 'nullable|array',
            'cout_gros_oeuvres_m2' => 'nullable|numeric|min:0',
            'cout_finition_m2' => 'nullable|numeric|min:0',
            'amenagement_divers' => 'nullable|numeric|min:0',
            'frais_groupement_etudes' => 'nullable|numeric|min:0',
            'frais_autorisation_eclatement' => 'nullable|numeric|min:0',
            'frais_lydec' => 'nullable|numeric|min:0',
            'surfaces_vendables' => 'nullable|array',
            'surface_vendable_commerce' => 'nullable|numeric|min:0',
            'surface_vendable_appart' => 'nullable|numeric|min:0',
            'prix_vente_m2_commerce' => 'nullable|numeric|min:0',
            'prix_vente_m2_appart' => 'nullable|numeric|min:0',
            'plans' => 'nullable|array',
        ]);

        $etude->fill($validated);
        $etude->calculate();
        $etude->save();

        return response()->json([
            'success' => true,
            'data' => $etude->load(['creator', 'reviewer']),
            'formatted' => $etude->formatted_data,
        ]);
    }

    /**
     * Submit etude for review
     */
    public function submit(Request $request, Listing $listing, EtudeInvestissement $etude)
    {
        $user = $request->user();

        if ($etude->listing_id !== $listing->id) {
            return response()->json([
                'success' => false,
                'message' => 'Etude not found for this listing.',
            ], 404);
        }

        if (!in_array($user->role, ['admin', 'agent'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        if ($etude->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft etudes can be submitted.',
            ], 422);
        }

        $etude->status = 'pending_review';
        $etude->save();

        return response()->json([
            'success' => true,
            'message' => 'Etude submitted for review.',
            'data' => $etude,
        ]);
    }

    /**
     * Approve or reject an etude (admin only)
     */
    public function review(Request $request, Listing $listing, EtudeInvestissement $etude)
    {
        $user = $request->user();

        if ($etude->listing_id !== $listing->id) {
            return response()->json([
                'success' => false,
                'message' => 'Etude not found for this listing.',
            ], 404);
        }

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can review etudes.',
            ], 403);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:1000',
        ]);

        $etude->reviewed_by = $user->id;
        $etude->reviewed_at = now();
        $etude->review_notes = $validated['notes'] ?? null;
        $etude->status = $validated['action'] === 'approve' ? 'approved' : 'rejected';
        $etude->save();

        return response()->json([
            'success' => true,
            'message' => $validated['action'] === 'approve' ? 'Etude approved.' : 'Etude rejected.',
            'data' => $etude->load(['creator', 'reviewer']),
        ]);
    }

    /**
     * Get AI suggestions for investment study
     */
    public function getSuggestions(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!in_array($user->role, ['admin', 'agent'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $result = $this->aiService->getInvestmentSuggestions([
            'superficie_terrain' => $listing->superficie,
            'localisation' => $listing->quartier ?? $listing->commune?->name_fr,
            'commune' => $listing->commune?->name_fr,
            'quartier' => $listing->quartier,
            'zonage' => $listing->zonage,
            'coefficient_occupation' => $listing->coefficient_occupation,
            'hauteur_max' => $listing->hauteur_max,
            'prix_terrain_m2' => $listing->prix_par_m2,
        ]);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get AI suggestions.',
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Analyze uploaded architectural plans
     */
    public function analyzePlans(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!in_array($user->role, ['admin', 'agent'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $request->validate([
            'plans' => 'required|array|min:1',
            'plans.*' => 'file|mimes:pdf,jpg,jpeg,png|max:20480',
            'context' => 'nullable|string|max:1000',
        ]);

        $result = $this->aiService->analyzePlans(
            $request->file('plans'),
            $request->input('context')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze plans.',
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data'],
        ]);
    }

    /**
     * Generate PDF for an etude
     */
    public function generatePdf(Request $request, Listing $listing, EtudeInvestissement $etude)
    {
        $user = $request->user();

        if ($etude->listing_id !== $listing->id) {
            return response()->json([
                'success' => false,
                'message' => 'Etude not found for this listing.',
            ], 404);
        }

        if (!$etude->canBeViewedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $result = $this->aiService->generateBusinessPlanPdf($etude->formatted_data);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF.',
                'error' => $result['error'] ?? 'Unknown error',
            ], 500);
        }

        // Update etude with PDF path
        $etude->pdf_path = $result['path'];
        $etude->pdf_generated_at = now();
        $etude->save();

        return response()->json([
            'success' => true,
            'data' => [
                'path' => $result['path'],
                'url' => $result['url'],
            ],
        ]);
    }

    /**
     * Download PDF
     */
    public function downloadPdf(Request $request, Listing $listing, EtudeInvestissement $etude)
    {
        $user = $request->user();

        if ($etude->listing_id !== $listing->id) {
            return response()->json([
                'success' => false,
                'message' => 'Etude not found for this listing.',
            ], 404);
        }

        if (!$etude->canBeViewedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        if (!$etude->pdf_path || !Storage::exists($etude->pdf_path)) {
            return response()->json([
                'success' => false,
                'message' => 'PDF not found. Please generate it first.',
            ], 404);
        }

        $filename = "business-plan-{$etude->titre_projet}.pdf";

        return Storage::download($etude->pdf_path, $filename);
    }

    /**
     * Delete an etude
     */
    public function destroy(Request $request, Listing $listing, EtudeInvestissement $etude)
    {
        $user = $request->user();

        if ($etude->listing_id !== $listing->id) {
            return response()->json([
                'success' => false,
                'message' => 'Etude not found for this listing.',
            ], 404);
        }

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can delete etudes.',
            ], 403);
        }

        // Delete PDF if exists
        if ($etude->pdf_path && Storage::exists($etude->pdf_path)) {
            Storage::delete($etude->pdf_path);
        }

        $etude->delete();

        return response()->json([
            'success' => true,
            'message' => 'Etude deleted.',
        ]);
    }

    private function canViewListing($user, Listing $listing): bool
    {
        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }

        return $listing->owner_id === $user->id;
    }
}
