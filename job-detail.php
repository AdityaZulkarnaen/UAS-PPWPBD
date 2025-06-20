<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';

// Start session
start_session();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login untuk melihat detail lowongan'
    ]);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID lowongan tidak valid'
    ]);
    exit;
}

$job_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        echo json_encode([
            'success' => false,
            'message' => 'Lowongan tidak ditemukan'
        ]);
        exit;
    }
    
    // Format dates
    $job['created_at'] = date('d M Y', strtotime($job['created_at']));
    if (!empty($job['deadline'])) {
        $job['deadline'] = date('d M Y', strtotime($job['deadline']));
    }
    
    echo json_encode([
        'success' => true,
        'job' => $job
    ]);
    
} catch (Exception $e) {
    error_log("Job detail error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan saat mengambil data lowongan'
    ]);
}
?>