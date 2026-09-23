<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\TouristSpot;
use App\Models\TouristSpotFavorite;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class TouristSpotApiController extends Controller
{
    public function getAllMunicipalities()
    {
        $municipalities = Municipality::withCount('touristSpots')
            ->orderBy('name')
            ->get()
            ->map(fn (Municipality $municipality) => $this->transformMunicipality($municipality));

        return response()->json([
            'success' => true,
            'data' => $municipalities,
        ]);
    }

    public function getMunicipality($municipalityId)
    {
        $municipality = Municipality::withCount('touristSpots')->find($municipalityId);

        if (!$municipality) {
            return response()->json([
                'success' => false,
                'message' => 'Municipality not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformMunicipality($municipality),
        ]);
    }

    public function getSpotsByMunicipality(Request $request, $municipalityId)
    {
        $favoriteSpotIds = $this->favoriteSpotIdsForRequest($request);

        $spots = $this->approvedSpotsQuery()
            ->where('municipality_id', $municipalityId)
            ->with(['municipality'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('name')
            ->get()
            ->map(fn (TouristSpot $spot) => $this->transformSpot($spot, false, $favoriteSpotIds));

        return response()->json([
            'success' => true,
            'data' => $spots,
        ]);
    }

    public function getSpotDetail(Request $request, $spotId)
    {
        $favoriteSpotIds = $this->favoriteSpotIdsForRequest($request);

        $spot = $this->approvedSpotsQuery()
            ->with(['municipality', 'reviews' => function ($query) {
                $query->where('status', 'approved')
                    ->with('user')
                    ->latest();
            }])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->find($spotId);

        if (!$spot) {
            return response()->json([
                'success' => false,
                'message' => 'Tourist spot not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformSpot(
                $spot,
                true,
                $favoriteSpotIds
            ),
        ]);
    }

    public function searchTouristSpots(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $favoriteSpotIds = $this->favoriteSpotIdsForRequest($request);

        if ($query === '') {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $spots = $this->approvedSpotsQuery()
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', '%' . $query . '%')
                    ->orWhere('address', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%')
                    ->orWhereHas('municipality', function ($municipalityQuery) use ($query) {
                        $municipalityQuery->where('name', 'like', '%' . $query . '%');
                    });
            })
            ->with(['municipality'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('name')
            ->get()
            ->map(fn (TouristSpot $spot) => $this->transformSpot($spot, false, $favoriteSpotIds));

        return response()->json([
            'success' => true,
            'data' => $spots,
        ]);
    }

    public function addReview(Request $request, $spotId)
    {
        try {
            // Authentication is handled by ApiTokenAuth middleware
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please log in to submit a review.',
                ], 401);
            }

            $validated = $request->validate([
                'rating' => 'required|integer|between:1,5',
                'comment' => 'required|string|min:10',
            ]);

            $spot = TouristSpot::find($spotId);

            if (!$spot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tourist spot not found',
                ], 404);
            }

            $mediaInputs = $request->file('media');
            if ($mediaInputs === null) {
                $mediaInputs = $request->file('images') ?? $request->file('image');
            }

            if ($mediaInputs !== null && !is_array($mediaInputs)) {
                $mediaInputs = [$mediaInputs];
            }

            $imagePaths = [];
            $media = [];
            foreach (($mediaInputs ?? []) as $uploadedMedia) {
                if (!$uploadedMedia || !$uploadedMedia->isValid()) {
                    continue;
                }

                Validator::make(['media' => $uploadedMedia], [
                    'media' => 'file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,webm|max:51200',
                ])->validate();

                $path = $uploadedMedia->store('reviews', 'public');
                $type = str_starts_with((string) $uploadedMedia->getMimeType(), 'video/') ? 'video' : 'image';
                $media[] = ['path' => $path, 'type' => $type];
                if ($type === 'image') {
                    $imagePaths[] = $path;
                }
            }

            $reviewData = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
                'status' => 'approved',
            ];

            if (!empty($imagePaths)) {
                if (Schema::hasColumn('reviews', 'images')) {
                    $reviewData['images'] = $imagePaths;
                } elseif (Schema::hasColumn('reviews', 'image_path')) {
                    $reviewData['image_path'] = $imagePaths[0];
                }
            }
            if (!empty($media) && Schema::hasColumn('reviews', 'media')) {
                $reviewData['media'] = $media;
            }

            $review = $spot->reviews()->create($reviewData);

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully and automatically approved',
                'data' => $this->transformReview($review->load('user')),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error submitting review: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getTouristSpots(Request $request)
    {
        $favoriteSpotIds = $this->favoriteSpotIdsForRequest($request);

        $spots = $this->approvedSpotsQuery()
            ->with(['municipality'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('name')
            ->get()
            ->map(fn (TouristSpot $spot) => $this->transformSpot($spot, false, $favoriteSpotIds));

        return response()->json([
            'success' => true,
            'data' => $spots,
        ]);
    }

    /**
     * Get nearby tourist spots based on user's current coordinates
     * Uses Haversine formula to calculate distance
     */
    public function getNearbySpots(Request $request)
    {
        $favoriteSpotIds = $this->favoriteSpotIdsForRequest($request);

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100', // in kilometers
        ]);

        $userLat = $validated['latitude'];
        $userLng = $validated['longitude'];
        $radius = $validated['radius'] ?? 10; // default 10 km

        // Get all active spots
        $allSpots = $this->approvedSpotsQuery()
            ->with(['municipality'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get();

        // Calculate distance using Haversine formula
        $nearbySpots = $allSpots
            ->filter(function ($spot) use ($userLat, $userLng, $radius) {
                $distance = $this->haversineDistance($userLat, $userLng, $spot->latitude, $spot->longitude);
                return $distance <= $radius;
            })
            ->sortBy(function ($spot) use ($userLat, $userLng) {
                return $this->haversineDistance($userLat, $userLng, $spot->latitude, $spot->longitude);
            })
            ->map(fn (TouristSpot $spot) => $this->transformSpot($spot, false, $favoriteSpotIds));

        return response()->json([
            'success' => true,
            'data' => array_values($nearbySpots->toArray()),
            'user_location' => [
                'latitude' => $userLat,
                'longitude' => $userLng,
            ],
            'radius_km' => $radius,
        ]);
    }

    /**
     * Get directions (turn-by-turn steps) between two points
     * This is a simplified version that returns basic directions
     * In production, you might integrate with Google Directions API or similar
     */
    public function getDirections(Request $request)
    {
        $validated = $request->validate([
            'origin' => 'required|regex:/^-?\d+\.?\d*,-?\d+\.?\d*$/', // latitude,longitude format
            'destination' => 'required|regex:/^-?\d+\.?\d*,-?\d+\.?\d*$/', // latitude,longitude format
        ]);

        try {
            [$originLat, $originLng] = array_map('floatval', explode(',', $validated['origin']));
            [$destLat, $destLng] = array_map('floatval', explode(',', $validated['destination']));

            // Validate coordinates
            if (!$this->isValidCoordinate($originLat, $originLng) || !$this->isValidCoordinate($destLat, $destLng)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid coordinates',
                ], 400);
            }

            // Calculate distance
            $distance = $this->haversineDistance($originLat, $originLng, $destLat, $destLng);
            
            // Generate basic directions
            // In production, integrate with Google Directions API or OpenRouteService
            $directions = $this->generateBasicDirections($originLat, $originLng, $destLat, $destLng, $distance);

            return response()->json([
                'success' => true,
                'steps' => $directions,
                'distance_meters' => round($distance * 1000),
                'distance_km' => round($distance, 2),
                'origin' => [
                    'latitude' => $originLat,
                    'longitude' => $originLng,
                ],
                'destination' => [
                    'latitude' => $destLat,
                    'longitude' => $destLng,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate directions: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function toggleFavorite(Request $request, $spotId)
    {
        $user = $request->user() ?? $this->currentApiUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please log in to save favorites.',
            ], 401);
        }

        $spot = TouristSpot::find($spotId);
        if (!$spot) {
            return response()->json([
                'success' => false,
                'message' => 'Tourist spot not found',
            ], 404);
        }

        $favorite = TouristSpotFavorite::where('user_id', $user->id)
            ->where('tourist_spot_id', $spot->id)
            ->first();

        $isFavorited = false;
        if ($favorite) {
            $favorite->delete();
        } else {
            TouristSpotFavorite::create([
                'user_id' => $user->id,
                'tourist_spot_id' => $spot->id,
            ]);
            $isFavorited = true;
        }

        return response()->json([
            'success' => true,
            'message' => $isFavorited
                ? 'Spot added to favorites.'
                : 'Spot removed from favorites.',
            'data' => [
                'spot_id' => $spot->id,
                'is_favorited' => $isFavorited,
            ],
        ]);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula (in kilometers)
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadiusKm * $c;

        return $distance;
    }

    /**
     * Validate coordinate ranges
     */
    private function isValidCoordinate($lat, $lng)
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * Generate basic turn-by-turn directions
     * This is a simplified implementation.
     * In production, integrate with Google Directions API or OpenRouteService
     */
    private function generateBasicDirections($originLat, $originLng, $destLat, $destLng, $distance)
    {
        $bearing = $this->calculateBearing($originLat, $originLng, $destLat, $destLng);
        $direction = $this->getBearingDirection($bearing);

        // Generate 4 basic steps
        $steps = [];

        // Step 1: Start
        $steps[] = [
            'instruction' => "Start heading {$direction}",
            'distance' => round($distance * 1000 / 4) . ' m',
            'duration' => '1 min',
        ];

        // Step 2: Continue straight (60% of distance)
        $steps[] = [
            'instruction' => 'Continue straight',
            'distance' => round($distance * 1000 * 0.4) . ' m',
            'duration' => round($distance * 2.5) . ' mins',
        ];

        // Step 3: Prepare to arrive
        $steps[] = [
            'instruction' => "Turn slightly towards the destination",
            'distance' => round($distance * 1000 * 0.3) . ' m',
            'duration' => '2 mins',
        ];

        // Step 4: Arrive
        $steps[] = [
            'instruction' => 'Arrive at destination on the right',
            'distance' => round($distance * 1000 * 0.1) . ' m',
            'duration' => '1 min',
        ];

        return $steps;
    }

    /**
     * Calculate bearing between two points
     */
    private function calculateBearing($lat1, $lon1, $lat2, $lon2)
    {
        $dLon = deg2rad($lon2 - $lon1);
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);

        $y = sin($dLon) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($dLon);

        $bearing = atan2($y, $x);
        return (rad2deg($bearing) + 360) % 360;
    }

    /**
     * Convert bearing (0-360 degrees) to cardinal direction
     */
    private function getBearingDirection($bearing)
    {
        $directions = ['North', 'North-East', 'East', 'South-East', 'South', 'South-West', 'West', 'North-West'];
        $index = round($bearing / 45) % 8;
        return $directions[$index];
    }

    private function approvedSpotsQuery()
    {
        return TouristSpot::where('verification_status', 'approved')
            ->whereIn('status', ['open', 'active']);
    }

    private function currentApiUser(Request $request): ?User
    {
        $token = $request->bearerToken();

        if (!$token) {
            return null;
        }

        return User::where('api_token', $token)->first();
    }

    private function favoriteSpotIdsForRequest(Request $request): array
    {
        $user = $request->user() ?? $this->currentApiUser($request);

        if (!$user) {
            return [];
        }

        return TouristSpotFavorite::where('user_id', $user->id)
            ->pluck('tourist_spot_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function normalizeImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // If already a full URL, return as is
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        // Convert relative path to full URL
        return url($path);
    }

    private function transformMunicipality(Municipality $municipality): array
    {
        return [
            'id' => $municipality->id,
            'name' => $municipality->name,
            'description' => $municipality->description,
            'latitude' => $municipality->latitude,
            'longitude' => $municipality->longitude,
            'image_url' => $this->normalizeImageUrl($municipality->image_url),
            'tourist_spots_count' => $municipality->tourist_spots_count ?? 0,
            'created_at' => optional($municipality->created_at)->toIso8601String(),
            'updated_at' => optional($municipality->updated_at)->toIso8601String(),
        ];
    }

    private function transformReview(Review $review): array
    {
        $images = $review->images ?? null;
        if (empty($images) && isset($review->image_path) && $review->image_path) {
            $images = [$review->image_path];
        }

        $media = $review->media ?? [];
        if (empty($media)) {
            $media = collect($images ?? [])->map(fn ($path) => [
                'path' => $path,
                'type' => 'image',
            ])->values()->all();
        }

        $user = $review->relationLoaded('user') ? $review->user : null;

        return [
            'id' => $review->id,
            'tourist_spot_id' => $review->tourist_spot_id,
            'user_name' => $review->user_name,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'images' => $images ?? [],
            'media' => $media,
            'status' => $review->status,
            'created_at' => optional($review->created_at)->toIso8601String(),
            'updated_at' => optional($review->updated_at)->toIso8601String(),
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'profile_image_url' => $user->profile_image_url,
            ] : null,
        ];
    }

    private function transformSpot(
        TouristSpot $spot,
        bool $includeReviews = false,
        array $favoriteSpotIds = []
    ): array
    {
        $reviews = $includeReviews
            ? $spot->reviews->where('status', 'approved')->values()->map(fn (Review $review) => $this->transformReview($review))
            : null;

        return [
            'id' => $spot->id,
            'name' => $spot->name,
            'category' => $spot->category,
            'description' => $spot->description,
            'address' => $spot->address,
            'barangay' => $spot->barangay,
            'latitude' => $spot->latitude,
            'longitude' => $spot->longitude,
            'phone' => $spot->phone,
            'website' => $spot->website,
            'opening_days' => $spot->opening_days,
            'opening_time' => $spot->opening_time,
            'closing_time' => $spot->closing_time,
            'entrance_fee' => $spot->entrance_fee,
            'image_url' => $this->normalizeImageUrl($spot->image_url),
            'nearby_dining' => $spot->nearby_dining,
            'nearby_gas_stations' => $spot->nearby_gas_stations,
            'nearby_facilities' => $spot->nearby_facilities,
            'status' => $spot->status,
            'status_reason' => $spot->status_reason,
            'verification_status' => $spot->verification_status,
            'is_favorited' => in_array((int) $spot->id, $favoriteSpotIds, true),
            'municipality' => $spot->municipality ? [
                'id' => $spot->municipality->id,
                'name' => $spot->municipality->name,
                'description' => $spot->municipality->description,
                'image_url' => $this->normalizeImageUrl($spot->municipality->image_url),
            ] : null,
            'average_rating' => round((float) ($spot->reviews_avg_rating ?? $spot->reviews()->avg('rating') ?? 0), 1),
            'reviews_count' => (int) ($spot->reviews_count ?? $spot->reviews()->count()),
            'created_at' => optional($spot->created_at)->toIso8601String(),
            'updated_at' => optional($spot->updated_at)->toIso8601String(),
            'reviews' => $reviews,
        ];
    }
}

