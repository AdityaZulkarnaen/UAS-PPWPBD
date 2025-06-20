<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/csrf_helper.php';
require_once 'src/includes/profile_helper.php';

// Check if user is logged in
start_session();
if (!is_logged_in()) {
    header('Location: src/auth/login.php');
    exit();
}

generate_csrf_token();
$user_id = get_user_id();

// Handle bookmark toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_bookmark'])) {
    if (!validate_csrf()) {
        echo json_encode(['success' => false, 'message' => 'Token tidak valid']);
        exit();
    }
    
    $job_id = (int)$_POST['job_id'];
    $result = toggle_bookmark($user_id, $job_id);
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit();
    } else {
        $_SESSION['success_message'] = $result['message'];
        header('Location: bookmarks.php');
        exit();
    }
}

// Get user bookmarks
$bookmarks = get_user_bookmarks($user_id);

// Get messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lowongan Favorit - HireWay</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-primary: #ffffff;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }        /* Navbar - Using external navbar.css */

        .main-content {
            background: var(--bg-primary);
            margin-top: 2rem;
            border-radius: 2rem 2rem 0 0;
            min-height: calc(100vh - 100px);
            padding: 3rem 0;
        }

        .job-card {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-left: 4px solid var(--primary-color);
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12);
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

        .job-card:hover::before {
            transform: scaleX(1);
        }

        .job-type-badge {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .job-card h5 {
            font-weight: 700;
            color: var(--text-primary);
            margin: 1rem 0 0.5rem 0;
            font-size: 1.4rem;
        }

        .job-card h6 {
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .job-meta {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .job-meta i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .salary-text {
            background: linear-gradient(135deg, var(--success-color), #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        .job-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin: 1.5rem 0;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .bookmark-btn {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: white;
            border: 2px solid var(--danger-color);
            color: var(--danger-color);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(239, 68, 68, 0.2);
        }

        .bookmark-btn:hover {
            background: var(--danger-color);
            color: white;
            transform: scale(1.1);
        }

        .btn-detail {
            background: rgba(99, 102, 241, 0.1);
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-detail:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-apply {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 6rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 2rem;
            color: var(--primary-color);
            opacity: 0.3;
        }

        .section-title {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .bookmark-date {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .stats-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .stats-number {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-outline-modern {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }        .btn-outline-modern:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <h1 class="section-title">
                        <i class="fas fa-heart me-3"></i>Lowongan Favorit
                    </h1>
                    <p class="text-muted">Kumpulan lowongan kerja yang telah Anda simpan</p>
                </div>
                <div class="col-lg-4">
                    <div class="stats-card">
                        <div class="stats-number"><?= count($bookmarks) ?></div>
                        <div class="text-muted">Total Favorit</div>
                    </div>
                </div>
            </div>

            <!-- Bookmarks List -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($bookmarks)): ?>
                        <div class="empty-state">
                            <i class="fas fa-heart-broken"></i>
                            <h3>Belum Ada Lowongan Favorit</h3>
                            <p class="mb-4">
                                Mulai simpan lowongan yang menarik perhatian Anda dengan mengklik tombol bookmark (❤️) 
                                pada halaman detail lowongan.
                            </p>
                            <a href="jobs.php" class="btn-outline-modern">
                                <i class="fas fa-search me-2"></i>Jelajahi Lowongan
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($bookmarks as $job): ?>
                            <div class="job-card">
                                <!-- Bookmark Button -->
                                <button class="bookmark-btn" onclick="toggleBookmark(<?= $job['id'] ?>)" 
                                        data-job-id="<?= $job['id'] ?>" title="Hapus dari favorit">
                                    <i class="fas fa-heart"></i>
                                </button>

                                <div class="row">
                                    <div class="col-lg-8">
                                        <span class="job-type-badge"><?= htmlspecialchars($job['job_type']) ?></span>
                                        
                                        <h5><?= htmlspecialchars($job['title']) ?></h5>
                                        <h6><?= htmlspecialchars($job['company']) ?></h6>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="job-meta">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?= htmlspecialchars($job['location']) ?>
                                                </div>
                                                <div class="job-meta">
                                                    <i class="fas fa-clock"></i>
                                                    <?= htmlspecialchars($job['job_type']) ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="job-meta">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                    <span class="salary-text"><?= htmlspecialchars($job['salary']) ?></span>
                                                </div>
                                                <div class="job-meta">
                                                    <i class="fas fa-envelope"></i>
                                                    <?= htmlspecialchars($job['contact_email']) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="job-description">
                                            <?= htmlspecialchars($job['description']) ?>
                                        </div>

                                        <div class="bookmark-date">
                                            <i class="fas fa-heart me-2"></i>
                                            Disimpan pada: <?= date('d M Y H:i', strtotime($job['bookmarked_at'])) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-4 text-end d-flex flex-column justify-content-between">
                                        <div class="text-muted small">
                                            Diposting: <?= date('d M Y', strtotime($job['created_at'])) ?>
                                        </div>
                                        
                                        <div class="mt-3">                                            <div class="d-grid gap-2">
                                                <button class="btn-detail" onclick="showJobDetail(<?= $job['id'] ?>)">
                                                    <i class="fas fa-eye me-2"></i>Lihat Detail
                                                </button>
                                                
                                                <?php if (is_logged_in()): ?>
                                                    <a href="jobs.php" class="btn-apply">
                                                        <i class="fas fa-paper-plane me-2"></i>Lamar Sekarang
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Additional Actions -->
                        <div class="text-center mt-4">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <a href="jobs.php" class="btn-outline-modern w-100">
                                        <i class="fas fa-plus me-2"></i>Cari Lowongan Lain
                                    </a>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <a href="applications.php" class="btn-outline-modern w-100">
                                        <i class="fas fa-file-alt me-2"></i>Lihat Lamaran Saya
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show SweetAlert2 messages
        <?php if (isset($success_message)): ?>
            Swal.fire({
                title: 'Berhasil!',
                text: '<?= addslashes($success_message) ?>',
                icon: 'success',
                confirmButtonColor: '#6366f1'
            });
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            Swal.fire({
                title: 'Error!',
                text: '<?= addslashes($error_message) ?>',
                icon: 'error',
                confirmButtonColor: '#6366f1'
            });
        <?php endif; ?>

        // Toggle bookmark function
        function toggleBookmark(jobId) {
            Swal.fire({
                title: 'Hapus dari Favorit?',
                text: 'Apakah Anda yakin ingin menghapus lowongan ini dari daftar favorit?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create form data
                    const formData = new FormData();
                    formData.append('toggle_bookmark', '1');
                    formData.append('job_id', jobId);
                    formData.append('ajax', '1');
                    formData.append('csrf_token', '<?= get_csrf_token() ?>');

                    // Send AJAX request
                    fetch('bookmarks.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.bookmarked === false) {
                            // Remove the job card from DOM
                            const jobCard = document.querySelector(`[data-job-id="${jobId}"]`).closest('.job-card');
                            jobCard.style.transition = 'all 0.3s ease';
                            jobCard.style.opacity = '0';
                            jobCard.style.transform = 'translateX(-100px)';
                            
                            setTimeout(() => {
                                jobCard.remove();
                                
                                // Check if there are no more bookmarks
                                const remainingCards = document.querySelectorAll('.job-card');
                                if (remainingCards.length === 0) {
                                    location.reload(); // Reload to show empty state
                                } else {
                                    // Update stats counter
                                    const statsNumber = document.querySelector('.stats-number');
                                    if (statsNumber) {
                                        statsNumber.textContent = remainingCards.length;
                                    }
                                }
                            }, 300);

                            Swal.fire({
                                title: 'Berhasil!',
                                text: data.message,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menghapus bookmark',
                            icon: 'error',
                            confirmButtonColor: '#6366f1'
                        });
                    });
                }
            });
        }        // Add some animation on hover
        document.querySelectorAll('.job-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.borderLeftWidth = '6px';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.borderLeftWidth = '4px';
            });
        });

        // Job Detail Function
        function showJobDetail(jobId) {
            // Show loading SweetAlert
            Swal.fire({
                title: 'Memuat Detail...',
                html: 'Mohon tunggu sebentar <i class="fas fa-spinner fa-spin"></i>',
                allowOutsideClick: false,
                showConfirmButton: false,
                background: '#ffffff',
                color: '#1e293b'
            });
            
            fetch(`job-detail.php?id=${jobId}`)
                .then(response => response.json())
                .then(data => {
                    Swal.close();
                    
                    if (data.success) {
                        const job = data.job;
                        
                        // Update modal title
                        document.getElementById('jobDetailTitle').textContent = job.title || 'Detail Lowongan';
                        
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
                                            <i class="fas fa-heart-broken me-2"></i>Hapus dari Favorit
                                        </button>
                                    </div>
                                    <div>
                                        <button class="btn btn-success btn-lg" onclick="window.location.href='jobs.php'">
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
        }
    </script>

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
</body>
</html>
