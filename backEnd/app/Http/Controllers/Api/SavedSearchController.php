<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use Illuminate\Http\Request;

class SavedSearchController extends Controller
{
    /**
     * List saved searches for authenticated user
     */
    public function index(Request $request)
    {
        $searches = SavedSearch::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $searches,
        ]);
    }

    /**
     * Save a new search
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'filters' => 'required|array',
            'filters.type_terrain' => 'nullable|string',
            'filters.prix_min' => 'nullable|numeric',
            'filters.prix_max' => 'nullable|numeric',
            'filters.superficie_min' => 'nullable|numeric',
            'filters.superficie_max' => 'nullable|numeric',
            'filters.commune_id' => 'nullable|integer',
            'filters.rentabilite_min' => 'nullable|numeric',
            'filters.q' => 'nullable|string',
            'notify_new_listings' => 'boolean',
        ]);

        $search = SavedSearch::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'filters' => $validated['filters'],
            'notify_new_listings' => $validated['notify_new_listings'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recherche sauvegardée.',
            'data' => $search,
        ], 201);
    }

    /**
     * Delete a saved search
     */
    public function destroy(Request $request, SavedSearch $savedSearch)
    {
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $savedSearch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recherche supprimée.',
        ]);
    }

    /**
     * Toggle notification for a saved search
     */
    public function toggleNotification(Request $request, SavedSearch $savedSearch)
    {
        if ($savedSearch->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Non autorisé.'], 403);
        }

        $savedSearch->update([
            'notify_new_listings' => !$savedSearch->notify_new_listings,
        ]);

        return response()->json([
            'success' => true,
            'data' => $savedSearch,
        ]);
    }
}
