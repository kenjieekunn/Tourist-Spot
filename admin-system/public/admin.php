<?php
// Simple admin panel for managing tourist spots
session_start();

// Database connection
$db = new PDO('mysql:host=127.0.0.1;dbname=tourist_spot_db', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pre-defined municipalities
$municipalities = [
    1 => 'Lingayen',
    2 => 'Basista',
    3 => 'Urbiztondo',
    4 => 'Aguilar',
    5 => 'Bugallon',
    6 => 'Binmaley',
    7 => 'Labrador',
    8 => 'Mangatarem'
];

// Prepare municipalities in database
try {
    // Check if municipalities table has data
    $stmt = $db->query('SELECT COUNT(*) as cnt FROM municipalities');
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    if ($count == 0) {
        // Insert municipalities
        $mun_data = [
            ['name' => 'Lingayen', 'lat' => 16.0146, 'lng' => 120.2327, 'desc' => 'Capital of Pangasinan'],
            ['name' => 'Basista', 'lat' => 16.0427, 'lng' => 120.2436, 'desc' => 'Municipality in Pangasinan'],
            ['name' => 'Urbiztondo', 'lat' => 16.0713, 'lng' => 120.2189, 'desc' => 'Municipality in Pangasinan'],
            ['name' => 'Aguilar', 'lat' => 15.9896, 'lng' => 120.2553, 'desc' => 'Municipality in Pangasinan'],
            ['name' => 'Bugallon', 'lat' => 16.0574, 'lng' => 120.1905, 'desc' => 'Municipality in Pangasinan'],
            ['name' => 'Binmaley', 'lat' => 15.9789, 'lng' => 120.1835, 'desc' => 'Municipality in Pangasinan'],
            ['name' => 'Labrador', 'lat' => 16.0045, 'lng' => 120.2087, 'desc' => 'Municipality in Pangasinan'],
            ['name' => 'Mangatarem', 'lat' => 15.9523, 'lng' => 120.2348, 'desc' => 'Municipality in Pangasinan'],
        ];
        
        $insert_stmt = $db->prepare('INSERT INTO municipalities (name, description, latitude, longitude, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        
        foreach ($mun_data as $m) {
            $insert_stmt->execute([$m['name'], $m['desc'], $m['lat'], $m['lng']]);
        }
    }
} catch (Exception $e) {
    // Municipalities table might not exist yet
}

// Handle POST requests
$action = $_REQUEST['action'] ?? '';
$response = '';

if ($action === 'add_spot') {
    try {
        $stmt = $db->prepare('INSERT INTO tourist_spots (municipality_id, name, description, address, latitude, longitude, phone, website, opening_hours, entrance_fee, image_url, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $_POST['municipality_id'],
            $_POST['name'],
            $_POST['description'],
            $_POST['address'],
            $_POST['latitude'],
            $_POST['longitude'],
            $_POST['phone'],
            $_POST['website'],
            $_POST['opening_hours'],
            $_POST['entrance_fee'],
            $_POST['image_url'],
            'active'
        ]);
        $response = '<div class="alert alert-success">Tourist spot added successfully!</div>';
    } catch (Exception $e) {
        $response = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if ($action === 'edit_spot') {
    try {
        $stmt = $db->prepare('UPDATE tourist_spots SET municipality_id=?, name=?, description=?, address=?, latitude=?, longitude=?, phone=?, website=?, opening_hours=?, entrance_fee=?, image_url=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([
            $_POST['municipality_id'],
            $_POST['name'],
            $_POST['description'],
            $_POST['address'],
            $_POST['latitude'],
            $_POST['longitude'],
            $_POST['phone'],
            $_POST['website'],
            $_POST['opening_hours'],
            $_POST['entrance_fee'],
            $_POST['image_url'],
            $_POST['id']
        ]);
        $response = '<div class="alert alert-success">Tourist spot updated successfully!</div>';
    } catch (Exception $e) {
        $response = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

if ($action === 'delete_spot') {
    try {
        $stmt = $db->prepare('DELETE FROM tourist_spots WHERE id=?');
        $stmt->execute([$_POST['id']]);
        $response = '<div class="alert alert-success">Tourist spot deleted successfully!</div>';
    } catch (Exception $e) {
        $response = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Get list of tourist spots
$edit_id = $_GET['edit'] ?? null;
$edit_spot = null;

if ($edit_id) {
    $stmt = $db->prepare('SELECT * FROM tourist_spots WHERE id=?');
    $stmt->execute([$edit_id]);
    $edit_spot = $stmt->fetch(PDO::FETCH_ASSOC);
}

$spots_stmt = $db->query('SELECT * FROM tourist_spots ORDER BY name');
$spots = $spots_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourist Spot Admin - Manage Tourism</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; }
        .navbar-brand { color: white !important; font-weight: bold; font-size: 1.5rem; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 10px; }
        .btn-primary { background: #667eea; border: none; }
        .btn-primary:hover { background: #5568d3; }
        .table-hover tbody tr:hover { background-color: #f5f5f5; }
        .badge-status { padding: 0.5rem 1rem; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25); }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark mb-4" style="background: #667eea;">
        <div class="container">
            <span class="navbar-brand">🌍 Tourist Spot Management System</span>
            <small style="color: white;">Pangasinan 2nd District</small>
        </div>
    </nav>

    <div class="container">
        <?php echo $response; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">📍 Tourist Spots List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($spots)): ?>
                            <p class="text-muted">No tourist spots added yet. Create one using the form on the right.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Municipality</th>
                                            <th>Address</th>
                                            <th>Fee</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($spots as $spot): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($spot['name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($municipalities[$spot['municipality_id']] ?? 'Unknown'); ?></td>
                                                <td><?php echo htmlspecialchars($spot['address']); ?></td>
                                                <td>₱<?php echo number_format($spot['entrance_fee'] ?? 0, 2); ?></td>
                                                <td>
                                                    <a href="?edit=<?php echo $spot['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                                    <button class="btn btn-sm btn-danger" onclick="if(confirm('Are you sure?')) { document.getElementById('delete_form_<?php echo $spot['id']; ?>').submit(); }">Delete</button>
                                                    <form id="delete_form_<?php echo $spot['id']; ?>" method="POST" style="display:none;">
                                                        <input type="hidden" name="action" value="delete_spot">
                                                        <input type="hidden" name="id" value="<?php echo $spot['id']; ?>">
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><?php echo $edit_spot ? '✏️ Edit Tourist Spot' : '➕ Add New Tourist Spot'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="<?php echo $edit_spot ? 'edit_spot' : 'add_spot'; ?>">
                            <?php if ($edit_spot): ?>
                                <input type="hidden" name="id" value="<?php echo $edit_spot['id']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Municipality *</label>
                                <select name="municipality_id" class="form-control" required>
                                    <option value="">-- Select Municipality --</option>
                                    <?php foreach ($municipalities as $id => $name): ?>
                                        <option value="<?php echo $id; ?>" <?php echo ($edit_spot && $edit_spot['municipality_id'] == $id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_spot['name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description *</label>
                                <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($edit_spot['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address *</label>
                                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($edit_spot['address'] ?? ''); ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Latitude *</label>
                                    <input type="number" name="latitude" class="form-control" step="0.00001" value="<?php echo $edit_spot['latitude'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Longitude *</label>
                                    <input type="number" name="longitude" class="form-control" step="0.00001" value="<?php echo $edit_spot['longitude'] ?? ''; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($edit_spot['phone'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Website</label>
                                <input type="url" name="website" class="form-control" value="<?php echo htmlspecialchars($edit_spot['website'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Opening Hours</label>
                                <textarea name="opening_hours" class="form-control" rows="2" placeholder="e.g., Mon-Fri: 8AM-5PM"><?php echo htmlspecialchars($edit_spot['opening_hours'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Entrance Fee (₱)</label>
                                <input type="number" name="entrance_fee" class="form-control" step="0.01" value="<?php echo $edit_spot['entrance_fee'] ?? ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Image URL</label>
                                <input type="url" name="image_url" class="form-control" value="<?php echo htmlspecialchars($edit_spot['image_url'] ?? ''); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <?php echo $edit_spot ? '💾 Update Spot' : '➕ Add Spot'; ?>
                            </button>

                            <?php if ($edit_spot): ?>
                                <a href="?" class="btn btn-secondary w-100 mt-2">Cancel</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
