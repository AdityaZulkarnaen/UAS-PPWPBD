<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';

// Verify if the user is logged in
require_login();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="alert alert-danger">ID lowongan tidak valid</div>';
    exit;
}

$job_id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo '<div class="alert alert-danger">Lowongan tidak ditemukan</div>';
    exit;
}
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4><?= htmlspecialchars($job['title']) ?></h4>
                <h5 class="text-primary"><?= htmlspecialchars($job['company']) ?></h5>
            </div>
            <span class="badge bg-primary fs-6"><?= htmlspecialchars($job['job_type']) ?></span>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-map-marker-alt text-muted me-2"></i>
                    <span><?= htmlspecialchars($job['location']) ?></span>
                </div>
                <?php if (!empty($job['salary'])): ?>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-money-bill-wave text-muted me-2"></i>
                    <span class="text-success fw-bold"><?= htmlspecialchars($job['salary']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-calendar text-muted me-2"></i>
                    <span>Diposting: <?= format_date($job['created_at']) ?></span>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-envelope text-muted me-2"></i>
                    <a href="mailto:<?= htmlspecialchars($job['contact_email']) ?>" class="text-decoration-none">
                        <?= htmlspecialchars($job['contact_email']) ?>
                    </a>
                </div>
            </div>
        </div>
        
        <hr>
        
        <div class="mb-4">
            <h6 class="fw-bold mb-3">Deskripsi Pekerjaan</h6>
            <p class="text-justify"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
        </div>
        
        <div class="mb-4">
            <h6 class="fw-bold mb-3">Persyaratan</h6>
            <p class="text-justify"><?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
        </div>
        
        <div class="text-center">
            <a href="mailto:<?= htmlspecialchars($job['contact_email']) ?>?subject=Lamaran untuk posisi <?= urlencode($job['title']) ?>" 
               class="btn btn-primary btn-lg">
                <i class="fas fa-paper-plane me-2"></i>Lamar Sekarang
            </a>
        </div>
    </div>
</div>