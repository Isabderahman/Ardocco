<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FicheFinanciere;
use App\Models\FicheJuridique;
use App\Models\FicheTechnique;
use App\Models\Listing;
use App\Models\Notification;
use Illuminate\Http\Request;

class AgentListingController extends Controller
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
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');

        $statuses = ['soumis'];
        if ($request->filled('status')) {
            $statuses = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('status')))));
        }
        $query->whereIn('status', $statuses);

        if ($request->boolean('assigned_to_me')) {
            $query->where('agent_id', $user->id);
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('agent_id');
        }

        $listings = $query->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    public function requestRevision(Request $request, Listing $listing)
    {
        $agent = $request->user();

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        if (!in_array($listing->status, ['soumis'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Listing cannot be put in revision from current status.',
            ], 422);
        }

        if ($listing->agent_id && $listing->agent_id !== $agent->id && !$agent->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Listing is already assigned to another agent.',
            ], 403);
        }

        $listing->forceFill([
            'agent_id' => $listing->agent_id ?? $agent->id,
            'status' => 'en_revision',
            'validated_at' => null,
            'published_at' => null,
        ])->save();

        Notification::create([
            'user_id' => $listing->owner_id,
            'type' => 'listing_revision_requested',
            'title' => 'Annonce: modifications demandées',
            'message' => $validated['message'],
            'link' => "/listings/{$listing->id}",
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Revision requested.',
            'data' => $listing,
        ]);
    }

    public function approve(Request $request, Listing $listing)
    {
        $agent = $request->user();

        if (!in_array($listing->status, ['soumis'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Listing cannot be approved in current status.',
            ], 422);
        }

        if ($listing->agent_id && $listing->agent_id !== $agent->id && !$agent->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Listing is already assigned to another agent.',
            ], 403);
        }

        $missing = $this->missingRequiredData($listing);
        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => 'Listing is not complete.',
                'missing' => $missing,
            ], 422);
        }

        $now = now();

        $listing->forceFill([
            'agent_id' => $listing->agent_id ?? $agent->id,
            'status' => 'valide',
            'validated_at' => $now,
            'submitted_at' => $listing->submitted_at ?? $now,
        ])->save();

        $this->markFichesValidated($listing, $agent->id, $now);

        Notification::create([
            'user_id' => $listing->owner_id,
            'type' => 'listing_approved',
            'title' => 'Annonce validée',
            'message' => "Votre annonce {$listing->reference} a été validée par un agent.",
            'link' => "/listings/{$listing->id}",
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Listing approved.',
            'data' => $listing,
        ]);
    }

    public function reject(Request $request, Listing $listing)
    {
        $agent = $request->user();

        $validated = $request->validate([
            'reason' => 'nullable|string|max:2000',
        ]);

        if (!in_array($listing->status, ['soumis', 'en_revision'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Listing cannot be rejected in current status.',
            ], 422);
        }

        if ($listing->agent_id && $listing->agent_id !== $agent->id && !$agent->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Listing is already assigned to another agent.',
            ], 403);
        }

        $listing->forceFill([
            'agent_id' => $listing->agent_id ?? $agent->id,
            'status' => 'refuse',
            'validated_at' => null,
            'published_at' => null,
        ])->save();

        $reason = $validated['reason'] ?? "Votre annonce {$listing->reference} a été refusée.";

        Notification::create([
            'user_id' => $listing->owner_id,
            'type' => 'listing_rejected',
            'title' => 'Annonce refusée',
            'message' => $reason,
            'link' => "/listings/{$listing->id}",
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Listing rejected.',
            'data' => $listing,
        ]);
    }

    public function publish(Request $request, Listing $listing)
    {
        $agent = $request->user();

        if (!in_array($listing->status, ['valide'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Listing cannot be published in current status.',
            ], 422);
        }

        if ($listing->agent_id && $listing->agent_id !== $agent->id && !$agent->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Listing is already assigned to another agent.',
            ], 403);
        }

        $now = now();

        $listing->forceFill([
            'agent_id' => $listing->agent_id ?? $agent->id,
            'status' => 'publie',
            'published_at' => $now,
            'validated_at' => $listing->validated_at ?? $now,
            'visibility' => 'public',
        ])->save();

        Notification::create([
            'user_id' => $listing->owner_id,
            'type' => 'listing_published',
            'title' => 'Annonce publiée',
            'message' => "Votre annonce {$listing->reference} est maintenant publiée.",
            'link' => "/listings/{$listing->id}",
            'is_read' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Listing published.',
            'data' => $listing,
        ]);
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

    private function markFichesValidated(Listing $listing, string $validatorId, $validatedAt): void
    {
        FicheTechnique::query()
            ->where('listing_id', $listing->id)
            ->update([
                'validated_by' => $validatorId,
                'validated_at' => $validatedAt,
            ]);

        FicheFinanciere::query()
            ->where('listing_id', $listing->id)
            ->update([
                'validated_by' => $validatorId,
                'validated_at' => $validatedAt,
            ]);

        FicheJuridique::query()
            ->where('listing_id', $listing->id)
            ->update([
                'validated_by' => $validatorId,
                'validated_at' => $validatedAt,
            ]);
    }
}
