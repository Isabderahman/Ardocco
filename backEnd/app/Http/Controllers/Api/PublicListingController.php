<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class PublicListingController extends Controller
{
    public function index(Request $request)
    {
        $query = Listing::query()
            ->available()
            ->where('visibility', 'public')
            ->with([
                'commune.province.region',
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        if ($request->filled('type_terrain')) {
            $query->where('type_terrain', $request->input('type_terrain'));
        }

        if ($request->filled('commune_id')) {
            $query->where('commune_id', $request->input('commune_id'));
        }

        $prixMin = $request->filled('prix_min') ? (float) $request->input('prix_min') : null;
        $prixMax = $request->filled('prix_max') ? (float) $request->input('prix_max') : null;
        if ($prixMin !== null || $prixMax !== null) {
            if ($prixMin !== null && $prixMax !== null) {
                [$min, $max] = $prixMin <= $prixMax ? [$prixMin, $prixMax] : [$prixMax, $prixMin];
                $query->whereBetween('prix_demande', [$min, $max]);
            } elseif ($prixMin !== null) {
                $query->where('prix_demande', '>=', $prixMin);
            } elseif ($prixMax !== null) {
                $query->where('prix_demande', '<=', $prixMax);
            }
        }

        $superficieMin = $request->filled('superficie_min') ? (float) $request->input('superficie_min') : null;
        $superficieMax = $request->filled('superficie_max') ? (float) $request->input('superficie_max') : null;
        if ($superficieMin !== null || $superficieMax !== null) {
            if ($superficieMin !== null && $superficieMax !== null) {
                [$min, $max] = $superficieMin <= $superficieMax ? [$superficieMin, $superficieMax] : [$superficieMax, $superficieMin];
                $query->whereBetween('superficie', [$min, $max]);
            } elseif ($superficieMin !== null) {
                $query->where('superficie', '>=', $superficieMin);
            } elseif ($superficieMax !== null) {
                $query->where('superficie', '<=', $superficieMax);
            }
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

    public function show(Listing $listing)
    {
        if ($listing->visibility !== 'public' || !in_array($listing->status, ['publie', 'valide'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Not found.',
            ], 404);
        }

        $listing->load([
            'commune.province.region',
        ]);

        return response()->json([
            'success' => true,
            'data' => $listing,
        ]);
    }
}
