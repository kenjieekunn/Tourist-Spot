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
            <div class="modal-body">
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
                            <dt class="col-sm-4" data-preview-row="approved" hidden>Date approved</dt>
                            <dd class="col-sm-8" data-preview-value-for="approved" hidden></dd>
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
        </div>
    </div>
</div>

<script>
    (() => {
        const previewModal = document.getElementById('touristSpotPreviewModal');
        if (!previewModal) return;

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

            const data = trigger.dataset;
            const spotName = data.spotName || 'Tourist Spot Details';
            const spotLocation = [data.spotBarangay, data.spotMunicipality, 'Pangasinan'].filter(Boolean).join(', ');
            previewModal.querySelector('[data-preview-name]').textContent = spotLocation ? `${spotName} — ${spotLocation}` : spotName;
            previewModal.querySelector('[data-preview-category]').textContent = data.spotCategory || 'Uncategorized';
            previewModal.querySelector('[data-preview-municipality]').textContent = data.spotMunicipality || 'Unknown Municipality';

            const status = data.spotStatus || 'Recorded';
            const normalizedStatus = status.toLowerCase();
            const statusBadge = previewModal.querySelector('[data-preview-status]');
            statusBadge.className = 'badge ' + (normalizedStatus === 'pending' ? 'bg-warning text-dark' : (normalizedStatus === 'approved' ? 'bg-success' : (normalizedStatus === 'rejected' ? 'bg-danger' : 'bg-secondary')));
            statusBadge.textContent = status;
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
            setOptionalDetail('address', data.spotAddress);
            setOptionalDetail('barangay', data.spotBarangay);
            setOptionalDetail('hours', data.spotHours);
            setOptionalDetail('fee', data.spotFee);
            setOptionalDetail('phone', data.spotPhone);
            setOptionalDetail('website', data.spotWebsite);

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