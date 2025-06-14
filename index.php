<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php'; // Tambahkan ini
require_once 'src/includes/csrf_helper.php'; // Tambahkan ini

// Start session
start_session();
generate_csrf_token(); // Tambahkan ini

// Handle form submission untuk menambah job baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_job'])) {
    // Validasi CSRF token
    if (!validate_csrf()) {
        $_SESSION['error_message'] = "Token keamanan tidak valid. Silakan coba lagi.";
        header('Location: index.php');
        exit();
    }
    
    // Check if user is logged in before allowing job posting
    if (!is_logged_in()) {
        $_SESSION['error_message'] = "Anda harus login untuk memposting lowongan.";
    } else {
        $title = clean_input($_POST['title']);
        $company = clean_input($_POST['company']);
        $location = clean_input($_POST['location']);
        $salary = clean_input($_POST['salary']);
        $job_type = clean_input($_POST['job_type']);
        $description = clean_input($_POST['description']);
        $requirements = clean_input($_POST['requirements']);
        $contact_email = clean_input($_POST['contact_email']);
        
        $stmt = $pdo->prepare("INSERT INTO jobs (title, company, location, salary, job_type, description, requirements, contact_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$title, $company, $location, $salary, $job_type, $description, $requirements, $contact_email])) {
            $_SESSION['success_message'] = "Lowongan kerja berhasil ditambahkan!";
        } else {
            $_SESSION['error_message'] = "Gagal menambahkan lowongan kerja.";
        }
    }
    
    // Regenerate CSRF token setelah form submission
    regenerate_csrf_token();
    
    // Redirect untuk mencegah double submission
    header('Location: index.php');
    exit();
}

// Ambil pesan dari session dan hapus setelah diambil
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle pencarian untuk homepage (code tetap sama seperti sebelumnya)
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #8b5cf6;
            --accent-color: #06b6d4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px 0 rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Floating Animation */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Navbar dengan Glassmorphism */
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .navbar-nav .nav-link {
            color: var(--text-secondary) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary-color) !important;
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .navbar-nav .nav-link:hover::after {
            width: 80%;
        }

        /* Buttons dengan Gradient dan Animasi */
        .btn-modern {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px 0 rgba(99, 102, 241, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .btn-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px 0 rgba(99, 102, 241, 0.4);
        }

        .btn-outline-modern {
            border: 2px solid transparent;
            background: linear-gradient(white, white) padding-box, 
                       linear-gradient(135deg, var(--primary-color), var(--secondary-color)) border-box;
            color: var(--primary-color);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-outline-modern:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px 0 rgba(99, 102, 241, 0.3);
        }

        .btn-outline-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            transition: width 0.3s ease;
            z-index: -1;
        }

        .btn-outline-modern:hover::before {
            width: 100%;
        }

        /* Hero Section dengan Animasi */
        .hero-section {
            background: linear-gradient(135deg, 
                rgba(102, 126, 234, 0.1) 0%, 
                rgba(118, 75, 162, 0.1) 100%);
            padding: 6rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="%23667eea" fill-opacity="0.1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            animation: slideInUp 1s ease-out;
        }

        .hero-section h1 {
            font-weight: 800;
            font-size: 4rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--bg-primary), #FFFF00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .hero-section .lead {
            color: #ffffff;
            font-size: 1.4rem;
            font-weight: 400;
            margin-bottom: 3rem;
        }

        /* Search Box dengan Glassmorphism */
        .search-container {
            max-width: 700px;
            margin: 0 auto;
            position: relative;
        }

        .search-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 60px;
            padding: 0.5rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            transition: all 0.3s ease;
        }

        .search-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.4);
        }

        .search-box input {
            border: none;
            outline: none;
            font-size: 1.1rem;
            padding: 1rem 1.5rem;
            background: transparent;
            color: var(--text-primary);
        }

        .search-box input::placeholder {
            color: var(--text-secondary);
        }

        .search-box button {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .search-box button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px 0 rgba(99, 102, 241, 0.4);
        }

        /* Job Cards dengan Hover Effects */
        .jobs-container {
            background: var(--bg-primary);
            margin-top: -50px;
            position: relative;
            z-index: 2;
            border-radius: 2rem 2rem 0 0;
            padding: 4rem 0;
        }

        .job-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
            padding: 2rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s ease-out;
        }

        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .job-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px 0 rgba(0, 0, 0, 0.1);
        }

        .job-card:hover::before {
            transform: scaleX(1);
        }

        .job-type-badge {
            background: linear-gradient(135deg, var(--accent-color), var(--success-color));
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px 0 rgba(6, 182, 212, 0.3);
        }

        .job-card h5 {
            font-weight: 700;
            color: var(--text-primary);
            margin: 1.5rem 0 0.5rem 0;
            font-size: 1.4rem;
        }

        .job-card h6 {
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .job-meta {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .job-meta i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .salary-text {
            background: linear-gradient(135deg, var(--success-color), #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }

        .job-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 1.5rem 0;
        }

        /* Action Buttons */
        .btn-detail {
            background: rgba(99, 102, 241, 0.1);
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-detail:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px 0 rgba(99, 102, 241, 0.3);
        }

        .btn-apply {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px 0 rgba(16, 185, 129, 0.3);
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(16, 185, 129, 0.4);
        }

        /* Section Headers */
        .section-header h2 {
            font-weight: 800;
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .section-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin: 0;
        }

        /* Alert Messages dengan Animasi
        .alert {
            border: none;
            border-radius: 1rem;
            font-weight: 600;
            padding: 1.5rem;
            animation: slideInUp 0.5s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
            color: #dc2626;
            border-left: 4px solid #ef4444;
        } */

        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--text-primary), #0f172a);
            color: rgba(255, 255, 255, 0.8);
            padding: 3rem 0;
            font-size: 0.95rem;
        }

        /* Modal Improvements */
        .modal-content {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px 0 rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 2rem;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.5rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 6rem 2rem;
            animation: slideInUp 0.8s ease-out;
        }

        .empty-state i {
            color: var(--primary-color);
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }

        .empty-state h4 {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .hero-section .lead {
                font-size: 1.1rem;
            }
            
            .job-card {
                padding: 1.5rem;
            }
            
            .section-header h2 {
                font-size: 2rem;
            }
        }

        /* Loading Animation */
        .loading-dots {
            display: inline-block;
        }

        .loading-dots::after {
            content: '';
            animation: dots 1.5s steps(5, end) infinite;
        }

        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60% { content: '...'; }
            90%, 100% { content: ''; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-briefcase me-2"></i>HireWay
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php">Semua Lowongan</a>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="applications.php">
                                <i class="fas fa-file-alt me-2"></i>Lamaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bookmarks.php">
                                <i class="fas fa-heart me-2"></i>Favorit
                            </a>
                        </li>
                        <li class="nav-item">
                            <button class="btn btn-outline-modern me-3" data-bs-toggle="modal" data-bs-target="#addJobModal">
                                <i class="fas fa-plus me-2"></i>Posting Lowongan
                            </button>
                        </li>
                        <li class="nav-item">
                            <span class="nav-link">Halo, <?= htmlspecialchars(get_user_name()) ?>! 👋</span>
                        </li>
                        <?php if (is_admin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin.php">
                                    <i class="fas fa-cog me-2"></i>Admin
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="src/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="src/auth/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <button class="btn btn-modern ms-3" onclick="location.href='src/auth/register.php'">
                                Register
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-10 hero-content">
                    <h1>Temukan Pekerjaan Impian Anda ✨</h1>
                    <p class="lead">Ribuan lowongan kerja terbaik menanti Anda. Mulai perjalanan karir yang cemerlang hari ini!</p>
                    
                    <div class="search-container">
                        <div class="search-box">
                            <form method="GET" action="jobs.php" class="d-flex align-items-center">
                                <input type="text" name="search" class="form-control border-0 flex-grow-1" 
                                       placeholder="Cari pekerjaan, perusahaan, atau lokasi..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit" class="btn">
                                    <i class="fas fa-search me-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    

    <!-- Jobs Section -->
    <section class="jobs-container">
        <div class="container">
            <div class="row align-items-center section-header mb-5">
                <div class="col-md-8">
                    <h2>Lowongan Terbaru</h2>
                    <p>Menampilkan 6 lowongan terbaru dari total <strong><?= $total_jobs ?></strong> lowongan tersedia</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="jobs.php" class="btn btn-outline-modern">
                        Lihat Semua Lowongan
                        <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="empty-state">
                    <i class="fas fa-search fa-4x"></i>
                    <h4>Belum Ada Lowongan 😔</h4>
                    <p>Belum ada lowongan yang tersedia saat ini. Coba lagi nanti atau jadilah yang pertama memposting lowongan!</p>
                    <?php if (is_logged_in()): ?>
                        <button class="btn btn-modern mt-3" data-bs-toggle="modal" data-bs-target="#addJobModal">
                            <i class="fas fa-plus me-2"></i>Posting Lowongan Pertama
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($jobs as $index => $job): ?>
                        <div class="col-lg-4 col-md-6 mb-4" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="job-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="job-type-badge"><?= htmlspecialchars($job['job_type']) ?></span>
                                    <small class="text-muted">📅 <?= format_date($job['created_at']) ?></small>
                                </div>
                                
                                <h5><?= htmlspecialchars($job['title']) ?></h5>
                                <h6><?= htmlspecialchars($job['company']) ?></h6>
                                
                                <div class="job-meta">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?= htmlspecialchars($job['location']) ?></span>
                                </div>
                                
                                <?php if (!empty($job['salary'])): ?>
                                    <div class="job-meta">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span class="salary-text"><?= htmlspecialchars($job['salary']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="job-description"><?= substr(htmlspecialchars($job['description']), 0, 120) ?>...</p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <button class="btn-detail" onclick="showJobDetail(<?= $job['id'] ?>)">
                                        <i class="fas fa-eye me-2"></i>Detail
                                    </button>
                                    <?php if (is_logged_in()): ?>
                                        <a href="mailto:<?= htmlspecialchars($job['contact_email']) ?>" 
                                           class="btn-apply text-decoration-none">
                                            <i class="fas fa-paper-plane me-2"></i>Lamar
                                        </a>
                                    <?php else: ?>
                                        <a href="src/auth/login.php" 
                                           class="btn-apply text-decoration-none">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-center mt-5">
                    <a href="jobs.php" class="btn btn-modern btn-lg">
                        <i class="fas fa-briefcase me-2"></i>Jelajahi Semua <?= $total_jobs ?> Lowongan
                        <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container text-center">
            <p>&copy; 2025 HireWay. Dibuat dengan ❤️ untuk masa depan karir yang lebih baik.</p>
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
                <form method="POST" id="addJobForm">
                    <!-- CSRF Token menggunakan helper function -->
                    <?= csrf_token_field() ?>
                    
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
                        <button type="submit" name="add_job" class="btn btn-primary" id="submitBtn">
                            <span class="btn-text">Posting Lowongan</span>
                            <div class="spinner-border spinner-border-sm d-none" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </button>
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
        // Show SweetAlert2 messages
        <?php if (isset($success_message)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil! 🎉',
                text: '<?= addslashes($success_message) ?>',
                confirmButtonText: 'OK',
                confirmButtonColor: '#6366f1',
                background: '#ffffff',
                color: '#1e293b',
                showConfirmButton: true,
                timer: 5000,
                timerProgressBar: true,
                toast: false,
                position: 'center',
                customClass: {
                    popup: 'animated fadeInDown'
                }
            });
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops! 😓',
                text: '<?= addslashes($error_message) ?>',
                confirmButtonText: 'Coba Lagi',
                confirmButtonColor: '#ef4444',
                background: '#ffffff',
                color: '#1e293b',
                showConfirmButton: true,
                customClass: {
                    popup: 'animated fadeInDown'
                }
            });
        <?php endif; ?>

        // Job Detail Function dengan Loading Animation
        function showJobDetail(jobId) {
            <?php if (is_logged_in()): ?>
                // Show loading SweetAlert
                Swal.fire({
                    title: 'Memuat Detail...',
                    html: 'Mohon tunggu sebentar <i class="fas fa-spinner fa-spin"></i>',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    background: '#ffffff',
                    color: '#1e293b',
                    customClass: {
                        popup: 'animated fadeIn'
                    }
                });
                
                fetch(`job-detail.php?id=${jobId}`)
                    .then(response => response.text())
                    .then(data => {
                        Swal.close();
                        document.getElementById('jobDetailContent').innerHTML = data;
                        new bootstrap.Modal(document.getElementById('jobDetailModal')).show();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat! 😞',
                            text: 'Terjadi kesalahan saat memuat detail lowongan',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#ef4444'
                        });
                    });
            <?php else: ?>
                Swal.fire({
                    icon: 'warning',
                    title: 'Login Diperlukan! 🔐',
                    text: 'Anda harus login untuk melihat detail lowongan',
                    confirmButtonText: 'Login Sekarang',
                    cancelButtonText: 'Nanti',
                    showCancelButton: true,
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#64748b'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'src/auth/login.php';
                    }
                });
            <?php endif; ?>
        }

        // Add scroll animation
        window.addEventListener('scroll', () => {
            const cards = document.querySelectorAll('.job-card');
            cards.forEach(card => {
                const cardTop = card.getBoundingClientRect().top;
                const cardVisible = 150;
                
                if(cardTop < window.innerHeight - cardVisible) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        });

        // Auto close SweetAlert toast after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            // Additional initialization if needed
        });
    </script>
</body>
</html>