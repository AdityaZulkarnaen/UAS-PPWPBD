<?php
require_once '../../config.php';
require_once '../includes/session_manager.php';
require_once '../middleware/auth_middleware.php';

// Redirect if already logged in
guest_middleware();

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    $role = clean_input($_POST['role']);

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error_message = "Semua field harus diisi.";
    } elseif (!in_array($role, ['jobseeker', 'employer'])) {
        $error_message = "Role tidak valid.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Password tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password minimal 6 karakter.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error_message = "Email sudah terdaftar.";
        } else {
            // Create user - match database schema
            $hashed_password = hash_password($password);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            
            if ($stmt->execute([$username, $email, $hashed_password, $role])) {
                // Auto login after registration
                $user_id = $pdo->lastInsertId();
                start_user_session($user_id, $username, $role);
                
                // Redirect based on role
                if ($role === 'employer') {
                    header("Location: ../../company-setup.php");
                } else {
                    header("Location: ../../index.php");
                }
                exit();
            } else {
                $error_message = "Gagal membuat akun.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - JobPortal</title>
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
        .role-info {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .role-info.active {
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">Daftar Akun Baru</h2>
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger"><?= $error_message ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nama Lengkap</label>
                                <input type="text" name="username" class="form-control" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Daftar Sebagai</label>
                                <select name="role" id="roleSelect" class="form-select" required>
                                    <option value="">Pilih Role</option>
                                    <option value="jobseeker" <?= (isset($_POST['role']) && $_POST['role'] == 'jobseeker') ? 'selected' : '' ?>>Pencari Kerja</option>
                                    <option value="employer" <?= (isset($_POST['role']) && $_POST['role'] == 'employer') ? 'selected' : '' ?>>Perusahaan / Employer</option>
                                </select>
                                <div id="roleInfo" class="role-info"></div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Daftar</button>
                        </form>
                        <div class="text-center mt-3">
                            <p>Sudah punya akun? <a href="login.php">Login sekarang</a></p>
                            <a href="../../index.php" class="btn btn-link">Kembali ke Beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Role selection info
        const roleSelect = document.getElementById('roleSelect');
        const roleInfo = document.getElementById('roleInfo');
        
        const roleDescriptions = {
            'jobseeker': 'Akun untuk mencari pekerjaan, melamar ke perusahaan, dan mengelola profil karir.',
            'employer': 'Akun untuk perusahaan yang ingin memasang lowongan kerja dan mencari kandidat.'
        };
        
        roleSelect.addEventListener('change', function() {
            const selectedRole = this.value;
            if (selectedRole && roleDescriptions[selectedRole]) {
                roleInfo.textContent = roleDescriptions[selectedRole];
                roleInfo.classList.add('active');
            } else {
                roleInfo.textContent = '';
                roleInfo.classList.remove('active');
            }
        });
        
        // Show info for pre-selected role
        if (roleSelect.value) {
            roleSelect.dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>