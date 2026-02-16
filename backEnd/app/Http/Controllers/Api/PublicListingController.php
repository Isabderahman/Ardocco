<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;

class PublicListingController extends Controller
{
    /**
     * Fields visible to visitors (non-authenticated users)
     * According to the mind map: limited preview for visitors
     */
    private array $visitorFields = [
        'id',
        'reference',
        'title',
        'type_terrain',
        'superficie',
        'prix_demande',
        'latitude',
        'longitude',
        'geojson_polygon',
        'quartier',
        'commune_id',
        'views_count',
        'published_at',
        'created_at',
    ];

    /**
     * Additional fields visible to authenticated users (acheteur, vendeur, agent, expert, admin)
     */
    private array $authenticatedFields = [
        'description',
        'address',
        'prix_estime',
        'prix_par_m2',
        'titre_foncier',
        'forme_terrain',
        'topographie',
        'viabilisation',
        'zonage',
        'coefficient_occupation',
        'hauteur_max',
        'owner_id',
        'agent_id',
    ];

    public function index(Request $request)
    {
        $isAuthenticated = $request->user() !== null;

        $query = Listing::query()
            ->published()
            ->where('visibility', 'public')
            ->with([
                'commune.province.region',
                'documents' => function ($q) {
                    $q->public()
                        ->byType('photos')
                        ->select(['id', 'listing_id', 'document_type', 'file_path', 'is_public'])
                        ->orderByDesc('created_at')
                        ->limit(1);
                },
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        // Apply filters
        if ($request->filled('type_terrain')) {
            $query->where('type_terrain', $request->input('type_terrain'));
        }

        if ($request->filled('commune_id')) {
            $query->where('commune_id', $request->input('commune_id'));
        }

        // Rentability filter (new feature from mind map)
        if ($request->filled('rentabilite_min')) {
            $query->whereHas('ficheFinanciere', function ($q) use ($request) {
                $q->where('rentabilite', '>=', (float) $request->input('rentabilite_min'));
            });
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

        // Select appropriate fields based on authentication
        if (!$isAuthenticated) {
            $query->select($this->visitorFields);
        }

        $listings = $query->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $listings,
            'is_authenticated' => $isAuthenticated,
        ]);
    }

    public function show(Request $request, Listing $listing)
    {
        if ($listing->visibility !== 'public' || $listing->status !== 'publie') {
            return response()->json([
                'success' => false,
                'message' => 'Not found.',
            ], 404);
        }

        $isAuthenticated = $request->user() !== null;
        $user = $request->user();

        // Increment view count
        $listing->increment('views_count');

        // Load relations based on authentication
        $relations = [
            'commune.province.region',
            'documents' => function ($query) {
                $query->where('is_public', true)
                    ->where('document_type', 'photos')
                    ->select(['id', 'listing_id', 'document_type', 'file_path', 'is_public'])
                    ->orderByDesc('created_at')
                    ->limit(3);
            },
        ];

        if ($isAuthenticated && $user->canAccessFullListingDetails()) {
            // Full access for authenticated users
            $relations = array_merge($relations, [
                'ficheTechnique',
                'ficheFinanciere',
                'ficheJuridique',
                'owner:id,first_name,last_name,phone,email,company_name',
                'agent:id,first_name,last_name,phone,email,company_name',
                'documents' => function ($query) {
                    $query->where('is_public', true);
                },
                'etudesInvestissement' => function ($query) {
                    $query->where('status', 'approved')
                        ->orderByDesc('created_at');
                },
            ]);

            $listing->load($relations);

            return response()->json([
                'success' => true,
                'data' => $listing,
                'access_level' => 'full',
            ]);
        }

        // Limited access for visitors (non-authenticated)
        $listing->load($relations);

        // Return only visitor fields
        $limitedData = $listing->only($this->visitorFields);
        $limitedData['commune'] = $listing->commune;
        $limitedData['documents'] = $listing->documents;

        return response()->json([
            'success' => true,
            'data' => $limitedData,
            'access_level' => 'limited',
            'message' => 'Connectez-vous pour accéder aux détails complets',
        ]);
    }
}
