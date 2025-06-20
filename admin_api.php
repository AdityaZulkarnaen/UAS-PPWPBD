<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';

// Start session
start_session();

// Check if user is logged in and is admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_users':
            $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($users);
            break;

        case 'get_jobs':
            $stmt = $pdo->query("SELECT id, title, company, status, is_active, created_at FROM jobs ORDER BY created_at DESC");
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($jobs);
            break;

        case 'get_companies':
            $stmt = $pdo->query("SELECT id, name, industry, is_verified, created_at FROM companies ORDER BY created_at DESC");
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($companies);
            break;

        case 'get_applications':
            $stmt = $pdo->query("
                SELECT a.id, a.status, a.applied_at, j.title as job_title, u.name as user_name 
                FROM applications a 
                JOIN jobs j ON a.job_id = j.id 
                JOIN users u ON a.user_id = u.id 
                ORDER BY a.applied_at DESC
            ");
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($applications);
            break;

        case 'get_categories':
            $stmt = $pdo->query("SELECT id, name, description, is_active FROM categories ORDER BY name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($categories);
            break;

        case 'get_notifications':
            $stmt = $pdo->query("
                SELECT n.id, n.title, n.type, n.is_read, n.created_at, u.name as user_name 
                FROM notifications n 
                JOIN users u ON n.user_id = u.id 
                ORDER BY n.created_at DESC 
                LIMIT 100
            ");
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($notifications);
            break;

        case 'delete_user':
            $id = $_POST['id'] ?? 0;
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
                $result = $stmt->execute([$id]);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            }
            break;

        case 'delete_job':
            $id = $_POST['id'] ?? 0;
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
                $result = $stmt->execute([$id]);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            }
            break;

        case 'verify_company':
            $id = $_POST['id'] ?? 0;
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE companies SET is_verified = 1 WHERE id = ?");
                $result = $stmt->execute([$id]);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            }
            break;

        case 'delete_company':
            $id = $_POST['id'] ?? 0;
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
                $result = $stmt->execute([$id]);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            }
            break;

        case 'delete_category':
            $id = $_POST['id'] ?? 0;
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $result = $stmt->execute([$id]);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            }
            break;

        case 'delete_notification':
            $id = $_POST['id'] ?? 0;
            if ($id > 0) {
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
                $result = $stmt->execute([$id]);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
