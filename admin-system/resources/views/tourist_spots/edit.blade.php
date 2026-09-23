@extends('layouts.app')

@section('title', 'Edit Tourist Spot')
@section('header', '')

@section('content')
<script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}&libraries=places"></script>

<style>
    #map {
        height: 440px;
        border-radius: 8px;
        margin-top: 10px;
        border: 2px solid #dee2e6;
    }
    .search-location-container {
        margin-bottom: 10px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #b8dcd7;
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
        border-color: #0f766e;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.15);
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
        padding: 10px;
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
    .spot-map-column { position: sticky; top: 1rem; }
    #tourist-spot-form { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .35rem 1.25rem; align-items: start; }
    #tourist-spot-form > h5:first-of-type { grid-column: 2; grid-row: 2; margin-bottom: .25rem !important; }
    #tourist-spot-form > .municipality-field { grid-column: 1; grid-row: 2; }
    #tourist-spot-form > .category-field { grid-column: 1; grid-row: 3; }
    #tourist-spot-form > .name-field { grid-column: 1; grid-row: 4; }
    #tourist-spot-form > h5:nth-of-type(2) { grid-column: 1; grid-row: 5; }
    #tourist-spot-form > .description-field { grid-column: 1; grid-row: 6; }
    #tourist-spot-form > .images-field { grid-column: 1; grid-row: 7; }
    #tourist-spot-form > #schedule-section { grid-column: 1; grid-row: 8; }
    #tourist-spot-form > .status-field { grid-column: 1; grid-row: 9; }
    #tourist-spot-form > .search-location-container { grid-column: 2; grid-row: 3; }
    #tourist-spot-form > #map { grid-column: 2; grid-row: 4; }
    #tourist-spot-form > .map-legend { grid-column: 2; grid-row: 5; }
    #tourist-spot-form > .address-field { grid-column: 2; grid-row: 6; }
    #tourist-spot-form > #map ~ .mt-4 { grid-column: 2; grid-row: 7; }
    #tourist-spot-form > hr { display: none; }
    #tourist-spot-form > .d-flex { grid-column: 1 / -1; grid-row: 10; }
    #tourist-spot-form > input[type="hidden"],
    #tourist-spot-form > .text-danger.small { display: none; }
    #tourist-spot-form > .facility-tools,
    #tourist-spot-form > #facility-list,
    #tourist-spot-form > #facility-count { grid-column: 2; }
    #tourist-spot-form > .facility-tools { margin-top: 0; }
    .municipality-tag { display: inline-flex; align-items: center; gap: .45rem; padding: .55rem .8rem; border-radius: 999px; background: #eef4ff; color: #2453b8; font-weight: 700; }
    .category-chips { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; }
    .category-chip input { position: absolute; opacity: 0; pointer-events: none; }
    .category-chip label { display: flex; align-items: center; gap: .55rem; padding: .75rem .8rem; border: 1px solid #d9e0e8; border-radius: 10px; cursor: pointer; font-weight: 650; background: #fff; }
    .category-chip input:checked + label { border-color: #0f766e; background: #e8f5f2; color: #0f766e; }
    .dropzone { border: 2px dashed #b8c4d3; border-radius: 10px; padding: 1.1rem; text-align: center; background: #f8fafc; cursor: pointer; }
    .dropzone i { color: #0f766e; font-size: 1.8rem; }
    .dropzone.dragover { border-color: #0f766e; background: #effaf8; }
    .upload-previews { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; margin-top: .8rem; }
    .upload-preview { position: relative; }
    .upload-preview img { width: 100%; height: 90px; object-fit: cover; border-radius: 8px; }
    .cover-badge { position: absolute; left: .35rem; bottom: .35rem; font-size: .68rem; }
    .existing-image-remove { position: absolute; top: .35rem; right: .35rem; }
    .existing-image-card { position: relative; }
    .btn-tourism { background: #0f766e; border-color: #0f766e; color: #fff; }
    .btn-tourism:hover { background: #115e59; border-color: #115e59; color: #fff; }
    .map-legend { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: .65rem; font-size: .78rem; color: #4b5563; }
    .map-legend-item { display: inline-flex; align-items: center; gap: .35rem; }
    .map-legend-dot { width: .7rem; height: .7rem; border-radius: 50%; display: inline-block; border: 1px solid #fff; box-shadow: 0 0 0 1px #9ca3af; }
    .map-legend-dot.spot { background: #dc2626; }
    .map-legend-dot.dining { background: #ff6b35; }
    .map-legend-dot.gas { background: #0d6efd; }
    .map-legend-dot.radius { background: rgba(15,118,110,.35); }
    .search-status { min-height: 1.25rem; font-size: .8rem; }
    .status-reason { display: none; }
    .status-reason.show { display: block; }
    #tourist-spot-form {
        display: block;
    }
    #tourist-spot-form > * {
        grid-column: auto !important;
        grid-row: auto !important;
    }
    #tourist-spot-form > hr {
        display: block;
    }
    #tourist-spot-form > h5:first-of-type {
        margin-top: 0;
    }
    .edit-spot-card .alert { margin-bottom: 1rem; padding: .65rem .8rem; }
    .edit-spot-card .mb-3 { margin-bottom: .8rem !important; }
    .edit-spot-card .mb-4 { margin-bottom: 1rem !important; }
    .edit-spot-card h5 { margin-bottom: .8rem !important; }
    .edit-spot-card hr { margin: 1rem 0 !important; }
    .edit-spot-card .form-label { margin-bottom: .3rem; }
    .edit-spot-card .card-body { padding: 1.25rem !important; }
    @media (max-width: 991.98px) {
        .spot-map-column { position: static; }
        #map { height: 380px; }
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
    $isPending = $spot->verification_status === 'pending';
    $isApprovedPublished = $spot->verification_status === 'approved' && in_array($spot->status, ['open', 'active'], true);
    $normalizedStatus = old('status', $spot->status === 'active' ? 'open' : ($spot->status === 'inactive' ? 'closed' : $spot->status));
    $statusReason = old('status_reason', $spot->status_reason);
@endphp

<div class="row">
    <div class="col-12">
        <div class="card edit-spot-card">
            <div class="card-body">
                @if($isPending)
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-1"></i> This spot is still pending Super Admin approval. Changes will not affect its verification status.
                    </div>
                @elseif($isApprovedPublished)
                    <div class="alert alert-warning">
                        <i class="fas fa-triangle-exclamation me-1"></i> This spot is approved and published. Saving changes will update the live public listing immediately.
                    </div>
                @else
                    <div class="alert alert-info">
                        Saving changes will update this tourist spot while keeping its current verification status.
                    </div>
                @endif
                <form id="tourist-spot-form" action="{{ route('tourist_spots.update', $spot->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="return_to" value="{{ $cancelUrl }}">

                    <h5 class="mb-3"><i class="fas fa-map"></i> Location & Map</h5>

                    @if($assignedMunicipality)
                        <div class="mb-3 municipality-field">
                            <label class="form-label">Municipality</label>
                            <div class="municipality-tag">
                                <i class="fas fa-location-dot"></i><strong>{{ $assignedMunicipality->name }}</strong>
                            </div>
                            <input type="hidden" name="municipality_id" value="{{ $assignedMunicipality->id }}">
                        </div>
                    @else
                        <div class="mb-3 municipality-field">
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

                    <div class="mb-3 category-field">
                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                        <div class="category-chips">
                            @php $categoryIcons = ['beach' => 'fa-umbrella-beach', 'parks' => 'fa-tree', 'falls' => 'fa-water', 'nature' => 'fa-leaf', 'resort' => 'fa-person-swimming', 'historical' => 'fa-landmark', 'cultural' => 'fa-masks-theater', 'religious' => 'fa-church']; @endphp
                            @foreach($spotCategories as $value => $label)
                                <div class="category-chip"><input type="radio" id="category-{{ $value }}" name="category" value="{{ $value }}" @checked($selectedCategory === $value) required><label for="category-{{ $value }}"><i class="fas {{ $categoryIcons[$value] ?? 'fa-location-dot' }}"></i>{{ $label }}</label></div>
                            @endforeach
                        </div>
                        @error('category')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 name-field">
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
                            <button type="button" onclick="searchLocation()" class="btn btn-tourism mt-2" id="location-search-button">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <div class="form-text mt-2">You can search by municipality, barangay, landmark, or tourist spot name.</div>
                        <div id="location-search-status" class="search-status text-muted mt-1" aria-live="polite"></div>
                    </div>

                    <!-- Google Map -->
                    <div id="map"></div>
                    <div class="map-legend" aria-label="Map legend">
                        <span class="map-legend-item"><span class="map-legend-dot spot"></span> Tourist spot</span>
                        <span class="map-legend-item"><span class="map-legend-dot dining"></span> Dining</span>
                        <span class="map-legend-item"><span class="map-legend-dot gas"></span> Gas station</span>
                        <span class="map-legend-item"><span class="map-legend-dot radius"></span> 2 km facility radius</span>
                    </div>

                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $spot->latitude) }}" required>
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $spot->longitude) }}" required>
                    <input type="hidden" id="nearby_facilities" name="nearby_facilities" value="">
                    @if($errors->has('latitude') || $errors->has('longitude'))
                        <div class="text-danger small mt-2">
                            Please select a valid location on the map.
                        </div>
                    @endif

                    <div class="mb-3 address-field">
                        <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $spot->address) }}" required>
                            <button type="button" class="btn btn-outline-secondary" id="use-map-location"><i class="fas fa-location-crosshairs"></i> Use map location</button>
                        </div>
                        @error('address')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mt-4">
                        <h6 class="mb-2"><i class="fas fa-location-dot"></i> Nearby Facilities (Exact Locations)</h6>
                        <div class="facility-tools">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label">Facility Type</label>
                                    <select id="facility-type" class="form-select" disabled>
                                        <option value="" selected>Select Facility</option>
                                        <option value="dining">Dining</option>
                                        <option value="gas_station">Gas Station</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Facility Name</label>
                                    <input type="text" id="facility-name" class="form-control" placeholder="Add Name" disabled>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="button" id="facility-add-btn" class="btn btn-outline-primary w-100" disabled>
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                            </div>
                            <div class="form-text mt-2" id="facility-hint">
                                Select the main tourist spot location first.
                            </div>
                            <div class="form-text text-primary mt-1">
                                Nearby facilities must stay within a 2 km radius of the main spot marker.
                            </div>
                        </div>
                        <div id="facility-list" class="mt-3"></div>
                        <div id="facility-count" class="form-text text-primary mt-2">You can add multiple nearby facilities.</div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Basic Information</h5>
                    <div class="mb-3 description-field">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description', $spot->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 images-field">
                        <label for="images" class="form-label">Spot Images</label>
                        @if($spotImages->isNotEmpty())
                            <div class="row g-2 mb-2" id="existing-images">
                                @foreach($spotImages as $imageIndex => $spotImage)
                                    <div class="col-6 col-md-4 col-xl-3 existing-image-card" data-image-url="{{ $spotImage }}">
                                        <img src="{{ $spotImage }}" alt="{{ $spot->name }} image" class="img-fluid rounded" style="height: 120px; width: 100%; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-danger existing-image-remove" data-remove-image="{{ $spotImage }}" aria-label="Remove image"><i class="fas fa-times"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text mb-2">Current gallery. Remove individual images or add new photos below (maximum 5 total).</div>
                        @endif
                        <label for="images" class="dropzone d-block"><i class="fas fa-cloud-arrow-up d-block mb-2"></i><strong>Drag & drop photos here or click to browse</strong><small class="d-block text-muted mt-1">New photos are added to the current gallery.</small></label>
                        <input type="file" class="d-none @error('images') is-invalid @enderror" id="images" name="images[]" accept="image/*" multiple>
                        <div id="upload-previews" class="upload-previews"></div>
                        <div id="remove-images-container"></div>
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

                    <div class="mb-4 status-field">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                            <option value="open" @if($normalizedStatus == 'open') selected @endif>Open</option>
                            <option value="closed" @if($normalizedStatus == 'closed') selected @endif>Closed</option>
                            <option value="under_maintenance" @if($normalizedStatus == 'under_maintenance') selected @endif>Under Maintenance</option>
                            <option value="seasonal" @if($normalizedStatus == 'seasonal') selected @endif>Seasonal</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="status-reason mt-2" id="status-reason-wrap">
                            <label for="status_reason" class="form-label">Status reason/note <span class="text-muted small">(optional)</span></label>
                            <input type="text" class="form-control" id="status_reason" name="status_reason" value="{{ $statusReason }}" placeholder="e.g. Closed for renovation until Dec 2026">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-tourism">
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

</div>

<script>
    let map, marker, infoWindow;
    let searchTimeout;
    let facilityMarkers = [];
    let pendingFacility = null;
    let pendingFacilityMarker = null;
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
    };

    const facilityLabels = {
        dining: 'Dining',
        gas_station: 'Gas Station',
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

    function isLocationWithinMunicipality(lat, lng) {
        const bounds = getDistrictBounds();
        return lat >= bounds.south && lat <= bounds.north && lng >= bounds.west && lng <= bounds.east;
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

        const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=ph&q=${encodeURIComponent(buildSearchQuery(normalizedQuery))}`;
        return fetch(url, { headers: { Accept: 'application/json' } })
            .then((response) => response.json())
            .then((results) => {
                const first = Array.isArray(results) ? results[0] : null;
                if (!first || !Number.isFinite(Number(first.lat)) || !Number.isFinite(Number(first.lon))) {
                    return null;
                }

                return {
                    geometry: {
                        location: {
                            lat: () => Number(first.lat),
                            lng: () => Number(first.lon)
                        }
                    },
                    formatted_address: first.display_name || normalizedQuery
                };
            })
            .catch(() => null);
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

    function syncAddressFromCoordinates(lat, lng) {
        if (!googleGeocoder) return;
        googleGeocoder.geocode({ location: { lat: Number(lat), lng: Number(lng) } }, function(results, status) {
            if (status === 'OK' && results?.[0]?.formatted_address) {
                const addressInput = document.getElementById('address');
                if (addressInput) addressInput.value = results[0].formatted_address;
            }
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
                syncAddressFromCoordinates(newLat, newLng);
                updateMarkerLabel();
                refreshFacilityRadiusOverlay();
            });
        }

        updateMarkerLabel();
        refreshFacilityRadiusOverlay();
    }

    function getCurrentSpotCoordinates() {
        const latValue = document.getElementById('latitude')?.value?.trim();
        const lngValue = document.getElementById('longitude')?.value?.trim();
        if (!latValue || !lngValue) return null;
        const latInput = Number(latValue);
        const lngInput = Number(lngValue);
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
        updateFacilityControlsState();
    }

    function updateFacilityControlsState() {
        const hasLocation = Boolean(getCurrentSpotCoordinates());
        const typeSelect = document.getElementById('facility-type');
        const nameInput = document.getElementById('facility-name');
        const addButton = document.getElementById('facility-add-btn');
        [typeSelect, nameInput, addButton].forEach((control) => { if (control) control.disabled = !hasLocation; });
        if (hasLocation && nameInput?.placeholder === 'Add Name') {
            setFacilityHint('Choose a facility type, then click its location on the map.');
        }
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
                draggable: true,
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: 10, fillColor: '#dc2626', fillOpacity: .95, strokeColor: '#fff', strokeWeight: 2 }
            });

            marker.addListener('dragend', function() {
                const lat = marker.getPosition().lat();
                const lng = marker.getPosition().lng();
                updateCoordinates(lat, lng);
                syncAddressFromCoordinates(lat, lng);
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
                setPendingFacilityLocation(lat, lng);
                return;
            }

            updateCoordinates(lat, lng);
            ensureMarker(lat, lng);
            syncAddressFromCoordinates(lat, lng);
        });
    }

    function searchLocationAPI(query) {
        const normalizedQuery = String(query ?? '').trim();
        if (normalizedQuery.length < 1) {
            setSearchStatus('Type a location to search.');
            return;
        }
        setSearchStatus('Searching...', true);

        const localResults = buildLocalSuggestions(normalizedQuery);
        const tokens = normalizeSearchText(normalizedQuery).split(/\s+/).filter((token) => token.length >= 1);

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
                setSearchStatus(`${combined.length} location result${combined.length === 1 ? '' : 's'} found.`);
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
                setSearchStatus((fallbackPredictions?.length || localResults.length) ? 'Select a location result.' : 'No results found. Try a nearby landmark or barangay.');
            });
        });
    }

    function setSearchStatus(message, loading = false) {
        const status = document.getElementById('location-search-status');
        if (!status) return;
        status.innerHTML = loading
            ? '<i class="fas fa-spinner fa-spin me-1"></i>' + escapeHtml(message)
            : escapeHtml(message || '');
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
                const addressInput = document.getElementById('address');
                if (addressInput && place.formatted_address) addressInput.value = place.formatted_address;
                const addressPreview = document.getElementById('resolved-address-preview');
                if (addressPreview && place.formatted_address) addressPreview.textContent = place.formatted_address;
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
                const addressInput = document.getElementById('address');
                if (addressInput) addressInput.value = place.formatted_address || result.description || '';
                const addressPreview = document.getElementById('resolved-address-preview');
                if (addressPreview) addressPreview.textContent = place.formatted_address || result.description || '';
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
        const categoryInputs = document.querySelectorAll('input[name="category"]');
        const scheduleSection = document.getElementById('schedule-section');
        const scheduleInputs = scheduleSection
            ? scheduleSection.querySelectorAll('input, select, textarea')
            : [];

        function toggleScheduleFields() {
            if (!scheduleSection) return;
            const selectedCategory = document.querySelector('input[name="category"]:checked');
            const isPark = selectedCategory?.value === 'parks';
            scheduleSection.classList.toggle('d-none', !isPark);
            scheduleInputs.forEach((input) => {
                input.disabled = !isPark;
            });
        }

        if (scheduleSection) {
            categoryInputs.forEach((input) => input.addEventListener('change', toggleScheduleFields));
            toggleScheduleFields();
        }

        const imageInput = document.getElementById('images');
        const dropzone = document.querySelector('.dropzone');
        const previewContainer = document.getElementById('upload-previews');
        const removeImagesContainer = document.getElementById('remove-images-container');
        function renderImagePreviews(files) {
            if (!previewContainer) return;
            previewContainer.innerHTML = '';
            const remainingSlots = Math.max(0, 5 - document.querySelectorAll('.existing-image-card:not(.d-none)').length);
            Array.from(files).slice(0, remainingSlots).forEach((file, index) => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = function (event) {
                    const preview = document.createElement('div');
                    preview.className = 'upload-preview';
                    preview.innerHTML = `<img src="${event.target.result}" alt="Selected image ${index + 1}">${index === 0 ? '<span class="badge bg-dark cover-badge">Cover Image</span>' : ''}`;
                    previewContainer.appendChild(preview);
                };
                reader.readAsDataURL(file);
            });
        }
        imageInput?.addEventListener('change', () => renderImagePreviews(imageInput.files));
        document.querySelectorAll('[data-remove-image]').forEach((button) => {
            button.addEventListener('click', () => {
                const imageCard = button.closest('.existing-image-card');
                const imageUrl = button.dataset.removeImage;
                if (!imageCard || !imageUrl) return;
                imageCard.classList.add('d-none');
                if (removeImagesContainer) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'remove_images[]';
                    input.value = imageUrl;
                    removeImagesContainer.appendChild(input);
                }
                if (imageInput) renderImagePreviews(imageInput.files);
            });
        });
        dropzone?.addEventListener('dragover', (event) => { event.preventDefault(); dropzone.classList.add('dragover'); });
        dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
        dropzone?.addEventListener('drop', (event) => {
            event.preventDefault();
            dropzone.classList.remove('dragover');
            if (imageInput && event.dataTransfer.files.length) {
                imageInput.files = event.dataTransfer.files;
                renderImagePreviews(imageInput.files);
            }
        });

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
        const useMapLocationButton = document.getElementById('use-map-location');
        useMapLocationButton?.addEventListener('click', () => {
            const center = getCurrentSpotCoordinates();
            if (center) syncAddressFromCoordinates(center.lat, center.lng);
        });

        const statusSelect = document.getElementById('status');
        const statusReasonWrap = document.getElementById('status-reason-wrap');
        function toggleStatusReason() {
            statusReasonWrap?.classList.toggle('show', ['closed', 'under_maintenance', 'seasonal'].includes(statusSelect?.value));
        }
        statusSelect?.addEventListener('change', toggleStatusReason);
        toggleStatusReason();

        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const query = e.target.value;

            if (query.length >= 1) {
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
            form.addEventListener('submit', function(event) {
                if (form.dataset.submitting === 'true') {
                    return;
                }

                const lat = Number(document.getElementById('latitude')?.value);
                const lng = Number(document.getElementById('longitude')?.value);
                if (Number.isFinite(lat) && Number.isFinite(lng) && !isLocationWithinMunicipality(lat, lng)) {
                    event.preventDefault();
                    alert('The selected location is outside the assigned municipality. Please move the main marker inside the municipality map area.');
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

    function setPendingFacilityLocation(lat, lng) {
        const radiusStatus = getFacilityRadiusStatus(Number(lat), Number(lng));
        pendingFacility.lat = Number(lat);
        pendingFacility.lng = Number(lng);
        if (!radiusStatus.allowed) {
            setFacilityHint(radiusStatus.message, 'danger');
            const addButton = document.getElementById('facility-add-btn');
            if (addButton) addButton.disabled = true;
            return;
        }
        setFacilityHint('Preview location set. Click the map to move it or press Add to save.', 'primary');
        const addButton = document.getElementById('facility-add-btn');
        if (addButton) addButton.disabled = false;
        document.getElementById('facility-name')?.focus();
        if (!pendingFacilityMarker) {
            pendingFacilityMarker = new google.maps.Marker({
                map,
                draggable: true,
                title: 'Pending facility location',
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: 9, fillColor: '#dc2626', fillOpacity: .75, strokeColor: '#fff', strokeWeight: 2 }
            });
            pendingFacilityMarker.addListener('dragend', () => {
                const position = pendingFacilityMarker.getPosition();
                if (position) setPendingFacilityLocation(position.lat(), position.lng());
            });
        }
        pendingFacilityMarker.setPosition({ lat: Number(lat), lng: Number(lng) });
        pendingFacilityMarker.setMap(map);
    }

    function clearPendingFacilityLocation() {
        if (pendingFacilityMarker) pendingFacilityMarker.setMap(null);
        pendingFacilityMarker = null;
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
        const count = document.getElementById('facility-count');
        if (count) count.textContent = facilities.length
            ? facilities.length + ' nearby facilit' + (facilities.length === 1 ? 'y' : 'ies') + ' added. You can add more.'
            : 'You can add multiple nearby facilities.';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const addButton = document.getElementById('facility-add-btn');
        const typeSelect = document.getElementById('facility-type');
        const nameInput = document.getElementById('facility-name');
        const list = document.getElementById('facility-list');
        updateFacilityControlsState();

        typeSelect?.addEventListener('change', function() {
            if (!getCurrentSpotCoordinates()) {
                setFacilityHint('Select the main spot location first, then choose a facility type.', 'danger');
                return;
            }
            pendingFacility = { type: typeSelect.value };
            setFacilityHint('Click the map to choose the ' + facilityLabels[typeSelect.value] + ' location.', 'primary');
        });

        if (addButton) {
            addButton.addEventListener('click', function() {
                if (!getCurrentSpotCoordinates()) {
                    setFacilityHint('Select the main spot location first before adding nearby facilities.', 'danger');
                    return;
                }
                const type = typeSelect ? typeSelect.value : 'dining';
                const name = nameInput ? nameInput.value.trim() : '';
                if (!type) {
                    setFacilityHint('Select a facility type first.', 'danger');
                    if (typeSelect) typeSelect.focus();
                    return;
                }
                if (pendingFacility?.lat !== undefined && pendingFacility?.lng !== undefined) {
                    if (!name) {
                        setFacilityHint('Enter the facility name before pressing Add.', 'danger');
                        if (nameInput) nameInput.focus();
                        return;
                    }
                    const radiusStatus = getFacilityRadiusStatus(pendingFacility.lat, pendingFacility.lng);
                    if (!radiusStatus.allowed) return setFacilityHint(radiusStatus.message, 'danger');
                    addFacilityMarker(pendingFacility.type, name, pendingFacility.lat, pendingFacility.lng);
                    clearPendingFacilityLocation();
                    pendingFacility = null;
                    typeSelect.value = '';
                    nameInput.value = '';
                    addButton.textContent = 'Add';
                    setFacilityHint('Choose another facility type, click its map location, enter its name, then press Add.', 'muted');
                    return;
                }
                pendingFacility = { type };
                addButton.textContent = 'Add';
                setFacilityHint('Click the map to choose the ' + facilityLabels[type] + ' location.', 'primary');
            });
        }

        if (list) {
            list.addEventListener('click', function(event) {
                const target = event.target.closest('[data-remove-index]');
                if (!target) return;
                const index = Number(target.getAttribute('data-remove-index'));
                if (Number.isNaN(index)) return;
                if (facilities.length >= 3 && !window.confirm('Remove this facility from the saved nearby facilities?')) return;
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
