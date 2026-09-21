<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TouristSpotApiController;
use App\Http\Controllers\Api\SocialAuthController;

/*
|--------------------------------------------------------------------------
| API Routes for Flutter App
|--------------------------------------------------------------------------
|
| These routes are used by the Flutter app to fetch and manage data
|
*/

// Public API Routes - No authentication required
Route::prefix('v1')->group(function () {
    // API welcome/health for base /api/v1
    Route::get('/', function () {
        return response()->json([
            'success' => true,
            'message' => 'Tourist Spot API v1',
            'endpoints' => [
                'GET /api/v1/municipalities',
                'GET /api/v1/municipalities/{municipalityId}',
                'GET /api/v1/tourist-spots',
                'GET /api/v1/tourist-spots/search?q=',
                'GET /api/v1/municipalities/{municipalityId}/spots',
                'GET /api/v1/spots/{spotId}',
                'POST /api/v1/spots/{spotId}/favorite',
                'POST /api/v1/auth/google',
                'POST /api/v1/auth/facebook',
            ],
        ]);
    });

    // Social Authentication
    Route::post('/auth/google', [SocialAuthController::class, 'googleLogin']);
    Route::post('/auth/facebook', [SocialAuthController::class, 'facebookLogin']);

    // Get all tourist spots (NEW for Flutter general listing)
    Route::get('/tourist-spots', [TouristSpotApiController::class, 'getTouristSpots']);
    Route::get('/tourist-spots/search', [TouristSpotApiController::class, 'searchTouristSpots']);
    
    // Get all municipalities
    Route::get('/municipalities', [TouristSpotApiController::class, 'getAllMunicipalities']);
    Route::get('/municipalities/{municipalityId}', [TouristSpotApiController::class, 'getMunicipality']);

    // Get tourist spots by municipality
    Route::get('/municipalities/{municipalityId}/spots', [TouristSpotApiController::class, 'getSpotsByMunicipality']);

    // Get specific tourist spot details
    Route::get('/spots/{spotId}', [TouristSpotApiController::class, 'getSpotDetail']);

    // Get nearby tourist spots based on coordinates
    Route::get('/tourist-spots/nearby', [TouristSpotApiController::class, 'getNearbySpots']);

    // Get directions between two points
    Route::get('/directions', [TouristSpotApiController::class, 'getDirections']);
});

// Authenticated API Routes - Bearer token required
Route::middleware(\App\Http\Middleware\ApiTokenAuth::class)->prefix('v1')->group(function () {
    // Get current authenticated user
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'profile_image_url' => $request->user()->profile_image_url,
            ],
        ]);
    });

    // Add review to a spot (requires login)
    Route::post('/spots/{spotId}/reviews', [TouristSpotApiController::class, 'addReview']);

    // Toggle a tourist spot favorite for the signed-in user
    Route::post('/spots/{spotId}/favorite', [TouristSpotApiController::class, 'toggleFavorite']);
});
