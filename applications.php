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

// Handle application status filter
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Get user applications
$applications = get_user_applications($user_id, $status_filter);

// Get application statistics
$stats = [
    'total' => count(get_user_applications($user_id)),
    'pending' => count(get_user_applications($user_id, 'pending')),
    'reviewed' => count(get_user_applications($user_id, 'reviewed')),
    'accepted' => count(get_user_applications($user_id, 'accepted')),
    'rejected' => count(get_user_applications($user_id, 'rejected'))
];

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
    <title>Lamaran Saya - HireWay</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --info-color: #06b6d4;
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

        .stats-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stats-card:hover {
            transform: translateY(-2px);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .application-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .application-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .application-card.status-pending {
            border-left-color: var(--warning-color);
        }

        .application-card.status-reviewed {
            border-left-color: var(--info-color);
        }

        .application-card.status-accepted {
            border-left-color: var(--success-color);
        }

        .application-card.status-rejected {
            border-left-color: var(--danger-color);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .status-reviewed {
            background: rgba(6, 182, 212, 0.1);
            color: var(--info-color);
        }

        .status-shortlisted {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
        }

        .status-interview {
            background: rgba(139, 92, 246, 0.1);
            color: var(--secondary-color);
        }

        .status-accepted {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .filter-tabs {
            background: white;
            border-radius: 1rem;
            padding: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .filter-tab {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .filter-tab:hover {
            color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }

        .filter-tab.active:hover {
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            opacity: 0.5;
        }

        .job-meta {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .job-meta i {
            width: 16px;
            margin-right: 0.5rem;
        }

        .application-date {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .btn-outline-modern {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.875rem;
        }

        .btn-outline-modern:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }        .section-title {
            color: var(--text-primary);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
                <div class="col-12">
                    <h1 class="section-title">
                        <i class="fas fa-file-alt me-3"></i>Lamaran Saya
                    </h1>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?= $stats['total'] ?></div>
                        <div class="text-muted">Total Lamaran</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-number text-warning"><?= $stats['pending'] ?></div>
                        <div class="text-muted">Menunggu</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-number text-info"><?= $stats['reviewed'] ?></div>
                        <div class="text-muted">Ditinjau</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-number text-success"><?= $stats['accepted'] ?></div>
                        <div class="text-muted">Diterima</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-number text-danger"><?= $stats['rejected'] ?></div>
                        <div class="text-muted">Ditolak</div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="stats-card">
                        <div class="stats-number text-primary"><?= round(($stats['accepted'] / max($stats['total'], 1)) * 100) ?>%</div>
                        <div class="text-muted">Tingkat Berhasil</div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs d-flex justify-content-center flex-wrap">
                <a href="applications.php" class="filter-tab <?= empty($status_filter) ? 'active' : '' ?>">
                    Semua
                </a>
                <a href="applications.php?status=pending" class="filter-tab <?= $status_filter == 'pending' ? 'active' : '' ?>">
                    Menunggu
                </a>
                <a href="applications.php?status=reviewed" class="filter-tab <?= $status_filter == 'reviewed' ? 'active' : '' ?>">
                    Ditinjau
                </a>
                <a href="applications.php?status=shortlisted" class="filter-tab <?= $status_filter == 'shortlisted' ? 'active' : '' ?>">
                    Shortlist
                </a>
                <a href="applications.php?status=interview" class="filter-tab <?= $status_filter == 'interview' ? 'active' : '' ?>">
                    Interview
                </a>
                <a href="applications.php?status=accepted" class="filter-tab <?= $status_filter == 'accepted' ? 'active' : '' ?>">
                    Diterima
                </a>
                <a href="applications.php?status=rejected" class="filter-tab <?= $status_filter == 'rejected' ? 'active' : '' ?>">
                    Ditolak
                </a>
            </div>

            <!-- Applications List -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($applications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>Belum Ada Lamaran</h4>
                            <p class="mb-4">
                                <?php if (empty($status_filter)): ?>
                                    Anda belum melamar pekerjaan apapun. Mulai jelajahi lowongan yang tersedia!
                                <?php else: ?>
                                    Tidak ada lamaran dengan status "<?= ucfirst($status_filter) ?>".
                                <?php endif; ?>
                            </p>
                            <a href="jobs.php" class="btn-outline-modern">
                                <i class="fas fa-search me-2"></i>Cari Lowongan
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($applications as $application): ?>
                            <div class="application-card status-<?= $application['status'] ?>">
                                <div class="row align-items-center">
                                    <div class="col-lg-8">
                                        <div class="d-flex justify-content-between align-items-start mb-2">                                            <h5 class="mb-1">
                                                <button class="btn btn-link p-0 text-decoration-none text-dark" onclick="showJobDetail(<?= $application['job_id'] ?>)">
                                                    <?= htmlspecialchars($application['title']) ?>
                                                </button>
                                            </h5>
                                            <span class="status-badge status-<?= $application['status'] ?>">
                                                <?= ucfirst($application['status']) ?>
                                            </span>
                                        </div>
                                        
                                        <h6 class="text-primary mb-2"><?= htmlspecialchars($application['company']) ?></h6>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="job-meta">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?= htmlspecialchars($application['location']) ?>
                                                </div>
                                                <div class="job-meta">
                                                    <i class="fas fa-briefcase"></i>
                                                    <?= htmlspecialchars($application['job_type']) ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="job-meta">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                    <?= htmlspecialchars($application['salary']) ?>
                                                </div>
                                                <div class="job-meta">
                                                    <i class="fas fa-calendar"></i>
                                                    Dilamar: <?= date('d M Y', strtotime($application['applied_at'])) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (!empty($application['notes'])): ?>
                                            <div class="mt-3">
                                                <strong>Catatan dari Perusahaan:</strong>
                                                <p class="mb-0 text-muted"><?= htmlspecialchars($application['notes']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-lg-4 text-end">
                                        <div class="application-date mb-2">
                                            Diperbarui: <?= date('d M Y H:i', strtotime($application['updated_at'])) ?>
                                        </div>
                                          <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <button class="btn-outline-modern" onclick="showJobDetail(<?= $application['job_id'] ?>)">
                                                <i class="fas fa-eye me-1"></i>Lihat Lowongan
                                            </button>
                                            
                                            <?php if ($application['status'] == 'accepted'): ?>
                                                <span class="badge bg-success fs-6">
                                                    <i class="fas fa-check me-1"></i>Selamat!
                                                </span>
                                            <?php elseif ($application['status'] == 'interview'): ?>
                                                <span class="badge bg-warning fs-6">
                                                    <i class="fas fa-handshake me-1"></i>Interview
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination would go here if needed -->
                        <?php if (count($applications) >= 10): ?>
                            <div class="text-center mt-4">
                                <a href="#" class="btn-outline-modern">
                                    Muat Lebih Banyak
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        // Add some interactivity        document.querySelectorAll('.application-card').forEach(card => {
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
                                
                                <div class="d-flex justify-content-center mt-4 pt-3 border-top">
                                    <div class="alert alert-info text-center">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Anda sudah melamar pekerjaan ini. Status lamaran Anda dapat dilihat di halaman aplikasi.
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
