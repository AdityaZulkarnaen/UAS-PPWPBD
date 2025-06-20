<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php';

// Start session
start_session();

// Get company ID from URL
$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$company_id) {
    header('Location: companies.php');
    exit();
}

// Get company details
$stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header('Location: companies.php');
    exit();
}

// Get company jobs
$jobs_stmt = $pdo->prepare("SELECT * FROM jobs WHERE company_id = ? AND is_active = 1 AND status = 'published' ORDER BY created_at DESC LIMIT 10");
$jobs_stmt->execute([$company_id]);
$jobs = $jobs_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get company reviews
$reviews_stmt = $pdo->prepare("SELECT r.*, u.name as reviewer_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.company_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC LIMIT 10");
$reviews_stmt->execute([$company_id]);
$reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate average rating
$rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE company_id = ? AND is_approved = 1");
$rating_stmt->execute([$company_id]);
$rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);
$avg_rating = round($rating_data['avg_rating'], 1);
$total_reviews = $rating_data['total_reviews'];

$unread_count = is_logged_in() ? get_unread_notifications_count($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($company['name']) ?> - HireWay</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            overflow: hidden;
        }
        
        .company-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
        }
        .company-logo {
            width: 100px;
            height: 100px;
            border: 4px solid white;
            border-radius: 15px;
            object-fit: cover;
        }
        .rating-stars {
            color: #ffc107;
        }
        .company-stats {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: -50px;
            position: relative;
            z-index: 2;
        }
        .job-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .review-card {
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>    <!-- Navigation -->
    <?php include 'src/includes/navbar.php'; ?>

    <div class="main-content">
        <!-- Company Header -->
        <div class="company-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <?php if ($company['logo']): ?>
                        <img src="<?= htmlspecialchars($company['logo']) ?>" alt="Company Logo" class="company-logo">
                    <?php else: ?>
                        <div class="company-logo bg-white text-primary d-flex align-items-center justify-content-center">
                            <i class="fas fa-building" style="font-size: 2rem;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <h1 class="mb-2"><?= htmlspecialchars($company['name']) ?></h1>
                    <p class="mb-2 opacity-75"><?= htmlspecialchars($company['industry']) ?></p>
                    <div class="d-flex align-items-center">
                        <?php if ($total_reviews > 0): ?>
                            <div class="rating-stars me-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= $avg_rating ? '' : 'opacity-25' ?>"></i>
                                <?php endfor; ?>
                                <span class="ms-2"><?= $avg_rating ?> (<?= $total_reviews ?> review)</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($company['is_verified']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Verified
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Company Stats -->
    <div class="container">
        <div class="company-stats p-4 mb-5">
            <div class="row text-center">
                <div class="col-md-3">
                    <h4 class="text-primary"><?= count($jobs) ?>+</h4>
                    <p class="text-muted mb-0">Lowongan Aktif</p>
                </div>
                <div class="col-md-3">
                    <h4 class="text-primary"><?= $company['company_size'] ?></h4>
                    <p class="text-muted mb-0">Karyawan</p>
                </div>
                <div class="col-md-3">
                    <h4 class="text-primary"><?= $company['founded_year'] ?></h4>
                    <p class="text-muted mb-0">Didirikan</p>
                </div>
                <div class="col-md-3">
                    <h4 class="text-primary"><?= htmlspecialchars($company['city']) ?></h4>
                    <p class="text-muted mb-0">Lokasi</p>
                </div>
            </div>
        </div>

        <!-- Company Info Tabs -->
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs" id="companyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="about-tab" data-bs-toggle="tab" data-bs-target="#about" type="button" role="tab">
                            <i class="fas fa-info-circle me-2"></i>Tentang Perusahaan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs" type="button" role="tab">
                            <i class="fas fa-briefcase me-2"></i>Lowongan (<?= count($jobs) ?>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">
                            <i class="fas fa-star me-2"></i>Review (<?= $total_reviews ?>)
                        </button>
                    </li>
                </ul>
                <div class="tab-content mt-4" id="companyTabsContent">
                    <!-- About Tab -->
                    <div class="tab-pane fade show active" id="about" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Deskripsi Perusahaan</h5>
                                        <p><?= nl2br(htmlspecialchars($company['description'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Informasi Kontak</h5>
                                        <?php if ($company['website']): ?>
                                            <p><i class="fas fa-globe text-primary me-2"></i>
                                                <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank">
                                                    <?= htmlspecialchars($company['website']) ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if ($company['email']): ?>
                                            <p><i class="fas fa-envelope text-primary me-2"></i>
                                                <a href="mailto:<?= htmlspecialchars($company['email']) ?>">
                                                    <?= htmlspecialchars($company['email']) ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if ($company['phone']): ?>
                                            <p><i class="fas fa-phone text-primary me-2"></i>
                                                <?= htmlspecialchars($company['phone']) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if ($company['address']): ?>
                                            <p><i class="fas fa-map-marker-alt text-primary me-2"></i>
                                                <?= nl2br(htmlspecialchars($company['address'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Jobs Tab -->
                    <div class="tab-pane fade" id="jobs" role="tabpanel">
                        <?php if (empty($jobs)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-briefcase text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">Belum Ada Lowongan</h5>
                                <p class="text-muted">Perusahaan ini belum memposting lowongan pekerjaan</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($jobs as $job): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card job-card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?= htmlspecialchars($job['location']) ?>
                                                </p>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-briefcase me-1"></i>
                                                    <?= ucfirst($job['job_type']) ?>
                                                </p>
                                                <?php if ($job['salary']): ?>
                                                    <p class="text-success mb-2">
                                                        <i class="fas fa-money-bill-wave me-1"></i>
                                                        <?= htmlspecialchars($job['salary']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="card-text"><?= substr(htmlspecialchars($job['description']), 0, 100) ?>...</p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <?= time_ago($job['created_at']) ?>
                                                    </small>
                                                    <a href="jobs.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">
                                                        Lihat Detail
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Reviews Tab -->
                    <div class="tab-pane fade" id="reviews" role="tabpanel">
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-star text-muted" style="font-size: 3rem;"></i>
                                <h5 class="text-muted mt-3">Belum Ada Review</h5>
                                <p class="text-muted">Jadilah yang pertama memberikan review untuk perusahaan ini</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="card review-card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0"><?= htmlspecialchars($review['reviewer_name']) ?></h6>
                                            <div class="rating-stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?= $i <= $review['rating'] ? '' : 'opacity-25' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <?php if ($review['title']): ?>
                                            <h6 class="text-primary"><?= htmlspecialchars($review['title']) ?></h6>
                                        <?php endif; ?>
                                        <p><?= nl2br(htmlspecialchars($review['review'])) ?></p>
                                        <?php if ($review['pros']): ?>
                                            <div class="mb-2">
                                                <strong class="text-success">Pros:</strong>
                                                <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($review['pros'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($review['cons']): ?>
                                            <div class="mb-2">
                                                <strong class="text-danger">Cons:</strong>
                                                <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($review['cons'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            <?= date('d M Y', strtotime($review['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>            </div>
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
    </script>
</body>
</html>
