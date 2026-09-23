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
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <form action="{{ route('tourist_spots.update', $spot->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <h5 class="mb-3"><i class="fas fa-map"></i> Location & Map</h5>

                    <div class="mb-3">
    <label for="municipality_id" class="form-label">Municipality <span class="text-danger">*</span></label>
    <select class="form-select @error('municipality_id') is-invalid @enderror" id="municipality_id" name="municipality_id" required>
        <option value="">Select Municipality</option>
        @foreach($municipalities as $municipality)
            <option value="{{ $municipality->id }}" @if(old('municipality_id', $spot->municipality_id) == $municipality->id) selected @endif>
                {{ $municipality->name }}
            </option>
        @endforeach
    </select>
    @error('municipality_id')
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
                                    placeholder="Search for location... (e.g., 'Hundred Islands, Pangasinan')" 
                                    autocomplete="off"
                                >
                                <div class="search-suggestions" id="search-suggestions"></div>
                            </div>
                            <button type="button" onclick="searchLocation()" class="btn btn-primary mt-2">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>

                    <!-- Map -->
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
                        <label for="image" class="form-label">Spot Image</label>
                        @if($spot->image_url)
                            <div class="mb-2">
                                <img src="{{ preg_match('#^https?://#i', $spot->image_url) ? $spot->image_url : url($spot->image_url) }}" alt="{{ $spot->name }} image" class="img-fluid rounded" style="max-height: 200px; object-fit: cover;">
                            </div>
                        @endif
                        <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                        @error('image')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Upload a JPG, PNG, or WebP image (max 2MB).</div>
                    </div>
                    @php
                        $selectedDays = old('opening_days', []);
                        if (empty($selectedDays) && $spot->opening_days) {
                            // Decode JSON if it's stored as JSON
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
                                    <input class="form-check-input" type="checkbox" id="day-{{ $key }}" name="opening_days[]" value="{{ $key }}" @checked(in_array($key, $selectedDays, true))>
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
                        <div class="form-text">Leave empty if schedule is not set yet.</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="opening_time" class="form-label">Opening Time</label>
                            <input type="time" class="form-control @error('opening_time') is-invalid @enderror" id="opening_time" name="opening_time" value="{{ old('opening_time', $spot->opening_time) }}">
                            @error('opening_time')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="closing_time" class="form-label">Closing Time</label>
                            <input type="time" class="form-control @error('closing_time') is-invalid @enderror" id="closing_time" name="closing_time" value="{{ old('closing_time', $spot->closing_time) }}">
                            @error('closing_time')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <div class="form-text">If you set time, both opening and closing must be filled.</div>
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
                        <a href="{{ route('tourist_spots.index') }}" class="btn btn-secondary">
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
    let map, marker;
    let searchTimeout;
    let facilityLayer;
    let pendingFacility = null;
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

    // Initialize map
    function initMap() {
        const mapLat = {{ $spot->latitude ?? 16.02 }};
        const mapLng = {{ $spot->longitude ?? 120.24 }};
        
        map = L.map('map').setView([mapLat, mapLng], 13);
        const streetLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '� OpenStreetMap contributors � CARTO',
            subdomains: 'abcd',
            maxZoom: 20
        });

        const imageryLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles � Esri � Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community',
            maxZoom: 20
        });

        const labelLayer = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles � Esri',
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
        
        // Add marker if spot exists
        @if($spot)
            marker = L.marker([{{ $spot->latitude }}, {{ $spot->longitude }}]).addTo(map);
        @endif
        facilityLayer = L.layerGroup().addTo(map);
        refreshFacilityMarkers();

        // Click on map to add marker
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            if (pendingFacility) {
                addFacilityMarker(pendingFacility.type, pendingFacility.name, lat, lng);
                pendingFacility = null;
                document.getElementById('facility-name').value = '';
                setFacilityHint('Select a type and name, then click the map to drop a marker.');
                return;
            }
            
            document.getElementById('latitude').value = lat.toFixed(5);
            document.getElementById('longitude').value = lng.toFixed(5);
            
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }
        });
    }
    
    // Search for location using Nominatim API
    function searchLocationAPI(query) {
        if (query.length < 3) return;
        
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=8`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => displaySuggestions(data))
            .catch(error => console.error('Search error:', error));
    }
    
    // Display search suggestions
    function displaySuggestions(results) {
        const suggestionsBox = document.getElementById('search-suggestions');
        suggestionsBox.innerHTML = '';
        
        if (results.length === 0) {
            suggestionsBox.innerHTML = '<div class="search-suggestion-item"><span style="color: #999;">No results found</span></div>';
            suggestionsBox.classList.add('show');
            return;
        }
        
        results.forEach(result => {
            const div = document.createElement('div');
            div.className = 'search-suggestion-item';
            div.innerHTML = `
                <div class="suggestion-name">${escapeHtml(result.name)}</div>
                <div class="suggestion-address">${escapeHtml(result.display_name.substring(0, 60))}...</div>
            `;
            div.onclick = () => selectSuggestion(result);
            suggestionsBox.appendChild(div);
        });
        
        suggestionsBox.classList.add('show');
    }
    
    // Select a suggestion
    function selectSuggestion(result) {
        const lat = parseFloat(result.lat);
        const lng = parseFloat(result.lon);
        
        document.getElementById('latitude').value = lat.toFixed(5);
        document.getElementById('longitude').value = lng.toFixed(5);
        document.getElementById('location-search').value = result.name;
        
        // Clear suggestions
        document.getElementById('search-suggestions').classList.remove('show');
        
        // Update map
        map.setView([lat, lng], 15);
        
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng]).addTo(map);
        }
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
        const searchInput = document.getElementById('location-search');
        
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
        return text.replace(/[&<>"']/g, m => map[m]);
    }
    
    // Initialize on load
    window.addEventListener('load', initMap);

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
        facilities.push({
            type,
            name,
            latitude: Number(lat.toFixed(6)),
            longitude: Number(lng.toFixed(6))
        });
        updateFacilityInput();
        refreshFacilityMarkers();
        renderFacilityList();
    }

    function refreshFacilityMarkers() {
        if (!facilityLayer) return;
        facilityLayer.clearLayers();
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
            marker.addTo(facilityLayer);
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
            feedback.innerHTML = '<small class="text-success"><i class="fas fa-check-circle"></i> Name is valid (' + value.length + ' characters)</small>';
            feedback.className = 'mt-2 text-success';
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        });
        
        // Validate on blur
        nameInput.addEventListener('blur', function() {
            this.value = this.value.trim();
            this.dispatchEvent(new Event('input'));
        });
</script>

@endsection



