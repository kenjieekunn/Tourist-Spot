<?php

namespace App\Http\Controllers;

use App\Events\TouristSpotChanged;
use App\Models\TouristSpot;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TouristSpotController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->belongsToMunicipalityTeam()) {
            abort_unless($user->hasPermission('manage_spots'), 403, 'You do not have permission to manage tourist spots.');
        }
        $spotsQuery = TouristSpot::with('municipality');

        if ($user && $user->isSuperAdmin()) {
            // Super admins can see everything.
        } elseif ($user && $user->belongsToMunicipalityTeam()) {
            $spotsQuery->where('municipality_id', $user->municipality_id);
        } else {
            $spotsQuery->where('verification_status', 'approved')
                ->whereIn('status', ['open', 'active']);
        }

        $searchTerm = trim((string) $request->query('q'));
        $municipalityId = $request->integer('municipality_id');
        $statusFilter = (string) $request->query('status', 'all');
        if (!in_array($statusFilter, ['all', 'approved', 'pending', 'closed'], true)) {
            $statusFilter = 'all';
        }
        $viewMode = $request->query('view') === 'list' ? 'list' : 'grid';

        if ($municipalityId > 0 && $user && $user->isSuperAdmin()) {
            $spotsQuery->where('municipality_id', $municipalityId);
        }

        if ($searchTerm !== '') {
            $spotsQuery->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('municipality', function ($municipalityQuery) use ($searchTerm) {
                        $municipalityQuery->where('name', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        if ($statusFilter === 'approved') {
            $spotsQuery->where('verification_status', 'approved');
        } elseif ($statusFilter === 'pending') {
            $spotsQuery->where('verification_status', 'pending');
        } elseif ($statusFilter === 'closed') {
            $spotsQuery->where('status', 'closed');
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
            'municipalityId' => $municipalityId,
            'statusFilter' => $statusFilter,
            'viewMode' => $viewMode,
            'municipalities' => Municipality::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        abort_unless($user && $user->isAdmin() && $user->hasPermission('manage_spots'), 403, 'You do not have permission to add tourist spots.');
        $municipalities = $user && $user->isSuperAdmin()
            ? Municipality::orderBy('name')->get()
            : collect();

        return view('tourist_spots.create', [
            'municipalities' => $municipalities,
            'assignedMunicipality' => $user && $user->belongsToMunicipalityTeam() ? $user->municipality : null,
            'spotCategories' => $this->spotCategories(),
            'districtMapContext' => $this->districtMapContext($user->belongsToMunicipalityTeam() ? $user->municipality : null),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->isAdmin() && $user->hasPermission('manage_spots'), 403, 'You do not have permission to add tourist spots.');
        $selectedCategory = (string) $request->input('category', 'nature');

        $validator = Validator::make($request->all(), [
            'municipality_id' => 'required|exists:municipalities,id',
            'barangay' => 'nullable|string|max:255',
            'category' => 'required|in:beach,parks,falls,nature,resort,historical,cultural,religious',
            'name' => 'required|string|min:3|max:255|unique:tourist_spots|regex:/^[a-zA-Z0-9\s\-.,&()\'"]+$/',
            'description' => 'required|string',
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
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
            'images.required' => 'Upload at least one reference image for this tourist spot.',
            'images.min' => 'Upload at least one reference image for this tourist spot.',
            'images.*.image' => 'Each reference image must be a valid image file.',
            'images.*.max' => 'Each reference image must not exceed 2MB.',
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

        if ($user->belongsToMunicipalityTeam() && !$this->coordinatesWithinMunicipality($user->municipality, (float) $validated['latitude'], (float) $validated['longitude'])) {
            throw ValidationException::withMessages([
                'latitude' => 'The selected location must be inside your assigned municipality.',
            ]);
        }

        if ($user && $user->belongsToMunicipalityTeam()) {
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

        if ($user && $user->belongsToMunicipalityTeam()) {
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
        abort_unless($user && $user->hasPermission('manage_spots'), 403, 'You do not have permission to edit tourist spots.');
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
            'assignedMunicipality' => $user && $user->belongsToMunicipalityTeam() ? $user->municipality : null,
            'spotCategories' => $this->spotCategories(),
            'initialFacilities' => $initialFacilities,
            'districtMapContext' => $this->districtMapContext($touristSpot->municipality),
        ]);
    }

    public function update(Request $request, TouristSpot $touristSpot)
    {
        $this->ensureSpotAccess($touristSpot);

        $user = auth()->user();
        abort_unless($user && $user->hasPermission('manage_spots'), 403, 'You do not have permission to edit tourist spots.');
        if ($user && $user->isSuperAdmin()) {
            abort(403, 'Super admin can verify tourist spots, but cannot edit them.');
        }

        $validator = Validator::make($request->all(), [
            'municipality_id' => 'required|exists:municipalities,id',
            'barangay' => 'nullable|string|max:255',
            'category' => 'required|in:beach,parks,falls,nature,resort,historical,cultural,religious',
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
            'status' => 'required|in:open,closed,active,inactive,under_maintenance,seasonal',
            'status_reason' => 'nullable|string|max:255',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string|max:2048',
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

        if ($user->belongsToMunicipalityTeam() && !$this->coordinatesWithinMunicipality($user->municipality, (float) $validated['latitude'], (float) $validated['longitude'])) {
            throw ValidationException::withMessages([
                'latitude' => 'The selected location must be inside your assigned municipality.',
            ]);
        }

        $validated['status'] = $validated['status'] === 'active'
            ? 'open'
            : ($validated['status'] === 'inactive' ? 'closed' : $validated['status']);
        if (!in_array($validated['status'], ['closed', 'under_maintenance', 'seasonal'], true)) {
            $validated['status_reason'] = null;
        }

        if ($user && $user->belongsToMunicipalityTeam()) {
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

        unset($validated['images'], $validated['remove_images']);
        $existingImageUrls = $touristSpot->image_urls ?? [];
        $removedImageUrls = array_values(array_intersect($existingImageUrls, $request->input('remove_images', [])));
        if (!empty($removedImageUrls)) {
            $this->deleteStoredImages($removedImageUrls);
        }
        $remainingImageUrls = array_values(array_diff($existingImageUrls, $removedImageUrls));
        $newImageUrls = $this->storeImagesAndGetUrls($request, 'images', 'spot-images');
        $imageUrls = array_slice(array_values(array_unique(array_merge($remainingImageUrls, $newImageUrls))), 0, 5);
        if ($imageUrls !== $existingImageUrls || !empty($newImageUrls) || !empty($removedImageUrls)) {
            $validated['images'] = $imageUrls;
            $validated['image_url'] = $imageUrls[0] ?? null;
        }

        if (Schema::hasColumn('tourist_spots', 'edited_by')) {
            $validated['edited_by'] = $user->id;
        }

        $touristSpot->update($validated);
        if (Schema::hasColumn('tourist_spots', 'rejection_reason')
            && str_starts_with((string) $touristSpot->rejection_reason, 'Revision requested:')) {
            $touristSpot->update(['rejection_reason' => null]);
        }
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
        if ($user && method_exists($user, 'belongsToMunicipalityTeam') && $user->belongsToMunicipalityTeam()) {
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

        $touristSpot->load(['municipality.admins', 'creator', 'reviews']);
        $verificationEvents = Schema::hasTable('tourist_spot_verification_events')
            ? DB::table('tourist_spot_verification_events')->where('tourist_spot_id', $touristSpot->id)->latest()->get()
            : collect();
        return view('tourist_spots.show', [
            'spot' => $touristSpot,
            'verificationEvents' => $verificationEvents,
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

        $allowedTypes = ['dining', 'gas_station'];
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
            'historical' => 'Historical',
            'cultural' => 'Cultural',
            'religious' => 'Church / Religious',
        ];
    }

    private function coordinatesWithinMunicipality(?Municipality $municipality, float $latitude, float $longitude): bool
    {
        if (!$municipality) {
            return false;
        }

        $context = $this->districtMapContext($municipality);
        if (!empty($context['boundary'])) {
            $inside = false;
            $boundary = $context['boundary'];
            for ($index = 0, $previous = count($boundary) - 1; $index < count($boundary); $previous = $index++) {
                [$currentLng, $currentLat] = $boundary[$index];
                [$previousLng, $previousLat] = $boundary[$previous];
                $intersects = (($currentLat > $latitude) !== ($previousLat > $latitude))
                    && ($longitude < ($previousLng - $currentLng) * ($latitude - $currentLat) / ($previousLat - $currentLat) + $currentLng);
                if ($intersects) {
                    $inside = !$inside;
                }
            }

            return $inside;
        }

        $bounds = $context['bounds'];

        return $latitude >= $bounds['south'] && $latitude <= $bounds['north']
            && $longitude >= $bounds['west'] && $longitude <= $bounds['east'];
    }

    private function districtMapContext(?Municipality $selectedMunicipality = null): array
    {
        $municipalities = [
            ['name' => 'Lingayen', 'latitude' => 16.0146, 'longitude' => 120.2327],
            ['name' => 'Binmaley', 'latitude' => 15.9789, 'longitude' => 120.1835],
            ['name' => 'Urbiztondo', 'latitude' => 16.0713, 'longitude' => 120.2189],
            ['name' => 'Basista', 'latitude' => 15.8531, 'longitude' => 120.4031],
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

        $boundaries = [
            'Basista' => [
                [120.380801, 15.884662], [120.386683, 15.867471], [120.393657, 15.841177], [120.412933, 15.843144],
                [120.429171, 15.847387], [120.445693, 15.853332], [120.445354, 15.853838], [120.445160, 15.854092],
                [120.445145, 15.854113], [120.444540, 15.854947], [120.444227, 15.855390], [120.443977, 15.855781],
                [120.443860, 15.855964], [120.443398, 15.856713], [120.442726, 15.857884], [120.442471, 15.858324],
                [120.442083, 15.858989], [120.441455, 15.860073], [120.441271, 15.860407], [120.440928, 15.860995],
                [120.440750, 15.861288], [120.440555, 15.861577], [120.440093, 15.862214], [120.440011, 15.862317],
                [120.439739, 15.862661], [120.439358, 15.863140], [120.438767, 15.863886], [120.438498, 15.864259],
                [120.438375, 15.864451], [120.438270, 15.864615], [120.438035, 15.865033], [120.437965, 15.865184],
                [120.437872, 15.865384], [120.437746, 15.865706], [120.437489, 15.866493], [120.437096, 15.867655],
                [120.436923, 15.868303], [120.436818, 15.868685], [120.436731, 15.869114], [120.436681, 15.869463],
                [120.436651, 15.870162], [120.436643, 15.870864], [120.436631, 15.871430], [120.436616, 15.871816],
                [120.436595, 15.872272], [120.436535, 15.872748], [120.436433, 15.873276], [120.404584, 15.894254],
                [120.392906, 15.891472], [120.388753, 15.889629], [120.386404, 15.888917], [120.380801, 15.884662],
            ],
        ];

        $municipalityNames = $selectedMunicipality
            ? [$selectedMunicipality->name]
            : array_column($municipalities, 'name');
        $selectedPoint = collect($municipalities)->firstWhere('name', $selectedMunicipality?->name);
        $visibleMunicipalities = $selectedPoint ? [$selectedPoint] : $municipalities;
        $visibleBarangays = $selectedMunicipality
            ? [$selectedMunicipality->name => ($barangays[$selectedMunicipality->name] ?? [])]
            : $barangays;
        $latitudes = array_column($visibleMunicipalities, 'latitude');
        $longitudes = array_column($visibleMunicipalities, 'longitude');
        $padding = 0.06;
        $localPlaces = collect($visibleMunicipalities)->map(function (array $municipality) {
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
            'municipalities' => $municipalityNames,
            'municipalityPoints' => $visibleMunicipalities,
            'barangays' => $visibleBarangays,
            'boundary' => $selectedMunicipality ? ($boundaries[$selectedMunicipality->name] ?? null) : null,
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
            'searchSuffix' => $selectedMunicipality
                ? $selectedMunicipality->name . ', Pangasinan, Philippines'
                : 'Pangasinan 2nd District, Pangasinan, Philippines',
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

        if ($user && $user->belongsToMunicipalityTeam() && (int) $touristSpot->municipality_id === (int) $user->municipality_id) {
            return;
        }

        abort(403, 'Unauthorized access to this tourist spot.');
    }
}
