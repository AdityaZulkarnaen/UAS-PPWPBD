<?php
require_once '../../config.php';
require_once '../includes/session_manager.php';
require_once '../middleware/auth_middleware.php';

// Redirect if already logged in
guest_middleware();

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);

    // Validate user credentials
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && verify_password($password, $user['password'])) {
        // Successful login - use username from database
        start_user_session(
            $user['id'], 
            $user['username'], // Changed from 'name' to 'username' to match database schema
            isset($user['is_admin']) ? $user['is_admin'] : 0
        );
        
        header("Location: ../../index.php");
        exit();
    } else {
        $error_message = "Email atau password salah.";
    }
}

// Check for timeout message
$timeout_message = isset($_GET['timeout']) ? "Sesi Anda telah berakhir. Silakan login kembali." : "";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - JobPortal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Login</h2>
                        <?php if ($timeout_message): ?>
                            <div class="alert alert-warning"><?= $timeout_message ?></div>
                        <?php endif; ?>
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        <div class="text-center mt-3">
                            <p>Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
                            <a href="../../index.php" class="btn btn-link">Kembali ke Beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>