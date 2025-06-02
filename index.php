<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';

// Start session
start_session();

// Handle form submission untuk menambah job baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_job'])) {
    // Check if user is logged in before allowing job posting
    if (!is_logged_in()) {
        $error_message = "Anda harus login untuk memposting lowongan.";
    } else {
        $title = clean_input($_POST['title']);
        $company = clean_input($_POST['company']);
        $location = clean_input($_POST['location']);
        $salary = clean_input($_POST['salary']);
        $job_type = clean_input($_POST['job_type']);
        $description = clean_input($_POST['description']);
        $requirements = clean_input($_POST['requirements']);
        $contact_email = clean_input($_POST['contact_email']);
        
        $stmt = $pdo->prepare("INSERT INTO jobs (title, company, location, salary, job_type, description, requirements, contact_email, posted_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$title, $company, $location, $salary, $job_type, $description, $requirements, $contact_email, get_user_id()])) {
            $success_message = "Lowongan kerja berhasil ditambahkan!";
        } else {
            $error_message = "Gagal menambahkan lowongan kerja.";
        }
    }
}

// Handle pencarian untuk homepage (hanya menampilkan 6 job terbaru)
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Query untuk mengambil 6 job terbaru
$sql = "SELECT * FROM jobs WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR company LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY created_at DESC LIMIT 6";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total job untuk ditampilkan di homepage
$total_jobs_stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
$total_jobs = $total_jobs_stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireWay - Temukan Pekerjaan Impian Anda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
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
                        <a class="nav-link" href="jobs.php">Semua Lowongan</a>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#addJobModal">
                                <i class="fas fa-plus me-1"></i>Posting Lowongan
                            </button>
                        </li>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Temukan Pekerjaan Impian Anda</h1>
            <p class="lead mb-4">Ribuan lowongan kerja menanti Anda. Mulai karir yang cemerlang hari ini!</p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form method="GET" action="jobs.php" class="d-flex">
                        <input type="text" name="search" class="form-control form-control-lg me-2" 
                               placeholder="Cari pekerjaan, perusahaan..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Alert Messages -->
    <?php if (isset($success_message)): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show">
                <?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $error_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Jobs Section -->
    <section id="jobs" class="py-5">
        <div class="container">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h2>Lowongan Terbaru</h2>
                    <p class="text-muted">Menampilkan 6 lowongan terbaru dari total <?= $total_jobs ?> lowongan</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="jobs.php" class="btn btn-primary">
                        <i class="fas fa-list me-1"></i>Lihat Semua Lowongan
                    </a>
                </div>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4>Tidak ada lowongan ditemukan</h4>
                    <p class="text-muted">Belum ada lowongan yang tersedia saat ini</p>
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
                
                <!-- Call to Action untuk melihat semua lowongan -->
                <div class="text-center mt-4">
                    <a href="jobs.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-arrow-right me-1"></i>Lihat Semua <?= $total_jobs ?> Lowongan
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container text-center">
            <p>&copy; 2025 HireWay. Semua hak dilindungi.</p>
        </div>
    </footer>

    <!-- Modal untuk menambah job -->
    <div class="modal fade" id="addJobModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Posting Lowongan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Judul Pekerjaan *</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nama Perusahaan *</label>
                                <input type="text" name="company" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Lokasi *</label>
                                <input type="text" name="location" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gaji</label>
                                <input type="text" name="salary" class="form-control" placeholder="Rp 5.000.000 - 8.000.000">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipe Pekerjaan *</label>
                                <select name="job_type" class="form-select" required>
                                    <option value="">Pilih Tipe</option>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Kontak *</label>
                                <input type="email" name="contact_email" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Pekerjaan *</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Persyaratan *</label>
                            <textarea name="requirements" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_job" class="btn btn-primary">Posting Lowongan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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