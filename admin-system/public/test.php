<?php
// Test connection
header('Content-Type: application/json');

try {
    $db = new PDO("mysql:host=127.0.0.1;dbname=tourist_spot_db", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $result = $db->query("SELECT COUNT(*) as municipalities FROM municipalities");
    $data = $result->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'System is working!',
        'municipalities' => (int)$data['municipalities'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
