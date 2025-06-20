<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php';

// Start session and check if user is employer
start_session();

if (!is_logged_in() || !is_employer()) {
    header('Location: src/auth/login.php');    exit();
}

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $application_id = intval($_POST['application_id']);
        $new_status = clean_input($_POST['status']);
        $notes = clean_input($_POST['notes'] ?? '');
        
        // Verify application belongs to employer's job
        $verify_stmt = $pdo->prepare("
            SELECT a.id 
            FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            WHERE a.id = ? AND j.posted_by = ?
        ");
        $verify_stmt->execute([$application_id, $_SESSION['user_id']]);
        
        if ($verify_stmt->fetch()) {
            $update_stmt = $pdo->prepare("
                UPDATE applications 
                SET status = ?, notes = ?, updated_at = NOW() 
                WHERE id = ?
            ");
              if ($update_stmt->execute([$new_status, $notes, $application_id])) {
                // Get application and job details for notification
                $app_detail_stmt = $pdo->prepare("
                    SELECT a.user_id, a.job_id, j.title as job_title, u.name as applicant_name, u.email as applicant_email
                    FROM applications a 
                    JOIN jobs j ON a.job_id = j.id 
                    JOIN users u ON a.user_id = u.id
                    WHERE a.id = ?
                ");
                $app_detail_stmt->execute([$application_id]);
                $app_detail = $app_detail_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($app_detail) {
                    // Create notification for the applicant
                    $notification_title = '';
                    $notification_message = '';
                    $notification_type = 'application_update';
                    
                    switch ($new_status) {
                        case 'accepted':
                            $notification_title = 'Lamaran Diterima! 🎉';
                            $notification_message = "Selamat! Lamaran Anda untuk posisi \"{$app_detail['job_title']}\" telah diterima.";
                            break;
                        case 'rejected':
                            $notification_title = 'Status Lamaran Diperbarui';
                            $notification_message = "Lamaran Anda untuk posisi \"{$app_detail['job_title']}\" tidak dapat dilanjutkan saat ini.";
                            break;
                        case 'interview':
                            $notification_title = 'Undangan Interview! 📞';
                            $notification_message = "Anda telah dipilih untuk tahap interview untuk posisi \"{$app_detail['job_title']}\".";
                            break;
                        case 'shortlisted':
                            $notification_title = 'Anda Masuk Shortlist! ⭐';
                            $notification_message = "Lamaran Anda untuk posisi \"{$app_detail['job_title']}\" telah masuk dalam daftar pendek.";
                            break;
                        default:
                            $notification_title = 'Status Lamaran Diperbarui';
                            $notification_message = "Status lamaran Anda untuk posisi \"{$app_detail['job_title']}\" telah diperbarui menjadi " . ucfirst($new_status) . ".";
                    }
                    
                    // Add notes to notification if provided
                    if (!empty($notes)) {
                        $notification_message .= "\n\nCatatan dari perusahaan: " . $notes;
                    }
                    
                    // Create notification
                    create_notification(
                        $app_detail['user_id'], 
                        $notification_title, 
                        $notification_message, 
                        $notification_type,
                        $application_id,
                        'application',
                        'applications.php'
                    );
                }
                
                $success_message = "Status pelamar berhasil diubah.";
            } else {
                $error_message = "Gagal mengubah status pelamar.";
            }
        } else {
            $error_message = "Akses tidak diizinkan.";
        }
    }
}

// Get job ID if specified
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : null;

// Build query based on job filter
if ($job_id) {
    // Verify job belongs to current employer
    $verify_stmt = $pdo->prepare("SELECT title FROM jobs WHERE id = ? AND posted_by = ?");
    $verify_stmt->execute([$job_id, $_SESSION['user_id']]);
    $job_info = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$job_info) {
        header('Location: my-jobs.php');
        exit();
    }
    
    $where_clause = "j.posted_by = ? AND a.job_id = ?";
    $params = [$_SESSION['user_id'], $job_id];
} else {
    $where_clause = "j.posted_by = ?";
    $params = [$_SESSION['user_id']];
}

// Get applications with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Ensure integer values for LIMIT and OFFSET
$per_page = (int) $per_page;
$offset = (int) $offset;

// Count total applications
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    WHERE $where_clause
");
$count_stmt->execute($params);
$total_applications = $count_stmt->fetchColumn();
$total_pages = ceil($total_applications / $per_page);

// Get applications - use direct concatenation for LIMIT and OFFSET
$applications_stmt = $pdo->prepare("
    SELECT a.*, j.title as job_title, u.name as applicant_name, u.email as applicant_email,
           u.phone, up.full_name, up.address, up.city
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    JOIN users u ON a.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE $where_clause 
    ORDER BY a.applied_at DESC 
    LIMIT $per_page OFFSET $offset
");

$applications_stmt->execute($params);
$applications = $applications_stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = is_logged_in() ? get_unread_notifications_count($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelamar - HireWay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .main-content {
            background: #ffffff;
            margin-top: 1rem;
            border-radius: 2rem 2rem 0 0;
            min-height: calc(100vh - 100px);
            padding: 2rem 0;
        }
        
        .applicant-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .applicant-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'src/includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-users me-2"></i>Pelamar</h2>
                    <?php if ($job_id): ?>
                        <p class="text-muted mb-0">Pelamar untuk: <strong><?= htmlspecialchars($job_info['title']) ?></strong></p>
                    <?php else: ?>
                        <p class="text-muted mb-0">Semua pelamar untuk lowongan Anda</p>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="my-jobs.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i>Kembali ke Lowongan
                    </a>
                    <?php if ($job_id): ?>
                        <a href="applicants.php" class="btn btn-primary">
                            <i class="fas fa-users me-1"></i>Semua Pelamar
                        </a>
                    <?php endif; ?>                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-users text-primary mb-2" style="font-size: 2rem;"></i>
                            <h4><?= $total_applications ?></h4>
                            <p class="mb-0 text-muted">Total Pelamar</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php
                    $pending_stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM applications a 
                        JOIN jobs j ON a.job_id = j.id 
                        WHERE $where_clause AND a.status = 'pending'
                    ");
                    $pending_stmt->execute($params);
                    $pending_count = $pending_stmt->fetchColumn();
                    ?>
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-clock text-warning mb-2" style="font-size: 2rem;"></i>
                            <h4><?= $pending_count ?></h4>
                            <p class="mb-0 text-muted">Menunggu Review</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php
                    $accepted_stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM applications a 
                        JOIN jobs j ON a.job_id = j.id 
                        WHERE $where_clause AND a.status = 'accepted'
                    ");
                    $accepted_stmt->execute($params);
                    $accepted_count = $accepted_stmt->fetchColumn();
                    ?>
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                            <h4><?= $accepted_count ?></h4>
                            <p class="mb-0 text-muted">Diterima</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php
                    $rejected_stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM applications a 
                        JOIN jobs j ON a.job_id = j.id 
                        WHERE $where_clause AND a.status = 'rejected'
                    ");
                    $rejected_stmt->execute($params);
                    $rejected_count = $rejected_stmt->fetchColumn();
                    ?>
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-times-circle text-danger mb-2" style="font-size: 2rem;"></i>
                            <h4><?= $rejected_count ?></h4>
                            <p class="mb-0 text-muted">Ditolak</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Applications List -->
            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-users text-muted" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mt-3">Belum Ada Pelamar</h4>
                    <p class="text-muted">Belum ada yang melamar ke lowongan Anda</p>
                </div>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                    <div class="card applicant-card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="card-title mb-1">
                                                <?= htmlspecialchars($application['full_name'] ?: $application['applicant_name']) ?>
                                            </h5>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($application['applicant_email']) ?>
                                                <?php if ($application['phone']): ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="fas fa-phone me-1"></i><?= htmlspecialchars($application['phone']) ?>
                                                <?php endif; ?>
                                            </p>
                                            <?php if (!$job_id): ?>
                                                <p class="text-primary mb-2">
                                                    <i class="fas fa-briefcase me-1"></i>Melamar untuk: 
                                                    <strong><?= htmlspecialchars($application['job_title']) ?></strong>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($application['city']): ?>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($application['city']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <div class="d-flex align-items-center">
                                                <span class="badge status-badge <?= 
                                                    $application['status'] === 'pending' ? 'bg-warning' : 
                                                    ($application['status'] === 'accepted' ? 'bg-success' : 'bg-danger') ?>">
                                                    <?= ucfirst($application['status']) ?>
                                                </span>
                                                <small class="text-muted ms-3">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Melamar: <?= date('d M Y H:i', strtotime($application['applied_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="d-flex flex-column gap-2">
                                        <?php if ($application['cover_letter']): ?>
                                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#coverLetterModal<?= $application['id'] ?>">
                                                <i class="fas fa-file-alt me-1"></i>Cover Letter
                                            </button>
                                        <?php endif; ?>
                                          <?php if ($application['cv_file']): ?>
                                            <a href="<?= htmlspecialchars($application['cv_file']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-download me-1"></i>Download CV
                                            </a>
                                        <?php endif; ?>
                                          <div class="btn-group" role="group">
                                            <?php if ($application['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menerima pelamar ini?')">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                    <input type="hidden" name="status" value="accepted">
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check me-1"></i>Terima
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menolak pelamar ini?')">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times me-1"></i>Tolak
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <button class="btn btn-outline-info btn-sm" onclick="contactApplicant('<?= htmlspecialchars($application['applicant_email']) ?>', '<?= htmlspecialchars($application['applicant_name']) ?>')">
                                                    <i class="fas fa-envelope me-1"></i>Kontak
                                                </button>
                                                <?php if ($application['status'] !== 'accepted' && $application['status'] !== 'rejected'): ?>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                                            <i class="fas fa-cog me-1"></i>Ubah Status
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                                    <input type="hidden" name="status" value="interview">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-handshake me-2"></i>Interview
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                                    <input type="hidden" name="status" value="accepted">
                                                                    <button type="submit" class="dropdown-item text-success">
                                                                        <i class="fas fa-check me-2"></i>Terima
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                                                    <input type="hidden" name="status" value="rejected">
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i class="fas fa-times me-2"></i>Tolak
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cover Letter Modal -->
                    <?php if ($application['cover_letter']): ?>
                        <div class="modal fade" id="coverLetterModal<?= $application['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Cover Letter - <?= htmlspecialchars($application['applicant_name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><?= nl2br(htmlspecialchars($application['cover_letter'])) ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= $job_id ? '&job_id=' . $job_id : '' ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $job_id ? '&job_id=' . $job_id : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= $job_id ? '&job_id=' . $job_id : '' ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-custom');
            if (navbar) {
                if (window.scrollY > 50) {                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
        });

        // Contact applicant function
        function contactApplicant(email, name) {
            window.open(`mailto:${email}?subject=Mengenai Lamaran Anda di HireWay&body=Halo ${name},%0D%0A%0D%0A`, '_blank');
        }
    </script>
</body>
</html>
