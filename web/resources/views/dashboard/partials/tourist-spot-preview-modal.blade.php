@if(config('services.google_maps.key'))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}"></script>
@endif

<div class="modal fade" id="touristSpotPreviewModal" tabindex="-1" aria-labelledby="touristSpotPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title text-break" id="touristSpotPreviewModalLabel" data-preview-name>Tourist Spot Details</h5>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge bg-light text-dark border" data-preview-category></span>
                        <span class="badge bg-light text-dark border" data-preview-municipality></span>
                        <span class="badge bg-secondary" data-preview-status></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-preview-content>
                <div class="alert alert-success py-2 mb-3" data-preview-save-feedback role="status" hidden></div>
                <div class="row g-4">
                    <div class="col-12 col-md-5" data-preview-media-column hidden>
                        <div data-preview-gallery hidden>
                            <div class="position-relative rounded overflow-hidden bg-light">
                                <img class="w-100" style="height: 280px; object-fit: cover;" data-preview-image alt="">
                                <button type="button" class="btn btn-light btn-sm position-absolute top-50 start-0 translate-middle-y ms-2" data-gallery-previous aria-label="Previous image"><i class="fas fa-chevron-left"></i></button>
                                <button type="button" class="btn btn-light btn-sm position-absolute top-50 end-0 translate-middle-y me-2" data-gallery-next aria-label="Next image"><i class="fas fa-chevron-right"></i></button>
                                <span class="badge bg-dark bg-opacity-75 position-absolute bottom-0 end-0 m-2" data-gallery-count></span>
                            </div>
                            <div class="d-flex gap-2 overflow-auto pt-2" data-gallery-thumbnails aria-label="Spot images"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-7">
                        <h6>Description</h6>
                        <p class="text-muted" data-preview-description></p>
                        <dl class="row mb-0">
                            <dt class="col-sm-4" data-preview-row="submitter" hidden>Submitted by</dt>
                            <dd class="col-sm-8" data-preview-value-for="submitter" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="submitterContact" hidden>Contributor contact</dt>
                            <dd class="col-sm-8 text-break" data-preview-value-for="submitterContact" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="submitted" hidden>Date submitted</dt>
                            <dd class="col-sm-8" data-preview-value-for="submitted" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="approved" hidden>Date verified</dt>
                            <dd class="col-sm-8" data-preview-value-for="approved" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="revisionReason" hidden>Revision request</dt>
                            <dd class="col-sm-8" data-preview-value-for="revisionReason" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="facilities" hidden>Nearby facilities</dt>
                            <dd class="col-sm-8" data-preview-value-for="facilities" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="address" hidden>Address</dt>
                            <dd class="col-sm-8 text-break" data-preview-value-for="address" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="barangay" hidden>Barangay</dt>
                            <dd class="col-sm-8" data-preview-value-for="barangay" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="coordinates" hidden>Coordinates</dt>
                            <dd class="col-sm-8 font-monospace small" data-preview-value-for="coordinates" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="hours" hidden>Opening hours</dt>
                            <dd class="col-sm-8" data-preview-value-for="hours" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="fee" hidden>Entrance fee</dt>
                            <dd class="col-sm-8" data-preview-value-for="fee" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="phone" hidden>Phone</dt>
                            <dd class="col-sm-8" data-preview-value-for="phone" hidden></dd>
                            <dt class="col-sm-4" data-preview-row="website" hidden>Website</dt>
                            <dd class="col-sm-8 text-break" data-preview-value-for="website" hidden></dd>
                        </dl>
                    </div>
                    <div class="col-12" data-preview-map-container hidden>
                        <h6 class="mb-2">Location preview</h6>
                        <div class="w-100 border rounded" style="height: 240px;" data-preview-map role="img" aria-label="Google Maps preview of tourist spot location"></div>
                        <div class="small text-warning mt-2" data-preview-map-unavailable hidden>Google Maps is unavailable. Check the configured Google Maps API key.</div>
                    </div>
                </div>
            </div>
            <div class="modal-body" data-edit-content hidden>
                <div class="btn-group w-100 mb-3" role="group" aria-label="Choose spot details to edit">
                    <button type="button" class="btn btn-outline-secondary active" data-edit-section="description" aria-pressed="true">Description</button>
                    <button type="button" class="btn btn-outline-secondary" data-edit-section="images" aria-pressed="false">Photos</button>
                    <button type="button" class="btn btn-outline-secondary" data-edit-section="facilities" aria-pressed="false">Nearby Facilities</button>
                </div>
                <div class="alert alert-danger py-2" data-edit-feedback role="alert" hidden></div>
                <div class="alert alert-success py-2" data-edit-success role="status" hidden></div>
                <form data-inline-edit-form>
                    @csrf
                    <input type="hidden" name="section" value="description" data-edit-section-value>
                    <div data-edit-panel="description">
                        <label class="form-label fw-semibold" for="preview-edit-description">Spot description</label>
                        <textarea class="form-control" id="preview-edit-description" name="description" rows="7" data-edit-description required></textarea>
                    </div>
                    <div data-edit-panel="images" hidden>
                        <div class="small text-muted mb-2">Remove existing photos or add JPG, PNG, or WebP files. Maximum 5 photos, 2 MB each.</div>
                        <div class="row g-2 mb-3" data-edit-image-list></div>
                        <label class="form-label fw-semibold" for="preview-edit-images">Add photos</label>
                        <input class="form-control" id="preview-edit-images" data-edit-images type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                        <div class="small text-muted mt-2" data-edit-image-selection></div>
                    </div>
                    <div data-edit-panel="facilities" hidden>
                        <p class="small text-muted mb-2">Choose a point on the map to place a facility pin. Only locations within 2 km of the spot can be added.</p>
                        <div class="w-100 border rounded bg-light" style="height: 280px;" data-edit-map role="application" aria-label="Map for placing nearby facility pins"></div>
                        <div class="small text-warning mt-2" data-edit-map-unavailable hidden>Map is unavailable. Check the Google Maps API key.</div>
                        <div class="d-flex flex-wrap align-items-end gap-2 mt-3">
                            <div class="flex-grow-1" style="min-width: 9rem;">
                                <label class="form-label small mb-1" for="preview-edit-facility-type">Facility type</label>
                                <select class="form-select form-select-sm" id="preview-edit-facility-type" data-new-facility-type>
                                    <option value="dining">Dining</option>
                                    <option value="gas_station">Gas station</option>
                                </select>
                            </div>
                            <div class="flex-grow-1" style="min-width: 11rem;">
                                <label class="form-label small mb-1" for="preview-edit-facility-name">Facility name</label>
                                <input class="form-control form-control-sm" id="preview-edit-facility-name" type="text" maxlength="120" data-new-facility-name>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" data-edit-add-facility disabled><i class="fas fa-map-pin me-1" aria-hidden="true"></i>Add pin</button>
                        </div>
                        <div class="small mt-2" data-edit-map-feedback aria-live="polite">Select a location on the map.</div>
                        <div data-edit-facility-list></div>
                    </div>
                </form>
            </div>
            @if(auth()->check() && auth()->user()->hasPermission('manage_spots') && !auth()->user()->isSuperAdmin())
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-edit-cancel hidden>Back to details</button>
                    <button type="button" class="btn btn-primary" data-edit-save hidden><i class="fas fa-save me-1" aria-hidden="true"></i>Save changes</button>
                    <button type="button" class="btn btn-warning" data-preview-edit hidden><i class="fas fa-pen-to-square me-1" aria-hidden="true"></i>Edit Spot</button>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    (() => {
        const previewModal = document.getElementById('touristSpotPreviewModal');
        if (!previewModal) return;
        const editAction = previewModal.querySelector('[data-preview-edit]');
        const previewContent = previewModal.querySelector('[data-preview-content]');
        const editContent = previewModal.querySelector('[data-edit-content]');
        const editForm = previewModal.querySelector('[data-inline-edit-form]');
        const editCancel = previewModal.querySelector('[data-edit-cancel]');
        const editSave = previewModal.querySelector('[data-edit-save]');
        const editFeedback = previewModal.querySelector('[data-edit-feedback]');
        const editSuccess = previewModal.querySelector('[data-edit-success]');
        const previewSaveFeedback = previewModal.querySelector('[data-preview-save-feedback]');
        const editUrlTemplate = @json(route('tourist_spots.preview-edit', ['touristSpot' => '__SPOT_ID__']));
        const updateUrlTemplate = @json(route('tourist_spots.preview-update', ['touristSpot' => '__SPOT_ID__']));

        const setOptionalDetail = (name, value) => {
            const label = previewModal.querySelector(`[data-preview-row="${name}"]`);
            const output = previewModal.querySelector(`[data-preview-value-for="${name}"]`);
            const hasValue = Boolean(value);
            label.hidden = !hasValue;
            output.hidden = !hasValue;
            output.textContent = value || '';
        };

        let galleryImages = [];
        let activeImageIndex = 0;
        let previewMap = null;
        let previewMarker = null;
        let previewCoordinates = null;
        let previewSpotName = '';
        let activeTrigger = null;
        let activeSpotId = null;
        let activeEditSection = 'description';
        let editImages = [];
        let editSpotCenter = { latitude: 0, longitude: 0 };
        let editFacilityMap = null;
        let editSpotMarker = null;
        let editRadiusCircle = null;
        let editCandidateMarker = null;
        let editFacilityMarkers = [];
        let selectedFacilityLocation = null;
        const facilityRadiusMeters = 2000;
        const facilityMarkerColors = { dining: '#ff6b35', gas_station: '#0d6efd' };

        const setEditFeedback = (message, isSuccess = false) => {
            if (!editFeedback || !editSuccess) return;
            editFeedback.hidden = isSuccess || !message;
            editSuccess.hidden = !isSuccess || !message;
            editFeedback.textContent = isSuccess ? '' : message;
            editSuccess.textContent = isSuccess ? message : '';
        };

        const setEditMode = (isEditing) => {
            previewContent.hidden = isEditing;
            editContent.hidden = !isEditing;
            editAction.hidden = isEditing || !activeSpotId;
            editCancel.hidden = !isEditing;
            editSave.hidden = !isEditing;
        };

        const setEditSection = (section) => {
            activeEditSection = section;
            editForm.querySelector('[data-edit-section-value]').value = section;
            previewModal.querySelectorAll('[data-edit-section]').forEach((button) => {
                const isActive = button.dataset.editSection === section;
                button.classList.toggle('active', isActive);
                button.setAttribute('aria-pressed', String(isActive));
            });
            previewModal.querySelectorAll('[data-edit-panel]').forEach((panel) => {
                panel.hidden = panel.dataset.editPanel !== section;
            });
            setEditFeedback('');
            if (section === 'facilities') requestAnimationFrame(initEditFacilityMap);
        };

        const distanceBetweenMeters = (first, second) => {
            const earthRadius = 6371000;
            const toRadians = (degrees) => degrees * Math.PI / 180;
            const deltaLatitude = toRadians(second.lat - first.lat);
            const deltaLongitude = toRadians(second.lng - first.lng);
            const firstLatitude = toRadians(first.lat);
            const secondLatitude = toRadians(second.lat);
            const haversine = Math.sin(deltaLatitude / 2) ** 2
                + Math.cos(firstLatitude) * Math.cos(secondLatitude) * Math.sin(deltaLongitude / 2) ** 2;
            return earthRadius * 2 * Math.atan2(Math.sqrt(haversine), Math.sqrt(1 - haversine));
        };

        const setFacilityMapFeedback = (message, allowed = null) => {
            const feedback = previewModal.querySelector('[data-edit-map-feedback]');
            feedback.textContent = message;
            feedback.className = `small mt-2 ${allowed === true ? 'text-success' : (allowed === false ? 'text-danger' : 'text-muted')}`;
        };

        const updateFacilityPinButton = () => {
            const button = previewModal.querySelector('[data-edit-add-facility]');
            const name = previewModal.querySelector('[data-new-facility-name]').value.trim();
            button.disabled = !selectedFacilityLocation?.allowed || !name;
        };

        const setCandidateFacilityLocation = (location) => {
            const point = { lat: location.lat(), lng: location.lng() };
            const center = { lat: editSpotCenter.latitude, lng: editSpotCenter.longitude };
            const distance = distanceBetweenMeters(center, point);
            const allowed = distance <= facilityRadiusMeters;
            selectedFacilityLocation = { ...point, allowed };

            if (!editCandidateMarker) {
                editCandidateMarker = new google.maps.Marker({
                    map: editFacilityMap,
                    draggable: true,
                    title: 'New nearby facility',
                    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 9, fillColor: '#a16207', fillOpacity: .95, strokeColor: '#fff', strokeWeight: 2 },
                });
                editCandidateMarker.addListener('dragend', () => setCandidateFacilityLocation(editCandidateMarker.getPosition()));
            }
            editCandidateMarker.setPosition(point);
            setFacilityMapFeedback(
                allowed
                    ? `Selected ${distance.toFixed(0)} m from the spot. Enter a name, then add the pin.`
                    : `Selected ${(distance / 1000).toFixed(2)} km away. Move the pin within the 2 km circle.`,
                allowed
            );
            updateFacilityPinButton();
        };

        const refreshEditFacilityMarkers = () => {
            if (!editFacilityMap || !window.google?.maps) return;
            editFacilityMarkers.forEach((marker) => marker.setMap(null));
            editFacilityMarkers = [];

            previewModal.querySelectorAll('[data-edit-facility-row]').forEach((row) => {
                const latitudeInput = row.querySelector('[data-facility-field="latitude"]');
                const longitudeInput = row.querySelector('[data-facility-field="longitude"]');
                const latitude = Number(latitudeInput.value);
                const longitude = Number(longitudeInput.value);
                if (!latitudeInput.value.trim() || !longitudeInput.value.trim() || !Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

                const type = row.querySelector('[data-facility-field="type"]').value;
                const name = row.querySelector('[data-facility-field="name"]').value.trim();
                const marker = new google.maps.Marker({
                    map: editFacilityMap,
                    position: { lat: latitude, lng: longitude },
                    draggable: true,
                    title: name || 'Nearby facility',
                    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 7, fillColor: facilityMarkerColors[type] || '#0f766e', fillOpacity: .95, strokeColor: '#fff', strokeWeight: 2 },
                });
                marker.addListener('dragend', () => {
                    const position = marker.getPosition();
                    const previousPosition = { lat: Number(latitudeInput.value), lng: Number(longitudeInput.value) };
                    const location = { lat: position.lat(), lng: position.lng() };
                    const center = { lat: editSpotCenter.latitude, lng: editSpotCenter.longitude };
                    const distance = distanceBetweenMeters(center, location);
                    if (distance > facilityRadiusMeters) {
                        marker.setPosition(previousPosition);
                        setFacilityMapFeedback('That facility is outside the 2 km radius. Its previous pin was restored.', false);
                        return;
                    }
                    latitudeInput.value = location.lat.toFixed(6);
                    longitudeInput.value = location.lng.toFixed(6);
                    setFacilityMapFeedback(`Facility pin is ${distance.toFixed(0)} m from the spot.`, true);
                });
                editFacilityMarkers.push(marker);
            });
        };

        const initEditFacilityMap = () => {
            const mapElement = previewModal.querySelector('[data-edit-map]');
            const unavailableMessage = previewModal.querySelector('[data-edit-map-unavailable]');
            if (!window.google?.maps) {
                mapElement.hidden = true;
                unavailableMessage.hidden = false;
                return;
            }

            mapElement.hidden = false;
            unavailableMessage.hidden = true;
            const center = { lat: editSpotCenter.latitude, lng: editSpotCenter.longitude };
            if (!editFacilityMap) {
                editFacilityMap = new google.maps.Map(mapElement, {
                    center,
                    zoom: 14,
                    mapTypeControl: true,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    streetViewControl: false,
                    fullscreenControl: false,
                    scrollwheel: false,
                });
                editSpotMarker = new google.maps.Marker({
                    position: center,
                    map: editFacilityMap,
                    title: 'Tourist spot location',
                    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 10, fillColor: '#dc2626', fillOpacity: .95, strokeColor: '#fff', strokeWeight: 2 },
                });
                editRadiusCircle = new google.maps.Circle({
                    map: editFacilityMap,
                    center,
                    radius: facilityRadiusMeters,
                    fillColor: '#0d6efd',
                    fillOpacity: .10,
                    strokeColor: '#0d6efd',
                    strokeOpacity: .45,
                    strokeWeight: 2,
                    clickable: false,
                });
                editFacilityMap.addListener('click', (event) => setCandidateFacilityLocation(event.latLng));
            } else {
                editFacilityMap.setCenter(center);
                editSpotMarker.setPosition(center);
                editRadiusCircle.setCenter(center);
            }

            requestAnimationFrame(() => {
                google.maps.event.trigger(editFacilityMap, 'resize');
                editFacilityMap.setCenter(center);
                refreshEditFacilityMarkers();
            });
        };

        const renderEditImages = () => {
            const imageList = previewModal.querySelector('[data-edit-image-list]');
            imageList.replaceChildren();
            editImages.forEach((image) => {
                const column = document.createElement('div');
                column.className = 'col-6 col-md-4';
                const frame = document.createElement('div');
                frame.className = 'position-relative';
                const thumbnail = document.createElement('img');
                thumbnail.src = image.url;
                thumbnail.alt = 'Existing spot photo';
                thumbnail.className = 'w-100 rounded';
                thumbnail.style.cssText = 'height:110px;object-fit:cover;';
                if (image.removed) thumbnail.style.opacity = '.35';
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = `btn btn-sm ${image.removed ? 'btn-secondary' : 'btn-danger'} position-absolute top-0 end-0 m-1`;
                removeButton.textContent = image.removed ? 'Keep' : 'Remove';
                removeButton.setAttribute('aria-label', `${image.removed ? 'Keep' : 'Remove'} existing photo`);
                removeButton.addEventListener('click', () => {
                    image.removed = !image.removed;
                    renderEditImages();
                });
                frame.append(thumbnail, removeButton);
                column.append(frame);
                imageList.append(column);
            });
        };

        const renderEditFacilities = (facilities) => {
            const facilityList = previewModal.querySelector('[data-edit-facility-list]');
            facilityList.replaceChildren();
            facilities.forEach((facility) => {
                const row = document.createElement('div');
                row.className = 'row g-2 align-items-end mb-3';
                row.dataset.editFacilityRow = '';

                const addField = (labelText, className, value, inputType, key) => {
                    const column = document.createElement('div');
                    column.className = className;
                    const label = document.createElement('label');
                    label.className = 'form-label small mb-1';
                    label.textContent = labelText;
                    const input = document.createElement(inputType === 'select' ? 'select' : 'input');
                    input.className = inputType === 'select' ? 'form-select form-select-sm' : 'form-control form-control-sm';
                    input.dataset.facilityField = key;
                    if (inputType === 'select') {
                        [['dining', 'Dining'], ['gas_station', 'Gas station']].forEach(([optionValue, optionLabel]) => {
                            const option = document.createElement('option');
                            option.value = optionValue;
                            option.textContent = optionLabel;
                            input.append(option);
                        });
                    } else {
                        input.type = inputType;
                    }
                    input.value = value ?? '';
                    input.required = true;
                    label.append(input);
                    column.append(label);
                    row.append(column);
                };

                addField('Type', 'col-12 col-md-3', facility.type || 'dining', 'select', 'type');
                addField('Name', 'col-12 col-md-3', facility.name, 'text', 'name');
                addField('Latitude', 'col-6 col-md-2', facility.latitude, 'number', 'latitude');
                addField('Longitude', 'col-6 col-md-2', facility.longitude, 'number', 'longitude');
                row.querySelector('[data-facility-field="latitude"]').step = 'any';
                row.querySelector('[data-facility-field="longitude"]').step = 'any';
                const removeColumn = document.createElement('div');
                removeColumn.className = 'col-12 col-md-2';
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-sm btn-outline-danger w-100';
                removeButton.textContent = 'Remove';
                removeButton.setAttribute('aria-label', `Remove ${facility.name || 'nearby facility'}`);
                removeButton.addEventListener('click', () => {
                    row.remove();
                    refreshEditFacilityMarkers();
                });
                removeColumn.append(removeButton);
                row.append(removeColumn);
                facilityList.append(row);
                row.querySelectorAll('[data-facility-field]').forEach((input) => input.addEventListener('change', refreshEditFacilityMarkers));
            });
            refreshEditFacilityMarkers();
        };

        const readEditFacilities = () => Array.from(previewModal.querySelectorAll('[data-edit-facility-row]')).map((row) => ({
            type: row.querySelector('[data-facility-field="type"]').value,
            name: row.querySelector('[data-facility-field="name"]').value.trim(),
            latitude: row.querySelector('[data-facility-field="latitude"]').value,
            longitude: row.querySelector('[data-facility-field="longitude"]').value,
        }));

        const loadEditMode = async () => {
            if (!activeSpotId) return;
            setEditMode(true);
            editAction.disabled = true;
            setEditFeedback('Loading editable details...');
            try {
                const url = editUrlTemplate.replace('__SPOT_ID__', encodeURIComponent(activeSpotId));
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to load editable spot details.');
                previewModal.querySelector('[data-edit-description]').value = data.description || '';
                editImages = (data.images || []).map((imageUrl) => ({ url: imageUrl, removed: false }));
                editSpotCenter = { latitude: Number(data.latitude), longitude: Number(data.longitude) };
                renderEditImages();
                renderEditFacilities(data.nearbyFacilities || []);
                selectedFacilityLocation = null;
                if (editCandidateMarker) editCandidateMarker.setMap(null);
                editCandidateMarker = null;
                previewModal.querySelector('[data-new-facility-name]').value = '';
                previewModal.querySelector('[data-edit-add-facility]').disabled = true;
                setFacilityMapFeedback('Select a location on the map.');
                if (editFacilityMap) initEditFacilityMap();
                previewModal.querySelector('[data-edit-images]').value = '';
                previewModal.querySelector('[data-edit-image-selection]').textContent = '';
                setEditSection('description');
            } catch (error) {
                setEditFeedback(error.message || 'Unable to load editable spot details.');
            } finally {
                editAction.disabled = false;
            }
        };

        const saveEditSection = async () => {
            if (!activeSpotId) return;
            const formData = new FormData(editForm);
            formData.set('section', activeEditSection);

            if (activeEditSection === 'images') {
                const files = Array.from(previewModal.querySelector('[data-edit-images]').files || []);
                const remainingCount = editImages.filter((image) => !image.removed).length;
                if (remainingCount + files.length > 5) {
                    setEditFeedback('A tourist spot can have a maximum of 5 images.');
                    return;
                }
                editImages.filter((image) => image.removed).forEach((image) => formData.append('remove_images[]', image.url));
            }

            if (activeEditSection === 'facilities') {
                const facilities = readEditFacilities();
                if (facilities.some((facility) => !facility.name || facility.latitude.trim() === '' || facility.longitude.trim() === '')) {
                    setEditFeedback('Complete each facility name and coordinates, or remove the empty row.');
                    return;
                }
                formData.set('nearby_facilities', JSON.stringify(facilities));
            }

            formData.set('_method', 'PATCH');
            editSave.disabled = true;
            editSave.innerHTML = '<i class="fas fa-spinner fa-spin me-1" aria-hidden="true"></i>Saving';
            setEditFeedback('');
            try {
                const url = updateUrlTemplate.replace('__SPOT_ID__', encodeURIComponent(activeSpotId));
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const result = await response.json();
                if (!response.ok) {
                    const validationMessages = Object.values(result.errors || {}).flat();
                    throw new Error(validationMessages.join(' ') || result.message || 'Unable to save these changes.');
                }

                previewModal.querySelector('[data-preview-description]').textContent = result.description || 'No description available.';
                galleryImages = result.images || [];
                renderGallery();
                setOptionalDetail('facilities', (result.nearbyFacilities || []).map((facility) => facility.name).join(', '));
                setOptionalDetail('revisionReason', result.rejectionReason);
                if (activeTrigger) {
                    activeTrigger.dataset.spotDescription = result.description || '';
                    activeTrigger.dataset.spotImages = JSON.stringify(result.images || []);
                    activeTrigger.dataset.spotImage = result.images?.[0] || '';
                    activeTrigger.dataset.spotRejectionReason = result.rejectionReason || '';
                }
                editImages = (result.images || []).map((imageUrl) => ({ url: imageUrl, removed: false }));
                renderEditImages();
                previewModal.querySelector('[data-edit-images]').value = '';
                previewModal.querySelector('[data-edit-image-selection]').textContent = '';
                previewSaveFeedback.textContent = result.message || 'Changes saved.';
                previewSaveFeedback.hidden = false;
                setEditFeedback('');
                setEditMode(false);
            } catch (error) {
                setEditFeedback(error.message || 'Unable to save these changes.');
            } finally {
                editSave.disabled = false;
                editSave.innerHTML = '<i class="fas fa-save me-1" aria-hidden="true"></i>Save changes';
            }
        };

        if (editAction && editForm) {
            editAction.addEventListener('click', loadEditMode);
            editCancel.addEventListener('click', () => setEditMode(false));
            editSave.addEventListener('click', saveEditSection);
            previewModal.querySelectorAll('[data-edit-section]').forEach((button) => {
                button.addEventListener('click', () => setEditSection(button.dataset.editSection));
            });
            previewModal.querySelector('[data-edit-add-facility]').addEventListener('click', () => {
                if (!selectedFacilityLocation?.allowed) return;
                const nameInput = previewModal.querySelector('[data-new-facility-name]');
                const typeInput = previewModal.querySelector('[data-new-facility-type]');
                const name = nameInput.value.trim();
                if (!name) return;
                const location = selectedFacilityLocation;
                const facility = {
                    type: typeInput.value,
                    name,
                    latitude: Number(location.lat.toFixed(6)),
                    longitude: Number(location.lng.toFixed(6)),
                };
                renderEditFacilities([...readEditFacilities(), facility]);
                selectedFacilityLocation = null;
                editCandidateMarker?.setMap(null);
                editCandidateMarker = null;
                nameInput.value = '';
                previewModal.querySelector('[data-edit-add-facility]').disabled = true;
                setFacilityMapFeedback('Pin added. Choose another point or save changes.', true);
            });
            previewModal.querySelector('[data-new-facility-name]').addEventListener('input', updateFacilityPinButton);
            previewModal.querySelector('[data-new-facility-type]').addEventListener('change', updateFacilityPinButton);
            previewModal.querySelector('[data-edit-images]').addEventListener('change', (event) => {
                const fileNames = Array.from(event.target.files || []).map((file) => file.name);
                previewModal.querySelector('[data-edit-image-selection]').textContent = fileNames.join(', ');
                setEditFeedback('');
            });
            previewModal.addEventListener('hidden.bs.modal', () => setEditMode(false));
        }

        const renderGallery = () => {
            const image = previewModal.querySelector('[data-preview-image]');
            const counter = previewModal.querySelector('[data-gallery-count]');
            const thumbnails = previewModal.querySelector('[data-gallery-thumbnails]');
            const hasImages = galleryImages.length > 0;

            previewModal.querySelector('[data-preview-media-column]').hidden = !hasImages;
            previewModal.querySelector('[data-preview-gallery]').hidden = !hasImages;
            if (!hasImages) {
                image.removeAttribute('src');
                thumbnails.replaceChildren();
                return;
            }

            const currentImage = galleryImages[activeImageIndex];
            image.src = currentImage;
            image.alt = `${previewModal.querySelector('[data-preview-name]').textContent} image ${activeImageIndex + 1}`;
            counter.textContent = `${activeImageIndex + 1} / ${galleryImages.length}`;
            previewModal.querySelector('[data-gallery-previous]').hidden = galleryImages.length < 2;
            previewModal.querySelector('[data-gallery-next]').hidden = galleryImages.length < 2;
            thumbnails.replaceChildren(...galleryImages.map((imageUrl, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn p-0 border rounded flex-shrink-0 overflow-hidden';
                button.setAttribute('aria-label', `Show image ${index + 1}`);
                button.setAttribute('aria-pressed', String(index === activeImageIndex));
                button.style.width = '64px';
                button.style.height = '52px';
                const thumbnail = document.createElement('img');
                thumbnail.src = imageUrl;
                thumbnail.alt = '';
                thumbnail.style.cssText = 'width:100%;height:100%;object-fit:cover;';
                button.append(thumbnail);
                button.addEventListener('click', () => {
                    activeImageIndex = index;
                    renderGallery();
                });
                return button;
            }));
        };

        previewModal.querySelector('[data-gallery-previous]').addEventListener('click', () => {
            activeImageIndex = (activeImageIndex - 1 + galleryImages.length) % galleryImages.length;
            renderGallery();
        });
        previewModal.querySelector('[data-gallery-next]').addEventListener('click', () => {
            activeImageIndex = (activeImageIndex + 1) % galleryImages.length;
            renderGallery();
        });

        const renderGoogleMap = () => {
            const mapElement = previewModal.querySelector('[data-preview-map]');
            const unavailableMessage = previewModal.querySelector('[data-preview-map-unavailable]');
            if (!previewCoordinates) return;

            if (!window.google?.maps) {
                mapElement.hidden = true;
                unavailableMessage.hidden = false;
                return;
            }

            mapElement.hidden = false;
            unavailableMessage.hidden = true;
            if (!previewMap) {
                previewMap = new google.maps.Map(mapElement, {
                    center: previewCoordinates,
                    zoom: 16,
                    mapTypeControl: true,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    streetViewControl: false,
                    fullscreenControl: false,
                    scrollwheel: false,
                });
                previewMarker = new google.maps.Marker({
                    position: previewCoordinates,
                    map: previewMap,
                    title: previewSpotName,
                    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 10, fillColor: '#dc2626', fillOpacity: .95, strokeColor: '#fff', strokeWeight: 2 },
                });
            } else {
                previewMap.setCenter(previewCoordinates);
                previewMap.setZoom(16);
                previewMarker.setPosition(previewCoordinates);
                previewMarker.setTitle(previewSpotName);
            }

            google.maps.event.trigger(previewMap, 'resize');
            previewMap.setCenter(previewCoordinates);
        };

        previewModal.addEventListener('shown.bs.modal', renderGoogleMap);

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-spot-preview]');
            if (!trigger) return;

            activeTrigger = trigger;
            const data = trigger.dataset;
            previewSaveFeedback.hidden = true;
            previewSaveFeedback.textContent = '';
            const spotName = data.spotName || 'Tourist Spot Details';
            const spotLocation = [data.spotBarangay, data.spotMunicipality, 'Pangasinan'].filter(Boolean).join(', ');
            previewModal.querySelector('[data-preview-name]').textContent = spotLocation ? `${spotName} — ${spotLocation}` : spotName;
            previewModal.querySelector('[data-preview-category]').textContent = data.spotCategory || 'Uncategorized';
            previewModal.querySelector('[data-preview-municipality]').textContent = data.spotMunicipality || 'Unknown Municipality';

            const status = data.spotStatus || 'Recorded';
            const normalizedStatus = status.toLowerCase();
            const statusBadge = previewModal.querySelector('[data-preview-status]');
            statusBadge.className = 'badge ' + (normalizedStatus === 'pending' ? 'bg-warning text-dark' : (['approved', 'verified'].includes(normalizedStatus) ? 'bg-success' : (normalizedStatus === 'rejected' ? 'bg-danger' : 'bg-secondary')));
            statusBadge.textContent = ['approved', 'verified'].includes(normalizedStatus) ? 'Verified' : status;
            previewModal.querySelector('[data-preview-description]').textContent = data.spotDescription || 'No description available.';

            try {
                galleryImages = JSON.parse(data.spotImages || '[]').filter((url) => typeof url === 'string' && url.length > 0);
            } catch {
                galleryImages = [];
            }
            if (!galleryImages.length && data.spotImage) galleryImages = [data.spotImage];
            activeImageIndex = 0;
            renderGallery();

            setOptionalDetail('submitter', data.spotSubmitter);
            setOptionalDetail('submitterContact', data.spotSubmitterContact);
            setOptionalDetail('submitted', data.spotSubmitted);
            setOptionalDetail('approved', data.spotApproved);
            setOptionalDetail('revisionReason', data.spotRejectionReason);
            setOptionalDetail('facilities', '');
            setOptionalDetail('address', data.spotAddress);
            setOptionalDetail('barangay', data.spotBarangay);
            setOptionalDetail('hours', data.spotHours);
            setOptionalDetail('fee', data.spotFee);
            setOptionalDetail('phone', data.spotPhone);
            setOptionalDetail('website', data.spotWebsite);

            if (editAction) {
                const spotId = data.spotId || trigger.closest('[data-spot-id]')?.dataset.spotId;
                activeSpotId = spotId;
                editAction.hidden = !spotId;
            }

            const latitude = Number(data.spotLatitude);
            const longitude = Number(data.spotLongitude);
            const hasCoordinates = data.spotLatitude !== '' && data.spotLongitude !== ''
                && Number.isFinite(latitude) && Number.isFinite(longitude)
                && latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180;
            setOptionalDetail('coordinates', hasCoordinates ? `${latitude.toFixed(6)}, ${longitude.toFixed(6)}` : '');
            const mapContainer = previewModal.querySelector('[data-preview-map-container]');
            const map = previewModal.querySelector('[data-preview-map]');
            mapContainer.hidden = !hasCoordinates;
            if (hasCoordinates) {
                previewCoordinates = { lat: latitude, lng: longitude };
                previewSpotName = data.spotName || 'Tourist spot';
            } else {
                previewCoordinates = null;
                map.hidden = false;
                previewModal.querySelector('[data-preview-map-unavailable]').hidden = true;
            }

            const sourceModal = trigger.closest('.modal');
            const showPreview = () => bootstrap.Modal.getOrCreateInstance(previewModal).show();
            if (sourceModal && sourceModal !== previewModal) {
                sourceModal.addEventListener('hidden.bs.modal', showPreview, { once: true });
                bootstrap.Modal.getOrCreateInstance(sourceModal).hide();
            } else {
                showPreview();
            }
        });
    })();
</script>