<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php';

// Start session
start_session();

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: src/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's job alerts
$stmt = $pdo->prepare("SELECT ja.*, c.name as category_name FROM job_alerts ja LEFT JOIN categories c ON ja.category_id = c.id WHERE ja.user_id = ? ORDER BY ja.created_at DESC");
$stmt->execute([$user_id]);
$job_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$categories_stmt = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug logging
    error_log('POST Request received in job-alerts.php');
    error_log('POST data: ' . print_r($_POST, true));
    error_log('User ID from session: ' . ($_SESSION['user_id'] ?? 'NOT SET'));
      // Check if this is a job alert creation (either with create_alert field or title + frequency)
    if (isset($_POST['create_alert']) || (isset($_POST['title']) && isset($_POST['frequency']))) {
        error_log('create_alert POST detected');
        try {            // Create new job alert
            $title = clean_input($_POST['title']);
            $keywords = clean_input($_POST['keywords'] ?? '');
            $location = clean_input($_POST['location'] ?? '');
            $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
            $job_type = !empty($_POST['job_type']) ? clean_input($_POST['job_type']) : null;
            $salary_min = !empty($_POST['salary_min']) ? intval($_POST['salary_min']) : null;
            $frequency = clean_input($_POST['frequency']);
            
            error_log("Processed data - Title: $title, Frequency: $frequency, User ID: $user_id");
            
            // Validate required fields
            if (empty($title)) {
                throw new Exception('Title is required');
            }
            if (strlen($title) < 3) {
                throw new Exception('Title must be at least 3 characters long');
            }
            if (empty($frequency)) {
                throw new Exception('Frequency is required');
            }
            
            $stmt = $pdo->prepare("INSERT INTO job_alerts (user_id, title, keywords, location, category_id, job_type, salary_min, frequency) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$user_id, $title, $keywords, $location, $category_id, $job_type, $salary_min, $frequency]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception('Database error: ' . $errorInfo[2]);
            }
              $alert_id = $pdo->lastInsertId();
            error_log('Job alert created successfully with ID: ' . $alert_id);
            
            // Create notification for successful alert creation
            create_notification($user_id, 'Job Alert Dibuat', 'Job alert "' . $title . '" berhasil dibuat dan aktif', 'job_alert');
            
            $_SESSION['success_message'] = 'Job alert berhasil dibuat!';
            error_log('Redirecting to job-alerts.php?success=created');
            header('Location: job-alerts.php?success=created');
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Gagal membuat job alert: ' . $e->getMessage();
            error_log('Job Alert Creation Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
    
    if (isset($_POST['toggle_alert'])) {
        // Toggle alert status
        $alert_id = intval($_POST['alert_id']);
        $is_active = intval($_POST['is_active']);
        
        $stmt = $pdo->prepare("UPDATE job_alerts SET is_active = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$is_active, $alert_id, $user_id]);
        
        header('Location: job-alerts.php');
        exit();
    }
    
    if (isset($_POST['delete_alert'])) {
        // Delete alert
        $alert_id = intval($_POST['alert_id']);
        
        $stmt = $pdo->prepare("DELETE FROM job_alerts WHERE id = ? AND user_id = ?");
        $stmt->execute([$alert_id, $user_id]);
        
        header('Location: job-alerts.php?success=deleted');
        exit();
    }
}

$unread_count = get_unread_notifications_count($user_id);

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
    <title>Job Alert - HireWay</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --card-gradient: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            --primary-color: #667eea;
            --primary-dark: #5a67d8;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --success-color: #00f2fe;
            --warning-color: #ffd89b;
            --danger-color: #ff6b6b;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --text-light: #a0aec0;
            --bg-primary: #ffffff;
            --bg-secondary: #f7fafc;
            --border-color: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.15);
            --shadow-lg: 0 8px 25px rgba(0,0,0,0.15);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.1);
            --border-radius: 20px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: var(--text-primary);
            background: var(--primary-gradient);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(240, 147, 251, 0.2) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(2deg); }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: 200px 0; }
        }        /* Enhanced Navbar - Removed duplicate styles, using navbar.css */

        /* Enhanced Main Content */
        .main-content {
            background: var(--bg-primary);
            margin-top: 2.5rem;
            border-radius: 3rem 3rem 0 0;
            min-height: calc(100vh - 120px);
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.1);
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: -100px;
            left: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 15s ease-in-out infinite;
            z-index: 0;
        }

        .main-content::after {
            content: '';
            position: absolute;
            bottom: -150px;
            right: -150px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(240, 147, 251, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 20s ease-in-out infinite reverse;
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
        }

        /* Stunning Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 5rem;
            animation: slideInUp 1s ease-out;
            position: relative;
        }

        .page-title {
            font-size: 4rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            position: relative;
            letter-spacing: -0.02em;
        }

        .page-title i {
            background: var(--secondary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-right: 1.5rem;
            filter: drop-shadow(0 4px 8px rgba(102, 126, 234, 0.3));
            animation: pulse 3s ease-in-out infinite;
        }

        .page-subtitle {
            font-size: 1.4rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.8;
            font-weight: 500;
        }

        /* Premium Button Styling */
        .btn-modern {
            background: var(--primary-gradient);
            border: none;
            padding: 1rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            color: white;
            transition: var(--transition);
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }

        .btn-modern:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: var(--shadow-xl);
            color: white;
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .btn-outline-modern {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            background: transparent;
        }

        .btn-outline-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: var(--primary-gradient);
            transition: width 0.4s ease;
            z-index: -1;
        }

        .btn-outline-modern:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: transparent;
        }

        .btn-outline-modern:hover::before {
            width: 100%;
        }

        /* Stunning Alert Cards */
        .alert-card {
            background: var(--card-gradient);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: var(--border-radius);
            padding: 2rem;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            animation: slideInUp 0.8s ease-out;
            height: 100%;
            border-left: 5px solid var(--primary-color);
        }

        .alert-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.8), rgba(248, 250, 252, 0.8));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .alert-card:hover {
            transform: translateY(-12px) scale(1.03);
            box-shadow: var(--shadow-xl);
            border-left-color: var(--accent-color);
        }

        .alert-card:hover::before {
            opacity: 1;
        }

        .alert-card.inactive {
            opacity: 0.7;
            border-left-color: var(--text-light);
        }

        .alert-card.inactive:hover {
            opacity: 0.9;
        }

        /* Enhanced Create Alert Card */
        .create-alert-card {
            background: rgba(255, 255, 255, 0.8);
            border: 3px dashed rgba(102, 126, 234, 0.3);
            border-radius: var(--border-radius);
            padding: 4rem 2rem;
            text-align: center;
            transition: var(--transition);
            animation: slideInUp 1s ease-out;
            position: relative;
            overflow: hidden;
        }

        .create-alert-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            animation: shimmer 3s infinite;
        }

        .create-alert-card:hover {
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        .create-alert-icon {
            font-size: 5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }

        /* Enhanced Alert Card Content */
        .alert-title {
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            line-height: 1.4;
        }

        .alert-meta {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .alert-meta i {
            width: 20px;
            margin-right: 0.8rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .frequency-badge {
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            box-shadow: var(--shadow-sm);
        }

        .frequency-badge.bg-success {
            background: var(--success-gradient) !important;
        }

        .frequency-badge.bg-secondary {
            background: linear-gradient(135deg, var(--text-secondary), #4a5568) !important;
        }

        /* Enhanced Statistics */
        .stats-container {
            background: var(--card-gradient);
            backdrop-filter: blur(25px);
            border-radius: 2.5rem;
            padding: 3rem;
            margin-bottom: 4rem;
            box-shadow: var(--shadow-lg);
            animation: slideInUp 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .stats-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(240, 147, 251, 0.05));
            z-index: -1;
        }

        .stat-item {
            text-align: center;
            padding: 2rem;
            position: relative;
            transition: var(--transition);
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        .stat-item::after {
            content: '';
            position: absolute;
            bottom: 1rem;
            left: 50%;
            width: 60px;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 2px;
            transform: translateX(-50%);
            transition: width 0.3s ease;
        }

        .stat-item:hover::after {
            width: 80px;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
            filter: drop-shadow(0 2px 4px rgba(102, 126, 234, 0.2));
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 700;
            margin-top: 1rem;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Enhanced Empty State */
        .empty-state {
            text-align: center;
            padding: 8rem 4rem;
            animation: slideInUp 1s ease-out;
            background: var(--card-gradient);
            border-radius: 2.5rem;
            border: 2px dashed rgba(102, 126, 234, 0.2);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .empty-state::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.08) 0%, transparent 70%);
            animation: float 8s ease-in-out infinite;
        }

        .empty-state-content {
            position: relative;
            z-index: 1;
        }

        .empty-state i {
            font-size: 6rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2.5rem;
            animation: pulse 2.5s infinite;
        }

        .empty-state h5 {
            font-weight: 900;
            color: var(--text-primary);
            margin-bottom: 2rem;
            font-size: 2.5rem;
            line-height: 1.3;
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1.3rem;
            margin-bottom: 3.5rem;
            line-height: 1.8;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 500;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--card-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .feature-icon:hover {
            transform: translateY(-8px) scale(1.1);
            box-shadow: var(--shadow-lg);
        }

        .feature-icon i {
            font-size: 2rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Enhanced Modal */
        .modal-content {
            border: none;
            border-radius: 2rem;
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(25px);
            background: var(--card-gradient);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 2rem 2.5rem 1.5rem;
            border-radius: 2rem 2rem 0 0;
        }

        .modal-title {
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.5rem;
        }

        .modal-body {
            padding: 2.5rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem 2.5rem 2rem;
            border-radius: 0 0 2rem 2rem;
        }

        /* Enhanced Form Controls */
        .form-control, .form-select {
            border: 2px solid rgba(226, 232, 240, 0.8);
            border-radius: 1.2rem;
            padding: 1rem 1.5rem;
            transition: var(--transition);
            font-weight: 500;
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
            background: white;
        }

        .form-label {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.8rem;
            font-size: 1rem;
        }

        .form-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-style: italic;
            margin-top: 0.5rem;
        }

        /* Enhanced Alerts */
        .alert {
            border: none;
            border-radius: 1.5rem;
            padding: 1.5rem 2rem;
            font-weight: 600;
            backdrop-filter: blur(20px);
            animation: slideInUp 0.6s ease-out;
            border-left: 5px solid;
        }

        .alert-success {
            background: rgba(0, 242, 254, 0.1);
            color: #0ea5e9;
            border-left-color: #0ea5e9;
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.1), rgba(0, 242, 254, 0.1));
            border: 1px solid rgba(79, 172, 254, 0.2);
            border-radius: 1.5rem;
            padding: 2rem;
            font-size: 1rem;
            animation: fadeInScale 0.8s ease-out;
            border-left: 5px solid #4facfe;
        }

        .alert-info i {
            color: #4facfe;
            font-size: 1.2rem;
        }

        /* Enhanced Dropdown */
        .dropdown-menu {
            border: none;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-xl);
            backdrop-filter: blur(25px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 1rem;
        }

        .dropdown-item {
            padding: 1rem 1.5rem;
            font-weight: 600;
            border-radius: 1rem;
            margin: 0.25rem 0;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-color);
            transform: translateX(8px);
        }

        .dropdown-item.text-danger:hover {
            background: rgba(255, 107, 107, 0.1);
            color: var(--danger-color);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2.8rem;
            }
            
            .page-subtitle {
                font-size: 1.2rem;
            }
            
            .alert-card {
                padding: 1.5rem;
            }
            
            .modal-body {
                padding: 2rem;
            }
            
            .empty-state {
                padding: 5rem 2.5rem;
            }
            
            .empty-state h5 {
                font-size: 2rem;
            }
            
            .stats-container {
                padding: 2rem;
            }
            
            .stat-number {
                font-size: 2.8rem;
            }
        }

        @media (max-width: 576px) {
            .page-title {
                font-size: 2.2rem;
            }
            
            .page-title i {
                margin-right: 0.8rem;
                font-size: 1.8rem;
            }
            
            .empty-state {
                padding: 4rem 2rem;
            }
            
            .empty-state h5 {
                font-size: 1.8rem;
            }
            
            .btn-modern {
                padding: 1rem 2.5rem;
                font-size: 1rem;
            }
            
            .feature-icon {
                width: 60px;
                height: 60px;
            }
            
            .feature-icon i {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>    <!-- Navigation -->
    <?php include 'src/includes/navbar.php'; ?><!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php if ($_GET['success'] === 'created'): ?>
                        <i class="fas fa-check-circle me-2"></i>Job alert berhasil dibuat dan aktif!
                    <?php elseif ($_GET['success'] === 'deleted'): ?>
                        <i class="fas fa-trash me-2"></i>Job alert berhasil dihapus!
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-bell me-3"></i>Job Alert
                </h1>
                <p class="page-subtitle">
                    Dapatkan notifikasi lowongan terbaru sesuai kriteria yang Anda inginkan
                </p>
                <button class="btn btn-modern" data-bs-toggle="modal" data-bs-target="#createAlertModal">
                    <i class="fas fa-plus me-2"></i>Buat Alert Baru
                </button>
            </div>

            <!-- Statistics -->
            <?php if (!empty($job_alerts)): ?>
                <div class="stats-container">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number"><?= count($job_alerts) ?></div>
                                <div class="stat-label">Total Alert</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number"><?= count(array_filter($job_alerts, fn($alert) => $alert['is_active'])) ?></div>
                                <div class="stat-label">Aktif</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number"><?= count(array_filter($job_alerts, fn($alert) => !$alert['is_active'])) ?></div>
                                <div class="stat-label">Nonaktif</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-item">
                                <div class="stat-number"><?= count(array_filter($job_alerts, fn($alert) => $alert['frequency'] === 'daily')) ?></div>
                                <div class="stat-label">Harian</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Job Alerts List -->
            <div class="row">                <?php if (empty($job_alerts)): ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <div class="empty-state-content">
                                <i class="fas fa-bell-slash"></i>
                                <h5>Belum Ada Job Alert 📭</h5>
                                <p>Buat job alert untuk mendapatkan notifikasi lowongan terbaru sesuai kriteria Anda. Kami akan mengirimkan email ketika ada lowongan yang cocok dengan preferensi Anda!</p>
                                <button class="btn btn-modern" data-bs-toggle="modal" data-bs-target="#createAlertModal">
                                    <i class="fas fa-plus me-2"></i>Buat Alert Pertama
                                </button>
                                
                                <div class="mt-4 pt-4">
                                    <div class="row justify-content-center">
                                        <div class="col-md-3 text-center mb-3">
                                            <div class="feature-icon">
                                                <i class="fas fa-search text-primary"></i>
                                            </div>
                                            <small class="text-muted">Filter Pintar</small>
                                        </div>
                                        <div class="col-md-3 text-center mb-3">
                                            <div class="feature-icon">
                                                <i class="fas fa-clock text-success"></i>
                                            </div>
                                            <small class="text-muted">Real-time</small>
                                        </div>
                                        <div class="col-md-3 text-center mb-3">
                                            <div class="feature-icon">
                                                <i class="fas fa-envelope text-info"></i>
                                            </div>
                                            <small class="text-muted">Email Notifikasi</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($job_alerts as $index => $alert): ?>
                        <div class="col-lg-4 col-md-6 mb-4" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="alert-card <?= !$alert['is_active'] ? 'inactive' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="alert-title"><?= htmlspecialchars($alert['title']) ?></h5>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-modern" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="alert_id" value="<?= $alert['id'] ?>">
                                                    <input type="hidden" name="is_active" value="<?= $alert['is_active'] ? 0 : 1 ?>">
                                                    <button type="submit" name="toggle_alert" class="dropdown-item">
                                                        <i class="fas fa-<?= $alert['is_active'] ? 'pause' : 'play' ?> me-2"></i>
                                                        <?= $alert['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                    </button>
                                                </form>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="alert_id" value="<?= $alert['id'] ?>">
                                                    <button type="submit" name="delete_alert" class="dropdown-item text-danger" onclick="return confirm('Hapus job alert ini?')">
                                                        <i class="fas fa-trash me-2"></i>Hapus
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <?php if ($alert['keywords']): ?>
                                        <div class="alert-meta">
                                            <i class="fas fa-search"></i>
                                            <span><strong>Keywords:</strong> <?= htmlspecialchars($alert['keywords']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($alert['location']): ?>
                                        <div class="alert-meta">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><strong>Lokasi:</strong> <?= htmlspecialchars($alert['location']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($alert['category_name']): ?>
                                        <div class="alert-meta">
                                            <i class="fas fa-tag"></i>
                                            <span><strong>Kategori:</strong> <?= htmlspecialchars($alert['category_name']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($alert['job_type']): ?>
                                        <div class="alert-meta">
                                            <i class="fas fa-briefcase"></i>
                                            <span><strong>Tipe:</strong> <?= ucfirst($alert['job_type']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($alert['salary_min']): ?>
                                        <div class="alert-meta">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span><strong>Gaji Min:</strong> Rp <?= number_format($alert['salary_min']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="frequency-badge <?= $alert['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= ucfirst($alert['frequency']) ?>
                                    </span>
                                    <small class="text-muted">
                                        <?= date('d M Y', strtotime($alert['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Alert Modal -->
    <div class="modal fade" id="createAlertModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Buat Job Alert Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="job-alerts.php" id="createAlertForm">
                    <input type="hidden" name="create_alert" value="1">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Nama Alert <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required placeholder="Contoh: Frontend Developer Jakarta">
                                    <div class="form-text">Berikan nama yang mudah diingat untuk alert ini</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="frequency" class="form-label">Frekuensi Notifikasi</label>
                                    <select class="form-select" name="frequency" id="frequency">
                                        <option value="daily">Harian</option>
                                        <option value="weekly" selected>Mingguan</option>
                                        <option value="monthly">Bulanan</option>
                                    </select>
                                    <div class="form-text">Seberapa sering Anda ingin menerima notifikasi</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="keywords" class="form-label">Kata Kunci</label>
                                    <input type="text" class="form-control" id="keywords" name="keywords" placeholder="Frontend, React, JavaScript">
                                    <div class="form-text">Pisahkan dengan koma untuk multiple keywords</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Lokasi</label>
                                    <input type="text" class="form-control" id="location" name="location" placeholder="Jakarta, Bandung, Remote">
                                    <div class="form-text">Lokasi pekerjaan yang diinginkan</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Kategori</label>
                                    <select class="form-select" name="category_id" id="category_id">
                                        <option value="">Semua Kategori</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Pilih kategori pekerjaan spesifik</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="job_type" class="form-label">Tipe Pekerjaan</label>
                                    <select class="form-select" name="job_type" id="job_type">
                                        <option value="">Semua Tipe</option>
                                        <option value="full-time">Full-time</option>
                                        <option value="part-time">Part-time</option>
                                        <option value="contract">Contract</option>
                                        <option value="internship">Internship</option>
                                        <option value="freelance">Freelance</option>
                                    </select>
                                    <div class="form-text">Jenis kontrak pekerjaan</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="salary_min" class="form-label">Gaji Minimum (Opsional)</label>
                            <input type="number" class="form-control" id="salary_min" name="salary_min" placeholder="5000000">
                            <div class="form-text">Masukkan angka dalam Rupiah. Kosongkan jika tidak ada preferensi gaji minimum</div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tips:</strong> Semakin spesifik kriteria Anda, semakin relevan lowongan yang akan Anda terima. Anda dapat mengatur ulang kriteria kapan saja.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-modern" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Batal
                        </button>
                        <button type="submit" name="create_alert" class="btn btn-modern">
                            <i class="fas fa-bell me-2"></i>Buat Alert
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-custom');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });        // Show success/error messages with SweetAlert2
        <?php if (isset($success_message)): ?>
            console.log('Success message from PHP:', '<?= addslashes($success_message) ?>');
            Swal.fire({
                icon: 'success',
                title: 'Berhasil! 🎉',
                text: '<?= addslashes($success_message) ?>',
                confirmButtonText: 'OK',
                confirmButtonColor: '#6366f1',
                background: '#ffffff',
                color: '#1e293b'
            });
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            console.log('Error message from PHP:', '<?= addslashes($error_message) ?>');
            Swal.fire({
                icon: 'error',
                title: 'Error! ❌',
                text: '<?= addslashes($error_message) ?>',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444',
                background: '#ffffff',
                color: '#1e293b'
            });
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <?php if ($_GET['success'] === 'created'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil! 🎉',
                    text: 'Job alert berhasil dibuat dan sudah aktif',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#6366f1',
                    background: '#ffffff',
                    color: '#1e293b'
                });
            <?php elseif ($_GET['success'] === 'deleted'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil! ✅',
                    text: 'Job alert berhasil dihapus',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#6366f1',
                    background: '#ffffff',
                    color: '#1e293b'
                });
            <?php endif; ?>
        <?php endif; ?>

        // Enhanced delete confirmation
        document.querySelectorAll('button[name="delete_alert"]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                
                Swal.fire({
                    title: 'Hapus Job Alert? 🗑️',
                    text: 'Anda akan berhenti menerima notifikasi dari alert ini',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6366f1',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    background: '#ffffff',
                    color: '#1e293b'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Toggle alert confirmation
        document.querySelectorAll('button[name="toggle_alert"]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                const isActive = this.parentElement.querySelector('input[name="is_active"]').value;
                const action = isActive === '1' ? 'Aktifkan' : 'Nonaktifkan';
                const icon = isActive === '1' ? 'question' : 'warning';
                
                Swal.fire({
                    title: `${action} Job Alert?`,
                    text: isActive === '1' ? 
                        'Alert akan mulai mengirim notifikasi sesuai frekuensi yang dipilih' : 
                        'Alert akan berhenti mengirim notifikasi',
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: `Ya, ${action}`,
                    cancelButtonText: 'Batal',
                    background: '#ffffff',
                    color: '#1e293b'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        // Form validation
        document.getElementById('createAlertModal').addEventListener('show.bs.modal', function() {
            // Reset form
            this.querySelector('form').reset();
            
            // Focus on title field
            setTimeout(() => {
                this.querySelector('#title').focus();
            }, 500);
        });        // Enhanced form submission
        document.querySelector('#createAlertModal form').addEventListener('submit', function(e) {
            console.log('Form submission started');
            
            const title = this.querySelector('#title').value.trim();
            console.log('Title value:', title);
            
            if (!title) {
                e.preventDefault();
                console.log('Form prevented: empty title');
                Swal.fire({
                    icon: 'error',
                    title: 'Oops! 😅',
                    text: 'Nama alert harus diisi',
                    confirmButtonColor: '#ef4444',
                    background: '#ffffff',
                    color: '#1e293b'
                });
                return;
            }

            console.log('Form validation passed, submitting...');
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Membuat Alert...';
            submitBtn.disabled = true;
            
            // Log form data
            const formData = new FormData(this);
            console.log('Form data being submitted:');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }
            
            // Re-enable after 3 seconds (fallback)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });

        // Enhanced form submission handling
        document.getElementById('createAlertForm').addEventListener('submit', function(e) {
            console.log('Form submit detected');
            
            // Validate required fields
            const title = document.getElementById('title').value.trim();
            const frequency = document.getElementById('frequency').value;
            
            console.log('Title:', title, 'Frequency:', frequency);
            
            if (!title) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error! ❌',
                    text: 'Nama alert harus diisi',
                    confirmButtonColor: '#ef4444'
                });
                return false;
            }
            
            if (title.length < 3) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error! ❌',
                    text: 'Nama alert harus minimal 3 karakter',
                    confirmButtonColor: '#ef4444'
                });
                return false;
            }
            
            if (!frequency) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error! ❌',
                    text: 'Frekuensi notifikasi harus dipilih',
                    confirmButtonColor: '#ef4444'
                });
                return false;
            }
            
            console.log('Form validation passed, submitting...');
            
            // Show loading
            Swal.fire({
                title: 'Membuat Job Alert...',
                html: 'Mohon tunggu sebentar <i class="fas fa-spinner fa-spin"></i>',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            
            return true;
        });

        // Add hover effects to alert cards
        document.querySelectorAll('.alert-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.borderLeftWidth = '6px';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.borderLeftWidth = '4px';
            });
        });

        // Format salary input
        const salaryInput = document.getElementById('salary_min');
        if (salaryInput) {
            salaryInput.addEventListener('input', function() {
                // Remove non-numeric characters
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Add visual feedback for large numbers
                if (this.value && parseInt(this.value) > 100000000) {
                    this.style.borderColor = '#f59e0b';
                    this.title = 'Gaji sangat tinggi, pastikan sudah benar';
                } else {
                    this.style.borderColor = '';
                    this.title = '';
                }
            });
        }        // Clean URL after showing alerts
        if (window.location.search.includes('success=')) {
            setTimeout(() => {
                const url = new URL(window.location);
                url.searchParams.delete('success');
                window.history.replaceState({}, document.title, url.pathname);
            }, 2000);
        }

        // Add floating animation to feature icons
        document.querySelectorAll('.feature-icon').forEach((icon, index) => {
            icon.style.animationDelay = `${index * 0.2}s`;
            icon.style.animation = 'bounce 2s infinite';
        });

        // Add counter animation for statistics
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 20);
        }

        // Animate statistics when they come into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const numbers = entry.target.querySelectorAll('.stat-number');
                    numbers.forEach(number => {
                        const target = parseInt(number.textContent);
                        if (target > 0) {
                            animateCounter(number, target);
                        }
                    });
                    observer.unobserve(entry.target);
                }
            });
        });

        const statsContainer = document.querySelector('.stats-container');
        if (statsContainer) {
            observer.observe(statsContainer);
        }

        // Add typing effect to page title
        function typeWriter(element, text, speed = 100) {
            let i = 0;
            element.innerHTML = '';
            
            function type() {
                if (i < text.length) {
                    element.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                }
            }
            
            type();
        }

        // Initialize typing effect
        document.addEventListener('DOMContentLoaded', function() {
            const titleElement = document.querySelector('.page-title');
            if (titleElement) {
                const originalText = titleElement.textContent;
                setTimeout(() => {
                    typeWriter(titleElement, originalText, 50);
                }, 500);
            }
        });
    </script>
</body>
</html>
