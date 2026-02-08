<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Listing;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * List user's favorites
     */
    public function index(Request $request)
    {
        $favorites = Favorite::where('user_id', $request->user()->id)
            ->with(['listing' => function ($query) {
                $query->select([
                    'id', 'reference', 'title', 'type_terrain',
                    'superficie', 'prix_demande', 'latitude', 'longitude',
                    'quartier', 'commune_id', 'status', 'published_at'
                ])->with('commune.province');
            }])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $favorites,
        ]);
    }

    /**
     * Add a listing to favorites
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'listing_id' => 'required|uuid|exists:listings,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $listing = Listing::findOrFail($validated['listing_id']);

        // Check if already favorited
        $existing = Favorite::where('user_id', $request->user()->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Ce terrain est déjà dans vos favoris.',
            ], 409);
        }

        $favorite = Favorite::create([
            'user_id' => $request->user()->id,
            'listing_id' => $listing->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ajouté aux favoris.',
            'data' => $favorite,
        ], 201);
    }

    /**
     * Remove from favorites
     */
    public function destroy(Request $request, Favorite $favorite)
    {
        if ($favorite->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Retiré des favoris.',
        ]);
    }
}
