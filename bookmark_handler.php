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

// Validate CSRF token - check if function exists first
if (function_exists('validate_csrf_token')) {
    if (!validate_csrf_token($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
} else {
    // Fallback validation if function doesn't exist
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $csrf_token) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

// Check if job exists
try {
    $stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error in bookmark_handler: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

try {
    $user_id = get_user_id();
    
    // Check if toggle_bookmark function exists
    if (!function_exists('toggle_bookmark')) {
        throw new Exception('toggle_bookmark function not found');
    }
    
    $result = toggle_bookmark($user_id, $job_id);
    
    echo json_encode([
        'success' => true,
        'bookmarked' => $result['bookmarked'],
        'message' => $result['message']
    ]);
} catch (Exception $e) {
    error_log("Bookmark error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>