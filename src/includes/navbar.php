<?php
// Get current page for active navigation
$current_page = basename($_SERVER['PHP_SELF']);

// Get unread notifications count if user is logged in
$unread_count = 0;
if (is_logged_in() && isset($_SESSION['user_id'])) {
    try {
        $unread_count = get_unread_notifications_count($_SESSION['user_id']);
    } catch (Exception $e) {
        $unread_count = 0; // Fallback if function fails
    }
}
?>

<!-- Consistent Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-briefcase me-2"></i>HireWay
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-home me-1"></i>Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'jobs.php' ? 'active' : '' ?>" href="jobs.php">
                        <i class="fas fa-briefcase me-1"></i>Lowongan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'companies.php' ? 'active' : '' ?>" href="companies.php">
                        <i class="fas fa-building me-1"></i>Perusahaan
                    </a>
                </li>                <?php if (is_logged_in()): ?>
                    <?php if (is_employer()): ?>
                        <!-- Employer Navigation -->
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'post-job.php' ? 'active' : '' ?>" href="post-job.php">
                                <i class="fas fa-plus-circle me-1"></i>Post Lowongan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'my-jobs.php' ? 'active' : '' ?>" href="my-jobs.php">
                                <i class="fas fa-briefcase me-1"></i>Kelola Lowongan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'applicants.php' ? 'active' : '' ?>" href="applicants.php">
                                <i class="fas fa-users me-1"></i>Pelamar
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Jobseeker Navigation -->
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'applications.php' ? 'active' : '' ?>" href="applications.php">
                                <i class="fas fa-file-alt me-1"></i>Lamaran
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'bookmarks.php' ? 'active' : '' ?>" href="bookmarks.php">
                                <i class="fas fa-bookmark me-1"></i>Tersimpan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'job-alerts.php' ? 'active' : '' ?>" href="job-alerts.php">
                                <i class="fas fa-bell-slash me-1"></i>Job Alert
                            </a>
                        </li>                    <?php endif; ?>
                    
                    <!-- Common Navigation for All Logged In Users -->
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'notifications.php' ? 'active' : '' ?>" href="notifications.php">
                            <i class="fas fa-bell me-1"></i>Notifikasi
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-warning text-dark ms-1"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Admin Dashboard Menu - Only for Admin -->
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'admin.php' ? 'active' : '' ?>" href="admin.php">
                                <i class="fas fa-tachometer-alt me-1"></i>Admin Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </a>                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user me-2"></i>Profil
                                </a>
                            </li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li>
                                    <a class="dropdown-item" href="admin.php">
                                        <i class="fas fa-crown me-2"></i>Admin Panel
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="src/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link btn-login" href="src/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-register" href="src/auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
