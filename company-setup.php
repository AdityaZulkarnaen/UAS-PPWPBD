<?php
require_once 'config.php';
require_once 'src/includes/session_manager.php';
require_once 'src/includes/profile_helper.php';

// Start session and check if user is employer
start_session();

if (!is_logged_in() || !is_employer()) {
    header('Location: src/auth/login.php');
    exit();
}

// Check if employer already has a company
$user_company_stmt = $pdo->prepare("SELECT company_id FROM users WHERE id = ?");
$user_company_stmt->execute([$_SESSION['user_id']]);
$user_data = $user_company_stmt->fetch(PDO::FETCH_ASSOC);

if ($user_data && $user_data['company_id']) {
    // User already has a company, redirect to my-jobs
    header('Location: my-jobs.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'create_new') {
        // Create new company
        $company_name = clean_input($_POST['company_name']);
        $industry = clean_input($_POST['industry']);
        $company_size = clean_input($_POST['company_size']);
        $description = clean_input($_POST['description']);
        $website = clean_input($_POST['website']);
        $email = clean_input($_POST['email']);
        $phone = clean_input($_POST['phone']);
        $address = clean_input($_POST['address']);
        $city = clean_input($_POST['city']);
        $province = clean_input($_POST['province']);
        $founded_year = !empty($_POST['founded_year']) ? intval($_POST['founded_year']) : null;
        
        // Validation
        if (empty($company_name) || empty($industry)) {
            $error_message = "Nama perusahaan dan industri harus diisi.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Insert company
                $company_stmt = $pdo->prepare("
                    INSERT INTO companies (name, description, industry, company_size, founded_year, 
                                         website, email, phone, address, city, province, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                if ($company_stmt->execute([
                    $company_name, $description, $industry, $company_size, $founded_year,
                    $website, $email, $phone, $address, $city, $province
                ])) {
                    $company_id = $pdo->lastInsertId();
                    
                    // Update user with company_id
                    $update_user_stmt = $pdo->prepare("UPDATE users SET company_id = ? WHERE id = ?");
                    if ($update_user_stmt->execute([$company_id, $_SESSION['user_id']])) {
                        $pdo->commit();
                        $success_message = "Perusahaan berhasil dibuat dan terhubung dengan akun Anda!";
                        header("Location: my-jobs.php");
                        exit();
                    } else {
                        $pdo->rollBack();
                        $error_message = "Gagal menghubungkan perusahaan dengan akun Anda.";
                    }
                } else {
                    $pdo->rollBack();
                    $error_message = "Gagal membuat perusahaan.";
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_message = "Error: " . $e->getMessage();
            }
        }
    } elseif ($action === 'join_existing') {
        // Join existing company
        $company_id = intval($_POST['existing_company_id']);
        
        if ($company_id) {
            // Update user with company_id
            $update_user_stmt = $pdo->prepare("UPDATE users SET company_id = ? WHERE id = ?");
            if ($update_user_stmt->execute([$company_id, $_SESSION['user_id']])) {
                $success_message = "Berhasil bergabung dengan perusahaan!";
                header("Location: my-jobs.php");
                exit();
            } else {
                $error_message = "Gagal bergabung dengan perusahaan.";
            }
        } else {
            $error_message = "Pilih perusahaan yang ingin diikuti.";
        }
    }
}

// Get existing companies for selection
$companies_stmt = $pdo->prepare("SELECT id, name, industry, city FROM companies ORDER BY name");
$companies_stmt->execute();
$existing_companies = $companies_stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = is_logged_in() ? get_unread_notifications_count($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Perusahaan - HireWay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
            padding: 2rem 0;
        }
        
        .setup-card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .option-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .option-card:hover, .option-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .option-details {
            display: none;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'src/includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <i class="fas fa-building text-primary mb-3" style="font-size: 4rem;"></i>
                        <h2>Setup Perusahaan Anda</h2>
                        <p class="text-muted">Untuk mulai posting lowongan, Anda perlu terhubung dengan perusahaan</p>
                    </div>

                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= $error_message ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                        </div>
                    <?php endif; ?>

                    <!-- Option Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="option-card" id="create-option" onclick="selectOption('create')">
                                <div class="text-center">
                                    <i class="fas fa-plus-circle mb-3" style="font-size: 3rem;"></i>
                                    <h4>Buat Perusahaan Baru</h4>
                                    <p class="mb-0">Daftarkan perusahaan Anda ke HireWay</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="option-card" id="join-option" onclick="selectOption('join')">
                                <div class="text-center">
                                    <i class="fas fa-handshake mb-3" style="font-size: 3rem;"></i>
                                    <h4>Bergabung dengan Perusahaan</h4>
                                    <p class="mb-0">Pilih dari perusahaan yang sudah terdaftar</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Create New Company Form -->
                    <div class="setup-card" id="create-form" style="display: none;">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-building me-2"></i>Informasi Perusahaan Baru
                            </h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="create_new">
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="company_name" class="form-label">Nama Perusahaan *</label>
                                            <input type="text" name="company_name" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="founded_year" class="form-label">Tahun Didirikan</label>
                                            <input type="number" name="founded_year" class="form-control" min="1900" max="<?= date('Y') ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="industry" class="form-label">Industri *</label>
                                            <select name="industry" class="form-select" required>
                                                <option value="">Pilih Industri</option>
                                                <option value="Technology">Technology</option>
                                                <option value="Finance">Finance</option>
                                                <option value="Healthcare">Healthcare</option>
                                                <option value="Education">Education</option>
                                                <option value="Manufacturing">Manufacturing</option>
                                                <option value="Retail">Retail</option>
                                                <option value="Consulting">Consulting</option>
                                                <option value="Media">Media</option>
                                                <option value="Real Estate">Real Estate</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="company_size" class="form-label">Ukuran Perusahaan</label>
                                            <select name="company_size" class="form-select">
                                                <option value="">Pilih Ukuran</option>
                                                <option value="1-10">1-10 karyawan</option>
                                                <option value="11-50">11-50 karyawan</option>
                                                <option value="51-200">51-200 karyawan</option>
                                                <option value="201-500">201-500 karyawan</option>
                                                <option value="500+">500+ karyawan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Deskripsi Perusahaan</label>
                                    <textarea name="description" class="form-control" rows="4" placeholder="Ceritakan tentang perusahaan Anda..."></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="website" class="form-label">Website</label>
                                            <input type="url" name="website" class="form-control" placeholder="https://company.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Perusahaan</label>
                                            <input type="email" name="email" class="form-control" placeholder="info@company.com">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">No. Telepon</label>
                                            <input type="text" name="phone" class="form-control" placeholder="021-123456">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="city" class="form-label">Kota</label>
                                            <input type="text" name="city" class="form-control" placeholder="Jakarta">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="province" class="form-label">Provinsi</label>
                                            <input type="text" name="province" class="form-control" placeholder="DKI Jakarta">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Alamat</label>
                                            <textarea name="address" class="form-control" rows="2" placeholder="Alamat lengkap perusahaan"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetSelection()">
                                        <i class="fas fa-arrow-left me-1"></i>Kembali
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-check me-1"></i>Buat Perusahaan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Join Existing Company Form -->
                    <div class="setup-card" id="join-form" style="display: none;">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-handshake me-2"></i>Pilih Perusahaan
                            </h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="join_existing">
                                
                                <div class="mb-3">
                                    <label for="existing_company_id" class="form-label">Pilih Perusahaan *</label>
                                    <select name="existing_company_id" class="form-select" required>
                                        <option value="">Pilih Perusahaan</option>
                                        <?php foreach ($existing_companies as $company): ?>
                                            <option value="<?= $company['id'] ?>">
                                                <?= htmlspecialchars($company['name']) ?> 
                                                (<?= htmlspecialchars($company['industry']) ?> - <?= htmlspecialchars($company['city']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Catatan:</strong> Pastikan Anda memiliki hak untuk mewakili perusahaan yang dipilih dalam posting lowongan kerja.
                                </div>

                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetSelection()">
                                        <i class="fas fa-arrow-left me-1"></i>Kembali
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-handshake me-1"></i>Bergabung
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectOption(option) {
            // Reset all options
            document.getElementById('create-option').classList.remove('selected');
            document.getElementById('join-option').classList.remove('selected');
            document.getElementById('create-form').style.display = 'none';
            document.getElementById('join-form').style.display = 'none';
            
            // Select current option
            if (option === 'create') {
                document.getElementById('create-option').classList.add('selected');
                document.getElementById('create-form').style.display = 'block';
            } else if (option === 'join') {
                document.getElementById('join-option').classList.add('selected');
                document.getElementById('join-form').style.display = 'block';
            }
        }
        
        function resetSelection() {
            document.getElementById('create-option').classList.remove('selected');
            document.getElementById('join-option').classList.remove('selected');
            document.getElementById('create-form').style.display = 'none';
            document.getElementById('join-form').style.display = 'none';
        }

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
