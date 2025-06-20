<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php';

// Start session
start_session();

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

try {
    switch ($input['action']) {
        case 'mark_read':
            if (!isset($input['notification_id'])) {
                throw new Exception('Notification ID required');
            }
            
            $result = mark_notification_read($input['notification_id'], $user_id);
            
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notification marked as read' : 'Failed to mark notification as read'
            ]);
            break;
            
        case 'get_unread_count':
            $count = get_unread_notifications_count($user_id);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        case 'get_recent':
            $limit = isset($input['limit']) ? intval($input['limit']) : 5;
            $notifications = get_user_notifications($user_id, true, $limit);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
