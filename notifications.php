<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php';

// Start session
start_session();

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: src/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all notifications with error handling
try {
    $notifications = get_user_notifications($user_id, false, 50);
    $unread_count = get_unread_notifications_count($user_id);
} catch (Exception $e) {
    error_log("Notifications error: " . $e->getMessage());
    $notifications = [];
    $unread_count = 0;
}

// Mark all as read if requested
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    header('Location: notifications.php');
    exit();
}

// Delete notification if requested
if (isset($_POST['delete_notification']) && isset($_POST['notification_id'])) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['notification_id'], $user_id]);
    header('Location: notifications.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - HireWay</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .main-content {
            background: #ffffff;
            margin-top: 2rem;
            border-radius: 2rem 2rem 0 0;
            min-height: calc(100vh - 120px);
            padding: 2rem 0;
        }
        
        .notification-item {
            border-left: 4px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .notification-item.unread {
            background-color: #f8f9fa;
            border-left-color: #0d6efd;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .notification-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .notification-actions {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .notification-item:hover .notification-actions {
            opacity: 1;
        }
    </style>
</head>
<body>    <!-- Navigation -->
    <?php include 'src/includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-bell me-2"></i>Notifikasi
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge bg-primary"><?= $unread_count ?> Belum Dibaca</span>
                                <?php endif; ?>
                            </h4>
                            <?php if ($unread_count > 0): ?>
                            <form method="POST" class="d-inline">
                                <button type="submit" name="mark_all_read" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-check-double me-1"></i>Tandai Semua Dibaca
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($notifications)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-bell-slash text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">Belum Ada Notifikasi</h5>
                                <p class="text-muted">Notifikasi akan muncul di sini ketika ada aktivitas baru</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="notification-item p-3 border-bottom <?= !$notification['is_read'] ? 'unread' : '' ?>">
                                    <div class="d-flex align-items-start">
                                        <div class="notification-icon <?= getNotificationIconClass($notification['type']) ?> me-3">
                                            <i class="<?= getNotificationIcon($notification['type']) ?>"></i>
                                        </div>
                                        
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($notification['title']) ?></h6>
                                            <p class="mb-2 text-muted"><?= htmlspecialchars($notification['message']) ?></p>
                                            <div class="notification-time">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= time_ago($notification['created_at']) ?>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary ms-2">Baru</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="notification-actions">
                                            <?php if (!$notification['is_read']): ?>
                                                <button class="btn btn-sm btn-outline-primary me-2" onclick="markAsRead(<?= $notification['id'] ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                <button type="submit" name="delete_notification" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus notifikasi ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-custom');
            if (navbar) {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
        });

        function markAsRead(notificationId) {
            fetch('notification_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_read',
                    notification_id: notificationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    </script>
</body>
</html>

<?php
function getNotificationIcon($type) {
    switch ($type) {
        case 'application':
            return 'fas fa-paper-plane';
        case 'job_alert':
            return 'fas fa-bell';
        case 'system':
            return 'fas fa-info-circle';
        case 'promotion':
            return 'fas fa-star';
        default:
            return 'fas fa-bell';
    }
}

function getNotificationIconClass($type) {
    switch ($type) {
        case 'application':
            return 'bg-success text-white';
        case 'job_alert':
            return 'bg-primary text-white';
        case 'system':
            return 'bg-info text-white';
        case 'promotion':
            return 'bg-warning text-dark';
        default:
            return 'bg-secondary text-white';
    }
}
?>
