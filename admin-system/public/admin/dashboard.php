<?php
// EXACT Laravel Admin Dashboard Replica (PHP version)
session_start();

// Check admin login
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
    die("Database connection failed: " . $e->getMessage());
}

// Exact DashboardController data
$totalSpots = $db->query("SELECT COUNT(*) as total FROM tourist_spots")->fetch(PDO::FETCH_ASSOC)['total'];
$totalMunicipalities = $db->query("SELECT COUNT(*) as total FROM municipalities")->fetch(PDO::FETCH_ASSOC)['total'];
$totalReviews = $db->query("SELECT COUNT(*) as total FROM reviews")->fetch(PDO::FETCH_ASSOC)['total'];
$pendingReviews = $db->query("SELECT COUNT(*) as total FROM reviews WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['total'];

// Recent spots exact query (latest 5, assume status field)
$recentSpotsStmt = $db->query("
    SELECT ts.*, m.name as municipality_name, m.id as municipality_id
    FROM tourist_spots ts 
    LEFT JOIN municipalities m ON ts.municipality_id = m.id 
    ORDER BY ts.created_at DESC 
    LIMIT 5
");
$recentSpots = $recentSpotsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tourist Spot Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* EXACT CSS from Laravel layout/app.blade.php */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #2c3e50;
            min-height: 100vh;
            color: white;
            padding: 0;
        }
        .sidebar .nav-link {
            color: #bbb;
            padding: 1rem 1.5rem;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
            border-left-color: #ff6b35;
        }
        .sidebar .brand {
            padding: 1.5rem;
            background-color: #1a252f;
            border-bottom: 1px solid #444;
            font-size: 1.5rem;
            font-weight: bold;
            color: #ff6b35;
        }
        .main-content {
            padding: 2rem 1.5rem;
        }
        .navbar-custom {
            margin-bottom: 1.5rem;
        }
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #ff6b35;
            border-color: #ff6b35;
        }
        .btn-primary:hover {
            background-color: #e55a2b;
            border-color: #e55a2b;
        }
        .stat-card {
            text-align: center;
            padding: 2rem;
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff6b35;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .navbar-custom {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
        }
        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- EXACT Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="brand">
                    <i class="fas fa-map-marker-alt"></i> Pangasinan 2nd District
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-dashboard"></i> Dashboard
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-map-location-dot"></i> Tourist Spots
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-city"></i> Municipalities
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-star"></i> Reviews
                    </a>
                    <hr style="border-color: #555;">
                    <a class="btn btn-sm btn-outline-danger w-100 mt-3" style="padding: 1rem 1.5rem;" href="index.php?logout=1">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- EXACT Main Content -->
            <div class="col-md-9 col-lg-10">
                <!-- EXACT Navbar -->
                <div class="navbar-custom d-flex justify-content-between align-items-center">
                    <h4 class="m-0">Dashboard</h4>
                    <div>
                        <span class="me-3">Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong></span>
                    </div>
                </div>

                <div class="main-content">
                    <!-- EXACT Stats Cards from dashboard.blade.php -->
                    <div class="row">
                        <div class="col-md-6 col-lg-4">
                            <div class="card stat-card">
                                <div class="stat-value"><?php echo $totalMunicipalities; ?></div>
                                <div class="stat-label">Municipalities</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card stat-card">
                                <div class="stat-value"><?php echo $totalSpots; ?></div>
                                <div class="stat-label">Tourist Spots</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card stat-card">
                                <div class="stat-value"><?php echo $totalReviews; ?></div>
                                <div class="stat-label">Total Reviews</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12 px-0">
                            <!-- EXACT Recent Table -->
                            <div class="card">
                                <div class="card-header bg-white border-bottom p-4">
                                    <h5 class="m-0"><i class="fas fa-star"></i> Recent Tourist Spots</h5>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-hover m-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Municipality</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recentSpots)): ?>
                                                <?php foreach ($recentSpots as $spot): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($spot['name']); ?></strong>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($spot['municipality_name'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo ($spot['status'] ?? 'active') === 'active' ? 'success' : 'danger'; ?>">
                                                                <?php echo ucfirst($spot['status'] ?? 'Active'); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="edit-spot.php?id=<?php echo $spot['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="edit-spot.php?id=<?php echo $spot['id']; ?>" class="btn btn-sm btn-warning">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4">No tourist spots yet</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
