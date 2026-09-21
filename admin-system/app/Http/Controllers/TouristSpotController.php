<?php

namespace App\Http\Controllers;

use App\Events\TouristSpotChanged;
use App\Models\TouristSpot;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TouristSpotController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $spotsQuery = TouristSpot::with('municipality');

        if ($user && $user->isSuperAdmin()) {
            // Super admins can see everything.
        } elseif ($user && $user->isMunicipalityAdmin()) {
            $spotsQuery->where('municipality_id', $user->municipality_id);
        } else {
            $spotsQuery->where('verification_status', 'approved')
                ->whereIn('status', ['open', 'active']);
        }

        $searchTerm = trim((string) $request->query('q'));

        if ($searchTerm !== '') {
            $spotsQuery->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('municipality', function ($municipalityQuery) use ($searchTerm) {
                        $municipalityQuery->where('name', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        $spots = $spotsQuery->get();
        $spotCategories = $this->spotCategories();
        $groupedSpots = collect($spotCategories)->mapWithKeys(function ($label, $key) use ($spots) {
            return [
                $key => $spots->filter(function ($spot) use ($key) {
                    return ($spot->category ?? 'nature') === $key;
                })->values(),
            ];
        });

        return view('tourist_spots.index', [
            'spots' => $spots,
            'groupedSpots' => $groupedSpots,
            'spotCategories' => $spotCategories,
            'searchTerm' => $searchTerm,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        abort_unless($user && $user->isAdmin(), 403, 'Unauthorized access to create tourist spots.');
        $municipalities = $user && $user->isSuperAdmin()
            ? Municipality::orderBy('name')->get()
            : collect();

        return view('tourist_spots.create', [
            'municipalities' => $municipalities,
            'assignedMunicipality' => $user && $user->isMunicipalityAdmin() ? $user->municipality : null,
            'spotCategories' => $this->spotCategories(),
            'districtMapContext' => $this->districtMapContext(),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->isAdmin(), 403, 'Unauthorized access to create tourist spots.');
        $selectedCategory = (string) $request->input('category', 'nature');

        $validator = Validator::make($request->all(), [
            'municipality_id' => 'required|exists:municipalities,id',
            'barangay' => 'nullable|string|max:255',
            'category' => 'required|in:beach,parks,falls,nature,resort',
            'name' => 'required|string|min:3|max:255|unique:tourist_spots|regex:/^[a-zA-Z0-9\s\-.,&()\'"]+$/',
            'description' => 'required|string',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'opening_days' => 'nullable|array',
            'opening_days.*' => 'in:mon,tue,wed,thu,fri,sat,sun',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'nearby_dining' => 'nullable|string',
            'nearby_gas_stations' => 'nullable|string',
            'nearby_facilities' => 'nullable|string',
        ], [
            'name.required' => 'The tourist spot name is required.',
            'name.min' => 'The tourist spot name must be at least 3 characters long.',
            'name.max' => 'The tourist spot name cannot exceed 255 characters.',
            'name.unique' => 'This tourist spot name already exists. Please use a different name.',
            'name.regex' => 'The tourist spot name can only contain letters, numbers, spaces, hyphens, periods, commas, ampersands, and parentheses.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $selectedCategory = (string) $request->input('category', 'nature');
            if ($selectedCategory !== 'parks') {
                return;
            }

            $open = $request->input('opening_time');
            $close = $request->input('closing_time');

            if (($open && !$close) || (!$open && $close)) {
                $validator->errors()->add('opening_time', 'Opening and closing time must both be provided.');
            }

            if ($open && $close && strcmp((string) $close, (string) $open) <= 0) {
                $validator->errors()->add('closing_time', 'Closing time must be after opening time.');
            }
        });

        $validator->after(function ($validator) use ($request) {
            $facilities = $this->normalizeFacilities($request->input('nearby_facilities'));
            if (empty($facilities)) {
                return;
            }

            $latitude = filter_var($request->input('latitude'), FILTER_VALIDATE_FLOAT);
            $longitude = filter_var($request->input('longitude'), FILTER_VALIDATE_FLOAT);
            if ($latitude === false || $longitude === false) {
                return;
            }

            foreach ($this->facilitiesOutsideRadius($facilities, (float) $latitude, (float) $longitude) as $violation) {
                $validator->errors()->add(
                    'nearby_facilities',
                    sprintf(
                        'Nearby facility "%s" is %.2f km away from the spot center and exceeds the 2 km radius.',
                        $violation['name'],
                        $violation['distance_km']
                    )
                );
            }
        });

        $validator->after(function ($validator) use ($request) {
            $normalizedName = $this->normalizeComparableText($request->input('name'));
            $normalizedAddress = $this->normalizeComparableText($request->input('address'));
            $latitude = round((float) $request->input('latitude'), 6);
            $longitude = round((float) $request->input('longitude'), 6);

            $existingSpot = TouristSpot::query()
                ->where(function ($query) use ($normalizedName, $normalizedAddress, $latitude, $longitude) {
                    if ($normalizedName !== '') {
                        $query->orWhereRaw('LOWER(TRIM(name)) = ?', [$normalizedName]);
                    }

                    if ($normalizedAddress !== '') {
                        $query->orWhereRaw('LOWER(TRIM(address)) = ?', [$normalizedAddress]);
                    }

                    $query->orWhere(function ($locationQuery) use ($latitude, $longitude) {
                        $locationQuery->whereRaw('ROUND(latitude, 6) = ?', [$latitude])
                            ->whereRaw('ROUND(longitude, 6) = ?', [$longitude]);
                    });
                })
                ->first();

            if (!$existingSpot) {
                return;
            }

            if ($normalizedName !== '' && $this->normalizeComparableText($existingSpot->name) === $normalizedName) {
                $validator->errors()->add('name', 'A tourist spot with this name already exists. Please use a different name.');
            }

            if ($normalizedAddress !== '' && $this->normalizeComparableText($existingSpot->address) === $normalizedAddress) {
                $validator->errors()->add('address', 'A tourist spot with this location already exists. Please choose a different location.');
            }

            if (round((float) $existingSpot->latitude, 6) === $latitude
                && round((float) $existingSpot->longitude, 6) === $longitude) {
                $validator->errors()->add('latitude', 'A tourist spot has already been saved at this location.');
            }
        });

        $validated = $validator->validate();

        if ($user && $user->isMunicipalityAdmin()) {
            $validated['municipality_id'] = $user->municipality_id;
            $validated['status'] = 'closed';
            $validated['verification_status'] = 'pending';
        } elseif ($user && $user->isSuperAdmin()) {
            $validated['status'] = 'open';
            $validated['verification_status'] = 'approved';
        } else {
            $validated['status'] = 'closed';
            $validated['verification_status'] = 'pending';
        }

        $validated['created_by'] = $user->id;

        $validated['nearby_facilities'] = $this->normalizeFacilities(
            $request->input('nearby_facilities')
        );

        if (($validated['category'] ?? 'nature') === 'parks') {
            $days = $request->input('opening_days', []);
            $days = is_array($days) ? array_values(array_unique(array_map('strtolower', $days))) : [];
            $allowed = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            $days = array_values(array_filter($days, fn ($d) => in_array($d, $allowed, true)));

            $validated['opening_days'] = $days ? json_encode($days) : null;
            $validated['opening_time'] = $request->input('opening_time') ?: null;
            $validated['closing_time'] = $request->input('closing_time') ?: null;
        } else {
            $validated['opening_days'] = null;
            $validated['opening_time'] = null;
            $validated['closing_time'] = null;
        }

        unset($validated['images']);
        $imageUrls = $this->storeImagesAndGetUrls($request, 'images', 'spot-images');
        if (!empty($imageUrls)) {
            $validated['images'] = $imageUrls;
            $validated['image_url'] = $imageUrls[0];
        }

        $spot = TouristSpot::create($validated);
// event(new TouristSpotChanged('created', $spot->toArray())); // Disabled broadcasting to fix Pusher error

        if ($user && $user->isMunicipalityAdmin()) {
            return redirect()->route('municipality-admin.tourist-spots')->with('success', 'Tourist spot created successfully!');
        }

        if ($user && $user->isSuperAdmin()) {
            return redirect()->route('super-admin.tourist-spots')->with('success', 'Tourist spot created successfully!');
        }

        return redirect()->route('tourist_spots.index')->with('success', 'Tourist spot created successfully!');
    }

    public function edit(TouristSpot $touristSpot)
    {
        $this->ensureSpotAccess($touristSpot);

        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            abort(403, 'Super admin can verify tourist spots, but cannot edit them.');
        }

        $municipalities = $user && $user->isSuperAdmin()
            ? Municipality::orderBy('name')->get()
            : collect();

        $initialFacilities = is_string($touristSpot->nearby_facilities) 
            ? json_decode($touristSpot->nearby_facilities, true) ?? []
            : ($touristSpot->nearby_facilities ?? []);
        return view('tourist_spots.edit', [
            'spot' => $touristSpot,
            'municipalities' => $municipalities,
            'assignedMunicipality' => $user && $user->isMunicipalityAdmin() ? $user->municipality : null,
            'spotCategories' => $this->spotCategories(),
            'initialFacilities' => $initialFacilities,
            'districtMapContext' => $this->districtMapContext(),
        ]);
    }

    public function update(Request $request, TouristSpot $touristSpot)
    {
        $this->ensureSpotAccess($touristSpot);

        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            abort(403, 'Super admin can verify tourist spots, but cannot edit them.');
        }

        $validator = Validator::make($request->all(), [
            'municipality_id' => 'required|exists:municipalities,id',
            'barangay' => 'nullable|string|max:255',
            'category' => 'required|in:beach,parks,falls,nature,resort',
            'name' => 'required|string|min:3|max:255|unique:tourist_spots,name,' . $touristSpot->id . '|regex:/^[a-zA-Z0-9\s\-.,&()\'"]+$/',
            'description' => 'required|string',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'opening_days' => 'nullable|array',
            'opening_days.*' => 'in:mon,tue,wed,thu,fri,sat,sun',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'nearby_dining' => 'nullable|string',
            'nearby_gas_stations' => 'nullable|string',
            'nearby_facilities' => 'nullable|string',
            'status' => 'required|in:open,closed,active,inactive',
        ], [
            'name.required' => 'The tourist spot name is required.',
            'name.min' => 'The tourist spot name must be at least 3 characters long.',
            'name.max' => 'The tourist spot name cannot exceed 255 characters.',
            'name.unique' => 'This tourist spot name already exists. Please use a different name.',
            'name.regex' => 'The tourist spot name can only contain letters, numbers, spaces, hyphens, periods, commas, ampersands, and parentheses.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $selectedCategory = (string) $request->input('category', 'nature');
            if ($selectedCategory !== 'parks') {
                return;
            }

            $open = $request->input('opening_time');
            $close = $request->input('closing_time');

            if (($open && !$close) || (!$open && $close)) {
                $validator->errors()->add('opening_time', 'Opening and closing time must both be provided.');
            }

            if ($open && $close && strcmp((string) $close, (string) $open) <= 0) {
                $validator->errors()->add('closing_time', 'Closing time must be after opening time.');
            }
        });

        $validator->after(function ($validator) use ($request, $touristSpot) {
            $facilities = $this->normalizeFacilities($request->input('nearby_facilities'));
            if (empty($facilities)) {
                return;
            }

            $latitude = filter_var($request->input('latitude'), FILTER_VALIDATE_FLOAT);
            $longitude = filter_var($request->input('longitude'), FILTER_VALIDATE_FLOAT);
            if ($latitude === false || $longitude === false) {
                return;
            }

            foreach ($this->facilitiesOutsideRadius($facilities, (float) $latitude, (float) $longitude) as $violation) {
                $validator->errors()->add(
                    'nearby_facilities',
                    sprintf(
                        'Nearby facility "%s" is %.2f km away from the spot center and exceeds the 2 km radius.',
                        $violation['name'],
                        $violation['distance_km']
                    )
                );
            }
        });

        $validator->after(function ($validator) use ($request, $touristSpot) {
            $normalizedName = $this->normalizeComparableText($request->input('name'));
            $normalizedAddress = $this->normalizeComparableText($request->input('address'));
            $latitude = round((float) $request->input('latitude'), 6);
            $longitude = round((float) $request->input('longitude'), 6);

            $existingSpot = TouristSpot::query()
                ->where('id', '!=', $touristSpot->id)
                ->where(function ($query) use ($normalizedName, $normalizedAddress, $latitude, $longitude) {
                    if ($normalizedName !== '') {
                        $query->orWhereRaw('LOWER(TRIM(name)) = ?', [$normalizedName]);
                    }

                    if ($normalizedAddress !== '') {
                        $query->orWhereRaw('LOWER(TRIM(address)) = ?', [$normalizedAddress]);
                    }

                    $query->orWhere(function ($locationQuery) use ($latitude, $longitude) {
                        $locationQuery->whereRaw('ROUND(latitude, 6) = ?', [$latitude])
                            ->whereRaw('ROUND(longitude, 6) = ?', [$longitude]);
                    });
                })
                ->first();

            if (!$existingSpot) {
                return;
            }

            if ($normalizedName !== '' && $this->normalizeComparableText($existingSpot->name) === $normalizedName) {
                $validator->errors()->add('name', 'A tourist spot with this name already exists. Please use a different name.');
            }

            if ($normalizedAddress !== '' && $this->normalizeComparableText($existingSpot->address) === $normalizedAddress) {
                $validator->errors()->add('address', 'A tourist spot with this location already exists. Please choose a different location.');
            }

            if (round((float) $existingSpot->latitude, 6) === $latitude
                && round((float) $existingSpot->longitude, 6) === $longitude) {
                $validator->errors()->add('latitude', 'A tourist spot has already been saved at this location.');
            }
        });

        $validated = $validator->validate();

        $validated['status'] = $validated['status'] === 'active'
            ? 'open'
            : ($validated['status'] === 'inactive' ? 'closed' : $validated['status']);

        if ($user && $user->isMunicipalityAdmin()) {
            $validated['municipality_id'] = $user->municipality_id;
            $validated['verification_status'] = $touristSpot->verification_status;
        }

        $validated['nearby_facilities'] = $this->normalizeFacilities(
            $request->input('nearby_facilities')
        );

        if (($validated['category'] ?? 'nature') === 'parks') {
            $days = $request->input('opening_days', []);
            $days = is_array($days) ? array_values(array_unique(array_map('strtolower', $days))) : [];
            $allowed = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            $days = array_values(array_filter($days, fn ($d) => in_array($d, $allowed, true)));

            $validated['opening_days'] = $days ? json_encode($days) : null;
            $validated['opening_time'] = $request->input('opening_time') ?: null;
            $validated['closing_time'] = $request->input('closing_time') ?: null;
        } else {
            $validated['opening_days'] = null;
            $validated['opening_time'] = null;
            $validated['closing_time'] = null;
        }

        unset($validated['images']);
        $imageUrls = $this->storeImagesAndGetUrls($request, 'images', 'spot-images', $touristSpot->image_urls ?? []);
        if (!empty($imageUrls)) {
            $validated['images'] = $imageUrls;
            $validated['image_url'] = $imageUrls[0];
        }

        $touristSpot->update($validated);
// event(new TouristSpotChanged('updated', $touristSpot->fresh()->toArray())); // Disabled broadcasting to fix Pusher error

        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            $parsed = parse_url($returnTo);
            $requestHost = $request->getHost();
            $returnHost = $parsed['host'] ?? null;
            $returnPath = $parsed['path'] ?? '';

            if (($returnHost === null && str_starts_with($returnTo, '/')) || $returnHost === $requestHost) {
                return redirect($returnTo)->with('success', 'Tourist spot updated successfully!');
            }
        }

        return redirect()->route('tourist_spots.index')->with('success', 'Tourist spot updated successfully!');
    }

    public function destroy(Request $request, TouristSpot $touristSpot)
    {
        $this->ensureSpotAccess($touristSpot);

        $deletedId = $touristSpot->id;
        $touristSpot->delete();
// event(new TouristSpotChanged('deleted', ['id' => $deletedId])); // Disabled broadcasting to fix Pusher error

        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            $parsed = parse_url($returnTo);
            $requestHost = $request->getHost();
            $returnHost = $parsed['host'] ?? null;

            if (($returnHost === null && str_starts_with($returnTo, '/')) || $returnHost === $requestHost) {
                return redirect($returnTo)->with('success', 'Tourist spot deleted successfully!');
            }
        }

        $user = $request->user();
        if ($user && method_exists($user, 'isMunicipalityAdmin') && $user->isMunicipalityAdmin()) {
            return redirect()->route('municipality-admin.tourist-spots')->with('success', 'Tourist spot deleted successfully!');
        }

        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return redirect()->route('super-admin.tourist-spots')->with('success', 'Tourist spot deleted successfully!');
        }

        return redirect()->route('tourist_spots.index')->with('success', 'Tourist spot deleted successfully!');
    }

    public function show(TouristSpot $touristSpot)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && (!$touristSpot->isVerified() || !in_array($touristSpot->status, ['open', 'active'], true)))) {
            abort(404);
        }

        $touristSpot->load(['municipality', 'reviews']);
        return view('tourist_spots.show', [
            'spot' => $touristSpot,
            'districtMapContext' => $this->districtMapContext(),
        ]);
    }

    public function reviewsJson(TouristSpot $touristSpot)
    {
        $reviews = $touristSpot->reviews()
            ->latest()
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user_name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'status' => $review->status,
                    'created_at' => $review->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $reviews->count(),
            'data' => $reviews,
        ]);
    }

    private function normalizeFacilities(?string $payload): array
    {
        if ($payload === null || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return [];
        }

        $allowedTypes = ['dining', 'gas_station', 'restroom'];
        $facilities = [];

        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $type = $entry['type'] ?? null;
            if (!in_array($type, $allowedTypes, true)) {
                continue;
            }

            $name = trim((string) ($entry['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $lat = filter_var($entry['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($entry['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($lat === false || $lng === false) {
                continue;
            }

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }

            $facilities[] = [
                'type' => $type,
                'name' => substr($name, 0, 120),
                'latitude' => round((float) $lat, 6),
                'longitude' => round((float) $lng, 6),
            ];
        }

        return $facilities;
    }

    private function facilitiesOutsideRadius(array $facilities, float $centerLat, float $centerLng): array
    {
        $violations = [];
        $radiusMeters = $this->facilityRadiusMeters();

        foreach ($facilities as $facility) {
            if (!is_array($facility)) {
                continue;
            }

            $lat = filter_var($facility['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($facility['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($lat === false || $lng === false) {
                continue;
            }

            $distanceMeters = $this->distanceMeters(
                $centerLat,
                $centerLng,
                (float) $lat,
                (float) $lng
            );

            if ($distanceMeters <= $radiusMeters) {
                continue;
            }

            $violations[] = [
                'name' => trim((string) ($facility['name'] ?? 'Facility')),
                'distance_km' => round($distanceMeters / 1000, 2),
            ];
        }

        return $violations;
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLng / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function facilityRadiusMeters(): float
    {
        return 2000.0;
    }

    private function spotCategories(): array
    {
        return [
            'beach' => 'Beach',
            'parks' => 'Parks',
            'falls' => 'Falls',
            'nature' => 'Nature',
            'resort' => 'Resort',
        ];
    }

    private function districtMapContext(): array
    {
        $municipalities = [
            ['name' => 'Lingayen', 'latitude' => 16.0146, 'longitude' => 120.2327],
            ['name' => 'Binmaley', 'latitude' => 15.9789, 'longitude' => 120.1835],
            ['name' => 'Urbiztondo', 'latitude' => 16.0713, 'longitude' => 120.2189],
            ['name' => 'Basista', 'latitude' => 16.0427, 'longitude' => 120.2436],
            ['name' => 'Labrador', 'latitude' => 16.0045, 'longitude' => 120.2087],
            ['name' => 'Bugallon', 'latitude' => 16.0574, 'longitude' => 120.1905],
            ['name' => 'Mangatarem', 'latitude' => 15.9523, 'longitude' => 120.2348],
            ['name' => 'Aguilar', 'latitude' => 15.9896, 'longitude' => 120.2553],
        ];

        $barangays = [
            'Lingayen' => ['Bolasi', 'Bonuan', 'Bonuan Binac', 'Caba', 'Dagupan', 'Lasip', 'Lingayen', 'Malued', 'Palsol', 'Rosario', 'San Fernando', 'San Jacinto', 'San Juan', 'San Vicente', 'Santa Cruz', 'Santo Domingo', 'Tagudin'],
            'Binmaley' => ['Alon', 'Asingan', 'Balangabang', 'Balantac', 'Balat', 'Balayong', 'Bani', 'Banaoang', 'Bangar', 'Bansalao', 'Bantay', 'Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5', 'Barangay 6'],
            'Urbiztondo' => ['Alon', 'Balangabang', 'Balantac', 'Balat', 'Balayong', 'Bani', 'Banaoang', 'Bangar', 'Bansalao', 'Bantay', 'Barangay 1', 'Barangay 2', 'Barangay 3', 'Barangay 4', 'Barangay 5'],
            'Basista' => ['Alon', 'Balangabang', 'Balantac', 'Balat', 'Balayong', 'Bani', 'Banaoang', 'Bangar', 'Bansalao', 'Bantay', 'Barangay 1', 'Barangay 2', 'Barangay 3'],
            'Labrador' => ['Alon', 'Balangabang', 'Balantac', 'Balat', 'Balayong', 'Bani', 'Banaoang', 'Bangar', 'Bansalao', 'Bantay', 'Barangay 1', 'Barangay 2'],
            'Bugallon' => ['Alon', 'Balangabang', 'Balantac', 'Balat', 'Balayong', 'Bani', 'Banaoang', 'Bangar', 'Bansalao', 'Bantay', 'Barangay 1'],
            'Mangatarem' => ['Alon', 'Balangabang', 'Balantac', 'Balat', 'Balayong', 'Bani', 'Banaoang', 'Bangar', 'Bansalao', 'Bantay'],
            'Aguilar' => ['Alon', 'Balangabang', 'Balantac', 'Balat', 'Balayong', 'Bani', 'Banaoang', 'Bangar', 'Bansalao'],
        ];

        $latitudes = array_column($municipalities, 'latitude');
        $longitudes = array_column($municipalities, 'longitude');
        $padding = 0.06;
        $municipalityNames = array_column($municipalities, 'name');
        $localPlaces = collect($municipalities)->map(function (array $municipality) {
            return [
                'name' => $municipality['name'],
                'subtitle' => 'Municipality center • Pangasinan 2nd District',
                'latitude' => $municipality['latitude'],
                'longitude' => $municipality['longitude'],
                'source' => 'Municipality center',
                'source_label' => 'Municipality center',
                'search_text' => strtolower($municipality['name'] . ' municipality center pangasinan 2nd district'),
            ];
        });

        $existingPlaces = TouristSpot::query()
            ->with(['municipality:id,name'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('municipality', function ($query) use ($municipalityNames) {
                $query->whereIn('name', $municipalityNames);
            })
            ->orderBy('name')
            ->limit(150)
            ->get()
            ->map(function (TouristSpot $spot) {
                $municipalityName = $spot->municipality->name ?? '';
                $subtitleParts = array_filter([$spot->address ?? null, $municipalityName ?: null]);
                $commonSubtitle = implode(' • ', $subtitleParts);
                $baseSearchText = strtolower(trim(implode(' ', array_filter([
                    $spot->name,
                    $spot->address,
                    $municipalityName,
                    $spot->category ?? null,
                ]))));

                return [
                    'name' => $spot->name,
                    'subtitle' => $commonSubtitle ?: ($municipalityName . ' tourist spot'),
                    'latitude' => $spot->latitude,
                    'longitude' => $spot->longitude,
                    'source' => 'Existing tourist spot',
                    'source_label' => 'Existing tourist spot',
                    'search_text' => $baseSearchText,
                ];
            })
            ->unique(fn (array $place) => strtolower($place['name'] . '|' . ($place['latitude'] . ',' . $place['longitude'])))
            ->values();

        $localPlaces = $localPlaces
            ->concat($existingPlaces)
            ->unique(fn (array $place) => strtolower($place['name'] . '|' . ($place['subtitle'] ?? '')))
            ->values()
            ->all();

        return [
            'municipalities' => array_column($municipalities, 'name'),
            'municipalityPoints' => $municipalities,
            'barangays' => $barangays,
            'localPlaces' => $localPlaces,
            'bounds' => [
                'north' => max($latitudes) + $padding,
                'south' => min($latitudes) - $padding,
                'east' => max($longitudes) + $padding,
                'west' => min($longitudes) - $padding,
            ],
            'center' => [
                'lat' => round(array_sum($latitudes) / count($latitudes), 6),
                'lng' => round(array_sum($longitudes) / count($longitudes), 6),
            ],
            'searchSuffix' => 'Pangasinan 2nd District, Pangasinan, Philippines',
        ];
    }

    private function storeImagesAndGetUrls(Request $request, string $inputName, string $directory, array $existingUrls = []): array
    {
        if (!$request->hasFile($inputName)) {
            return [];
        }

        $this->deleteStoredImages($existingUrls);

        $files = $request->file($inputName);
        $files = is_array($files) ? $files : [$files];

        $urls = [];
        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $path = $file->store($directory, 'public');
            $urls[] = Storage::url($path);
        }

        return $urls;
    }

    private function deleteStoredImages(array $imageUrls): void
    {
        foreach ($imageUrls as $imageUrl) {
            if (!$imageUrl) {
                continue;
            }

            $path = parse_url($imageUrl, PHP_URL_PATH) ?: '';
            $prefix = '/storage/';
            if (!str_starts_with($path, $prefix)) {
                continue;
            }

            $relative = ltrim(substr($path, strlen($prefix)), '/');
            if ($relative !== '') {
                Storage::disk('public')->delete($relative);
            }
        }
    }

    private function normalizeOpeningSchedule($days, ?string $openTime, ?string $closeTime): array
    {
        $days = is_array($days) ? array_values(array_unique(array_map('strtolower', $days))) : [];
        $allowed = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $days = array_values(array_filter($days, fn ($d) => in_array($d, $allowed, true)));

        $openTime = $openTime !== '' ? $openTime : null;
        $closeTime = $closeTime !== '' ? $closeTime : null;

        $dayLabel = $this->formatDayRangeLabel($days);
        $timeLabel = $openTime && $closeTime ? ($openTime . ' - ' . $closeTime) : null;

        $label = trim(($dayLabel ? ($dayLabel . ' ') : '') . ($timeLabel ?? ''));
        if ($label === '') {
            $label = null;
        }

        return [
            'days' => $days ?: null,
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'label' => $label,
        ];
    }

    private function normalizeComparableText(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        return preg_replace('/\s+/', ' ', $normalized) ?? '';
    }

    private function formatDayRangeLabel(array $days): ?string
    {
        if (!$days) {
            return null;
        }

        $order = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        usort($days, function ($a, $b) use ($order) {
            return array_search($a, $order, true) <=> array_search($b, $order, true);
        });

        if ($days === $order) {
            return 'Mon-Sun';
        }

        $map = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
        return implode(', ', array_map(fn ($d) => $map[$d] ?? $d, $days));
    }

    private function ensureSpotAccess(TouristSpot $touristSpot): void
    {
        $user = auth()->user();

        if ($user && $user->isSuperAdmin()) {
            return;
        }

        if ($user && $user->isMunicipalityAdmin() && (int) $touristSpot->municipality_id === (int) $user->municipality_id) {
            return;
        }

        abort(403, 'Unauthorized access to this tourist spot.');
    }
}
