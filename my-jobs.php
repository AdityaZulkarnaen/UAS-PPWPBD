<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php';

// Start session and check if user is employer
start_session();

if (!is_logged_in() || !is_employer()) {
    header('Location: src/auth/login.php');
    exit();
}

// Handle job actions (delete, toggle status)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $job_id = intval($_POST['job_id']);
    
    // Verify job belongs to current user
    $verify_stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND posted_by = ?");
    $verify_stmt->execute([$job_id, $_SESSION['user_id']]);
    
    if ($verify_stmt->fetch()) {
        switch ($_POST['action']) {
            case 'delete':
                $delete_stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND posted_by = ?");
                if ($delete_stmt->execute([$job_id, $_SESSION['user_id']])) {
                    $success_message = "Lowongan berhasil dihapus.";
                }
                break;
                
            case 'toggle_status':
                $new_status = $_POST['new_status'];
                $update_stmt = $pdo->prepare("UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ? AND posted_by = ?");
                if ($update_stmt->execute([$new_status, $job_id, $_SESSION['user_id']])) {
                    $success_message = "Status lowongan berhasil diubah.";
                }
                break;
                
            case 'toggle_active':
                $new_active = $_POST['is_active'] == '1' ? 0 : 1;
                $update_stmt = $pdo->prepare("UPDATE jobs SET is_active = ?, updated_at = NOW() WHERE id = ? AND posted_by = ?");
                if ($update_stmt->execute([$new_active, $job_id, $_SESSION['user_id']])) {
                    $success_message = $new_active ? "Lowongan diaktifkan." : "Lowongan dinonaktifkan.";
                }
                break;
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Build query
$where_conditions = ["posted_by = ?"];
$params = [$_SESSION['user_id']];

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(title LIKE ? OR company LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get jobs with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Ensure integer values for LIMIT and OFFSET
$per_page = (int) $per_page;
$offset = (int) $offset;

// Count total jobs
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE $where_clause");
$count_stmt->execute($params);
$total_jobs = $count_stmt->fetchColumn();
$total_pages = ceil($total_jobs / $per_page);

// Get jobs - use direct concatenation for LIMIT and OFFSET
$jobs_stmt = $pdo->prepare("
    SELECT j.*, c.name as company_name, cat.name as category_name,
           (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as applications_count
    FROM jobs j 
    LEFT JOIN companies c ON j.company_id = c.id 
    LEFT JOIN categories cat ON j.category_id = cat.id 
    WHERE $where_clause 
    ORDER BY j.created_at DESC 
    LIMIT $per_page OFFSET $offset
");

$jobs_stmt->execute($params);
$jobs = $jobs_stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = is_logged_in() ? get_unread_notifications_count($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Lowongan - HireWay</title>
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
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .job-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 15px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .btn-action {
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
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
                    <h2><i class="fas fa-briefcase me-2"></i>Kelola Lowongan</h2>
                    <p class="text-muted mb-0">Kelola semua lowongan pekerjaan yang Anda posting</p>
                </div>
                <a href="post-job.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Post Lowongan Baru
                </a>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Overview -->
            <div class="stats-card">
                <div class="row text-center">
                    <div class="col-md-3">
                        <?php
                        $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE posted_by = ?");
                        $total_stmt->execute([$_SESSION['user_id']]);
                        $total_all = $total_stmt->fetchColumn();
                        ?>
                        <h3><?= $total_all ?></h3>
                        <p class="mb-0">Total Lowongan</p>
                    </div>
                    <div class="col-md-3">
                        <?php
                        $active_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE posted_by = ? AND status = 'published' AND is_active = 1");
                        $active_stmt->execute([$_SESSION['user_id']]);
                        $total_active = $active_stmt->fetchColumn();
                        ?>
                        <h3><?= $total_active ?></h3>
                        <p class="mb-0">Aktif</p>
                    </div>
                    <div class="col-md-3">
                        <?php
                        $draft_stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE posted_by = ? AND status = 'draft'");
                        $draft_stmt->execute([$_SESSION['user_id']]);
                        $total_draft = $draft_stmt->fetchColumn();
                        ?>
                        <h3><?= $total_draft ?></h3>
                        <p class="mb-0">Draft</p>
                    </div>
                    <div class="col-md-3">
                        <?php
                        $apps_stmt = $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.posted_by = ?");
                        $apps_stmt->execute([$_SESSION['user_id']]);
                        $total_applications = $apps_stmt->fetchColumn();
                        ?>
                        <h3><?= $total_applications ?></h3>
                        <p class="mb-0">Total Lamaran</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Cari Lowongan</label>
                            <input type="text" name="search" class="form-control" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Cari berdasarkan judul atau perusahaan">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                                <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="paused" <?= $status_filter === 'paused' ? 'selected' : '' ?>>Paused</option>
                                <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Filter
                            </button>
                            <a href="my-jobs.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Jobs List -->
            <?php if (empty($jobs)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-briefcase text-muted" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mt-3">Belum Ada Lowongan</h4>
                    <p class="text-muted">Mulai posting lowongan pertama Anda</p>
                    <a href="post-job.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Post Lowongan Baru
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="card job-card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-1">
                                                <a href="job-detail.php?id=<?= $job['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($job['title']) ?>
                                                </a>
                                            </h5>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-building me-1"></i><?= htmlspecialchars($job['company_name']) ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($job['category_name']) ?>
                                            </p>
                                            <div class="d-flex flex-wrap gap-2 mb-2">
                                                <span class="badge status-badge <?= 
                                                    $job['status'] === 'published' ? 'bg-success' : 
                                                    ($job['status'] === 'draft' ? 'bg-secondary' : 
                                                    ($job['status'] === 'paused' ? 'bg-warning' : 'bg-danger')) ?>">
                                                    <?= ucfirst($job['status']) ?>
                                                </span>
                                                <span class="badge status-badge <?= $job['is_active'] ? 'bg-primary' : 'bg-light text-dark' ?>">
                                                    <?= $job['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                                </span>
                                                <?php if ($job['is_featured']): ?>
                                                    <span class="badge status-badge bg-warning">
                                                        <i class="fas fa-star me-1"></i>Featured
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($job['is_urgent']): ?>
                                                    <span class="badge status-badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Urgent
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>Diposting: <?= date('d M Y', strtotime($job['created_at'])) ?>
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-eye me-1"></i><?= $job['views_count'] ?> views
                                                <span class="mx-2">•</span>
                                                <i class="fas fa-users me-1"></i><?= $job['applications_count'] ?> lamaran
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="btn-group" role="group">
                                        <a href="job-detail.php?id=<?= $job['id'] ?>" class="btn btn-outline-primary btn-action">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-job.php?id=<?= $job['id'] ?>" class="btn btn-outline-secondary btn-action">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="applicants.php?job_id=<?= $job['id'] ?>" class="btn btn-outline-info btn-action">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-danger btn-action dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="toggle_active">
                                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                        <input type="hidden" name="is_active" value="<?= $job['is_active'] ?>">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-<?= $job['is_active'] ? 'pause' : 'play' ?> me-2"></i>
                                                            <?= $job['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php if ($job['status'] === 'published'): ?>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                            <input type="hidden" name="new_status" value="paused">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="fas fa-pause me-2"></i>Pause
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" onsubmit="return confirm('Yakin ingin menghapus lowongan ini?')" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash me-2"></i>Hapus
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
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
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }
        });
    </script>
</body>
</html>
