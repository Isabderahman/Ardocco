<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Listing;
use App\Models\ContactRequest;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // ==========================================
    // USER MANAGEMENT
    // ==========================================

    /**
     * List all users with filters
     */
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('account_status')) {
            $query->where('account_status', $request->input('account_status'));
        }

        if ($request->filled('q')) {
            $term = '%' . $request->input('q') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('email', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('company_name', 'like', $term);
            });
        }

        $users = $query->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Get users pending approval (contract signed, waiting for admin)
     */
    public function pendingApprovals(Request $request)
    {
        $users = User::query()
            ->where('account_status', 'pending_approval')
            ->with('contracts')
            ->orderByDesc('contract_signed_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Get single user details
     */
    public function showUser(User $user)
    {
        $user->load(['ownedListings', 'favorites', 'contracts']);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update user role
     */
    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:acheteur,vendeur,agent,expert,admin',
        ]);

        $user->update(['role' => $validated['role']]);

        // Notify user of role change
        Notification::create([
            'user_id' => $user->id,
            'type' => 'role_changed',
            'title' => 'Rôle modifié',
            'message' => "Votre rôle a été mis à jour: {$validated['role']}",
            'link' => '/dashboard',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rôle mis à jour.',
            'data' => $user,
        ]);
    }

    /**
     * Activate/Deactivate user account
     */
    public function toggleUserStatus(Request $request, User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'Compte activé.' : 'Compte désactivé.',
            'data' => $user,
        ]);
    }

    // ==========================================
    // LISTING MANAGEMENT
    // ==========================================

    /**
     * Get listings pending approval
     */
    public function pendingListings(Request $request)
    {
        $query = Listing::query()
            ->where('status', 'soumis')
            ->with(['owner:id,first_name,last_name,email', 'commune.province'])
            ->orderByDesc('submitted_at');

        $listings = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    /**
     * Feature/unfeature a listing
     */
    public function toggleFeatured(Listing $listing)
    {
        $listing->update(['is_featured' => !$listing->is_featured]);

        return response()->json([
            'success' => true,
            'message' => $listing->is_featured ? 'Annonce mise en avant.' : 'Mise en avant retirée.',
            'data' => $listing,
        ]);
    }

    /**
     * Approve & publish a listing (make it public)
     */
    public function approveListing(Request $request, Listing $listing)
    {
        if (!in_array($listing->status, ['soumis', 'valide'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Listing cannot be approved in current status.',
            ], 422);
        }

        $now = now();

        $listing->forceFill([
            'status' => 'publie',
            'visibility' => 'public',
            'published_at' => $now,
            'validated_at' => $listing->validated_at ?? $now,
            'submitted_at' => $listing->submitted_at ?? $now,
        ])->save();

        if ($listing->owner_id) {
            Notification::create([
                'user_id' => $listing->owner_id,
                'type' => 'listing_published',
                'title' => 'Annonce publiée',
                'message' => "Votre annonce {$listing->reference} est maintenant publiée.",
                'link' => "/terrains/{$listing->id}",
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Listing approved.',
            'data' => $listing,
        ]);
    }

    /**
     * Reject a listing (send back to draft with reason)
     */
    public function rejectListing(Request $request, Listing $listing)
    {
        if (!in_array($listing->status, ['soumis', 'valide'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Listing cannot be rejected in current status.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $listing->forceFill([
            'status' => 'refuse',
            'validated_at' => null,
            'published_at' => null,
        ])->save();

        if ($listing->owner_id) {
            Notification::create([
                'user_id' => $listing->owner_id,
                'type' => 'listing_rejected',
                'title' => 'Annonce refusée',
                'message' => "Votre annonce {$listing->reference} a été refusée. Raison: {$validated['reason']}",
                'link' => "/terrains/{$listing->id}",
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Listing rejected.',
            'data' => $listing,
        ]);
    }

    /**
     * Force delete a listing
     */
    public function deleteListing(Listing $listing)
    {
        // Notify owner
        if ($listing->owner_id) {
            Notification::create([
                'user_id' => $listing->owner_id,
                'type' => 'listing_deleted',
                'title' => 'Annonce supprimée',
                'message' => "Votre annonce {$listing->reference} a été supprimée par un administrateur.",
                'link' => '/dashboard',
            ]);
        }

        $listing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Annonce supprimée.',
        ]);
    }

    // ==========================================
    // ANALYTICS & STATISTICS
    // ==========================================

    /**
     * Get dashboard statistics
     */
    public function stats()
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'by_role' => User::query()
                    ->select('role', DB::raw('count(*) as count'))
                    ->groupBy('role')
                    ->pluck('count', 'role'),
                'by_status' => User::query()
                    ->select('account_status', DB::raw('count(*) as count'))
                    ->groupBy('account_status')
                    ->pluck('count', 'account_status'),
                'active' => User::where('is_active', true)->count(),
                'pending_approval' => User::where('account_status', 'pending_approval')->count(),
                'pending_contract' => User::where('account_status', 'pending_contract')->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
            ],
            'listings' => [
                'total' => Listing::count(),
                'by_status' => Listing::query()
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'pending_approval' => Listing::where('status', 'soumis')->count(),
                'published' => Listing::where('status', 'publie')->count(),
                'new_this_month' => Listing::whereMonth('created_at', now()->month)->count(),
                'total_views' => Listing::sum('views_count'),
            ],
            'contact_requests' => [
                'total' => ContactRequest::count(),
                'pending' => ContactRequest::where('status', 'pending')->count(),
                'this_month' => ContactRequest::whereMonth('created_at', now()->month)->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get listing performance analytics
     */
    public function listingAnalytics(Request $request)
    {
        $period = $request->input('period', 30); // days

        $listings = Listing::query()
            ->where('status', 'publie')
            ->where('published_at', '>=', now()->subDays($period))
            ->select([
                'id', 'reference', 'title', 'views_count',
                'prix_demande', 'published_at'
            ])
            ->withCount('favorites')
            ->withCount('accessRequests as contact_requests_count')
            ->orderByDesc('views_count')
            ->limit(20)
            ->get();

        $dailyViews = Listing::query()
            ->where('published_at', '>=', now()->subDays($period))
            ->select([
                DB::raw('DATE(published_at) as date'),
                DB::raw('SUM(views_count) as views'),
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'top_listings' => $listings,
                'daily_views' => $dailyViews,
            ],
        ]);
    }

    /**
     * Get user activity report
     */
    public function userActivity(Request $request)
    {
        $period = $request->input('period', 30);

        $registrations = User::query()
            ->where('created_at', '>=', now()->subDays($period))
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $logins = User::query()
            ->whereNotNull('last_login')
            ->where('last_login', '>=', now()->subDays($period))
            ->select([
                DB::raw('DATE(last_login) as date'),
                DB::raw('COUNT(*) as count'),
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'registrations' => $registrations,
                'logins' => $logins,
            ],
        ]);
    }
}
