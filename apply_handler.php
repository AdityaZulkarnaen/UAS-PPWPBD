<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php';
require_once 'src/includes/csrf_helper.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
start_session();
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$job_id = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
$csrf_token = $_POST['csrf_token'] ?? '';

if (!$job_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid job ID']);
    exit;
}

// Validate CSRF token
if (function_exists('validate_csrf_token')) {
    if (!validate_csrf_token($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
} else {
    // Fallback validation
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $csrf_token) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

try {
    $user_id = get_user_id();
    
    // Check if job exists
    $stmt = $pdo->prepare("SELECT title, company FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }
    
    // Check if user already applied
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
    $stmt->execute([$job_id, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false, 
            'message' => 'Anda sudah melamar pekerjaan ini sebelumnya'
        ]);
        exit;
    }
    
    // Insert application
    $stmt = $pdo->prepare("
        INSERT INTO applications (job_id, user_id, status, applied_at) 
        VALUES (?, ?, 'pending', NOW())
    ");
    
    if ($stmt->execute([$job_id, $user_id])) {
        // Get total applications count for this user
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ?");
        $count_stmt->execute([$user_id]);
        $total_applications = $count_stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'message' => 'Lamaran berhasil dikirim! Silahkan tunggu pesan dari bagian HR.',
            'job_title' => $job['title'],
            'company' => $job['company'],
            'total_applications' => $total_applications
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal mengirim lamaran. Silakan coba lagi.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Application error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>