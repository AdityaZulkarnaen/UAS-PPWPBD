<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php'; // Tambahkan ini
require_once 'src/includes/csrf_helper.php'; // Tambahkan ini

// Start session
start_session();
generate_csrf_token(); // Tambahkan ini

// Pagination settings
$jobs_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $jobs_per_page;

// Handle pencarian dan filter
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$location_filter = isset($_GET['location']) ? clean_input($_GET['location']) : '';
$job_type_filter = isset($_GET['job_type']) ? clean_input($_GET['job_type']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Query untuk mengambil data jobs dengan pagination
$sql = "SELECT j.*, c.name as category_name FROM jobs j LEFT JOIN categories c ON j.category_id = c.id WHERE j.is_active = 1 AND j.status = 'published'";
$count_sql = "SELECT COUNT(*) FROM jobs j WHERE j.is_active = 1 AND j.status = 'published'";
$params = [];

if (!empty($search)) {
    $search_condition = " AND (j.title LIKE ? OR j.company LIKE ? OR j.description LIKE ?)";
    $sql .= $search_condition;
    $count_sql .= $search_condition;
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($location_filter)) {
    $location_condition = " AND j.location LIKE ?";
    $sql .= $location_condition;
    $count_sql .= $location_condition;
    $params[] = "%$location_filter%";
}

if (!empty($job_type_filter)) {
    $job_type_condition = " AND j.job_type = ?";
    $sql .= $job_type_condition;
    $count_sql .= $job_type_condition;
    $params[] = $job_type_filter;
}

if (!empty($category_filter)) {
    $category_condition = " AND j.category_id = ?";
    $sql .= $category_condition;
    $count_sql .= $category_condition;
    $params[] = $category_filter;
}

// Get total count
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_jobs = $count_stmt->fetchColumn();

// Calculate total pages
$total_pages = ceil($total_jobs / $jobs_per_page);

// Get jobs for current page
$sql .= " ORDER BY j.created_at DESC LIMIT $jobs_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data untuk filter
$locations_stmt = $pdo->query("SELECT DISTINCT location FROM jobs WHERE location IS NOT NULL ORDER BY location");
$locations = $locations_stmt->fetchAll(PDO::FETCH_COLUMN);

// Ambil categories untuk filter
$categories_stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Lowongan - HireWay</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
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
        }        /* Navbar - Using external navbar.css */

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
            color: #FFFF00;
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

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, 
                rgba(102, 126, 234, 0.1) 0%, 
                rgba(118, 75, 162, 0.1) 100%);
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
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

        .page-header-content {
            position: relative;
            z-index: 1;
            animation: slideInUp 1s ease-out;
        }

        .page-header h1 {
            font-weight: 800;
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--bg-primary), #FFFF00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .page-header .lead {
            color: #ffffff;
            font-size: 1.2rem;
            font-weight: 400;
            margin-bottom: 0;
        }

        /* Search & Filter Section */
        .search-filter-section {
            background: var(--bg-primary);
            margin-top: -30px;
            position: relative;
            z-index: 2;
            border-radius: 2rem 2rem 0 0;
            padding: 3rem 0 2rem 0;
            box-shadow: 0 -10px 40px 0 rgba(0, 0, 0, 0.1);
        }

        .filter-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
            transition: all 0.3s ease;
        }

        .filter-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.15);
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            transition: all 0.3s ease;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.8);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
            background: white;
        }

        /* Jobs Container */
        .jobs-container {
            background: var(--bg-primary);
            padding: 2rem 0 4rem 0;
        }

        /* Job Cards dengan Hover Effects */
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
            height: 100%;
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
            cursor: pointer;
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(16, 185, 129, 0.4);
            color: white;
        }

        .btn-apply:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Pagination */
        .pagination {
            --bs-pagination-border-radius: 50px;
        }

        .page-link {
            border: 2px solid var(--border-color);
            color: var(--text-secondary);
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            margin: 0 0.25rem;
            border-radius: 50px !important;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px 0 rgba(99, 102, 241, 0.3);
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 15px 0 rgba(99, 102, 241, 0.3);
        }

        .page-item.disabled .page-link {
            color: var(--text-secondary);
            opacity: 0.5;
            cursor: not-allowed;
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

        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--text-primary), #0f172a);
            color: rgba(255, 255, 255, 0.8);
            padding: 3rem 0;
            font-size: 0.95rem;
        }

        /* Statistics Card */
        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.05);
            text-align: center;
            margin-bottom: 2rem;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stats-label {
            color: var(--text-secondary);
            font-weight: 600;
            margin-top: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .page-header .lead {
                font-size: 1rem;
            }
            
            .job-card {
                padding: 1.5rem;
            }
            
            .filter-card {
                padding: 1.5rem;
            }
        }

        .btn-bookmark {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
            color: #ef4444;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .btn-bookmark:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px 0 rgba(239, 68, 68, 0.4);
        }

        .btn-bookmark.bookmarked {
            background: #ef4444;
            color: white;
        }

        .btn-bookmark.bookmarked:hover {
            background: rgba(239, 68, 68, 0.8);
        }

        .btn-bookmark.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Job Detail Modal Styling */
        .job-detail-content {
            font-family: 'Inter', sans-serif;
        }

        .job-detail-content h4 {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .job-detail-content h5 {
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .job-detail-content h6 {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .job-detail-content .d-flex {
            margin-bottom: 0.75rem;
        }

        .job-detail-content .d-flex i {
            width: 20px;
            font-size: 1rem;
        }

        .job-detail-content .d-flex span {
            font-size: 0.95rem;
            color: var(--text-primary);
            line-height: 1.5;
        }

        .job-description-detail {
            background: rgba(99, 102, 241, 0.05);
            border-left: 4px solid var(--primary-color);
            padding: 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            line-height: 1.7;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .job-requirements {
            background: rgba(16, 185, 129, 0.05);
            border-left: 4px solid var(--success-color);
            padding: 1.5rem;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            line-height: 1.7;
            color: var(--text-primary);
        }

        .job-requirements ul, .job-description-detail ul,
        .job-requirements ol, .job-description-detail ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .job-requirements li, .job-description-detail li {
            margin-bottom: 0.5rem;
        }

        .modal-lg {
            max-width: 800px;
        }

        .modal-content {
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1.5rem 2rem;
        }

        .modal-body {
            padding: 2rem;
        }

        .badge.fs-6 {
            font-size: 0.9rem !important;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .border-top {
            border-color: rgba(0, 0, 0, 0.1) !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 1rem;
            }
            
            .modal-body {
                padding: 1.5rem;
            }
            
            .job-detail-content .row {
                margin-bottom: 1rem;
            }
            
            .job-detail-content .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
            }
            
            .job-detail-content .d-flex i {
                margin-bottom: 0.25rem;
            }
        }
    </style>
</head>
<body>    <!-- Navbar -->
    <?php include 'src/includes/navbar.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container text-center">
            <div class="page-header-content">
                <h1>Semua Lowongan Kerja ✨</h1>
                <p class="lead">Temukan pekerjaan impian Anda dari ribuan lowongan terbaik</p>
            </div>
        </div>
    </section>

    <!-- Search & Filter Section -->
    <section class="search-filter-section">
        <div class="container">
            <div class="filter-card">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">🔍 Kata Kunci</label>
                        <input type="text" name="search" class="form-control" placeholder="Cari pekerjaan..." 
                               value="<?= htmlspecialchars($search) ?>">                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">📍 Lokasi</label>
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
                    <div class="col-md-2">
                        <label class="form-label fw-bold">🏷️ Kategori</label>
                        <select name="category" class="form-select">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">💼 Tipe Pekerjaan</label>
                        <select name="job_type" class="form-select">
                            <option value="">Semua Tipe</option>
                            <option value="full-time" <?= $job_type_filter == 'full-time' ? 'selected' : '' ?>>Full-time</option>
                            <option value="part-time" <?= $job_type_filter == 'part-time' ? 'selected' : '' ?>>Part-time</option>
                            <option value="contract" <?= $job_type_filter == 'contract' ? 'selected' : '' ?>>Contract</option>
                            <option value="internship" <?= $job_type_filter == 'internship' ? 'selected' : '' ?>>Internship</option>
                            <option value="freelance" <?= $job_type_filter == 'freelance' ? 'selected' : '' ?>>Freelance</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-modern w-100">
                            <i class="fas fa-search me-2"></i>Cari Lowongan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Jobs Section -->
    <section class="jobs-container">
        <div class="container">
            <!-- Statistics -->
            <div class="stats-card">
                <div class="stats-number"><?= $total_jobs ?></div>
                <div class="stats-label">Lowongan Ditemukan</div>
                <p class="text-muted mb-0">Halaman <?= $current_page ?> dari <?= $total_pages ?></p>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="empty-state">
                    <i class="fas fa-search fa-4x"></i>
                    <h4>Tidak Ada Lowongan Ditemukan 😔</h4>
                    <p>Coba ubah kata kunci pencarian atau filter Anda untuk menemukan lowongan yang sesuai</p>
                    <a href="jobs.php" class="btn btn-modern mt-3">
                        <i class="fas fa-refresh me-2"></i>Reset Filter
                    </a>
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
                                    <div class="d-flex gap-2">
                                        <button class="btn-detail" onclick="showJobDetail(<?= $job['id'] ?>)">
                                            <i class="fas fa-eye me-2"></i>Detail
                                        </button>
                                        <?php if (is_logged_in()): ?>
                                            <?php 
                                            try {
                                                $user_id = get_user_id();
                                                $is_bookmarked = is_job_bookmarked($user_id, $job['id']);
                                            } catch (Exception $e) {
                                                $is_bookmarked = false;
                                                error_log("Bookmark check error: " . $e->getMessage());
                                            }
                                            ?>
                                            <button class="btn-bookmark <?= $is_bookmarked ? 'bookmarked' : '' ?>" 
                                                    onclick="toggleBookmark(<?= $job['id'] ?>, this)"
                                                    data-job-id="<?= $job['id'] ?>">
                                                <i class="fas fa-heart me-2"></i>
                                                <span class="bookmark-text"><?= $is_bookmarked ? 'Tersimpan' : 'Simpan' ?></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (is_logged_in()): ?>
                                        <button class="btn-apply" 
                                                onclick="applyJob(<?= $job['id'] ?>, '<?= htmlspecialchars($job['title']) ?>', '<?= htmlspecialchars($job['company']) ?>')">
                                            <i class="fas fa-paper-plane me-2"></i>Lamar
                                        </button>
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

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Job pagination" class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>">
                                        <i class="fas fa-chevron-left me-2"></i>Sebelumnya
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
                                        Selanjutnya<i class="fas fa-chevron-right ms-2"></i>
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
    <footer>
        <div class="container text-center">
            <p>&copy; 2025 HireWay. Dibuat dengan ❤️ untuk masa depan karir yang lebih baik.</p>
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
        // Apply Job functionality
        function applyJob(jobId, jobTitle, company) {
            Swal.fire({
                title: 'Konfirmasi Lamaran',
                html: `Apakah Anda yakin ingin melamar pekerjaan:<br><strong>${jobTitle}</strong><br>di <strong>${company}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Lamar Sekarang!',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const formData = new FormData();
                    formData.append('job_id', jobId);
                    formData.append('csrf_token', '<?= get_csrf_token() ?>');
                    
                    return fetch('apply_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message);
                        }
                        return data;
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Request failed: ${error.message}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const data = result.value;
                    Swal.fire({
                        icon: 'success',
                        title: 'Lamaran Berhasil Dikirim! 🎉',
                        html: `
                            <div class="text-start">
                                <p><strong>Posisi:</strong> ${data.job_title}</p>
                                <p><strong>Perusahaan:</strong> ${data.company}</p>
                                <p><strong>Status:</strong> <span class="badge bg-warning">Menunggu</span></p>
                                <p><strong>Total Lamaran Anda:</strong> ${data.total_applications}</p>
                                <hr>
                                <p class="text-muted">Silahkan tunggu pesan dari bagian HR. Kami akan menghubungi Anda segera!</p>
                            </div>
                        `,
                        confirmButtonText: 'Lihat Riwayat Lamaran',
                        showCancelButton: true,
                        cancelButtonText: 'OK',
                        confirmButtonColor: '#6366f1',
                        cancelButtonColor: '#10b981'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'applications.php';
                        }
                    });
                }
            });
        }

        // Bookmark functionality
        function toggleBookmark(jobId, button) {
            // Show loading state
            button.classList.add('loading');
            const originalText = button.querySelector('.bookmark-text').textContent;
            button.querySelector('.bookmark-text').textContent = 'Loading...';
            
            const formData = new FormData();
            formData.append('job_id', jobId);
            formData.append('csrf_token', '<?= get_csrf_token() ?>');
            
            fetch('bookmark_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Update button state
                    if (data.bookmarked) {
                        button.classList.add('bookmarked');
                        button.querySelector('.bookmark-text').textContent = 'Tersimpan';
                    } else {
                        button.classList.remove('bookmarked');
                        button.querySelector('.bookmark-text').textContent = 'Simpan';
                    }
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil! 🎉',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal! 😞',
                        text: data.message,
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ef4444'
                    });
                    
                    // Restore original text
                    button.querySelector('.bookmark-text').textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal! 😞',
                    text: 'Terjadi kesalahan saat memproses bookmark',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ef4444'
                });
                
                // Restore original text
                button.querySelector('.bookmark-text').textContent = originalText;
            })
            .finally(() => {
                // Remove loading state
                button.classList.remove('loading');
            });
        }

        // Job Detail Function
        function showJobDetail(jobId) {
            <?php if (is_logged_in()): ?>
                // Show loading SweetAlert
                Swal.fire({
                    title: 'Memuat Detail...',
                    html: 'Mohon tunggu sebentar <i class="fas fa-spinner fa-spin"></i>',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    background: '#ffffff',
                    color: '#1e293b'
                });
                
                // Ubah URL sesuai file yang ada
                fetch(`job-detail.php?id=${jobId}`)
                    .then(response => response.json())
                    .then(data => {
                        Swal.close();
                        
                        if (data.success) {
                            const job = data.job;
                            
                            // Update modal title
                            document.getElementById('jobDetailTitle').textContent = job.title;
                              // Update modal content
                            document.getElementById('jobDetailContent').innerHTML = `
                                <div class="job-detail-content">
                                    <div class="row mb-4">
                                        <div class="col-md-8">
                                            <h4 class="text-primary mb-3">${job.title || 'Tidak Ada Judul'}</h4>
                                            <h5 class="text-secondary mb-3">
                                                <i class="fas fa-building me-2"></i>${job.company || 'Perusahaan Tidak Diketahui'}
                                            </h5>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <span class="badge bg-primary fs-6 px-3 py-2">
                                                <i class="fas fa-briefcase me-1"></i>${job.job_type || 'Full-time'}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="fas fa-map-marker-alt text-primary me-3"></i>
                                                <span><strong>Lokasi:</strong> ${job.location || 'Tidak Disebutkan'}</span>
                                            </div>
                                            ${job.salary && job.salary.trim() !== '' ? `
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="fas fa-money-bill-wave text-success me-3"></i>
                                                <span><strong>Gaji:</strong> ${job.salary}</span>
                                            </div>
                                            ` : ''}
                                            ${job.contact_email && job.contact_email.trim() !== '' ? `
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="fas fa-envelope text-info me-3"></i>
                                                <span><strong>Email:</strong> <a href="mailto:${job.contact_email}" class="text-decoration-none">${job.contact_email}</a></span>
                                            </div>
                                            ` : ''}
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="fas fa-calendar-alt text-info me-3"></i>
                                                <span><strong>Dipublikasi:</strong> ${job.created_at || 'Tidak Diketahui'}</span>
                                            </div>
                                            ${job.deadline && job.deadline.trim() !== '' ? `
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="fas fa-clock text-warning me-3"></i>
                                                <span><strong>Batas Waktu:</strong> ${job.deadline}</span>
                                            </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <div class="mb-4">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-file-alt me-2"></i>Deskripsi Pekerjaan
                                        </h6>
                                        <div class="job-description-detail">
                                            ${job.description ? job.description.replace(/\n/g, '<br>') : 'Tidak ada deskripsi yang tersedia.'}
                                        </div>
                                    </div>
                                    
                                    ${job.requirements && job.requirements.trim() !== '' ? `
                                    <div class="mb-4">
                                        <h6 class="text-primary mb-3">
                                            <i class="fas fa-list-check me-2"></i>Persyaratan
                                        </h6>
                                        <div class="job-requirements">
                                            ${job.requirements.replace(/\n/g, '<br>')}
                                        </div>
                                    </div>
                                    ` : ''}
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                        <div>
                                            <button class="btn btn-outline-danger me-2" onclick="toggleBookmark(${job.id}, this)">
                                                <i class="fas fa-heart me-2"></i>Simpan
                                            </button>
                                        </div>
                                        <div>
                                            <button class="btn btn-success btn-lg" onclick="applyJob(${job.id}, '${job.title || 'Pekerjaan ini'}', '${job.company || 'Perusahaan ini'}')">
                                                <i class="fas fa-paper-plane me-2"></i>Lamar Sekarang
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            // Show modal
                            const modal = new bootstrap.Modal(document.getElementById('jobDetailModal'));
                            modal.show();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal Memuat! 😞',
                                text: data.message || 'Terjadi kesalahan saat memuat detail lowongan',
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.close();
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
    </script>
</body>
</html>
