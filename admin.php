<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php';

// Start session
start_session();

// Check if user is logged in and is admin
if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
    header('Location: src/auth/login.php');
    exit();
}

// Get dashboard statistics
$stats = [];

// Users stats
$users_stmt = $pdo->query("SELECT COUNT(*) as total, 
    SUM(CASE WHEN role = 'jobseeker' THEN 1 ELSE 0 END) as jobseekers,
    SUM(CASE WHEN role = 'employer' THEN 1 ELSE 0 END) as employers,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
    FROM users");
$stats['users'] = $users_stmt->fetch(PDO::FETCH_ASSOC);

// Jobs stats
$jobs_stmt = $pdo->query("SELECT COUNT(*) as total,
    SUM(CASE WHEN status = 'published' AND is_active = 1 THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
    FROM jobs");
$stats['jobs'] = $jobs_stmt->fetch(PDO::FETCH_ASSOC);

// Applications stats
$applications_stmt = $pdo->query("SELECT COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN applied_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
    FROM applications");
$stats['applications'] = $applications_stmt->fetch(PDO::FETCH_ASSOC);

// Companies stats
$companies_stmt = $pdo->query("SELECT COUNT(*) as total,
    SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
    FROM companies");
$stats['companies'] = $companies_stmt->fetch(PDO::FETCH_ASSOC);

// Recent activities
$recent_jobs = $pdo->query("SELECT title, company, created_at FROM jobs ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$recent_users = $pdo->query("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$recent_applications = $pdo->query("SELECT a.*, j.title as job_title, u.name as user_name FROM applications a JOIN jobs j ON a.job_id = j.id JOIN users u ON a.user_id = u.id ORDER BY a.applied_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - HireWay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --sidebar-width: 280px;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--primary-gradient);
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h4 {
            color: white;
            margin: 0;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .sidebar-header p {
            color: rgba(255,255,255,0.7);
            margin: 0.5rem 0 0;
            font-size: 0.9rem;
        }        .sidebar .nav-link {
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            border-radius: 0;
            margin: 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left-color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.15);
            border-left-color: #ffd700;
            box-shadow: inset 0 0 20px rgba(255,255,255,0.1);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h2 {
            margin: 0;
            color: #2d3436;
            font-weight: 600;
        }

        .page-header .page-meta {
            color: #636e72;
            font-size: 0.9rem;
        }

        .stat-card {
            background: white;
            border: none;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .stat-card.primary::before { background: var(--primary-gradient); }
        .stat-card.success::before { background: var(--success-gradient); }
        .stat-card.warning::before { background: var(--warning-gradient); }
        .stat-card.secondary::before { background: var(--secondary-gradient); }

        .stat-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: white;
        }

        .stat-icon.primary { background: var(--primary-gradient); }
        .stat-icon.success { background: var(--success-gradient); }
        .stat-icon.warning { background: var(--warning-gradient); }
        .stat-icon.secondary { background: var(--secondary-gradient); }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin: 0;
            color: #2d3436;
        }

        .stat-label {
            color: #636e72;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-growth {
            color: #00b894;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .chart-card {
            background: white;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .chart-card .card-header {
            background: transparent;
            border: none;
            padding: 1.5rem 2rem 0;
            font-weight: 600;
            color: #2d3436;
        }

        .chart-card .card-body {
            padding: 1rem 2rem 2rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .activity-card {
            background: white;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            height: 100%;
        }

        .activity-card .card-header {
            background: transparent;
            border: none;
            padding: 1.5rem 2rem 0;
            font-weight: 600;
            color: #2d3436;
        }

        .activity-item {
            padding: 1rem;
            border-left: 4px solid #ddd;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            border-left-color: #667eea;
            transform: translateX(5px);
        }

        .activity-item h6 {
            margin-bottom: 0.5rem;
            color: #2d3436;
            font-weight: 600;
        }

        .activity-item .text-muted {
            font-size: 0.85rem;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .admin-welcome {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .admin-welcome h3 {
            margin: 0;
            font-weight: 600;
        }

        .admin-welcome p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-crown me-2"></i>Admin Panel</h4>
            <p>HireWay Management System</p>
        </div>
          <nav class="nav flex-column">
            <a class="nav-link active" href="#" data-tab="dashboard">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a class="nav-link" href="#" data-tab="users">
                <i class="fas fa-users me-2"></i>Manage Users
            </a>
            <a class="nav-link" href="#" data-tab="jobs">
                <i class="fas fa-briefcase me-2"></i>Manage Jobs
            </a>
            <a class="nav-link" href="#" data-tab="companies">
                <i class="fas fa-building me-2"></i>Manage Companies
            </a>
            <a class="nav-link" href="#" data-tab="applications">
                <i class="fas fa-file-alt me-2"></i>Applications
            </a>
            <a class="nav-link" href="#" data-tab="categories">
                <i class="fas fa-tags me-2"></i>Categories
            </a>
            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin: 1rem 0;"></div>
            <a class="nav-link" href="index.php">
                <i class="fas fa-home me-2"></i>Back to Site
            </a>
            <a class="nav-link" href="src/auth/logout.php">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2 id="page-title"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview</h2>
            </div>
            <div class="page-meta">
                <i class="fas fa-calendar me-2"></i><?= date('l, d F Y - H:i') ?> WIB
            </div>
        </div>        <!-- Welcome Message -->
        <div class="admin-welcome">
            <h3>Welcome back, Administrator!</h3>
            <p>Here's what's happening with your platform today.</p>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboard-content" class="tab-content active">
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card primary">
                        <div class="stat-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?= number_format($stats['users']['total']) ?></div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-growth">
                            <i class="fas fa-arrow-up me-1"></i>+<?= $stats['users']['new_this_month'] ?> this month
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="stat-number"><?= number_format($stats['jobs']['active']) ?></div>
                        <div class="stat-label">Active Jobs</div>
                        <div class="stat-growth">
                            <i class="fas fa-arrow-up me-1"></i>+<?= $stats['jobs']['new_this_month'] ?> this month
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card warning">
                        <div class="stat-icon warning">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-number"><?= number_format($stats['applications']['total']) ?></div>
                        <div class="stat-label">Total Applications</div>
                        <div class="stat-growth">
                            <i class="fas fa-arrow-up me-1"></i>+<?= $stats['applications']['new_this_month'] ?> this month
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stat-card secondary">
                        <div class="stat-icon secondary">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-number"><?= number_format($stats['companies']['total']) ?></div>
                        <div class="stat-label">Registered Companies</div>
                        <div class="stat-growth">
                            <i class="fas fa-arrow-up me-1"></i>+<?= $stats['companies']['new_this_month'] ?> this month
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-4">
                    <div class="chart-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>User Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="userTypesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="chart-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Application Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="applicationStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="activity-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Recent Jobs</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recent_jobs as $job): ?>
                                <div class="activity-item">
                                    <h6><?= htmlspecialchars($job['title']) ?></h6>
                                    <p class="text-muted mb-1"><?= htmlspecialchars($job['company']) ?></p>
                                    <small class="text-muted"><?= time_ago($job['created_at']) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="activity-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>Recent Users</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recent_users as $user): ?>
                                <div class="activity-item">
                                    <h6><?= htmlspecialchars($user['name']) ?></h6>
                                    <p class="text-muted mb-1"><?= htmlspecialchars($user['email']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'employer' ? 'warning' : 'primary') ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                        <small class="text-muted"><?= time_ago($user['created_at']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="activity-card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Recent Applications</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recent_applications as $app): ?>
                                <div class="activity-item">
                                    <h6><?= htmlspecialchars($app['user_name']) ?></h6>
                                    <p class="text-muted mb-1">Applied for: <?= htmlspecialchars($app['job_title']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?= $app['status'] === 'accepted' ? 'success' : ($app['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($app['status']) ?>
                                        </span>
                                        <small class="text-muted"><?= time_ago($app['applied_at']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>        <!-- Users Tab -->
        <div id="users-content" class="tab-content">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card primary">
                        <div class="stat-icon primary">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-number"><?= $stats['users']['jobseekers'] ?></div>
                        <div class="stat-label">Job Seekers</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card warning">
                        <div class="stat-icon warning">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-number"><?= $stats['users']['employers'] ?></div>
                        <div class="stat-label">Employers</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?= $stats['users']['total'] ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Users Management</h5>
                    <button class="btn btn-primary" onclick="loadUsers()">
                        <i class="fas fa-sync me-2"></i>Refresh Data
                    </button>
                </div>
                <div class="card-body">
                    <div id="users-table">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $users_query = $pdo->query("SELECT id, name, email, role, is_active, created_at FROM users ORDER BY created_at DESC LIMIT 10");
                                    $users = $users_query->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($users as $user):
                                    ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'employer' ? 'warning' : 'primary') ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(<?= $user['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $user['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jobs Tab -->
        <div id="jobs-content" class="tab-content">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?= $stats['jobs']['active'] ?></div>
                        <div class="stat-label">Active Jobs</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card warning">
                        <div class="stat-icon warning">
                            <i class="fas fa-pause-circle"></i>
                        </div>
                        <div class="stat-number"><?= $stats['jobs']['total'] - $stats['jobs']['active'] ?></div>
                        <div class="stat-label">Inactive Jobs</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card primary">
                        <div class="stat-icon primary">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="stat-number"><?= $stats['jobs']['total'] ?></div>
                        <div class="stat-label">Total Jobs</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card secondary">
                        <div class="stat-icon secondary">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="stat-number"><?= $stats['jobs']['new_this_month'] ?></div>
                        <div class="stat-label">New This Month</div>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Jobs Management</h5>
                    <button class="btn btn-primary" onclick="loadJobs()">
                        <i class="fas fa-sync me-2"></i>Refresh Data
                    </button>
                </div>
                <div class="card-body">
                    <div id="jobs-table">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Company</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Applications</th>
                                        <th>Posted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $jobs_query = $pdo->query("SELECT j.*, c.name as company_name FROM jobs j LEFT JOIN companies c ON j.company_id = c.id ORDER BY j.created_at DESC LIMIT 10");
                                    $jobs = $jobs_query->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($jobs as $job):
                                    ?>
                                    <tr>
                                        <td><?= $job['id'] ?></td>
                                        <td><?= htmlspecialchars($job['title']) ?></td>
                                        <td><?= htmlspecialchars($job['company_name'] ?? $job['company']) ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= ucfirst(str_replace('-', ' ', $job['job_type'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $job['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($job['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= $job['applications_count'] ?></td>
                                        <td><?= date('d M Y', strtotime($job['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editJob(<?= $job['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteJob(<?= $job['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Companies Tab -->
        <div id="companies-content" class="tab-content">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="stat-number"><?= $stats['companies']['verified'] ?></div>
                        <div class="stat-label">Verified Companies</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card warning">
                        <div class="stat-icon warning">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="stat-number"><?= $stats['companies']['total'] - $stats['companies']['verified'] ?></div>
                        <div class="stat-label">Pending Verification</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card primary">
                        <div class="stat-icon primary">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-number"><?= $stats['companies']['total'] ?></div>
                        <div class="stat-label">Total Companies</div>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Companies Management</h5>
                    <button class="btn btn-primary" onclick="loadCompanies()">
                        <i class="fas fa-sync me-2"></i>Refresh Data
                    </button>
                </div>
                <div class="card-body">
                    <div id="companies-table">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Industry</th>
                                        <th>Size</th>
                                        <th>Verified</th>
                                        <th>Jobs</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $companies_query = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM jobs j WHERE j.company_id = c.id) as jobs_count FROM companies c ORDER BY c.created_at DESC LIMIT 10");
                                    $companies = $companies_query->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($companies as $company):
                                    ?>
                                    <tr>
                                        <td><?= $company['id'] ?></td>
                                        <td><?= htmlspecialchars($company['name']) ?></td>
                                        <td><?= htmlspecialchars($company['industry'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($company['company_size'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $company['is_verified'] ? 'success' : 'warning' ?>">
                                                <?= $company['is_verified'] ? 'Verified' : 'Pending' ?>
                                            </span>
                                        </td>
                                        <td><?= $company['jobs_count'] ?></td>
                                        <td><?= date('d M Y', strtotime($company['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-success me-1" onclick="verifyCompany(<?= $company['id'] ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editCompany(<?= $company['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCompany(<?= $company['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Applications Tab -->
        <div id="applications-content" class="tab-content">
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card warning">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?= $stats['applications']['pending'] ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card primary">
                        <div class="stat-icon primary">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-number"><?= $stats['applications']['total'] - $stats['applications']['pending'] ?></div>
                        <div class="stat-label">Reviewed</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card success">
                        <div class="stat-icon success">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-number"><?= $stats['applications']['total'] ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card secondary">
                        <div class="stat-icon secondary">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-number"><?= $stats['applications']['new_this_month'] ?></div>
                        <div class="stat-label">This Month</div>
                    </div>
                </div>
            </div>

            <div class="chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Applications Management</h5>
                    <button class="btn btn-primary" onclick="loadApplications()">
                        <i class="fas fa-sync me-2"></i>Refresh Data
                    </button>
                </div>
                <div class="card-body">
                    <div id="applications-table">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Applicant</th>
                                        <th>Job</th>
                                        <th>Company</th>
                                        <th>Status</th>
                                        <th>Applied</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $applications_query = $pdo->query("SELECT a.*, j.title as job_title, u.name as user_name, c.name as company_name FROM applications a JOIN jobs j ON a.job_id = j.id JOIN users u ON a.user_id = u.id LEFT JOIN companies c ON j.company_id = c.id ORDER BY a.applied_at DESC LIMIT 10");
                                    $applications = $applications_query->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($applications as $app):
                                    ?>
                                    <tr>
                                        <td><?= $app['id'] ?></td>
                                        <td><?= htmlspecialchars($app['user_name']) ?></td>
                                        <td><?= htmlspecialchars($app['job_title']) ?></td>
                                        <td><?= htmlspecialchars($app['company_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $app['status'] === 'accepted' ? 'success' : ($app['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($app['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($app['applied_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="viewApplication(<?= $app['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Tab -->
        <div id="categories-content" class="tab-content">
            <div class="chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Categories Management</h5>
                    <div>
                        <button class="btn btn-success me-2" onclick="addCategory()">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
                        <button class="btn btn-primary" onclick="loadCategories()">
                            <i class="fas fa-sync me-2"></i>Refresh Data
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="categories-table">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Icon</th>
                                        <th>Color</th>
                                        <th>Status</th>
                                        <th>Jobs Count</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $categories_query = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM jobs j WHERE j.category_id = c.id) as jobs_count FROM categories c ORDER BY c.sort_order, c.name");
                                    $categories = $categories_query->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($categories as $category):
                                    ?>
                                    <tr>
                                        <td><?= $category['id'] ?></td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td><?= htmlspecialchars($category['slug']) ?></td>
                                        <td>
                                            <i class="<?= htmlspecialchars($category['icon']) ?>" style="color: <?= htmlspecialchars($category['color']) ?>"></i>
                                        </td>
                                        <td>
                                            <span class="badge" style="background-color: <?= htmlspecialchars($category['color']) ?>">
                                                <?= htmlspecialchars($category['color']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $category['is_active'] ? 'success' : 'secondary' ?>">
                                                <?= $category['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td><?= $category['jobs_count'] ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editCategory(<?= $category['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?= $category['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Tab Navigation - Simple and reliable approach
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing admin dashboard...');
            
            // Get all navigation links with data-tab attribute
            const navLinks = document.querySelectorAll('a[data-tab]');
            const pageTitle = document.getElementById('page-title');
            
            console.log('Found navigation links:', navLinks.length);
            
            // Add click event to each navigation link
            navLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const targetTab = this.getAttribute('data-tab');
                    console.log('Switching to tab:', targetTab);
                    
                    // Remove active class from all nav links
                    navLinks.forEach(function(nav) {
                        nav.classList.remove('active');
                    });
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Hide all tab contents
                    const allTabContents = document.querySelectorAll('.tab-content');
                    allTabContents.forEach(function(content) {
                        content.style.display = 'none';
                        content.classList.remove('active');
                    });
                    
                    // Show target tab content
                    const targetContent = document.getElementById(targetTab + '-content');
                    if (targetContent) {
                        targetContent.style.display = 'block';
                        targetContent.classList.add('active');
                        console.log('Successfully switched to tab:', targetTab);
                    } else {
                        console.error('Tab content not found for:', targetTab);
                    }
                    
                    // Update page title
                    const titles = {
                        'dashboard': '<i class="fas fa-tachometer-alt me-2"></i>Dashboard Overview',
                        'users': '<i class="fas fa-users me-2"></i>Users Management',
                        'jobs': '<i class="fas fa-briefcase me-2"></i>Jobs Management',
                        'companies': '<i class="fas fa-building me-2"></i>Companies Management',
                        'applications': '<i class="fas fa-file-alt me-2"></i>Applications Management',
                        'categories': '<i class="fas fa-tags me-2"></i>Categories Management'
                    };
                    
                    if (titles[targetTab] && pageTitle) {
                        pageTitle.innerHTML = titles[targetTab];
                    }
                });
            });

            // Initialize charts
            setTimeout(function() {
                initializeCharts();
            }, 500);
        });

        // Initialize Charts Function
        function initializeCharts() {
            console.log('Initializing charts...');
            
            // User Types Chart
            const userTypesCtx = document.getElementById('userTypesChart');
            if (userTypesCtx) {
                console.log('Creating user types chart...');
                new Chart(userTypesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Job Seekers', 'Employers'],
                        datasets: [{
                            data: [<?= $stats['users']['jobseekers'] ?>, <?= $stats['users']['employers'] ?>],
                            backgroundColor: ['#667eea', '#fa709a'],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Application Status Chart
            const applicationStatusCtx = document.getElementById('applicationStatusChart');
            if (applicationStatusCtx) {
                console.log('Creating application status chart...');
                new Chart(applicationStatusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Pending', 'Processed'],
                        datasets: [{
                            data: [<?= $stats['applications']['pending'] ?>, <?= $stats['applications']['total'] - $stats['applications']['pending'] ?>],
                            backgroundColor: ['#ffc107', '#198754'],
                            borderWidth: 0,
                            hoverOffset: 10
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            console.log('Charts initialized successfully');
        }
    </script>
</body>
</html>
