<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\FicheTechnique;
use App\Models\FicheFinanciere;
use App\Models\FicheJuridique;
use App\Models\Notification;
use Illuminate\Http\Request;

class ExpertController extends Controller
{
    /**
     * Get listings pending expertise validation
     */
    public function pendingExpertise(Request $request)
    {
        $user = $request->user();
        $expertiseType = $request->input('type', 'all');

        $query = Listing::query()
            ->whereIn('status', ['soumis', 'valide', 'publie'])
            ->with(['commune.province', 'owner:id,first_name,last_name']);

        // Filter by expertise type that needs validation
        if ($expertiseType === 'technique') {
            $query->whereDoesntHave('ficheTechnique', function ($q) {
                $q->whereNotNull('validated_at');
            });
        } elseif ($expertiseType === 'financiere') {
            $query->whereDoesntHave('ficheFinanciere', function ($q) {
                $q->whereNotNull('validated_at');
            });
        } elseif ($expertiseType === 'juridique') {
            $query->whereDoesntHave('ficheJuridique', function ($q) {
                $q->whereNotNull('validated_at');
            });
        }

        $listings = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    /**
     * Get listing with all expertise fiches for review
     */
    public function showForExpertise(Request $request, Listing $listing)
    {
        $listing->load([
            'ficheTechnique',
            'ficheFinanciere',
            'ficheJuridique',
            'commune.province.region',
            'owner:id,first_name,last_name,email,phone',
            'documents',
        ]);

        return response()->json([
            'success' => true,
            'data' => $listing,
        ]);
    }

    /**
     * Validate or update technical expertise
     */
    public function validateTechnique(Request $request, Listing $listing)
    {
        $user = $request->user();

        $validated = $request->validate([
            'accessibility' => 'nullable|string|max:1000',
            'neighborhood' => 'nullable|string|max:1000',
            'technical_constraints' => 'nullable|array',
            'opportunities' => 'nullable|string|max:2000',
            'equipment' => 'nullable|array',
            'photo_analysis' => 'nullable|string|max:2000',
            'expert_notes' => 'nullable|string|max:2000',
            'conclusion' => 'required|string|max:2000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $fiche = FicheTechnique::updateOrCreate(
            ['listing_id' => $listing->id],
            array_merge($validated, [
                'validated_by' => $user->id,
                'validated_at' => now(),
            ])
        );

        // Notify owner
        if ($listing->owner_id) {
            Notification::create([
                'user_id' => $listing->owner_id,
                'type' => 'expertise_validated',
                'title' => 'Expertise technique validée',
                'message' => "L'expertise technique de votre terrain {$listing->reference} a été validée.",
                'link' => "/sell/{$listing->id}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Expertise technique validée.',
            'data' => $fiche,
        ]);
    }

    /**
     * Validate or update financial expertise with rentability calculation
     */
    public function validateFinanciere(Request $request, Listing $listing)
    {
        $user = $request->user();

        $validated = $request->validate([
            'estimated_market_price' => 'nullable|numeric|min:0',
            'price_per_sqm' => 'nullable|numeric|min:0',
            'comparables' => 'nullable|array',
            'valuation_assumptions' => 'nullable|string|max:2000',
            'development_costs' => 'nullable|numeric|min:0',
            'projected_sale_price' => 'nullable|numeric|min:0',
            'taxes_fees' => 'nullable|numeric|min:0',
            'expert_notes' => 'nullable|string|max:2000',
            'conclusion' => 'required|string|max:2000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // Calculate rentability
        $rentabilite = null;
        if (isset($validated['projected_sale_price']) && $listing->prix_demande > 0) {
            $totalCosts = $listing->prix_demande
                + ($validated['development_costs'] ?? 0)
                + ($validated['taxes_fees'] ?? 0);

            if ($totalCosts > 0) {
                $rentabilite = (($validated['projected_sale_price'] - $totalCosts) / $totalCosts) * 100;
            }
        }

        $fiche = FicheFinanciere::updateOrCreate(
            ['listing_id' => $listing->id],
            array_merge($validated, [
                'rentabilite' => $rentabilite,
                'validated_by' => $user->id,
                'validated_at' => now(),
            ])
        );

        // Notify owner
        if ($listing->owner_id) {
            Notification::create([
                'user_id' => $listing->owner_id,
                'type' => 'expertise_validated',
                'title' => 'Expertise financière validée',
                'message' => "L'expertise financière de votre terrain {$listing->reference} a été validée.",
                'link' => "/sell/{$listing->id}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Expertise financière validée.',
            'data' => $fiche,
        ]);
    }

    /**
     * Validate or update juridical expertise
     */
    public function validateJuridique(Request $request, Listing $listing)
    {
        $user = $request->user();

        $validated = $request->validate([
            'land_status' => 'nullable|string|max:255',
            'title_number' => 'nullable|string|max:100',
            'legal_owner' => 'nullable|string|max:255',
            'servitudes' => 'nullable|array',
            'restrictions' => 'nullable|array',
            'legal_issues' => 'nullable|string|max:2000',
            'missing_documents' => 'nullable|array',
            'compliance_status' => 'nullable|string|in:conforme,non_conforme,en_cours',
            'expert_notes' => 'nullable|string|max:2000',
            'conclusion' => 'required|string|max:2000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $fiche = FicheJuridique::updateOrCreate(
            ['listing_id' => $listing->id],
            array_merge($validated, [
                'validated_by' => $user->id,
                'validated_at' => now(),
            ])
        );

        // Notify owner
        if ($listing->owner_id) {
            Notification::create([
                'user_id' => $listing->owner_id,
                'type' => 'expertise_validated',
                'title' => 'Expertise juridique validée',
                'message' => "L'expertise juridique de votre terrain {$listing->reference} a été validée.",
                'link' => "/sell/{$listing->id}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Expertise juridique validée.',
            'data' => $fiche,
        ]);
    }

    /**
     * Attach documents to expertise
     */
    public function attachDocuments(Request $request, Listing $listing, string $type)
    {
        $user = $request->user();

        if (!in_array($type, ['technique', 'financiere', 'juridique'])) {
            return response()->json(['success' => false, 'message' => 'Type invalide.'], 400);
        }

        $validated = $request->validate([
            'documents' => 'required|array|max:10',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480',
        ]);

        $uploadedDocs = [];
        foreach ($request->file('documents') as $file) {
            $path = $file->store("expertises/{$listing->id}/{$type}", 'public');
            $uploadedDocs[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'uploaded_by' => $user->id,
                'uploaded_at' => now()->toDateTimeString(),
            ];
        }

        // Get the fiche model
        $ficheModel = match($type) {
            'technique' => FicheTechnique::class,
            'financiere' => FicheFinanciere::class,
            'juridique' => FicheJuridique::class,
        };

        $fiche = $ficheModel::firstOrCreate(['listing_id' => $listing->id]);

        $existingDocs = $fiche->attached_documents ?? [];
        $fiche->update([
            'attached_documents' => array_merge($existingDocs, $uploadedDocs),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Documents attachés avec succès.',
            'data' => $uploadedDocs,
        ]);
    }
}
