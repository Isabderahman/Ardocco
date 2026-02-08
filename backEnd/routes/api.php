<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AgentListingController;
use App\Http\Controllers\Api\GeoLocationController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\PublicListingController;
use App\Http\Controllers\Api\ContactRequestController;
use App\Http\Controllers\Api\SavedSearchController;
use App\Http\Controllers\Api\ExpertController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;

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

    // Contract signing (no auth required - uses token from email)
    Route::post('/sign-contract', [AuthController::class, 'signContract'])->name('sign-contract');
    Route::post('/resend-contract', [AuthController::class, 'resendContract'])->name('resend-contract');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Admin: Account approval
        Route::post('/users/{user}/approve', [AuthController::class, 'approveAccount'])
            ->middleware('role:admin')
            ->name('users.approve');
        Route::post('/users/{user}/reject', [AuthController::class, 'rejectAccount'])
            ->middleware('role:admin')
            ->name('users.reject');
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
    Route::post('/listings', [ListingController::class, 'store'])->middleware('role:vendeur,agent,admin')->name('listings.store');
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
Route::middleware('auth:sanctum')->get('/user', fn(Request $request) => $request->user());

// ==========================================
// ACHETEUR FEATURES (Buyer)
// ==========================================

Route::middleware('auth:sanctum')->group(function () {
    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{favorite}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

    // Saved Searches
    Route::get('/saved-searches', [SavedSearchController::class, 'index'])->name('saved-searches.index');
    Route::post('/saved-searches', [SavedSearchController::class, 'store'])->name('saved-searches.store');
    Route::delete('/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');
    Route::post('/saved-searches/{savedSearch}/toggle-notification', [SavedSearchController::class, 'toggleNotification'])->name('saved-searches.toggle-notification');

    // Contact Requests
    Route::get('/contact-requests', [ContactRequestController::class, 'index'])->name('contact-requests.index');
    Route::post('/contact-requests', [ContactRequestController::class, 'store'])->name('contact-requests.store');
    Route::get('/contact-requests/{contactRequest}', [ContactRequestController::class, 'show'])->name('contact-requests.show');
    Route::post('/contact-requests/{contactRequest}/respond', [ContactRequestController::class, 'respond'])
        ->middleware('role:agent,admin')
        ->name('contact-requests.respond');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// ==========================================
// EXPERT MODULE
// ==========================================

Route::prefix('expert')->middleware(['auth:sanctum', 'role:expert,admin'])->name('expert.')->group(function () {
    Route::get('/listings', [ExpertController::class, 'pendingExpertise'])->name('listings.index');
    Route::get('/listings/{listing}', [ExpertController::class, 'showForExpertise'])->name('listings.show');
    Route::post('/listings/{listing}/technique', [ExpertController::class, 'validateTechnique'])->name('validate.technique');
    Route::post('/listings/{listing}/financiere', [ExpertController::class, 'validateFinanciere'])->name('validate.financiere');
    Route::post('/listings/{listing}/juridique', [ExpertController::class, 'validateJuridique'])->name('validate.juridique');
    Route::post('/listings/{listing}/documents/{type}', [ExpertController::class, 'attachDocuments'])->name('attach.documents');
});

// ==========================================
// ADMIN DASHBOARD
// ==========================================

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->name('admin.')->group(function () {
    // User Management
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/pending-approvals', [AdminController::class, 'pendingApprovals'])->name('users.pending-approvals');
    Route::get('/users/{user}', [AdminController::class, 'showUser'])->name('users.show');
    Route::put('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
    Route::post('/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('users.toggle-status');

    // Listing Management
    Route::get('/listings/pending', [AdminController::class, 'pendingListings'])->name('listings.pending');
    Route::post('/listings/{listing}/toggle-featured', [AdminController::class, 'toggleFeatured'])->name('listings.toggle-featured');
    Route::delete('/listings/{listing}', [AdminController::class, 'deleteListing'])->name('listings.delete');

    // Analytics
    Route::get('/stats', [AdminController::class, 'stats'])->name('stats');
    Route::get('/analytics/listings', [AdminController::class, 'listingAnalytics'])->name('analytics.listings');
    Route::get('/analytics/users', [AdminController::class, 'userActivity'])->name('analytics.users');
});
