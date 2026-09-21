<?php
// Delete tourist spot
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

$spot_id = $_GET['id'] ?? null;
$municipality_id = $_GET['municipality_id'] ?? null;

if ($spot_id) {
    try {
        $stmt = $db->prepare("DELETE FROM tourist_spots WHERE id = ?");
        $stmt->execute([$spot_id]);
        header('Location: dashboard.php?municipality_id=' . $municipality_id . '&deleted=1');
        exit;
    } catch (Exception $e) {
        header('Location: dashboard.php?municipality_id=' . $municipality_id . '&error=1');
        exit;
    }
}

header('Location: dashboard.php');
exit;
?>
