<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur pour gérer les API de géolocalisation
 *
 * Endpoints disponibles :
 * - GET /api/geo/regions
 * - GET /api/geo/provinces/{regionCode}
 * - GET /api/geo/communes/{provinceCode}
 * - GET /api/geo/communes/nearby
 * - GET /api/geo/search
 */
class GeoLocationController extends Controller
{
    /**
     * Liste toutes les régions avec coordonnées GPS
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function regions()
    {
        $regions = DB::table('regions')
            ->select('id', 'name_fr', 'name_ar', 'code', 'latitude', 'longitude')
            ->orderBy('name_fr')
            ->get();

        return response()->json([
            'success' => true,
            'total' => $regions->count(),
            'data' => $regions
        ]);
    }

    /**
     * Liste les provinces d'une région
     *
     * @param string $regionCode Code de la région (ex: CS)
     * @return \Illuminate\Http\JsonResponse
     */
    public function provinces($regionCode)
    {
        $provinces = DB::table('provinces')
            ->join('regions', 'provinces.region_id', '=', 'regions.id')
            ->where('regions.code', $regionCode)
            ->select(
                'provinces.id',
                'provinces.name_fr',
                'provinces.name_ar',
                'provinces.code',
                'provinces.latitude',
                'provinces.longitude',
                'provinces.properties',
                'provinces.bbox',
                'provinces.geometry',
                'regions.name_fr as region_name'
            )
            ->orderBy('provinces.name_fr')
            ->get()
            ->map(function ($province) {
                // Decode JSON fields
                $province->properties = json_decode($province->properties);
                $province->bbox = json_decode($province->bbox);
                $province->geometry = json_decode($province->geometry);
                return $province;
            });

        if ($provinces->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Region not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'region_code' => $regionCode,
            'total' => $provinces->count(),
            'data' => $provinces
        ]);
    }

    /**
     * Get a specific province by code with its boundary data
     *
     * @param string $provinceCode Code de la province (ex: CAS)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProvinceByCode($provinceCode)
    {
        $province = DB::table('provinces')
            ->join('regions', 'provinces.region_id', '=', 'regions.id')
            ->where('provinces.code', $provinceCode)
            ->select(
                'provinces.id',
                'provinces.name_fr',
                'provinces.name_ar',
                'provinces.code',
                'provinces.latitude',
                'provinces.longitude',
                'provinces.properties',
                'provinces.bbox',
                'provinces.geometry',
                'regions.name_fr as region_name',
                'regions.code as region_code'
            )
            ->first();

        if (!$province) {
            return response()->json([
                'success' => false,
                'message' => 'Province not found'
            ], 404);
        }

        // Decode JSON fields
        $province->properties = json_decode($province->properties);
        $province->bbox = json_decode($province->bbox);
        $province->geometry = json_decode($province->geometry);

        return response()->json([
            'success' => true,
            'data' => $province
        ]);
    }

    /**
     * Liste les communes d'une province
     *
     * @param string $provinceCode Code de la province (ex: CAS)
     * @return \Illuminate\Http\JsonResponse
     */
    public function communes($provinceCode)
    {
        $communes = DB::table('communes')
            ->join('provinces', 'communes.province_id', '=', 'provinces.id')
            ->where('provinces.code', $provinceCode)
            ->select(
                'communes.id',
                'communes.name_fr',
                'communes.name_ar',
                'communes.type',
                'communes.code_postal',
                'communes.latitude',
                'communes.longitude',
                'provinces.name_fr as province_name',
                'provinces.code as province_code'
            )
            ->orderBy('communes.type')
            ->orderBy('communes.name_fr')
            ->get();

        if ($communes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Province not found'
            ], 404);
        }

        // Grouper par type
        $urbaines = $communes->where('type', 'urbaine')->values();
        $rurales = $communes->where('type', 'rurale')->values();

        return response()->json([
            'success' => true,
            'province_code' => $provinceCode,
            'total' => $communes->count(),
            'urbaines' => $urbaines->count(),
            'rurales' => $rurales->count(),
            'data' => [
                'all' => $communes,
                'by_type' => [
                    'urbaines' => $urbaines,
                    'rurales' => $rurales
                ]
            ]
        ]);
    }

    /**
     * Recherche les communes à proximité d'un point GPS
     *
     * Paramètres :
     * - latitude (required)
     * - longitude (required)
     * - radius (optional, default: 10 km)
     * - limit (optional, default: 50)
     * - type (optional: urbaine|rurale)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:200',
            'limit' => 'nullable|integer|min:1|max:500',
            'type' => 'nullable|in:urbaine,rurale'
        ]);

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $radius = $request->input('radius', 10);
        $limit = $request->input('limit', 50);
        $type = $request->input('type');

        // Requête de base avec formule de Haversine
        $query = DB::table('communes')
            ->join('provinces', 'communes.province_id', '=', 'provinces.id')
            ->select(
                'communes.id',
                'communes.name_fr',
                'communes.name_ar',
                'communes.type',
                'communes.code_postal',
                'communes.latitude',
                'communes.longitude',
                'provinces.name_fr as province_name',
                'provinces.code as province_code'
            )
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(communes.latitude)) * cos(radians(communes.longitude) - radians(?)) + sin(radians(?)) * sin(radians(communes.latitude)))) AS distance',
                [$lat, $lng, $lat]
            );

        // Filtrer par type si spécifié
        if ($type) {
            $query->where('communes.type', $type);
        }

        // Appliquer le rayon et la limite
        $communes = $query
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'search_point' => [
                'latitude' => $lat,
                'longitude' => $lng
            ],
            'radius_km' => $radius,
            'total' => $communes->count(),
            'data' => $communes
        ]);
    }

    /**
     * Recherche de communes par nom (autocomplete)
     *
     * Paramètres :
     * - q (required) : terme de recherche
     * - latitude (optional) : pour trier par proximité
     * - longitude (optional) : pour trier par proximité
     * - limit (optional, default: 10)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        $query = $request->input('q');
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $limit = $request->input('limit', 10);

        $results = DB::table('communes')
            ->join('provinces', 'communes.province_id', '=', 'provinces.id')
            ->where(function($q) use ($query) {
                $q->where('communes.name_fr', 'LIKE', "%{$query}%")
                  ->orWhere('communes.name_ar', 'LIKE', "%{$query}%");
            })
            ->select(
                'communes.id',
                'communes.name_fr',
                'communes.name_ar',
                'communes.type',
                'communes.code_postal',
                'communes.latitude',
                'communes.longitude',
                'provinces.name_fr as province_name',
                'provinces.code as province_code'
            );

        // Si coordonnées fournies, calculer la distance et trier par proximité
        if ($lat && $lng) {
            $results->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(communes.latitude)) * cos(radians(communes.longitude) - radians(?)) + sin(radians(?)) * sin(radians(communes.latitude)))) AS distance',
                [$lat, $lng, $lat]
            )->orderBy('distance');
        } else {
            // Sinon, trier alphabétiquement
            $results->orderBy('communes.name_fr');
        }

        $communes = $results->limit($limit)->get();

        return response()->json([
            'success' => true,
            'query' => $query,
            'total' => $communes->count(),
            'data' => $communes
        ]);
    }

    /**
     * Récupère une commune spécifique par ID avec ses détails complets
     *
     * @param string $id UUID de la commune
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $commune = DB::table('communes')
            ->join('provinces', 'communes.province_id', '=', 'provinces.id')
            ->join('regions', 'provinces.region_id', '=', 'regions.id')
            ->where('communes.id', $id)
            ->select(
                'communes.*',
                'provinces.name_fr as province_name',
                'provinces.name_ar as province_name_ar',
                'provinces.code as province_code',
                'provinces.latitude as province_latitude',
                'provinces.longitude as province_longitude',
                'regions.name_fr as region_name',
                'regions.name_ar as region_name_ar',
                'regions.code as region_code'
            )
            ->first();

        if (!$commune) {
            return response()->json([
                'success' => false,
                'message' => 'Commune not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $commune
        ]);
    }

    /**
     * Statistiques géographiques
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $stats = [
            'regions' => DB::table('regions')->count(),
            'provinces' => DB::table('provinces')->count(),
            'communes' => [
                'total' => DB::table('communes')->count(),
                'urbaines' => DB::table('communes')->where('type', 'urbaine')->count(),
                'rurales' => DB::table('communes')->where('type', 'rurale')->count(),
            ],
            'with_gps' => [
                'regions' => DB::table('regions')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->count(),
                'provinces' => DB::table('provinces')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->count(),
                'communes' => DB::table('communes')
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Export de toutes les communes de la région Casablanca-Settat
     * Format optimisé pour les cartes (Leaflet, Google Maps)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportCasablancaSettat()
    {
        $data = DB::table('communes')
            ->join('provinces', 'communes.province_id', '=', 'provinces.id')
            ->join('regions', 'provinces.region_id', '=', 'regions.id')
            ->where('regions.code', 'CS')
            ->select(
                'communes.id',
                'communes.name_fr',
                'communes.name_ar',
                'communes.type',
                'communes.code_postal',
                'communes.latitude as lat',
                'communes.longitude as lng',
                'provinces.name_fr as province',
                'provinces.code as province_code'
            )
            ->orderBy('provinces.name_fr')
            ->orderBy('communes.type')
            ->orderBy('communes.name_fr')
            ->get();

        // Grouper par province
        $byProvince = $data->groupBy('province')->map(function ($items) {
            return [
                'total' => $items->count(),
                'communes' => $items->values()
            ];
        });

        return response()->json([
            'success' => true,
            'region' => 'Casablanca-Settat',
            'total_communes' => $data->count(),
            'data' => [
                'all' => $data,
                'by_province' => $byProvince
            ]
        ]);
    }
}
