@extends('layouts.app')

@section('title', 'Add Tourist Spot')
@section('header', 'Add New Tourist Spot')

@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@if(env('GOOGLE_MAPS_API_KEY'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>
@endif

<style>
    #map {
        height: 400px;
        border-radius: 8px;
        margin-top: 10px;
        border: 2px solid #dee2e6;
    }
    .spot-marker-tooltip {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(13, 110, 253, 0.25);
        color: #1f2937;
        font-weight: 600;
        padding: 4px 8px;
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
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
    .suggestion-source {
        margin-top: 2px;
        font-size: 0.72rem;
        color: #2563eb;
        text-transform: uppercase;
        letter-spacing: 0.04em;
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
    $initialFacilities = $oldFacilities ? json_decode($oldFacilities, true) : [];
    if (!is_array($initialFacilities)) {
        $initialFacilities = [];
    }
    $selectedCategory = old('category', 'nature');
    $showScheduleFields = $selectedCategory === 'parks';
    $cancelUrl = auth()->user()->isMunicipalityAdmin()
        ? route('municipality-admin.tourist-spots')
        : (auth()->user()->isSuperAdmin() ? route('super-admin.tourist-spots') : route('tourist_spots.index'));
@endphp

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                @if(auth()->user()->isSuperAdmin())
                    <div class="alert alert-info">
                        Spots created here will be published immediately for public use.
                    </div>
                @endif
                <form id="tourist-spot-form" action="{{ route('tourist_spots.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

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
                                    <option value="{{ $municipality->id }}" @selected(old('municipality_id') == $municipality->id)>
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
                                <option value="{{ $value }}" @selected(old('category', 'nature') === $value)>
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
                            value="{{ old('name') }}" 
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
                                    placeholder="Search places, barangays, or landmarks in Pangasinan 2nd District..."
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

                    <!-- Map -->
                    <div id="map"></div>

                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}" required>
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}" required>
                    <input type="hidden" id="address" name="address" value="{{ old('address') }}" required>
                    <input type="hidden" id="nearby_facilities" name="nearby_facilities" value="">
                    @if($errors->has('latitude') || $errors->has('longitude'))
                        <div class="text-danger small mt-2">
                            Please select a valid location on the map.
                        </div>
                    @endif
                    <div class="mt-3">
                        <label class="form-label">Address</label>
                        <div id="resolved-address-preview" class="form-control bg-light" style="min-height: 42px; line-height: 1.4;">
                            {{ old('address') ?: 'Select a location to automatically fill the address.' }}
                        </div>
                    </div>

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
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="images" class="form-label">Spot Images</label>
                        <input type="file" class="form-control @error('images') is-invalid @enderror" id="images" name="images[]" accept="image/*" multiple>
                        @error('images')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Upload up to 5 JPG, PNG, or WebP images (max 2MB each). The first image becomes the cover image.</div>
                    </div>

                    <div id="schedule-section" class="{{ $showScheduleFields ? '' : 'd-none' }}">
                        @php
                            $selectedDays = old('opening_days', []);
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
                            @error('opening_days.*')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Schedule fields apply to parks only.</div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="opening_time" class="form-label">Opening Time</label>
                                <input type="time" class="form-control schedule-input @error('opening_time') is-invalid @enderror" id="opening_time" name="opening_time" value="{{ old('opening_time') }}">
                                @error('opening_time')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="closing_time" class="form-label">Closing Time</label>
                                <input type="time" class="form-control schedule-input @error('closing_time') is-invalid @enderror" id="closing_time" name="closing_time" value="{{ old('closing_time') }}">
                                @error('closing_time')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-text">If you set time, both opening and closing must be filled.</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Spot
                        </button>
                        <a href="{{ $cancelUrl }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
    let map, marker;
    let searchTimeout;
    let facilityMarkers = [];
    let pendingFacility = null;
    let googleAutocompleteService = null;
    let googleGeocoder = null;
    let spotLabelInfoWindow = null;
    let useGoogleMaps = false;
    let facilityRadiusOverlay = null;
    let addressResolveRequestId = 0;
    let bypassAddressSubmission = false;
    const FACILITY_RADIUS_METERS = 2000;
    const districtMapContext = @json($districtMapContext);
    const assignedMunicipalityName = @json($assignedMunicipality?->name);
    const localPlaces = Array.isArray(districtMapContext?.localPlaces) ? districtMapContext.localPlaces : [];
    const barangays = districtMapContext?.barangays || {};
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
        return {
            north: bounds.north,
            south: bounds.south,
            east: bounds.east,
            west: bounds.west
        };
    }

    function focusMap(lat, lng, zoom) {
        if (!map) {
            return;
        }

        if (useGoogleMaps && typeof map.setCenter === 'function') {
            map.setCenter({ lat, lng });
            if (Number.isFinite(Number(zoom))) {
                map.setZoom(Number(zoom));
            }
            return;
        }

        if (typeof map.setView === 'function') {
            map.setView([lat, lng], Number.isFinite(Number(zoom)) ? Number(zoom) : undefined);
        }
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

        return searchNominatimSuggestions(normalizedQuery).then((results) => {
            const first = Array.isArray(results) ? results[0] : null;
            if (!first || !Number.isFinite(Number(first.latitude)) || !Number.isFinite(Number(first.longitude))) {
                return null;
            }

            return {
                geometry: {
                    location: {
                        lat: () => Number(first.latitude),
                        lng: () => Number(first.longitude)
                    }
                },
                formatted_address: first.subtitle || first.name || normalizedQuery
            };
        });
    }

    function extractBarangayFromAddress(address, municipalityName) {
        if (!address || !municipalityName || !barangays[municipalityName]) {
            return null;
        }

        const addressLower = normalizeSearchText(address);
        const municipalityBarangays = barangays[municipalityName] || [];

        for (const barangay of municipalityBarangays) {
            const barangayLower = normalizeSearchText(barangay);
            if (addressLower.includes(barangayLower)) {
                return barangay;
            }
        }

        return null;
    }

    function extractStreetNumber(address) {
        if (!address) return null;

        const patterns = [
            /(?:street|st|road|rd|avenue|ave|blvd|boulevard|lane|ln|drive|dr|court|ct|circle|cir|plaza|pl)\s+#?\s*(\d+[a-z]?)/i,
            /^\s*#?\s*(\d+[a-z]?)\s+/i,
            /(\d+[a-z]?)\s+(?:street|st|road|rd|avenue|ave|blvd|boulevard|lane|ln|drive|dr|court|ct|circle|cir|plaza|pl)/i,
            /#\s*(\d+[a-z]?)/i,
            /(?:lot|block)\s+#?\s*(\d+[a-z]?)/i,
        ];

        for (const pattern of patterns) {
            const match = address.match(pattern);
            if (match && match[1]) {
                return match[1];
            }
        }

        return null;
    }

    function enhanceAddressWithDetails(address, municipalityName) {
        const barangay = extractBarangayFromAddress(address, municipalityName);
        const streetNumber = extractStreetNumber(address);
        const details = [];

        if (streetNumber) {
            details.push(`Street #${streetNumber}`);
        }

        if (barangay) {
            details.push(`Barangay ${barangay}`);
        }

        if (details.length > 0) {
            return `${address} (${details.join(', ')})`;
        }

        return address;
    }

    function extractAddressDetails(address, municipalityName) {
        return {
            streetNumber: extractStreetNumber(address),
            barangay: extractBarangayFromAddress(address, municipalityName),
            fullAddress: address
        };
    }

    function normalizeSearchText(value) {
        return String(value ?? '').trim().toLowerCase();
    }

    function initGoogleSearchServices() {
        if (!window.google || !google.maps || !google.maps.places) {
            return false;
        }

        googleAutocompleteService = new google.maps.places.AutocompleteService();
        googleGeocoder = new google.maps.Geocoder();
        return true;
    }

    function getSpotLabel() {
        const nameInput = document.getElementById('name');
        const value = nameInput ? nameInput.value.trim() : '';
        return value || 'Tourist Spot';
    }

    function updateMarkerLabel() {
        if (!marker) return;
        const label = getSpotLabel();
        if (useGoogleMaps && window.google?.maps) {
            marker.setTitle(label);
            if (!spotLabelInfoWindow) {
                spotLabelInfoWindow = new google.maps.InfoWindow({
                    disableAutoPan: true
                });
            }

            spotLabelInfoWindow.setContent(`<div style="font-weight: 600;">${escapeHtml(label)}</div>`);
            spotLabelInfoWindow.open({
                map,
                anchor: marker,
                shouldFocus: false
            });
            return;
        }

        if (typeof marker.bindTooltip === 'function') {
            marker.bindTooltip(label, {
                permanent: true,
                direction: 'top',
                offset: [0, -10],
                className: 'spot-marker-tooltip'
            }).openTooltip();
        }
    }

    function ensureMarker(lat, lng) {
        if (useGoogleMaps && window.google?.maps) {
            if (marker) {
                marker.setPosition({ lat, lng });
            } else {
                marker = new google.maps.Marker({
                    position: { lat, lng },
                    map,
                    draggable: true,
                    title: getSpotLabel()
                });

                marker.addListener('dragend', function() {
                    const position = marker.getPosition();
                    if (!position) {
                        return;
                    }

                    updateCoordinates(position.lat(), position.lng());
                    updateMarkerLabel();
                    refreshFacilityRadiusOverlay();
                    syncAddressForCoordinates(position.lat(), position.lng());
                });
            }

            updateMarkerLabel();
            refreshFacilityRadiusOverlay();
            return;
        }

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { riseOnHover: true }).addTo(map);
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

        if (useGoogleMaps && marker?.getPosition) {
            const position = marker.getPosition();
            if (position) {
                return { lat: position.lat(), lng: position.lng() };
            }
        }

        if (marker?.getLatLng) {
            const position = marker.getLatLng();
            if (position) {
                return { lat: position.lat, lng: position.lng };
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
                if (useGoogleMaps && typeof facilityRadiusOverlay.setMap === 'function') {
                    facilityRadiusOverlay.setMap(null);
                } else if (typeof facilityRadiusOverlay.remove === 'function') {
                    facilityRadiusOverlay.remove();
                }
                facilityRadiusOverlay = null;
            }
            return;
        }

        if (useGoogleMaps && window.google?.maps) {
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
            } else {
                facilityRadiusOverlay.setCenter(center);
                facilityRadiusOverlay.setRadius(FACILITY_RADIUS_METERS);
            }
            return;
        }

        if (facilityRadiusOverlay && typeof facilityRadiusOverlay.remove === 'function') {
            facilityRadiusOverlay.remove();
        }

        facilityRadiusOverlay = L.circle([center.lat, center.lng], {
            radius: FACILITY_RADIUS_METERS,
            color: '#0d6efd',
            weight: 2,
            opacity: 0.45,
            fillColor: '#0d6efd',
            fillOpacity: 0.10
        }).addTo(map);
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
                : `That facility is ${ (distanceMeters / 1000).toFixed(2) } km away, which exceeds the 2 km radius.`
        };
    }

    function updateCoordinates(lat, lng) {
        document.getElementById('latitude').value = lat.toFixed(6);
        document.getElementById('longitude').value = lng.toFixed(6);
    }

    function setResolvedAddress(address) {
        const input = document.getElementById('address');
        const preview = document.getElementById('resolved-address-preview');
        const value = String(address ?? '').trim();

        if (input) {
            input.value = value;
        }

        if (preview) {
            preview.textContent = value || 'Select a location to automatically resolve the exact address.';
        }
    }

    function setResolvedAddressPreview(message) {
        const preview = document.getElementById('resolved-address-preview');
        if (preview) {
            preview.textContent = String(message ?? '').trim() || 'Select a location to automatically resolve the exact address.';
        }
    }

    function resolveAddressFromCoordinates(lat, lng) {
        return new Promise((resolve) => {
            if (googleGeocoder && window.google?.maps) {
                googleGeocoder.geocode({
                    location: { lat, lng }
                }, function(results, status) {
                    if (status === 'OK' && Array.isArray(results) && results[0]?.formatted_address) {
                        resolve(results[0].formatted_address);
                        return;
                    }

                    resolve('');
                });
                return;
            }

            const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=18&addressdetails=1`;
            fetch(url)
                .then((response) => response.json())
                .then((data) => {
                    resolve(String(data?.display_name || '').trim());
                })
                .catch(() => resolve(''));
        });
    }

    async function syncAddressForCoordinates(lat, lng, fallbackAddress = '') {
        const requestId = ++addressResolveRequestId;
        setResolvedAddressPreview('Resolving exact address...');
        const resolved = await resolveAddressFromCoordinates(lat, lng);
        if (requestId !== addressResolveRequestId) {
            return;
        }

        setResolvedAddress(resolved || fallbackAddress || `${lat.toFixed(6)}, ${lng.toFixed(6)}`);
    }

    // Initialize map
    function initMap() {
        const bounds = getDistrictBounds();
        const districtCenter = districtMapContext?.center || { lat: 16.02, lng: 120.24 };
        useGoogleMaps = Boolean(window.google?.maps);

        if (useGoogleMaps) {
            map = new google.maps.Map(document.getElementById('map'), {
                scrollwheel: false,
                center: districtCenter,
                zoom: 11,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true
            });

            refreshFacilityMarkers();
            map.fitBounds(getDistrictBoundsLiteral());

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
                syncAddressForCoordinates(lat, lng);
            });
        } else {
            map = L.map('map', { scrollWheelZoom: false }).setView([districtCenter.lat, districtCenter.lng], 11);

            const streetLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '© OpenStreetMap contributors © CARTO',
                subdomains: 'abcd',
                maxZoom: 20
            });

            const imageryLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community',
                maxZoom: 20
            });

            const labelLayer = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Tiles © Esri',
                maxZoom: 20
            });

            streetLayer.addTo(map);
            labelLayer.addTo(map);

            L.control.layers(
                {
                    'Detailed Streets': streetLayer,
                    'Satellite': imageryLayer
                },
                {
                    'Labels': labelLayer
                }
            ).addTo(map);
            facilityMarkers = [];
            refreshFacilityMarkers();

            const districtBounds = L.latLngBounds(
                [bounds.south, bounds.west],
                [bounds.north, bounds.east]
            );
            map.fitBounds(districtBounds, { padding: [20, 20] });

            // Click on map to add marker
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

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
                syncAddressForCoordinates(lat, lng);
            });
        }

        const nameInput = document.getElementById('name');
        if (nameInput) {
            nameInput.addEventListener('input', updateMarkerLabel);
            nameInput.addEventListener('blur', updateMarkerLabel);
        }
    }
    
    function buildSearchQuery(query) {
        const trimmed = query.trim();
        const municipalitySelect = document.getElementById('municipality_id');
        const selectedMunicipality = municipalitySelect?.selectedOptions?.[0]?.textContent?.trim() || '';
        const municipalityName = selectedMunicipality || assignedMunicipalityName || '';
        const parts = [trimmed];

        if (municipalityName) {
            parts.push(municipalityName);
        }

        parts.push(getSearchSuffix());

        return parts.filter(Boolean).join(', ');
    }

    function buildNominatimUrl(query, bounded = true) {
        const bounds = getDistrictBounds();
        const params = new URLSearchParams({
            format: 'jsonv2',
            q: buildSearchQuery(query),
            limit: '10',
            addressdetails: '1',
            namedetails: '1',
            countrycodes: 'ph',
            'accept-language': 'en'
        });

        if (bounded) {
            params.set('viewbox', `${bounds.west},${bounds.north},${bounds.east},${bounds.south}`);
            params.set('bounded', '1');
        }

        return `https://nominatim.openstreetmap.org/search?${params.toString()}`;
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

    function buildLocalSuggestions(query) {
        const normalizedQuery = normalizeSearchText(query);
        if (normalizedQuery.length < 1) {
            return [];
        }

        const tokens = normalizedQuery.split(/\s+/).filter((token) => token.length >= 1);
        const barangayResults = [];

        getAllBarangays().forEach(({ barangay, municipalityName }) => {
            const barangayLower = normalizeSearchText(barangay);
            const municipalityLower = normalizeSearchText(municipalityName);
            const matchesQuery = barangayLower.startsWith(normalizedQuery)
                || barangayLower.includes(normalizedQuery)
                || municipalityLower.startsWith(normalizedQuery)
                || municipalityLower.includes(normalizedQuery)
                || tokens.some((token) => barangayLower.startsWith(token) || barangayLower.includes(token) || municipalityLower.startsWith(token) || municipalityLower.includes(token));

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

    function searchGoogleSuggestions(query) {
        return new Promise((resolve) => {
            if (!googleAutocompleteService || !window.google?.maps?.places) {
                resolve([]);
                return;
            }

            googleAutocompleteService.getPlacePredictions({
                input: buildSearchQuery(query),
                componentRestrictions: { country: 'ph' },
                types: ['geocode']
            }, function(predictions, status) {
                if (status !== google.maps.places.PlacesServiceStatus.OK || !Array.isArray(predictions)) {
                    resolve([]);
                    return;
                }

                resolve(predictions.map((prediction) => ({
                    type: 'google',
                    name: prediction.structured_formatting?.main_text || prediction.description || 'Place',
                    subtitle: prediction.structured_formatting?.secondary_text || '',
                    placeId: prediction.place_id,
                    source: 'Google Places',
                    sourcePriority: 2,
                    search_text: prediction.description || prediction.structured_formatting?.main_text || '',
                })));
            });
        });
    }

    function searchNominatimSuggestions(query) {
        const normalizedQuery = query.trim();
        if (normalizedQuery.length < 3) {
            return Promise.resolve([]);
        }

        return fetch(buildNominatimUrl(normalizedQuery, true))
            .then((response) => response.json())
            .then((results) => {
                const boundedResults = Array.isArray(results) ? results : [];
                if (boundedResults.length > 0) {
                    return boundedResults;
                }

                return fetch(buildNominatimUrl(normalizedQuery, false))
                    .then((response) => response.json())
                    .then((fallbackResults) => Array.isArray(fallbackResults) ? fallbackResults : []);
            })
            .then((results) => results.map((result) => ({
                type: 'nominatim',
                name: result.name || result.display_name || 'Location',
                subtitle: result.display_name || '',
                latitude: Number(result.lat),
                longitude: Number(result.lon),
                source: 'Nominatim',
                sourcePriority: 1,
                search_text: result.display_name || result.name || '',
            })))
            .catch(() => []);
    }

    function searchLocationAPI(query) {
        const normalizedQuery = query.trim();
        if (normalizedQuery.length < 3) return;

        Promise.all([
            Promise.resolve(buildLocalSuggestions(normalizedQuery)),
            searchGoogleSuggestions(normalizedQuery)
        ]).then(([localResults, googleResults]) => {
            const primaryResults = dedupeSuggestions([
                ...localResults,
                ...googleResults
            ]);

            const tokens = normalizeSearchText(normalizedQuery).split(/\s+/).filter((token) => token.length >= 2);
            primaryResults.sort((a, b) => scoreSuggestion(b, normalizeSearchText(normalizedQuery), tokens) - scoreSuggestion(a, normalizeSearchText(normalizedQuery), tokens));

            if (primaryResults.length > 0) {
                displaySuggestions(primaryResults.slice(0, 10));
                return;
            }

            return searchNominatimSuggestions(normalizedQuery).then((nominatimResults) => {
                const combined = dedupeSuggestions([...localResults, ...nominatimResults]);
                combined.sort((a, b) => scoreSuggestion(b, normalizeSearchText(normalizedQuery), tokens) - scoreSuggestion(a, normalizeSearchText(normalizedQuery), tokens));
                if (combined.length > 0) {
                    displaySuggestions(combined.slice(0, 10));
                    return;
                }

                displaySuggestions(buildLocalSuggestions(normalizedQuery));
            });
        }).catch((error) => {
            console.error('Search error:', error);
            displaySuggestions(buildLocalSuggestions(normalizedQuery));
        });
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
    
    // Display search suggestions
    function displaySuggestions(results) {
        const suggestionsBox = document.getElementById('search-suggestions');
        const selectedMunicipality = getSelectedMunicipalityName();
        suggestionsBox.innerHTML = '';
        
        if (results.length === 0) {
            suggestionsBox.innerHTML = '<div class="search-suggestion-item"><span style="color: #999;">No results found</span></div>';
            suggestionsBox.classList.add('show');
            return;
        }
        
        results.forEach(result => {
            const div = document.createElement('div');
            div.className = 'search-suggestion-item';
            const resultName = String(result.name || result.display_name || 'Location');
            const resultAddress = String(result.subtitle || result.display_name || '').substring(0, 80);
            const resultSource = String(result.source || 'Search result');

            let detailsHtml = '';
            if (result.type !== 'barangay') {
                const details = extractAddressDetails(resultAddress, selectedMunicipality);
                const detailsParts = [];
                if (details.streetNumber) {
                    detailsParts.push(`Street #${escapeHtml(details.streetNumber)}`);
                }
                if (details.barangay) {
                    detailsParts.push(`Barangay ${escapeHtml(details.barangay)}`);
                }
                if (detailsParts.length > 0) {
                    detailsHtml = `<div class="suggestion-address" style="font-size: 0.8rem; color: #0d6efd; margin-top: 2px;">${detailsParts.join(' • ')}</div>`;
                }
            }

            div.innerHTML = `
                <div class="suggestion-name">${escapeHtml(resultName)}</div>
                <div class="suggestion-address">${escapeHtml(resultAddress)}${resultAddress.length >= 80 ? '...' : ''}</div>
                ${result.type === 'barangay' ? '<div class="suggestion-type-badge">Barangay</div>' : ''}
                ${detailsHtml}
                <div class="suggestion-source">${escapeHtml(resultSource)}</div>
            `;
            div.onclick = () => selectSuggestion(result);
            suggestionsBox.appendChild(div);
        });
        
        suggestionsBox.classList.add('show');
    }
    
    // Select a suggestion
    async function selectSuggestion(result) {
        document.getElementById('search-suggestions').classList.remove('show');
        const selectedMunicipality = getSelectedMunicipalityName();

        if (result.type === 'google' && googleGeocoder && result.placeId) {
            try {
                const place = await geocodePlaceId(result.placeId);
                if (place) {
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();
                    const enhancedAddress = enhanceAddressWithDetails(place.formatted_address, selectedMunicipality);
                    document.getElementById('location-search').value = place.formatted_address || result.name || '';
                    updateCoordinates(lat, lng);
                    focusMap(lat, lng, 17);
                    ensureMarker(lat, lng);
                    setResolvedAddress(enhancedAddress);
                    return;
                }
            } catch (error) {
                console.error('Google geocode error:', error);
            }
        }

        if (result.type === 'barangay') {
            const barangayContext = result.municipalityName ? `${result.name}, ${result.municipalityName}` : result.name;
            const geocoded = await geocodeLocationQuery(barangayContext);
            if (geocoded?.geometry?.location) {
                const lat = geocoded.geometry.location.lat();
                const lng = geocoded.geometry.location.lng();
                updateCoordinates(lat, lng);
                const barangayLabel = result.municipalityName
                    ? `${result.name}, ${result.municipalityName}`
                    : `${result.name}${selectedMunicipality ? `, ${selectedMunicipality}` : ''}`;
                document.getElementById('location-search').value = barangayLabel;
                focusMap(lat, lng, 16);
                ensureMarker(lat, lng);
                const resolvedAddress = geocoded.formatted_address || barangayLabel;
                syncAddressForCoordinates(lat, lng, resolvedAddress);
                return;
            }
        }

        const lat = Number(result.latitude ?? result.lat);
        const lng = Number(result.longitude ?? result.lon);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            return;
        }

        updateCoordinates(lat, lng);
        document.getElementById('location-search').value = result.name || result.subtitle || '';
        
        // Update map
        focusMap(lat, lng, 17);
        ensureMarker(lat, lng);
        const enhancedAddress = enhanceAddressWithDetails(result.subtitle || result.name || '', selectedMunicipality);
        syncAddressForCoordinates(lat, lng, enhancedAddress);
    }
    
    // Search location function
    function searchLocation() {
        const query = document.getElementById('location-search').value;
        if (query.trim()) {
            searchLocationAPI(query);
        }
    }
    
    // Real-time search as user types
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

        const searchInput = document.getElementById('location-search');
        const nameInput = document.getElementById('name');
        const form = document.getElementById('tourist-spot-form');
        
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value;
            
            if (query.length >= 3) {
                searchTimeout = setTimeout(() => {
                    searchLocationAPI(query);
                }, 500); // Debounce search by 500ms
            } else {
                document.getElementById('search-suggestions').classList.remove('show');
            }
        });
        
        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-location-input-group')) {
                document.getElementById('search-suggestions').classList.remove('show');
            }
        });
        
        // Allow Enter key to search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchLocation();
            }
        });

        if (nameInput) {
            nameInput.addEventListener('input', updateMarkerLabel);
        }

        function lockFormSubmission(targetForm) {
            if (!targetForm || targetForm.dataset.submitting === 'true') {
                return false;
            }

            targetForm.dataset.submitting = 'true';
            targetForm.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                button.disabled = true;
            });

            return true;
        }

        if (form) {
            form.addEventListener('submit', async function(e) {
                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return;
                }

                if (bypassAddressSubmission) {
                    bypassAddressSubmission = false;
                    lockFormSubmission(form);
                    return;
                }

                const addressInput = document.getElementById('address');
                const lat = Number(document.getElementById('latitude')?.value);
                const lng = Number(document.getElementById('longitude')?.value);

                if (addressInput && addressInput.value.trim() === '' && Number.isFinite(lat) && Number.isFinite(lng)) {
                    e.preventDefault();
                    await syncAddressForCoordinates(lat, lng);
                    bypassAddressSubmission = true;
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                    return;
                }

                lockFormSubmission(form);
            });
        }

    });
    
    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text ?? '').replace(/[&<>"']/g, m => map[m]);
    }
    
    // Initialize on load
    window.addEventListener('load', function() {
        initGoogleSearchServices();
        initMap();
        const initialLat = Number(document.getElementById('latitude')?.value);
        const initialLng = Number(document.getElementById('longitude')?.value);
        if (Number.isFinite(initialLat) && Number.isFinite(initialLng)) {
            ensureMarker(initialLat, initialLng);
            const existingAddress = document.getElementById('address')?.value || '';
            if (existingAddress) {
                setResolvedAddress(existingAddress);
            }
            refreshFacilityRadiusOverlay();
        }
    });

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
        if (useGoogleMaps && window.google?.maps) {
            facilityMarkers.forEach((facilityMarker) => facilityMarker.setMap(null));
            facilityMarkers = [];

            facilities.forEach((facility) => {
                const color = facilityColors[facility.type] || '#6c757d';
                const marker = new google.maps.Marker({
                    position: { lat: Number(facility.latitude), lng: Number(facility.longitude) },
                    map,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: color,
                        fillOpacity: 0.95,
                        strokeColor: '#ffffff',
                        strokeWeight: 2,
                        scale: 7
                    },
                    title: `${facility.name} (${facilityLabels[facility.type] || 'Facility'})`
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `<strong>${escapeHtml(facility.name)}</strong><br>${facilityLabels[facility.type] || 'Facility'}`
                });
                marker.addListener('click', () => infoWindow.open({ map, anchor: marker }));
                facilityMarkers.push(marker);
            });
            return;
        }

        if (!map) return;
        if (!facilityMarkers.length) {
            facilityMarkers = [];
        }
        facilityMarkers.forEach((facilityMarker) => {
            if (typeof facilityMarker.remove === 'function') {
                facilityMarker.remove();
            } else if (typeof map.removeLayer === 'function') {
                map.removeLayer(facilityMarker);
            }
        });
        facilityMarkers = [];
        facilities.forEach((facility) => {
            const color = facilityColors[facility.type] || '#6c757d';
            const marker = L.circleMarker([facility.latitude, facility.longitude], {
                radius: 6,
                color: '#ffffff',
                weight: 2,
                fillColor: color,
                fillOpacity: 0.9
            });
            marker.bindPopup(`<strong>${escapeHtml(facility.name)}</strong><br>${facilityLabels[facility.type] || 'Facility'}`);
            marker.addTo(map);
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
            return `
                <div class="facility-list-item d-flex align-items-start">
                    <div class="flex-grow-1">
                        <span class="facility-badge" style="background:${color};">${label}</span>
                        <strong>${escapeHtml(facility.name)}</strong>
                        <div class="text-muted small mt-1">
                            ${Number(facility.latitude).toFixed(6)}, ${Number(facility.longitude).toFixed(6)}
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-2" data-remove-index="${index}">
                        Remove
                    </button>
                </div>
            `;
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
                setFacilityHint(`Click the map to place "${name}" (${facilityLabels[type]}).`, 'primary');
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
    // Real-time validation for name field
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.getElementById('name');
        const feedbackDiv = document.getElementById('name-validation-feedback');
        
        // Name validation regex pattern
        const namePattern = /^[a-zA-Z0-9\s\-.,&()\']*$/;
        
        nameInput.addEventListener('input', function() {
            const value = this.value.trim();
            const feedback = feedbackDiv;
            
            // Clear previous feedback
            feedback.innerHTML = '';
            feedback.className = 'mt-2';
            
            if (value.length === 0) {
                this.classList.remove('is-invalid', 'is-valid');
                return;
            }
            
            // Check length
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
            
            // Check pattern
            if (!namePattern.test(value)) {
                feedback.innerHTML = '<small class="text-danger"><i class="fas fa-times-circle"></i> Name contains invalid characters. Only letters, numbers, spaces, and - . , & \' are allowed.</small>';
                feedback.className = 'mt-2 text-danger';
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
                return;
            }
            
            // All validations passed
            feedback.innerHTML = '';
            feedback.className = 'mt-2';
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        });
        
        // Validate on blur
        nameInput.addEventListener('blur', function() {
            this.value = this.value.trim();
            this.dispatchEvent(new Event('input'));
        });
    });

</script>

@endsection
