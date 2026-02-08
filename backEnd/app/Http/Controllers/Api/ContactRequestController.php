<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\Listing;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContactRequestController extends Controller
{
    /**
     * List contact requests for the authenticated user
     * - Acheteur: see their own requests
     * - Agent/Admin: see requests for their managed listings
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = ContactRequest::query()->with(['listing:id,title,reference', 'user:id,first_name,last_name,email']);

        if ($user->isAcheteur() || $user->isVendeur()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isAgent()) {
            $query->whereHas('listing', function ($q) use ($user) {
                $q->where('agent_id', $user->id);
            });
        }

        $requests = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Create a contact request for a listing
     * Available to: acheteur, vendeur (when interested in another listing)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'listing_id' => 'required|uuid|exists:listings,id',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|max:2000',
            'documents' => 'nullable|array|max:5',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $user = $request->user();
        $listing = Listing::findOrFail($validated['listing_id']);

        // Check if listing is public
        if ($listing->visibility !== 'public' || !in_array($listing->status, ['publie', 'valide'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette annonce n\'est pas disponible.',
            ], 404);
        }

        // Handle document uploads
        $uploadedDocs = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('contact-requests/' . $listing->id, 'public');
                $uploadedDocs[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        $contactRequest = ContactRequest::create([
            'listing_id' => $listing->id,
            'user_id' => $user->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'],
            'documents' => $uploadedDocs,
            'status' => 'pending',
        ]);

        // Notify the agent or owner
        $notifyUserId = $listing->agent_id ?? $listing->owner_id;
        if ($notifyUserId) {
            Notification::create([
                'user_id' => $notifyUserId,
                'type' => 'contact_request',
                'title' => 'Nouvelle demande de contact',
                'message' => "Vous avez reçu une demande de contact pour l'annonce {$listing->reference}",
                'link' => "/dashboard/contact-requests/{$contactRequest->id}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Votre demande a été envoyée avec succès.',
            'data' => $contactRequest,
        ], 201);
    }

    /**
     * Get details of a contact request
     */
    public function show(Request $request, ContactRequest $contactRequest)
    {
        $user = $request->user();

        // Check access
        if (!$this->canAccess($user, $contactRequest)) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        $contactRequest->load(['listing', 'user']);

        return response()->json([
            'success' => true,
            'data' => $contactRequest,
        ]);
    }

    /**
     * Agent responds to a contact request
     */
    public function respond(Request $request, ContactRequest $contactRequest)
    {
        $user = $request->user();

        if (!$user->isAgent() && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        $validated = $request->validate([
            'response' => 'required|string|max:2000',
            'status' => 'required|in:responded,closed',
        ]);

        $contactRequest->update([
            'response' => $validated['response'],
            'status' => $validated['status'],
            'responded_at' => now(),
            'responded_by' => $user->id,
        ]);

        // Notify the requester
        if ($contactRequest->user_id) {
            Notification::create([
                'user_id' => $contactRequest->user_id,
                'type' => 'contact_response',
                'title' => 'Réponse à votre demande',
                'message' => "Vous avez reçu une réponse concernant l'annonce {$contactRequest->listing->reference}",
                'link' => "/dashboard/contact-requests/{$contactRequest->id}",
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Réponse envoyée.',
            'data' => $contactRequest,
        ]);
    }

    private function canAccess($user, $contactRequest): bool
    {
        if ($user->isAdmin()) return true;
        if ($contactRequest->user_id === $user->id) return true;
        if ($user->isAgent() && $contactRequest->listing->agent_id === $user->id) return true;
        return false;
    }
}
