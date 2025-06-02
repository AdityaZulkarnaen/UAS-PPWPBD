<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';

// Start session
start_session();

// Pagination settings
$jobs_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $jobs_per_page;

// Handle pencarian dan filter
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$location_filter = isset($_GET['location']) ? clean_input($_GET['location']) : '';
$job_type_filter = isset($_GET['job_type']) ? clean_input($_GET['job_type']) : '';

// Query untuk mengambil data jobs dengan pagination
$sql = "SELECT * FROM jobs WHERE 1=1";
$count_sql = "SELECT COUNT(*) FROM jobs WHERE 1=1";
$params = [];

if (!empty($search)) {
    $search_condition = " AND (title LIKE ? OR company LIKE ? OR description LIKE ?)";
    $sql .= $search_condition;
    $count_sql .= $search_condition;
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($location_filter)) {
    $location_condition = " AND location LIKE ?";
    $sql .= $location_condition;
    $count_sql .= $location_condition;
    $params[] = "%$location_filter%";
}

if (!empty($job_type_filter)) {
    $job_type_condition = " AND job_type = ?";
    $sql .= $job_type_condition;
    $count_sql .= $job_type_condition;
    $params[] = $job_type_filter;
}

// Get total count
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_jobs = $count_stmt->fetchColumn();

// Calculate total pages
$total_pages = ceil($total_jobs / $jobs_per_page);

// Get jobs for current page
$sql .= " ORDER BY created_at DESC LIMIT $jobs_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data untuk filter
$locations_stmt = $pdo->query("SELECT DISTINCT location FROM jobs ORDER BY location");
$locations = $locations_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Lowongan - HireWay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .job-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .job-type-badge {
            font-size: 0.8rem;
        }
        .salary-text {
            color: #28a745;
            font-weight: bold;
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .search-section {
            background-color: #f8f9fa;
            padding: 30px 0;
        }
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-briefcase me-2"></i>HireWay
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="jobs.php">Semua Lowongan</a>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link">Halo, <?= htmlspecialchars(get_user_name()) ?>!</a>
                        </li>
                        <?php if (is_admin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin.php">
                                    <i class="fas fa-cog me-1"></i>Admin
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="src/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="src/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="src/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container text-center">
            <h1 class="display-5 mb-3">Semua Lowongan Kerja</h1>
            <p class="lead">Temukan pekerjaan yang sesuai dengan keahlian Anda</p>
        </div>
    </section>

    <!-- Search & Filter Section -->
    <section class="search-section">
        <div class="container">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Kata kunci..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="location" class="form-select">
                        <option value="">Semua Lokasi</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= htmlspecialchars($location) ?>" 
                                    <?= $location_filter == $location ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="job_type" class="form-select">
                        <option value="">Semua Tipe</option>
                        <option value="Full-time" <?= $job_type_filter == 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                        <option value="Part-time" <?= $job_type_filter == 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                        <option value="Contract" <?= $job_type_filter == 'Contract' ? 'selected' : '' ?>>Contract</option>
                        <option value="Internship" <?= $job_type_filter == 'Internship' ? 'selected' : '' ?>>Internship</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </section>

    <!-- Jobs Section -->
    <section class="py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col">
                    <h3>Ditemukan <?= $total_jobs ?> lowongan kerja</h3>
                    <p class="text-muted">Halaman <?= $current_page ?> dari <?= $total_pages ?></p>
                </div>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4>Tidak ada lowongan ditemukan</h4>
                    <p class="text-muted">Coba ubah kata kunci pencarian atau filter Anda</p>
                    <a href="jobs.php" class="btn btn-primary">Reset Filter</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($jobs as $job): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card job-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="badge bg-primary job-type-badge"><?= htmlspecialchars($job['job_type']) ?></span>
                                        <small class="text-muted"><?= format_date($job['created_at']) ?></small>
                                    </div>
                                    
                                    <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                                    <h6 class="card-subtitle mb-2 text-primary"><?= htmlspecialchars($job['company']) ?></h6>
                                    
                                    <div class="mb-2">
                                        <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                        <small><?= htmlspecialchars($job['location']) ?></small>
                                    </div>
                                    
                                    <?php if (!empty($job['salary'])): ?>
                                        <div class="mb-3">
                                            <i class="fas fa-money-bill-wave text-muted me-1"></i>
                                            <span class="salary-text"><?= htmlspecialchars($job['salary']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <p class="card-text"><?= substr(htmlspecialchars($job['description']), 0, 100) ?>...</p>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <button class="btn btn-outline-primary btn-sm" 
                                            onclick="showJobDetail(<?= $job['id'] ?>)">
                                        Lihat Detail
                                    </button>
                                    <?php if (is_logged_in()): ?>
                                        <a href="mailto:<?= htmlspecialchars($job['contact_email']) ?>" 
                                           class="btn btn-primary btn-sm float-end">
                                            Lamar Sekarang
                                        </a>
                                    <?php else: ?>
                                        <a href="src/auth/login.php" 
                                           class="btn btn-primary btn-sm float-end">
                                            Login untuk Melamar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Job pagination" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>">
                                        <i class="fas fa-chevron-left"></i> Sebelumnya
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>">
                                        Selanjutnya <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            <p>&copy; 2025 HireWay. Semua hak dilindungi.</p>
        </div>
    </footer>

    <!-- Modal untuk detail job -->
    <div class="modal fade" id="jobDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobDetailTitle">Detail Lowongan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="jobDetailContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showJobDetail(jobId) {
            <?php if (is_logged_in()): ?>
                fetch(`job-detail.php?id=${jobId}`)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('jobDetailContent').innerHTML = data;
                        new bootstrap.Modal(document.getElementById('jobDetailModal')).show();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Gagal memuat detail lowongan');
                    });
            <?php else: ?>
                alert('Anda harus login untuk melihat detail lowongan');
                window.location.href = 'src/auth/login.php';
            <?php endif; ?>
        }
    </script>
</body>
</html>
