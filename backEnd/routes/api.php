<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AgentListingController;
use App\Http\Controllers\Api\GeoLocationController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\PublicListingController;

/**
 * Routes API pour la géolocalisation
 *
 * Préfixe : /api
 */

// Route de test
Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});

// ==========================================
// AUTH (Sanctum tokens)
// ==========================================

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

// ==========================================
// LISTINGS (vendeur workflow)
// ==========================================

// Public listings (no auth)
Route::get('/public/listings', [PublicListingController::class, 'index'])->name('public.listings.index');
Route::get('/public/listings/{listing}', [PublicListingController::class, 'show'])->name('public.listings.show');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
    Route::post('/listings', [ListingController::class, 'store'])->middleware('role:vendeur')->name('listings.store');
    Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listings.show');
    Route::match(['put', 'patch'], '/listings/{listing}', [ListingController::class, 'update'])->name('listings.update');
    Route::put('/listings/{listing}/fiches/technique', [ListingController::class, 'upsertFicheTechnique'])->name('listings.fiches.technique');
    Route::put('/listings/{listing}/fiches/financiere', [ListingController::class, 'upsertFicheFinanciere'])->name('listings.fiches.financiere');
    Route::put('/listings/{listing}/fiches/juridique', [ListingController::class, 'upsertFicheJuridique'])->name('listings.fiches.juridique');
    Route::post('/listings/{listing}/submit', [ListingController::class, 'submit'])->middleware('role:vendeur')->name('listings.submit');
});

// ==========================================
// AGENT REVIEW (validation workflow)
// ==========================================

Route::prefix('agent')->middleware(['auth:sanctum', 'role:agent,admin'])->name('agent.')->group(function () {
    Route::get('/listings', [AgentListingController::class, 'index'])->name('listings.index');
    Route::post('/listings/{listing}/request-revision', [AgentListingController::class, 'requestRevision'])->name('listings.request_revision');
    Route::post('/listings/{listing}/approve', [AgentListingController::class, 'approve'])->name('listings.approve');
    Route::post('/listings/{listing}/reject', [AgentListingController::class, 'reject'])->name('listings.reject');
    Route::post('/listings/{listing}/publish', [AgentListingController::class, 'publish'])->name('listings.publish');
});

// ==========================================
// ROUTES DE GÉOLOCALISATION
// ==========================================

Route::prefix('geo')->name('geo.')->group(function () {

    // Statistiques générales
    Route::get('/stats', [GeoLocationController::class, 'stats'])->name('stats');

    // Régions
    Route::get('/regions', [GeoLocationController::class, 'regions'])->name('regions');

    // Provinces d'une région
    Route::get('/provinces/{regionCode}', [GeoLocationController::class, 'provinces'])->name('provinces');

    // Get a specific province by code with boundary data
    Route::get('/province/{provinceCode}', [GeoLocationController::class, 'getProvinceByCode'])->name('province.show');

    // Communes d'une province
    Route::get('/communes/{provinceCode}', [GeoLocationController::class, 'communes'])->name('communes');

    // Recherche de communes à proximité (nearby search)
    Route::get('/nearby', [GeoLocationController::class, 'nearby'])->name('nearby');

    // Recherche de communes par nom (autocomplete)
    Route::get('/search', [GeoLocationController::class, 'search'])->name('search');

    // Détails d'une commune spécifique
    Route::get('/commune/{id}', [GeoLocationController::class, 'show'])->name('commune.show');

    // Export complet Casablanca-Settat (pour cartes)
    Route::get('/export/casablanca-settat', [GeoLocationController::class, 'exportCasablancaSettat'])->name('export.cs');
});

// ==========================================
// EXEMPLE D'AUTRES ROUTES API
// ==========================================

// User info (example)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
