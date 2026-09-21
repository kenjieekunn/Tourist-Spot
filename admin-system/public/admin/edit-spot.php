<?php
// Edit/Add Tourist Spot with Map
session_start();

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: index.php');
    exit;
}

$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'tourist_spot_db';

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed");
}

$municipality_id = $_GET['municipality_id'] ?? $_POST['municipality_id'] ?? null;
$spot_id = $_GET['id'] ?? null;
$spot = null;
$message = '';
$error = '';

// Get municipalities
$municipalities_stmt = $db->query("SELECT * FROM municipalities ORDER BY name");
$municipalities = $municipalities_stmt->fetchAll(PDO::FETCH_ASSOC);

// Load spot if editing
if ($spot_id) {
    $stmt = $db->prepare("SELECT * FROM tourist_spots WHERE id = ?");
    $stmt->execute([$spot_id]);
    $spot = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$spot) {
        $error = 'Tourist spot not found';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $municipality_id = $_POST['municipality_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $address = $_POST['address'] ?? '';
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $image_url = $_POST['image_url'] ?? '';
    $nearby_dining = $_POST['nearby_dining'] ?? '';
    $nearby_gas_stations = $_POST['nearby_gas_stations'] ?? '';

    // Trim whitespace
    $name = trim($name);
    $description = trim($description);
    $address = trim($address);

    // Validate required fields
    if (!$municipality_id || !$name || !$description || !$address || !$latitude || !$longitude) {
        $error = 'Please fill in all required fields';
    }
    // Validate name - minimum 3 characters
    elseif (strlen($name) < 3) {
        $error = 'Tourist spot name must be at least 3 characters long';
    }
    // Validate name - maximum 255 characters
    elseif (strlen($name) > 255) {
        $error = 'Tourist spot name cannot exceed 255 characters';
    }
    // Validate name - allowed characters only
    elseif (!preg_match('/^[a-zA-Z0-9\s\-.,&()\']+$/', $name)) {
        $error = 'Tourist spot name contains invalid characters. Only letters, numbers, spaces, and special characters (-, ., ,, &, \') are allowed';
    }
    // Check for duplicate name (if creating new record)
    elseif (!$spot_id) {
        $stmt = $db->prepare("SELECT id FROM tourist_spots WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            $error = 'This tourist spot name already exists. Please use a different name';
        }
    }
    // Check for duplicate name (if editing, exclude current record)
    elseif ($spot_id) {
        $stmt = $db->prepare("SELECT id FROM tourist_spots WHERE name = ? AND id != ? LIMIT 1");
        $stmt->execute([$name, $spot_id]);
        if ($stmt->fetch()) {
            $error = 'This tourist spot name already exists. Please use a different name';
        }
    }

    if (!$error) {
        try {
            if ($spot_id) {
                // Update
                $stmt = $db->prepare("UPDATE tourist_spots SET municipality_id=?, name=?, description=?, address=?, latitude=?, longitude=?, image_url=?, nearby_dining=?, nearby_gas_stations=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$municipality_id, $name, $description, $address, $latitude, $longitude, $image_url, $nearby_dining, $nearby_gas_stations, $spot_id]);
                $message = 'Tourist spot updated successfully!';
            } else {
                // Insert
                $stmt = $db->prepare("INSERT INTO tourist_spots (municipality_id, name, description, address, latitude, longitude, image_url, nearby_dining, nearby_gas_stations, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())");
                $stmt->execute([$municipality_id, $name, $description, $address, $latitude, $longitude, $image_url, $nearby_dining, $nearby_gas_stations]);
                $message = 'Tourist spot created successfully!';
            }
            
            // Redirect after 2 seconds
            echo '<script>
                setTimeout(function() {
                    window.location.href = "dashboard.php?municipality_id=' . $municipality_id . '";
                }, 2000);
            </script>';
        } catch (Exception $e) {
            $error = 'Error saving tourist spot: ' . $e->getMessage();
        }
    }
}

// Default coordinates for map center (Pangasinan)
$map_lat = $spot ? $spot['latitude'] : 16.02;
$map_lng = $spot ? $spot['longitude'] : 120.24;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $spot ? 'Edit' : 'Add'; ?> Tourist Spot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Control Geocoder for search functionality -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <style>
        body { background: #f5f5f5; font-family: 'Segoe UI'; }
        .form-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .form-card { background: white; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
        }
        .form-body { padding: 30px; }
        .form-section { margin-bottom: 30px; }
        .form-section-title {
            font-weight: bold;
            color: #667eea;
            font-size: 1rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 40px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn-submit:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-cancel {
            border: 2px solid #ddd;
            color: #666;
            padding: 12px 40px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            background: white;
        }
        #map {
            height: 400px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 2px solid #ddd;
        }
        .search-location-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 2px solid #667eea;
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
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .search-location-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
        .map-controls {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .map-controls button {
            padding: 8px 15px;
            border: 1px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .map-controls button:hover {
            background: #667eea;
            color: white;
        }
        .alert { border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-weight: 600; margin-bottom: 8px; color: #333; }
        .required::after { content: ' *'; color: red; }
        .row { --bs-gutter-x: 20px; }
        .success-message {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .gps-status {
            padding: 10px 15px;
            background: #f0f0f0;
            border-radius: 5px;
            font-size: 0.9rem;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <i class="fas fa-<?php echo $spot ? 'edit' : 'plus'; ?>"></i>
                <?php echo $spot ? 'Edit Tourist Spot' : 'Add New Tourist Spot'; ?>
            </div>
            
            <div class="form-body">
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        <br><small>Redirecting...</small>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <!-- Location Section -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-map"></i> Location & Map</div>
                        
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="form-label required">Municipality</label>
                                <select name="municipality_id" class="form-control" required>
                                    <option value="">-- Select Municipality --</option>
                                    <?php foreach ($municipalities as $mun): ?>
                                        <option value="<?php echo $mun['id']; ?>" 
                                            <?php echo ($municipality_id == $mun['id'] || ($spot && $spot['municipality_id'] == $mun['id'])) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mun['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 form-group">
                                <label class="form-label required">Name</label>
                                <input 
                                    type="text" 
                                    name="name" 
                                    id="name-input"
                                    class="form-control" 
                                    value="<?php echo htmlspecialchars($spot['name'] ?? ''); ?>" 
                                    minlength="3"
                                    maxlength="255"
                                    pattern="[a-zA-Z0-9\s\-.,&()']+"
                                    placeholder="Enter tourist spot name (3-255 characters)"
                                    required
                                >
                                <small style="display: block; margin-top: 8px; color: #666;">
                                    <i class="fas fa-info-circle"></i> Name must be 3-255 characters
                                </small>
                                <div id="name-validation-feedback" style="margin-top: 8px;"></div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="form-label required">Latitude</label>
                                <input type="number" name="latitude" id="latitude" class="form-control" step="0.00001" value="<?php echo $spot['latitude'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 form-group">
                                <label class="form-label required">Longitude</label>
                                <input type="number" name="longitude" id="longitude" class="form-control" step="0.00001" value="<?php echo $spot['longitude'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="map-controls">
                            <button type="button" onclick="getCurrentLocation()">
                                <i class="fas fa-crosshairs"></i> Use Current Location
                            </button>
                            <small style="flex: 1; display: flex; align-items: center; color: #999;">
                                Click on map to set location, search for location, or enter coordinates
                            </small>
                        </div>

                        <!-- Location Search Bar -->
                        <div class="search-location-container">
                            <div class="search-location-form">
                                <div class="search-location-input-group">
                                    <input 
                                        type="text" 
                                        id="location-search" 
                                        class="search-location-input" 
                                        placeholder="Search for location... (e.g., 'Hundred Islands, Pangasinan' or address)"
                                        autocomplete="off"
                                    >
                                    <div class="search-suggestions" id="search-suggestions"></div>
                                </div>
                                <button type="button" onclick="searchLocation()" style="padding: 10px 20px; border: 1px solid #667eea; background: #667eea; color: white; border-radius: 5px; cursor: pointer; font-weight: 600;">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                        
                        <div id="map"></div>
                        
                        <div class="gps-status" id="gps-status" style="display:none;">
                            <i class="fas fa-info-circle"></i> <span id="gps-text"></span>
                        </div>
                    </div>
                    
                    <!-- Basic Information -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="fas fa-info-circle"></i> Basic Information</div>
                        
                        <div class="form-group">
                            <label class="form-label required">Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($spot['address'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required">Description (History)</label>
                            <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($spot['description'] ?? ''); ?></textarea>
                            <small class="text-muted">Describe the history and significance of this tourist spot</small>
                        </div>
                        
                    <div class="form-group">
                        <label class="form-label">Image URL</label>
                        <input type="url" name="image_url" class="form-control" value="<?php echo htmlspecialchars($spot['image_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                    </div>
                </div>

                <!-- Nearby Facilities -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-utensils"></i> Nearby Facilities</div>
                    
                    <div class="form-group">
                        <label class="form-label">Nearby Dining Areas</label>
                        <textarea name="nearby_dining" class="form-control" rows="3" placeholder="List nearby restaurants or food establishments..."><?php echo htmlspecialchars($spot['nearby_dining'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nearby Gas Stations</label>
                        <textarea name="nearby_gas_stations" class="form-control" rows="3" placeholder="List nearby gas stations or fuel services..."><?php echo htmlspecialchars($spot['nearby_gas_stations'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-section" style="margin-bottom: 0;">
                    <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> <?php echo $spot ? 'Update Spot' : 'Create Spot'; ?>
                        </button>
                        <button type="button" class="btn-cancel" onclick="window.location.href='dashboard.php?municipality_id=<?php echo $municipality_id; ?>'">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let map, marker;
        let searchTimeout;

        // Initialize map
        function initMap() {
            map = L.map('map').setView([<?php echo $map_lat; ?>, <?php echo $map_lng; ?>], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);
            
            // Add marker if editing
            <?php if ($spot): ?>
                marker = L.marker([<?php echo $spot['latitude']; ?>, <?php echo $spot['longitude']; ?>]).addTo(map);
            <?php endif; ?>
            
            // Click on map to add marker
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                
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
            
            showGpsStatus('Location pinpointed: ' + result.name, true);
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
        
        // Get current location
        function getCurrentLocation() {
            if (navigator.geolocation) {
                showGpsStatus('Getting your location...');
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        document.getElementById('latitude').value = lat.toFixed(5);
                        document.getElementById('longitude').value = lng.toFixed(5);
                        
                        map.setView([lat, lng], 15);
                        
                        if (marker) {
                            marker.setLatLng([lat, lng]);
                        } else {
                            marker = L.marker([lat, lng]).addTo(map);
                        }
                        
                        showGpsStatus('Location set: ' + lat.toFixed(5) + ', ' + lng.toFixed(5), true);
                    },
                    function(error) {
                        showGpsStatus('Error getting location. Make sure location permission is enabled.', false);
                    }
                );
            } else {
                showGpsStatus('Geolocation is not supported by your browser', false);
            }
        }
        
        function showGpsStatus(text, success = null) {
            const status = document.getElementById('gps-status');
            const statusText = document.getElementById('gps-text');
            status.style.display = 'block';
            statusText.innerText = text;
            if (success !== null) {
                status.style.borderLeftColor = success ? '#28a745' : '#dc3545';
                status.style.background = success ? '#d4edda' : '#f8d7da';
            }
        }
        
        // Initialize on load
        window.addEventListener('load', initMap);

        // Real-time validation for name field
        document.addEventListener('DOMContentLoaded', function() {
            const nameInput = document.getElementById('name-input');
            const feedbackDiv = document.getElementById('name-validation-feedback');
            
            if (!nameInput) return;
            
            // Name validation regex pattern
            const namePattern = /^[a-zA-Z0-9\s\-.,&()\']*$/;
            
            nameInput.addEventListener('input', function() {
                const value = this.value.trim();
                const feedback = feedbackDiv;
                
                // Clear previous feedback
                feedback.innerHTML = '';
                feedback.style.display = 'none';
                
                if (value.length === 0) {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                    return;
                }
                
                feedback.style.display = 'block';
                
                // Check length
                if (value.length < 3) {
                    feedback.innerHTML = '<small style="color: #ff9800;"><i class="fas fa-exclamation-triangle"></i> Name must be at least 3 characters (current: ' + value.length + ')</small>';
                    this.style.borderColor = '#ff9800';
                    this.style.boxShadow = 'inset 0 1px 3px rgba(0, 0, 0, 0.1)';
                    return;
                }
                
                if (value.length > 255) {
                    feedback.innerHTML = '<small style="color: #dc3545;"><i class="fas fa-times-circle"></i> Name cannot exceed 255 characters (current: ' + value.length + ')</small>';
                    this.style.borderColor = '#dc3545';
                    this.style.boxShadow = 'inset 0 1px 3px rgba(0, 0, 0, 0.1)';
                    return;
                }
                
                // Check pattern
                if (!namePattern.test(value)) {
                    feedback.innerHTML = '<small style="color: #dc3545;"><i class="fas fa-times-circle"></i> Name contains invalid characters. Only letters, numbers, spaces, and - . , & \' are allowed.</small>';
                    this.style.borderColor = '#dc3545';
                    this.style.boxShadow = 'inset 0 1px 3px rgba(0, 0, 0, 0.1)';
                    return;
                }
                
                // All validations passed
                feedback.innerHTML = '<small style="color: #28a745;"><i class="fas fa-check-circle"></i> Name is valid (' + value.length + ' characters)</small>';
                this.style.borderColor = '#28a745';
                this.style.boxShadow = 'inset 0 1px 3px rgba(0, 0, 0, 0.1)';
            });
            
            // Validate on blur
            nameInput.addEventListener('blur', function() {
                this.value = this.value.trim();
                this.dispatchEvent(new Event('input'));
            });
        });

    </script>
</body>
</html>
