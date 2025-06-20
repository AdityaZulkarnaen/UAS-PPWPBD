<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php';

// Start session
start_session();

// Pagination settings
$companies_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $companies_per_page;

// Handle search and filters
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$industry_filter = isset($_GET['industry']) ? clean_input($_GET['industry']) : '';
$city_filter = isset($_GET['city']) ? clean_input($_GET['city']) : '';
$size_filter = isset($_GET['size']) ? clean_input($_GET['size']) : '';

// Query to get companies with pagination
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM jobs j WHERE j.company_id = c.id AND j.is_active = 1 AND j.status = 'published') as active_jobs_count,
        (SELECT AVG(rating) FROM reviews r WHERE r.company_id = c.id AND r.is_approved = 1) as avg_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.company_id = c.id AND r.is_approved = 1) as total_reviews
        FROM companies c WHERE 1=1";
$count_sql = "SELECT COUNT(*) FROM companies c WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $count_sql .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($industry_filter)) {
    $sql .= " AND c.industry = ?";
    $count_sql .= " AND c.industry = ?";
    $params[] = $industry_filter;
}

if (!empty($city_filter)) {
    $sql .= " AND c.city = ?";
    $count_sql .= " AND c.city = ?";
    $params[] = $city_filter;
}

if (!empty($size_filter)) {
    $sql .= " AND c.company_size = ?";
    $count_sql .= " AND c.company_size = ?";
    $params[] = $size_filter;
}

// Get total count
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_companies = $count_stmt->fetchColumn();

// Calculate total pages
$total_pages = ceil($total_companies / $companies_per_page);

// Get companies for current page
$sql .= " ORDER BY c.is_verified DESC, c.is_premium DESC, c.created_at DESC LIMIT $companies_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get filter options
$industries_stmt = $pdo->query("SELECT DISTINCT industry FROM companies WHERE industry IS NOT NULL ORDER BY industry");
$industries = $industries_stmt->fetchAll(PDO::FETCH_COLUMN);

$cities_stmt = $pdo->query("SELECT DISTINCT city FROM companies WHERE city IS NOT NULL ORDER BY city");
$cities = $cities_stmt->fetchAll(PDO::FETCH_COLUMN);

$sizes = ['1-10', '11-50', '51-200', '201-500', '500+'];

$unread_count = is_logged_in() ? get_unread_notifications_count($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perusahaan - HireWay</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
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
            color: white;
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4rem 0 2rem 0;
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
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
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
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            line-height: 1.2;
        }

        .page-header .lead {
            font-size: 1.2rem;
            font-weight: 400;
            margin-bottom: 0;
            opacity: 0.9;
        }

        /* Search & Filter Section */
        .search-filter-section {
            background: var(--bg-primary);
            margin-top: -30px;
            position: relative;
            z-index: 2;
            border-radius: 2rem 2rem 0 0;
            padding: 3rem 0 2rem 0;
            box-shadow: 0 -4px 20px 0 rgba(0, 0, 0, 0.05);
        }        /* Filter Card Styling */
        .filter-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .filter-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        .filter-card h5 {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 1rem;
            padding: 0.75rem 1rem;
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

        /* Companies Container */
        .companies-container {
            background: var(--bg-primary);
            padding: 2rem 0 4rem 0;
        }        /* Company Cards Styling yang Diperbaiki */
        .company-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.6s ease-out;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .company-card::before {
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

        .company-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03), rgba(139, 92, 246, 0.03));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .company-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px 0 rgba(0, 0, 0, 0.15);
        }

        .company-card:hover::before {
            transform: scaleX(1);
        }

        .company-card:hover::after {
            opacity: 1;
        }

        .company-card-content {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
        }        .company-logo {
            width: 80px;
            height: 80px;
            border-radius: 1.2rem;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.8);
            box-shadow: var(--shadow-md);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .company-card:hover .company-logo {
            transform: scale(1.1);
            box-shadow: var(--shadow-lg);
        }

        .company-logo-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 1.2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .company-card:hover .company-logo-placeholder {
            transform: scale(1.1) rotate(5deg);
            box-shadow: var(--shadow-lg);
        }        .company-name {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            font-size: 1.4rem;
            line-height: 1.3;
            transition: color 0.3s ease;
        }

        .company-card:hover .company-name {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .company-industry {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 1.25rem;
            font-size: 0.95rem;
            padding: 0.25rem 0.75rem;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 50px;
            display: inline-block;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .company-meta {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .company-meta i {
            width: 18px;
            margin-right: 0.5rem;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .active-jobs {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            color: var(--success-color);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: inline-block;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .company-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }        .btn-company-detail {
            background: rgba(99, 102, 241, 0.1);
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.875rem 2rem;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
            margin-top: auto;
        }

        .btn-company-detail::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-company-detail:hover::before {
            left: 100%;
        }

        .btn-company-detail:hover {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px 0 rgba(99, 102, 241, 0.4);
        }        /* Badge Styling yang Diperbaiki */
        .verified-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
            animation: pulse 2s infinite;
        }

        .premium-badge {
            position: absolute;
            top: 20px;
            left: 20px;
            background: linear-gradient(135deg, #ffc107, #ff8c00);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 10px rgba(255, 193, 7, 0.3);
            animation: float 3s ease-in-out infinite;
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
            margin-bottom: 2rem;
        }

        /* Statistics Card */
        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
        }

        .stats-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .stats-label {
            color: var(--text-secondary);
            font-weight: 600;
            margin-top: 0.5rem;
            font-size: 1.1rem;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, var(--text-primary), #0f172a);
            color: rgba(255, 255, 255, 0.8);
            padding: 3rem 0;
            font-size: 0.95rem;
        }        /* Responsive Design Improvements */
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2.5rem;
            }
            
            .page-header .lead {
                font-size: 1rem;
            }
            
            .company-card {
                padding: 1.5rem;
            }
            
            .filter-card {
                padding: 1.5rem;
                margin-bottom: 2rem;
            }
            
            .company-logo,
            .company-logo-placeholder {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .company-name {
                font-size: 1.2rem;
            }
            
            .btn-company-detail {
                padding: 0.75rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .stats-number {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 3rem 0 1.5rem 0;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .search-filter-section {
                padding: 2rem 0 1.5rem 0;
            }
            
            .companies-container {
                padding: 1.5rem 0 3rem 0;
            }
            
            .company-card {
                padding: 1.25rem;
            }
            
            .filter-card {
                padding: 1.25rem;
            }
            
            .btn-modern,
            .btn-outline-modern {
                padding: 0.625rem 1.25rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>    <!-- Navigation -->
    <?php include 'src/includes/navbar.php'; ?><!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <div class="page-header-content text-center">
                <h1>
                    <i class="fas fa-building me-3"></i>Perusahaan Terbaik
                </h1>
                <p class="lead">
                    Temukan perusahaan impian Anda dan pelajari lebih lanjut tentang budaya kerja mereka
                </p>
            </div>
        </div>
    </section>

    <!-- Search & Filter Section -->
    <section class="search-filter-section">
        <div class="container">        <div class="row">
            <!-- Statistics -->
            <div class="col-lg-3 mb-4">
                <div class="stats-card">
                    <div class="stats-number"><?= $total_companies ?></div>
                    <div class="stats-label">Total Perusahaan</div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="col-lg-9">
                <div class="filter-card">
                    <h5><i class="fas fa-filter me-2"></i>Filter & Pencarian</h5>
                    <form method="GET">
                        <div class="row">
                            <!-- Search -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cari Perusahaan</label>
                                <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama perusahaan...">
                            </div>

                            <!-- Industry -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Industri</label>
                                <select class="form-select" name="industry">
                                    <option value="">Semua Industri</option>
                                    <?php foreach ($industries as $industry): ?>
                                        <option value="<?= htmlspecialchars($industry) ?>" <?= $industry_filter === $industry ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($industry) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- City -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kota</label>
                                <select class="form-select" name="city">
                                    <option value="">Semua Kota</option>
                                    <?php foreach ($cities as $city): ?>
                                        <option value="<?= htmlspecialchars($city) ?>" <?= $city_filter === $city ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($city) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Company Size -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ukuran Perusahaan</label>
                                <select class="form-select" name="size">
                                    <option value="">Semua Ukuran</option>
                                    <?php foreach ($sizes as $size): ?>
                                        <option value="<?= $size ?>" <?= $size_filter === $size ? 'selected' : '' ?>>
                                            <?= $size ?> karyawan
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-modern">
                                <i class="fas fa-search me-2"></i>Cari
                            </button>
                            <a href="companies.php" class="btn btn-outline-modern">
                                <i class="fas fa-times me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Companies Section -->
    <section class="companies-container">
        <div class="container">
            <!-- Results Info -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">Hasil Pencarian</h3>
                <p class="text-muted mb-0">
                    Menampilkan <?= count($companies) ?> dari <?= $total_companies ?> perusahaan
                </p>
            </div>

            <?php if (empty($companies)): ?>
                <div class="empty-state">
                    <i class="fas fa-building fa-4x"></i>
                    <h4>Tidak Ada Perusahaan Ditemukan 😔</h4>
                    <p>Coba ubah kata kunci pencarian atau filter Anda untuk menemukan perusahaan yang sesuai</p>
                    <a href="companies.php" class="btn btn-modern">
                        <i class="fas fa-refresh me-2"></i>Reset Filter
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($companies as $index => $company): ?>
                        <div class="col-lg-4 col-md-6 mb-4" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="company-card">
                                <?php if ($company['is_premium']): ?>
                                    <div class="premium-badge">
                                        <i class="fas fa-crown me-1"></i>Premium
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($company['is_verified']): ?>
                                    <div class="verified-badge">
                                        <i class="fas fa-check-circle me-1"></i>Verified
                                    </div>
                                <?php endif; ?>                                <div class="company-card-content text-center">
                                    <!-- Company Logo -->
                                    <div class="d-flex justify-content-center mb-3">
                                        <?php if ($company['logo']): ?>
                                            <img src="<?= htmlspecialchars($company['logo']) ?>" alt="Company Logo" class="company-logo mx-auto">
                                        <?php else: ?>
                                            <div class="company-logo-placeholder mx-auto">
                                                <i class="fas fa-building"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Company Name -->
                                    <h5 class="company-name"><?= htmlspecialchars($company['name']) ?></h5>
                                    
                                    <!-- Industry -->
                                    <p class="company-industry"><?= htmlspecialchars($company['industry']) ?></p>
                                    
                                    <!-- Location -->
                                    <div class="company-meta">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($company['city']) ?></span>
                                    </div>

                                    <!-- Company Size -->
                                    <div class="company-meta">
                                        <i class="fas fa-users"></i>
                                        <span><?= htmlspecialchars($company['company_size']) ?> karyawan</span>
                                    </div>

                                    <!-- Rating -->
                                    <?php if ($company['total_reviews'] > 0): ?>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= round($company['avg_rating']) ? '' : 'opacity-25' ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ms-1">(<?= $company['total_reviews'] ?>)</span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Active Jobs -->
                                    <div class="active-jobs">
                                        <i class="fas fa-briefcase me-1"></i>
                                        <?= $company['active_jobs_count'] ?> lowongan aktif
                                    </div>

                                    <!-- Description -->
                                    <p class="company-description"><?= substr(htmlspecialchars($company['description']), 0, 100) ?>...</p>

                                    <!-- View Button -->
                                    <a href="company-detail.php?id=<?= $company['id'] ?>" class="btn btn-company-detail">
                                        <i class="fas fa-eye me-2"></i>Lihat Detail
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Companies pagination" class="mt-5">
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
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
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
