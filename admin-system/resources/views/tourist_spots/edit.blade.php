@extends('layouts.app')

@section('title', 'Edit Tourist Spot')
@section('header', 'Edit Tourist Spot: ' . $spot->name)

@section('content')
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>

<style>
    #map {
        height: 400px;
        border-radius: 8px;
        margin-top: 10px;
        border: 2px solid #dee2e6;
    }
    .search-location-container {
        margin-bottom: 15px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 2px solid #007bff;
    }
    .search-location-form {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: flex-start;
    }
    .search-location-input-group {
        flex: 1;
        min-width: 250px;
        position: relative;
    }
    .search-location-input {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        font-size: 1rem;
    }
    .search-location-input:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }
    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 5px 5px;
        max-height: 250px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .search-suggestions.show {
        display: block;
    }
    .search-suggestion-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
    }
    .search-suggestion-item:hover {
        background: #f5f5f5;
    }
    .search-suggestion-item:last-child {
        border-bottom: none;
    }
    .suggestion-name {
        font-weight: 500;
        color: #333;
    }
    .suggestion-address {
        font-size: 0.85rem;
        color: #999;
    }
    .suggestion-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 4px;
        padding: 2px 8px;
        border-radius: 999px;
        background: #e8f1ff;
        color: #0d6efd;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }
    .facility-tools {
        padding: 15px;
        background: #f8f9fa;
        border: 1px solid #e1e5ea;
        border-radius: 8px;
    }
    .facility-list-item {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 12px;
        margin-bottom: 10px;
        background: #fff;
    }
    .facility-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 0.75rem;
        color: #fff;
        margin-right: 8px;
    }
</style>

@php
    $oldFacilities = old('nearby_facilities');
    $initialFacilities = $oldFacilities ? json_decode($oldFacilities, true) : ($spot->nearby_facilities ?? []);
    if (!is_array($initialFacilities)) {
        $initialFacilities = [];
    }
    $cancelUrl = url()->previous();
    if (!$cancelUrl || $cancelUrl === url()->current()) {
        $cancelUrl = route('tourist_spots.index');
    }
    $selectedCategory = old('category', $spot->category ?? 'nature');
    $showScheduleFields = $selectedCategory === 'parks';
    $spotImages = collect($spot->image_urls ?? []);
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                @if(auth()->user()->isSuperAdmin())
                    <div class="alert alert-info">
                        Super admin edits remain published immediately.
                    </div>
                @else
                    <div class="alert alert-info">
                        Saving changes from a municipality admin will keep approved spots published immediately.
                    </div>
                @endif
                <form id="tourist-spot-form" action="{{ route('tourist_spots.update', $spot->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="return_to" value="{{ $cancelUrl }}">

                    <h5 class="mb-3"><i class="fas fa-map"></i> Location & Map</h5>

                    @if($assignedMunicipality)
                        <div class="mb-3">
                            <label class="form-label">Municipality</label>
                            <div class="border rounded-3 p-3 bg-light">
                                <strong>{{ $assignedMunicipality->name }}</strong>
                            </div>
                            <input type="hidden" name="municipality_id" value="{{ $assignedMunicipality->id }}">
                        </div>
                    @else
                        <div class="mb-3">
                            <label for="municipality_id" class="form-label">Municipality <span class="text-danger">*</span></label>
                            <select class="form-select @error('municipality_id') is-invalid @enderror" id="municipality_id" name="municipality_id" required>
                                <option value="">Select Municipality</option>
                                @foreach($municipalities as $municipality)
                                    <option value="{{ $municipality->id }}" @selected(old('municipality_id', $spot->municipality_id) == $municipality->id)>
                                        {{ $municipality->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('municipality_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                            <option value="">Select Category</option>
                            @foreach($spotCategories as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', $spot->category ?? 'nature') == $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('category')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Spot Name <span class="text-danger">*</span></label>
                        <input 
                            type="text" 
                            class="form-control @error('name') is-invalid @enderror" 
                            id="name" 
                            name="name" 
                            value="{{ old('name', $spot->name) }}" 
                            required
                            minlength="3"
                            maxlength="255"
                            pattern="[a-zA-Z0-9\s\-.,&()']+"
                            placeholder="Enter tourist spot name (3-255 characters)"
                        >
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div id="name-validation-feedback" class="mt-2"></div>
                    </div>

                    <!-- Location Search Bar -->
                    <div class="search-location-container">
                        <label class="form-label">Search for Location</label>
                        <div class="search-location-form">
                            <div class="search-location-input-group">
                                <input 
                                    type="text" 
                                    id="location-search" 
                                    class="search-location-input" 
                                    placeholder="Search streets, barangays, landmarks... (e.g., 'Barangay Poblacion', 'Capitol Road')"
                                    autocomplete="off"
                                >
                                <div class="search-suggestions" id="search-suggestions"></div>
                            </div>
                            <button type="button" onclick="searchLocation()" class="btn btn-primary mt-2">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <div class="form-text mt-2">You can search by municipality, barangay, landmark, or tourist spot name.</div>
                    </div>

                    <!-- Google Map -->
                    <div id="map"></div>

                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $spot->latitude) }}" required>
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $spot->longitude) }}" required>
                    <input type="hidden" id="nearby_facilities" name="nearby_facilities" value="">
                    @if($errors->has('latitude') || $errors->has('longitude'))
                        <div class="text-danger small mt-2">
                            Please select a valid location on the map.
                        </div>
                    @endif

                    <div class="mt-4">
                        <h6 class="mb-2"><i class="fas fa-location-dot"></i> Nearby Facilities (Exact Locations)</h6>
                        <div class="facility-tools">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label">Facility Type</label>
                                    <select id="facility-type" class="form-select">
                                        <option value="dining">Dining</option>
                                        <option value="gas_station">Gas Station</option>
                                        <option value="restroom">Restroom</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Facility Name</label>
                                    <input type="text" id="facility-name" class="form-control" placeholder="e.g., Bos Coffee">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" id="facility-add-btn" class="btn btn-outline-primary w-100">
                                        Click map to add
                                    </button>
                                </div>
                            </div>
                            <div class="form-text mt-2" id="facility-hint">
                                Select a type and name, then click the map to drop a marker.
                            </div>
                            <div class="form-text text-primary mt-1">
                                Nearby facilities must stay within a 2 km radius of the main spot marker.
                            </div>
                        </div>
                        <div id="facility-list" class="mt-3"></div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Basic Information</h5>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description', $spot->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $spot->address) }}" required>
                        @error('address')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="images" class="form-label">Spot Images</label>
                        @if($spotImages->isNotEmpty())
                            <div class="row g-2 mb-2">
                                @foreach($spotImages as $spotImage)
                                    <div class="col-6 col-md-4 col-xl-3">
                                        <img src="{{ $spotImage }}" alt="{{ $spot->name }} image" class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;">
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text mb-2">Uploading new images replaces the current gallery.</div>
                        @endif
                        <input type="file" class="form-control @error('images') is-invalid @enderror" id="images" name="images[]" accept="image/*" multiple>
                        @error('images')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Upload up to 5 JPG, PNG, or WebP images (max 2MB each). The first image becomes the cover image.</div>
                    </div>

                    <div id="schedule-section" class="{{ $showScheduleFields ? '' : 'd-none' }}">
                        @php
                            $selectedDays = old('opening_days', []);
                            if (empty($selectedDays) && $spot->opening_days) {
                                $decoded = json_decode($spot->opening_days, true);
                                $selectedDays = is_array($decoded) ? $decoded : [];
                            }
                            if (!is_array($selectedDays)) {
                                $selectedDays = [];
                            }
                        @endphp

                        <div class="mb-3">
                            <label class="form-label">Open Days</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach([
                                    'mon' => 'Mon',
                                    'tue' => 'Tue',
                                    'wed' => 'Wed',
                                    'thu' => 'Thu',
                                    'fri' => 'Fri',
                                    'sat' => 'Sat',
                                    'sun' => 'Sun',
                                ] as $key => $label)
                                    <div class="form-check form-check-inline m-0">
                                        <input class="form-check-input schedule-input" type="checkbox" id="day-{{ $key }}" name="opening_days[]" value="{{ $key }}" @checked(in_array($key, $selectedDays, true))>
                                        <label class="form-check-label" for="day-{{ $key }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('opening_days')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Schedule fields apply to parks only.</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="opening_time" class="form-label">Opening Time</label>
                                <input type="time" class="form-control schedule-input @error('opening_time') is-invalid @enderror" id="opening_time" name="opening_time" value="{{ old('opening_time', $spot->opening_time) }}">
                                @error('opening_time')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="closing_time" class="form-label">Closing Time</label>
                                <input type="time" class="form-control schedule-input @error('closing_time') is-invalid @enderror" id="closing_time" name="closing_time" value="{{ old('closing_time', $spot->closing_time) }}">
                                @error('closing_time')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-text">If you set time, both opening and closing must be filled.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        @php
                            $normalizedStatus = old('status', in_array($spot->status, ['active', 'open']) ? 'open' : 'closed');
                        @endphp
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="open" @if($normalizedStatus == 'open') selected @endif>Open</option>
                            <option value="closed" @if($normalizedStatus == 'closed') selected @endif>Closed</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Spot
                        </button>
                        <a href="{{ $cancelUrl }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="m-0"><i class="fas fa-info-circle"></i> Spot Details</h5>
            </div>
            <div class="card-body small">
                <p><strong>Last Updated:</strong> {{ $spot->updated_at->format('M d, Y H:i') }}</p>
                <p><strong>Created:</strong> {{ $spot->created_at->format('M d, Y H:i') }}</p>
                <p><strong>Total Reviews:</strong> {{ $spot->reviews->count() }}</p>
                <p><strong>Average Rating:</strong> {{ number_format($spot->getAverageRating(), 1) }}/5</p>
            </div>
        </div>
    </div>
</div>

<script>
    let map, marker, infoWindow;
    let searchTimeout;
    let facilityMarkers = [];
    let pendingFacility = null;
    let placesService, autocompleteService;
    let googleGeocoder = null;
    let facilityRadiusOverlay = null;
    const districtMapContext = @json($districtMapContext);
    const assignedMunicipalityName = @json($assignedMunicipality?->name);
    const localPlaces = Array.isArray(districtMapContext?.localPlaces) ? districtMapContext.localPlaces : [];
    const barangays = districtMapContext?.barangays || {};
    const FACILITY_RADIUS_METERS = 2000;

    const facilityColors = {
        dining: '#ff6b35',
        gas_station: '#0d6efd',
        restroom: '#20c997'
    };

    const facilityLabels = {
        dining: 'Dining',
        gas_station: 'Gas Station',
        restroom: 'Restroom'
    };

    const initialFacilities = @json($initialFacilities);
    let facilities = Array.isArray(initialFacilities) ? initialFacilities : [];

    function getDistrictBounds() {
        const bounds = districtMapContext?.bounds || {};
        return {
            north: Number(bounds.north ?? 16.12),
            south: Number(bounds.south ?? 15.90),
            east: Number(bounds.east ?? 120.31),
            west: Number(bounds.west ?? 120.12)
        };
    }

    function getDistrictBoundsLiteral() {
        const bounds = getDistrictBounds();
        return new google.maps.LatLngBounds(
            { lat: bounds.south, lng: bounds.west },
            { lat: bounds.north, lng: bounds.east }
        );
    }

    function getSearchSuffix() {
        return districtMapContext?.searchSuffix || 'Pangasinan 2nd District, Pangasinan, Philippines';
    }

    function normalizeSearchText(value) {
        return String(value ?? '').trim().toLowerCase();
    }

    function getSelectedMunicipalityName() {
        const municipalitySelect = document.getElementById('municipality_id');
        const selectedMunicipality = municipalitySelect?.selectedOptions?.[0]?.textContent?.trim() || '';
        return selectedMunicipality || assignedMunicipalityName || '';
    }

    function getAllBarangays() {
        return Object.entries(barangays).flatMap(([municipalityName, barangayList]) => {
            if (!Array.isArray(barangayList)) {
                return [];
            }

            return barangayList.map((barangay) => ({
                barangay,
                municipalityName,
            }));
        });
    }

    function normalizeLocalPlace(place) {
        const latitude = Number(place.latitude ?? place.lat);
        const longitude = Number(place.longitude ?? place.lng);
        return {
            type: 'local',
            name: place.name || 'Local place',
            subtitle: place.subtitle || '',
            latitude,
            longitude,
            source: place.source_label || 'Local place',
            sourcePriority: 3,
            search_text: place.search_text || `${place.name || ''} ${place.subtitle || ''}`,
        };
    }

    function getResultSearchText(result) {
        return normalizeSearchText(
            result.search_text
            || result.subtitle
            || result.display_name
            || result.name
        );
    }

    function scoreSuggestion(result, normalizedQuery, tokens) {
        const searchText = getResultSearchText(result);
        let score = Number(result.sourcePriority ?? 0);

        if (!searchText) {
            return score;
        }

        if (searchText.includes(normalizedQuery)) {
            score += 8;
        }

        tokens.forEach((token) => {
            if (searchText.includes(token)) {
                score += 2;
            }
        });

        if (normalizeSearchText(result.source).includes('municipality')) {
            score += 1;
        }

        if (normalizeSearchText(result.source).includes('google')) {
            score += 1;
        }

        return score;
    }

    function dedupeSuggestions(items) {
        const seen = new Set();
        const results = [];

        items.forEach((item) => {
            const key = item.placeId
                ? `google:${item.placeId}`
                : `${normalizeSearchText(item.name)}|${normalizeSearchText(item.subtitle)}|${normalizeSearchText(item.source)}|${Number(item.latitude ?? item.lat ?? 0).toFixed(4)}|${Number(item.longitude ?? item.lng ?? 0).toFixed(4)}`;

            if (seen.has(key)) {
                return;
            }

            seen.add(key);
            results.push(item);
        });

        return results;
    }

    function buildLocalSuggestions(query) {
        const normalizedQuery = normalizeSearchText(query);
        if (normalizedQuery.length < 3) {
            return [];
        }

        const tokens = normalizedQuery.split(/\s+/).filter((token) => token.length >= 2);
        const barangayResults = [];

        getAllBarangays().forEach(({ barangay, municipalityName }) => {
            const barangayLower = normalizeSearchText(barangay);
            const municipalityLower = normalizeSearchText(municipalityName);
            const matchesQuery = barangayLower.includes(normalizedQuery)
                || municipalityLower.includes(normalizedQuery)
                || tokens.some((token) => barangayLower.includes(token) || municipalityLower.includes(token));

            if (!matchesQuery) {
                return;
            }

            barangayResults.push({
                type: 'barangay',
                name: barangay,
                municipalityName,
                subtitle: `Barangay - ${municipalityName}, Pangasinan 2nd District`,
                latitude: null,
                longitude: null,
                source: 'Barangay',
                sourcePriority: 4,
                search_text: `${barangayLower} barangay ${municipalityLower} pangasinan 2nd district`,
            });
        });

        return localPlaces
            .map(normalizeLocalPlace)
            .filter((place) => {
                if (!Number.isFinite(place.latitude) || !Number.isFinite(place.longitude)) {
                    return false;
                }

                const searchText = getResultSearchText(place);
                return searchText.includes(normalizedQuery) || tokens.some((token) => searchText.includes(token));
            })
            .concat(barangayResults)
            .sort((a, b) => scoreSuggestion(b, normalizedQuery, tokens) - scoreSuggestion(a, normalizedQuery, tokens))
            .slice(0, 8);
    }

    function getSpotLabel() {
        const nameInput = document.getElementById('name');
        const value = nameInput ? nameInput.value.trim() : '';
        return value || 'Tourist Spot';
    }

    function initGoogleSearchServices() {
        if (!window.google || !google.maps || !google.maps.places) {
            return false;
        }

        autocompleteService = new google.maps.places.AutocompleteService();
        googleGeocoder = new google.maps.Geocoder();
        return true;
    }

    function geocodePlaceId(placeId) {
        return new Promise((resolve, reject) => {
            if (!googleGeocoder || !placeId) {
                reject(new Error('Google geocoder is not available.'));
                return;
            }

            googleGeocoder.geocode({ placeId }, function(results, status) {
                if (status === 'OK' && Array.isArray(results) && results[0]) {
                    resolve(results[0]);
                    return;
                }

                reject(new Error(status || 'Geocode failed.'));
            });
        });
    }

    function geocodeLocationQuery(query) {
        const normalizedQuery = String(query ?? '').trim();
        if (!normalizedQuery) {
            return Promise.resolve(null);
        }

        if (googleGeocoder && window.google?.maps) {
            return new Promise((resolve) => {
                googleGeocoder.geocode({
                    address: buildSearchQuery(normalizedQuery)
                }, function(results, status) {
                    if (status === 'OK' && Array.isArray(results) && results[0]?.geometry?.location) {
                        resolve(results[0]);
                        return;
                    }

                    resolve(null);
                });
            });
        }

        return Promise.resolve(null);
    }

    function updateMarkerLabel() {
        if (!marker) return;
        const label = getSpotLabel();
        marker.setTitle(label);
        marker.setLabel({
            text: label,
            color: '#1f2937',
            fontSize: '12px',
            fontWeight: '600'
        });
    }

    function ensureMarker(lat, lng) {
        if (marker) {
            marker.setPosition({ lat: lat, lng: lng });
        } else {
            marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: getSpotLabel(),
                draggable: true
            });

            marker.addListener('dragend', function() {
                const newLat = marker.getPosition().lat();
                const newLng = marker.getPosition().lng();
                updateCoordinates(newLat, newLng);
                updateMarkerLabel();
                refreshFacilityRadiusOverlay();
            });
        }

        updateMarkerLabel();
        refreshFacilityRadiusOverlay();
    }

    function getCurrentSpotCoordinates() {
        const latInput = Number(document.getElementById('latitude')?.value);
        const lngInput = Number(document.getElementById('longitude')?.value);
        if (Number.isFinite(latInput) && Number.isFinite(lngInput)) {
            return { lat: latInput, lng: lngInput };
        }

        if (marker?.getPosition) {
            const position = marker.getPosition();
            if (position) {
                return { lat: position.lat(), lng: position.lng() };
            }
        }

        return null;
    }

    function calculateDistanceMeters(lat1, lng1, lat2, lng2) {
        const earthRadius = 6371000;
        const deltaLat = (lat2 - lat1) * Math.PI / 180;
        const deltaLng = (lng2 - lng1) * Math.PI / 180;
        const lat1Rad = lat1 * Math.PI / 180;
        const lat2Rad = lat2 * Math.PI / 180;

        const a = Math.sin(deltaLat / 2) ** 2
            + Math.cos(lat1Rad) * Math.cos(lat2Rad) * Math.sin(deltaLng / 2) ** 2;

        return earthRadius * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    }

    function refreshFacilityRadiusOverlay() {
        const center = getCurrentSpotCoordinates();
        if (!center || !map) {
            if (facilityRadiusOverlay) {
                facilityRadiusOverlay.setMap(null);
                facilityRadiusOverlay = null;
            }
            return;
        }

        if (!facilityRadiusOverlay) {
            facilityRadiusOverlay = new google.maps.Circle({
                map,
                center,
                radius: FACILITY_RADIUS_METERS,
                fillColor: '#0d6efd',
                fillOpacity: 0.10,
                strokeColor: '#0d6efd',
                strokeOpacity: 0.45,
                strokeWeight: 2,
                clickable: false
            });
            return;
        }

        facilityRadiusOverlay.setCenter(center);
        facilityRadiusOverlay.setRadius(FACILITY_RADIUS_METERS);
    }

    function getFacilityRadiusStatus(lat, lng) {
        const center = getCurrentSpotCoordinates();
        if (!center) {
            return {
                allowed: false,
                message: 'Select the main spot location first before adding nearby facilities.'
            };
        }

        const distanceMeters = calculateDistanceMeters(center.lat, center.lng, lat, lng);
        return {
            allowed: distanceMeters <= FACILITY_RADIUS_METERS,
            distanceMeters,
            message: distanceMeters <= FACILITY_RADIUS_METERS
                ? ''
                : `That facility is ${(distanceMeters / 1000).toFixed(2)} km away, which exceeds the 2 km radius.`
        };
    }

    function updateCoordinates(lat, lng) {
        document.getElementById('latitude').value = lat.toFixed(6);
        document.getElementById('longitude').value = lng.toFixed(6);
    }

    function buildSearchQuery(query) {
        const municipalitySelect = document.getElementById('municipality_id');
        const selectedMunicipality = municipalitySelect?.selectedOptions?.[0]?.textContent?.trim() || '';
        const municipalityName = selectedMunicipality || assignedMunicipalityName || '';
        const parts = [query.trim()];

        if (municipalityName) {
            parts.push(municipalityName);
        }

        parts.push(getSearchSuffix());

        return parts.filter(Boolean).join(', ');
    }

    function makeAutocompleteRequest(query, useRestriction = true) {
        const request = {
            input: buildSearchQuery(query),
            componentRestrictions: { country: 'ph' },
            types: ['establishment', 'geocode', 'street_address']
        };

        if (useRestriction) {
            request.bounds = getDistrictBoundsLiteral();
            request.strictBounds = true;
        }

        return request;
    }

    function filterLocalPredictions(predictions) {
        if (!Array.isArray(predictions) || predictions.length === 0) {
            return [];
        }

        const terms = [
            ...(districtMapContext?.municipalities || []),
            'pangasinan 2nd district',
            'pangasinan'
        ].map((term) => String(term).toLowerCase());

        const filtered = predictions.filter((prediction) => {
            const description = String(prediction.description || '').toLowerCase();
            return terms.some((term) => description.includes(term));
        });

        return filtered.length > 0 ? filtered : predictions;
    }

    function initMap() {
        const mapLat = {{ $spot->latitude ?? 16.02 }};
        const mapLng = {{ $spot->longitude ?? 120.24 }};

        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 14,
            center: { lat: mapLat, lng: mapLng },
            mapTypeControl: true,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        });

        placesService = new google.maps.places.PlacesService(map);
        autocompleteService = new google.maps.places.AutocompleteService();
        infoWindow = new google.maps.InfoWindow();

        @if($spot)
            marker = new google.maps.Marker({
                position: { lat: {{ $spot->latitude }}, lng: {{ $spot->longitude }} },
                map: map,
                title: '{{ $spot->name }}',
                draggable: true
            });

            marker.addListener('dragend', function() {
                const lat = marker.getPosition().lat();
                const lng = marker.getPosition().lng();
                updateCoordinates(lat, lng);
                updateMarkerLabel();
                refreshFacilityRadiusOverlay();
            });
            updateMarkerLabel();
            refreshFacilityRadiusOverlay();
        @endif

        refreshFacilityMarkers();

        map.addListener('click', function(e) {
            const lat = e.latLng.lat();
            const lng = e.latLng.lng();

            if (pendingFacility) {
                if (addFacilityMarker(pendingFacility.type, pendingFacility.name, lat, lng)) {
                    pendingFacility = null;
                    document.getElementById('facility-name').value = '';
                    setFacilityHint('Select a type and name, then click the map to drop a marker.');
                }
                return;
            }

            updateCoordinates(lat, lng);
            ensureMarker(lat, lng);
        });
    }

    function searchLocationAPI(query) {
        const normalizedQuery = String(query ?? '').trim();
        if (normalizedQuery.length < 3) return;

        const localResults = buildLocalSuggestions(normalizedQuery);
        const tokens = normalizeSearchText(normalizedQuery).split(/\s+/).filter((token) => token.length >= 2);

        autocompleteService.getPlacePredictions(makeAutocompleteRequest(normalizedQuery, true), function(predictions, status) {
            const googleResults = (status === google.maps.places.PlacesServiceStatus.OK && Array.isArray(predictions))
                ? predictions.map((prediction) => ({
                    type: 'google',
                    name: prediction.structured_formatting?.main_text || prediction.description || 'Place',
                    subtitle: prediction.structured_formatting?.secondary_text || '',
                    description: prediction.description || '',
                    placeId: prediction.place_id,
                    source: 'Google Places',
                    sourcePriority: 2,
                    search_text: prediction.description || prediction.structured_formatting?.main_text || '',
                }))
                : [];

            const combined = dedupeSuggestions([...localResults, ...googleResults]);
            combined.sort((a, b) => scoreSuggestion(b, normalizeSearchText(normalizedQuery), tokens) - scoreSuggestion(a, normalizeSearchText(normalizedQuery), tokens));

            if (combined.length > 0) {
                displaySuggestions(combined.slice(0, 10));
                return;
            }

            autocompleteService.getPlacePredictions(makeAutocompleteRequest(normalizedQuery, false), function(fallbackPredictions, fallbackStatus) {
                if (fallbackStatus === google.maps.places.PlacesServiceStatus.OK && Array.isArray(fallbackPredictions)) {
                    displaySuggestions(fallbackPredictions.map((prediction) => ({
                        type: 'google',
                        name: prediction.structured_formatting?.main_text || prediction.description || 'Place',
                        subtitle: prediction.structured_formatting?.secondary_text || '',
                        description: prediction.description || '',
                        placeId: prediction.place_id,
                        source: 'Google Places',
                        sourcePriority: 2,
                        search_text: prediction.description || prediction.structured_formatting?.main_text || '',
                    })));
                } else {
                    displaySuggestions(localResults);
                }
            });
        });
    }

    function displaySuggestions(results) {
        const suggestionsBox = document.getElementById('search-suggestions');
        suggestionsBox.innerHTML = '';

        if (!results || results.length === 0) {
            suggestionsBox.innerHTML = '<div class="search-suggestion-item"><span style="color: #999;">No results found</span></div>';
            suggestionsBox.classList.add('show');
            return;
        }

        results.forEach((result) => {
            const div = document.createElement('div');
            div.className = 'search-suggestion-item';
            const resultName = String(result.name || result.main_text || result.display_name || 'Location');
            const resultAddress = String(result.subtitle || result.secondary_text || result.display_name || '').substring(0, 80);
            const resultSource = String(result.source || 'Search result');
            div.innerHTML = '<div class="suggestion-name">' + escapeHtml(resultName) + '</div>' +
                           '<div class="suggestion-address">' + escapeHtml(resultAddress) + '</div>' +
                           (result.type === 'barangay' ? '<div class="suggestion-type-badge">Barangay</div>' : '') +
                           '<div class="suggestion-source">' + escapeHtml(resultSource) + '</div>';
            div.onclick = () => selectSuggestion(result);
            suggestionsBox.appendChild(div);
        });

        suggestionsBox.classList.add('show');
    }

    async function selectSuggestion(result) {
        document.getElementById('location-search').value = result.description || result.name || result.subtitle || '';
        document.getElementById('search-suggestions').classList.remove('show');

        if (result.type === 'barangay') {
            const selectedMunicipality = getSelectedMunicipalityName();
            const barangayContext = result.municipalityName ? `${result.name}, ${result.municipalityName}` : result.name;
            const place = await geocodeLocationQuery(barangayContext);
            if (place?.geometry?.location) {
                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();
                updateCoordinates(lat, lng);
                map.setCenter({ lat: lat, lng: lng });
                map.setZoom(16);
                ensureMarker(lat, lng);
                document.getElementById('location-search').value = result.municipalityName
                    ? `${result.name}, ${result.municipalityName}`
                    : `${result.name}${selectedMunicipality ? `, ${selectedMunicipality}` : ''}`;
                return;
            }
        }

        if (!result.placeId) {
            return;
        }

        const request = {
            placeId: result.placeId,
            fields: ['geometry', 'name', 'formatted_address', 'address_components']
        };

        placesService.getDetails(request, function(place, status) {
            if (status === google.maps.places.PlacesServiceStatus.OK) {
                const lat = place.geometry.location.lat();
                const lng = place.geometry.location.lng();

                updateCoordinates(lat, lng);

                map.setCenter({ lat: lat, lng: lng });
                map.setZoom(17);
                ensureMarker(lat, lng);
            }
        });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    window.addEventListener('load', function() {
        initGoogleSearchServices();
        initMap();
    });

    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category');
        const scheduleSection = document.getElementById('schedule-section');
        const scheduleInputs = scheduleSection
            ? scheduleSection.querySelectorAll('input, select, textarea')
            : [];

        function toggleScheduleFields() {
            if (!categorySelect || !scheduleSection) return;
            const isPark = categorySelect.value === 'parks';
            scheduleSection.classList.toggle('d-none', !isPark);
            scheduleInputs.forEach((input) => {
                input.disabled = !isPark;
            });
        }

        if (categorySelect && scheduleSection) {
            categorySelect.addEventListener('change', toggleScheduleFields);
            toggleScheduleFields();
        }

        const municipalitySelect = document.getElementById('municipality_id');
        if (municipalitySelect) {
            municipalitySelect.addEventListener('change', function() {
                // keep autocomplete results in sync with the selected municipality
                searchLocationAPI(document.getElementById('location-search')?.value || '');
            });
        }

        const searchInput = document.getElementById('location-search');
        const nameInput = document.getElementById('name');
        const form = document.getElementById('tourist-spot-form');

        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value;

            if (query.length >= 3) {
                searchTimeout = setTimeout(() => {
                    searchLocationAPI(query);
                }, 500);
            } else {
                document.getElementById('search-suggestions').classList.remove('show');
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-location-input-group')) {
                document.getElementById('search-suggestions').classList.remove('show');
            }
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });

        if (nameInput) {
            nameInput.addEventListener('input', updateMarkerLabel);
            nameInput.addEventListener('blur', updateMarkerLabel);
        }

        if (form) {
            form.addEventListener('submit', function() {
                if (form.dataset.submitting === 'true') {
                    return;
                }

                form.dataset.submitting = 'true';
                form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                    button.disabled = true;
                });
            });
        }
    });

    function searchLocation() {
        const query = document.getElementById('location-search').value;
        if (query.trim()) {
            searchLocationAPI(query);
        }
    }

    function setFacilityHint(message, tone = 'muted') {
        const hint = document.getElementById('facility-hint');
        if (!hint) return;
        hint.textContent = message;
        hint.className = 'form-text mt-2';
        if (tone === 'primary') {
            hint.classList.add('text-primary');
        } else if (tone === 'danger') {
            hint.classList.add('text-danger');
        }
    }

    function updateFacilityInput() {
        const input = document.getElementById('nearby_facilities');
        if (!input) return;
        input.value = JSON.stringify(facilities);
    }

    function addFacilityMarker(type, name, lat, lng) {
        const radiusStatus = getFacilityRadiusStatus(Number(lat), Number(lng));
        if (!radiusStatus.allowed) {
            setFacilityHint(radiusStatus.message, 'danger');
            return false;
        }

        facilities.push({
            type,
            name,
            latitude: Number(lat.toFixed(6)),
            longitude: Number(lng.toFixed(6))
        });
        updateFacilityInput();
        refreshFacilityMarkers();
        renderFacilityList();
        setFacilityHint(`Facility added within ${(radiusStatus.distanceMeters / 1000).toFixed(2)} km of the main spot marker.`, 'primary');
        return true;
    }

    function refreshFacilityMarkers() {
        facilityMarkers.forEach(m => m.setMap(null));
        facilityMarkers = [];

        facilities.forEach((facility) => {
            const color = facilityColors[facility.type] || '#6c757d';
            const label = facilityLabels[facility.type] || 'Facility';

            const marker = new google.maps.Marker({
                position: { lat: facility.latitude, lng: facility.longitude },
                map: map,
                title: facility.name,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: color,
                    fillOpacity: 0.9,
                    strokeColor: '#ffffff',
                    strokeWeight: 2
                }
            });

            marker.addListener('click', function() {
                infoWindow.setContent('<div style="padding: 8px;">' +
                    '<strong>' + escapeHtml(facility.name) + '</strong><br>' +
                    '<small>' + label + '</small><br>' +
                    '<small>' + facility.latitude.toFixed(6) + ', ' + facility.longitude.toFixed(6) + '</small>' +
                    '</div>');
                infoWindow.open(map, marker);
            });

            facilityMarkers.push(marker);
        });
    }

    function renderFacilityList() {
        const list = document.getElementById('facility-list');
        if (!list) return;
        if (!facilities.length) {
            list.innerHTML = '<div class="text-muted small">No nearby facilities added yet.</div>';
            return;
        }
        list.innerHTML = facilities.map((facility, index) => {
            const color = facilityColors[facility.type] || '#6c757d';
            const label = facilityLabels[facility.type] || 'Facility';
            return '<div class="facility-list-item d-flex align-items-start">' +
                   '<div class="flex-grow-1">' +
                   '<span class="facility-badge" style="background:' + color + ';">' + label + '</span>' +
                   '<strong>' + escapeHtml(facility.name) + '</strong>' +
                   '<div class="text-muted small mt-1">' +
                   facility.latitude.toFixed(6) + ', ' + facility.longitude.toFixed(6) +
                   '</div></div>' +
                   '<button type="button" class="btn btn-sm btn-outline-danger ms-2" data-remove-index="' + index + '">' +
                   'Remove</button></div>';
        }).join('');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const addButton = document.getElementById('facility-add-btn');
        const typeSelect = document.getElementById('facility-type');
        const nameInput = document.getElementById('facility-name');
        const list = document.getElementById('facility-list');

        if (addButton) {
            addButton.addEventListener('click', function() {
                if (!getCurrentSpotCoordinates()) {
                    setFacilityHint('Select the main spot location first before adding nearby facilities.', 'danger');
                    return;
                }
                const type = typeSelect ? typeSelect.value : 'dining';
                const name = nameInput ? nameInput.value.trim() : '';
                if (!name) {
                    setFacilityHint('Enter a facility name before placing it on the map.', 'danger');
                    if (nameInput) nameInput.focus();
                    return;
                }
                pendingFacility = { type, name };
                setFacilityHint('Click the map to place "' + name + '" (' + facilityLabels[type] + ').', 'primary');
            });
        }

        if (list) {
            list.addEventListener('click', function(event) {
                const target = event.target.closest('[data-remove-index]');
                if (!target) return;
                const index = Number(target.getAttribute('data-remove-index'));
                if (Number.isNaN(index)) return;
                facilities.splice(index, 1);
                updateFacilityInput();
                refreshFacilityMarkers();
                renderFacilityList();
            });
        }

        updateFacilityInput();
        renderFacilityList();
    });

    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('name');
        const feedbackDiv = document.getElementById('name-validation-feedback');

        const namePattern = /^[a-zA-Z0-9\s\-.,&()\']*$/;

        nameInput.addEventListener('input', function() {
            const value = this.value.trim();
            const feedback = feedbackDiv;

            feedback.innerHTML = '';
            feedback.className = 'mt-2';

            if (value.length === 0) {
                this.classList.remove('is-invalid', 'is-valid');
                return;
            }

            if (value.length < 3) {
                feedback.innerHTML = '<small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Name must be at least 3 characters (current: ' + value.length + ')</small>';
                feedback.className = 'mt-2 text-warning';
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
                return;
            }

            if (value.length > 255) {
                feedback.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle"></i> Name cannot exceed 255 characters (current: ' + value.length + ')</small>';
                feedback.className = 'mt-2 text-danger';
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
                return;
            }

            if (!namePattern.test(value)) {
                feedback.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle"></i> Name contains invalid characters. Only letters, numbers, spaces, and - . , & \' are allowed.</small>';
                feedback.className = 'mt-2 text-danger';
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
                return;
            }

            feedback.innerHTML = '';
            feedback.className = 'mt-2';
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        });

        nameInput.addEventListener('blur', function() {
            this.value = this.value.trim();
            this.dispatchEvent(new Event('input'));
        });
    });
</script>

@endsection
