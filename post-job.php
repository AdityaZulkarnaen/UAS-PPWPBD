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

// Get user's company
$user_company_stmt = $pdo->prepare("SELECT u.company_id, c.name as company_name FROM users u LEFT JOIN companies c ON u.company_id = c.id WHERE u.id = ?");
$user_company_stmt->execute([$_SESSION['user_id']]);
$user_company = $user_company_stmt->fetch(PDO::FETCH_ASSOC);

// If user doesn't have a company, redirect to setup
if (!$user_company || !$user_company['company_id']) {
    header('Location: company-setup.php');
    exit();
}

// Get categories for dropdown
$categories_stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = clean_input($_POST['title']);
    $company_id = $user_company['company_id']; // Use user's company
    $category_id = intval($_POST['category_id']);
    $description = clean_input($_POST['description']);
    $requirements = clean_input($_POST['requirements']);
    $responsibilities = clean_input($_POST['responsibilities']);
    $benefits = clean_input($_POST['benefits']);
    $job_type = clean_input($_POST['job_type']);
    $work_location = clean_input($_POST['work_location']);
    $location = clean_input($_POST['location']);
    $salary = clean_input($_POST['salary']);
    $salary_min = !empty($_POST['salary_min']) ? intval($_POST['salary_min']) : null;
    $salary_max = !empty($_POST['salary_max']) ? intval($_POST['salary_max']) : null;
    $experience_required = clean_input($_POST['experience_required']);
    $education_required = clean_input($_POST['education_required']);
    $application_deadline = !empty($_POST['application_deadline']) ? $_POST['application_deadline'] : null;
    $contact_email = clean_input($_POST['contact_email']);
    $contact_phone = clean_input($_POST['contact_phone']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $status = clean_input($_POST['status']);    // Validation
    if (empty($title) || empty($category_id) || empty($description)) {
        $error_message = "Field yang wajib diisi belum lengkap.";
    } else {
        // Use company name from user's company
        $company_name = $user_company['company_name'];

        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = $slug . '-' . uniqid();

        // Handle skills as JSON
        $skills_required = null;
        if (!empty($_POST['skills_required'])) {
            $skills_array = array_map('trim', explode(',', $_POST['skills_required']));
            $skills_required = json_encode($skills_array);
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO jobs (
                    title, slug, company, company_id, category_id, description, requirements, 
                    responsibilities, benefits, job_type, work_location, location, salary,
                    salary_min, salary_max, experience_required, education_required, skills_required,
                    application_deadline, contact_email, contact_phone, is_featured, is_urgent,
                    status, posted_by, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )
            ");
              if ($stmt->execute([
                $title, $slug, $company_name, $company_id, $category_id, $description,
                $requirements, $responsibilities, $benefits, $job_type, $work_location,
                $location, $salary, $salary_min, $salary_max, $experience_required,
                $education_required, $skills_required, $application_deadline,
                $contact_email, $contact_phone, $is_featured, $is_urgent,
                $status, $_SESSION['user_id']
            ])) {
                $job_id = $pdo->lastInsertId();
                
                // Process job alerts for newly posted job
                if ($status === 'published') {
                    try {
                        $new_job_data = [
                            'id' => $job_id,
                            'title' => $title,
                            'company' => $company_name,
                            'category_id' => $category_id,
                            'description' => $description,
                            'requirements' => $requirements,
                            'job_type' => $job_type,
                            'location' => $location,
                            'salary' => $salary,
                            'salary_min' => $salary_min,
                            'salary_max' => $salary_max,
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                        
                        // Check for matching job alerts and send notifications
                        check_job_alerts_for_new_job($new_job_data);
                    } catch (Exception $e) {
                        // Log error but don't stop the job posting process
                        error_log("Job Alert Error: " . $e->getMessage());
                    }
                }
                
                $success_message = "Lowongan berhasil diposting!";
                // Redirect to job management page after successful posting
                header("Location: my-jobs.php");
                exit();
            } else {
                $error_message = "Gagal memposting lowongan.";
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

$unread_count = is_logged_in() ? get_unread_notifications_count($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Lowongan - HireWay</title>
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
        
        .form-card {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: none;
            border-radius: 15px;
        }
        
        .form-section {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .btn-post {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
        }
        
        .btn-post:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'src/includes/navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card form-card">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-4">
                                <i class="fas fa-plus-circle text-primary me-3" style="font-size: 2rem;"></i>
                                <div>
                                    <h2 class="mb-0">Post Lowongan Baru</h2>
                                    <p class="text-muted mb-0">Buat lowongan pekerjaan untuk perusahaan Anda</p>
                                </div>
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

                            <form method="POST">
                                <!-- Basic Information -->
                                <div class="form-section">
                                    <h5 class="section-title">
                                        <i class="fas fa-info-circle me-2"></i>Informasi Dasar
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="title" class="form-label">Judul Lowongan *</label>
                                                <input type="text" name="title" class="form-control" 
                                                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>" 
                                                       placeholder="contoh: Frontend Developer" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select name="status" class="form-select">
                                                    <option value="published" <?= (isset($_POST['status']) && $_POST['status'] == 'published') ? 'selected' : '' ?>>Publish</option>
                                                    <option value="draft" <?= (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : '' ?>>Draft</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                      <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="company_name" class="form-label">Perusahaan</label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($user_company['company_name']) ?>" readonly>
                                                <div class="form-text">Posting untuk: <?= htmlspecialchars($user_company['company_name']) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="category_id" class="form-label">Kategori *</label>
                                                <select name="category_id" class="form-select" required>
                                                    <option value="">Pilih Kategori</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?= $category['id'] ?>" 
                                                                <?= (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($category['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Job Details -->
                                <div class="form-section">
                                    <h5 class="section-title">
                                        <i class="fas fa-clipboard-list me-2"></i>Detail Pekerjaan
                                    </h5>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Deskripsi Pekerjaan *</label>
                                        <textarea name="description" class="form-control" rows="4" 
                                                  placeholder="Jelaskan tentang pekerjaan ini..." required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="responsibilities" class="form-label">Tanggung Jawab</label>
                                        <textarea name="responsibilities" class="form-control" rows="3" 
                                                  placeholder="Apa saja tanggung jawab dalam pekerjaan ini?"><?= isset($_POST['responsibilities']) ? htmlspecialchars($_POST['responsibilities']) : '' ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="requirements" class="form-label">Persyaratan</label>
                                        <textarea name="requirements" class="form-control" rows="3" 
                                                  placeholder="Apa persyaratan untuk posisi ini?"><?= isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : '' ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="benefits" class="form-label">Benefit & Fasilitas</label>
                                        <textarea name="benefits" class="form-control" rows="3" 
                                                  placeholder="Benefit apa yang ditawarkan?"><?= isset($_POST['benefits']) ? htmlspecialchars($_POST['benefits']) : '' ?></textarea>
                                    </div>
                                </div>

                                <!-- Job Specifications -->
                                <div class="form-section">
                                    <h5 class="section-title">
                                        <i class="fas fa-cogs me-2"></i>Spesifikasi Pekerjaan
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="job_type" class="form-label">Tipe Pekerjaan</label>
                                                <select name="job_type" class="form-select">
                                                    <option value="full-time" <?= (isset($_POST['job_type']) && $_POST['job_type'] == 'full-time') ? 'selected' : '' ?>>Full Time</option>
                                                    <option value="part-time" <?= (isset($_POST['job_type']) && $_POST['job_type'] == 'part-time') ? 'selected' : '' ?>>Part Time</option>
                                                    <option value="contract" <?= (isset($_POST['job_type']) && $_POST['job_type'] == 'contract') ? 'selected' : '' ?>>Contract</option>
                                                    <option value="internship" <?= (isset($_POST['job_type']) && $_POST['job_type'] == 'internship') ? 'selected' : '' ?>>Internship</option>
                                                    <option value="freelance" <?= (isset($_POST['job_type']) && $_POST['job_type'] == 'freelance') ? 'selected' : '' ?>>Freelance</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="work_location" class="form-label">Lokasi Kerja</label>
                                                <select name="work_location" class="form-select">
                                                    <option value="onsite" <?= (isset($_POST['work_location']) && $_POST['work_location'] == 'onsite') ? 'selected' : '' ?>>Onsite</option>
                                                    <option value="remote" <?= (isset($_POST['work_location']) && $_POST['work_location'] == 'remote') ? 'selected' : '' ?>>Remote</option>
                                                    <option value="hybrid" <?= (isset($_POST['work_location']) && $_POST['work_location'] == 'hybrid') ? 'selected' : '' ?>>Hybrid</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="location" class="form-label">Lokasi</label>
                                                <input type="text" name="location" class="form-control" 
                                                       value="<?= isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '' ?>" 
                                                       placeholder="Jakarta, Indonesia">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="experience_required" class="form-label">Pengalaman</label>
                                                <select name="experience_required" class="form-select">
                                                    <option value="">Tidak ditentukan</option>
                                                    <option value="fresh-graduate" <?= (isset($_POST['experience_required']) && $_POST['experience_required'] == 'fresh-graduate') ? 'selected' : '' ?>>Fresh Graduate</option>
                                                    <option value="1-2-years" <?= (isset($_POST['experience_required']) && $_POST['experience_required'] == '1-2-years') ? 'selected' : '' ?>>1-2 Tahun</option>
                                                    <option value="3-5-years" <?= (isset($_POST['experience_required']) && $_POST['experience_required'] == '3-5-years') ? 'selected' : '' ?>>3-5 Tahun</option>
                                                    <option value="5+-years" <?= (isset($_POST['experience_required']) && $_POST['experience_required'] == '5+-years') ? 'selected' : '' ?>>5+ Tahun</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="education_required" class="form-label">Pendidikan</label>
                                                <select name="education_required" class="form-select">
                                                    <option value="">Tidak ditentukan</option>
                                                    <option value="SMA" <?= (isset($_POST['education_required']) && $_POST['education_required'] == 'SMA') ? 'selected' : '' ?>>SMA</option>
                                                    <option value="D3" <?= (isset($_POST['education_required']) && $_POST['education_required'] == 'D3') ? 'selected' : '' ?>>D3</option>
                                                    <option value="S1" <?= (isset($_POST['education_required']) && $_POST['education_required'] == 'S1') ? 'selected' : '' ?>>S1</option>
                                                    <option value="S2" <?= (isset($_POST['education_required']) && $_POST['education_required'] == 'S2') ? 'selected' : '' ?>>S2</option>
                                                    <option value="S3" <?= (isset($_POST['education_required']) && $_POST['education_required'] == 'S3') ? 'selected' : '' ?>>S3</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="application_deadline" class="form-label">Deadline Lamaran</label>
                                                <input type="date" name="application_deadline" class="form-control" 
                                                       value="<?= isset($_POST['application_deadline']) ? $_POST['application_deadline'] : '' ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="skills_required" class="form-label">Skills yang Dibutuhkan</label>
                                        <input type="text" name="skills_required" class="form-control" 
                                               value="<?= isset($_POST['skills_required']) ? htmlspecialchars($_POST['skills_required']) : '' ?>" 
                                               placeholder="PHP, JavaScript, MySQL (pisahkan dengan koma)">
                                        <div class="form-text">Pisahkan setiap skill dengan koma</div>
                                    </div>
                                </div>

                                <!-- Salary & Contact -->
                                <div class="form-section">
                                    <h5 class="section-title">
                                        <i class="fas fa-money-bill-wave me-2"></i>Gaji & Kontak
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="salary" class="form-label">Range Gaji (Text)</label>
                                                <input type="text" name="salary" class="form-control" 
                                                       value="<?= isset($_POST['salary']) ? htmlspecialchars($_POST['salary']) : '' ?>" 
                                                       placeholder="5-10 Juta / Negosiasi">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="salary_min" class="form-label">Gaji Min (Angka)</label>
                                                <input type="number" name="salary_min" class="form-control" 
                                                       value="<?= isset($_POST['salary_min']) ? $_POST['salary_min'] : '' ?>" 
                                                       placeholder="5000000">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="salary_max" class="form-label">Gaji Max (Angka)</label>
                                                <input type="number" name="salary_max" class="form-control" 
                                                       value="<?= isset($_POST['salary_max']) ? $_POST['salary_max'] : '' ?>" 
                                                       placeholder="10000000">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="contact_email" class="form-label">Email Kontak</label>
                                                <input type="email" name="contact_email" class="form-control" 
                                                       value="<?= isset($_POST['contact_email']) ? htmlspecialchars($_POST['contact_email']) : '' ?>" 
                                                       placeholder="hr@company.com">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="contact_phone" class="form-label">No. Telepon</label>
                                                <input type="text" name="contact_phone" class="form-control" 
                                                       value="<?= isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : '' ?>" 
                                                       placeholder="021-123456">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Additional Options -->
                                <div class="form-section">
                                    <h5 class="section-title">
                                        <i class="fas fa-star me-2"></i>Opsi Tambahan
                                    </h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" 
                                                       <?= (isset($_POST['is_featured'])) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_featured">
                                                    <i class="fas fa-star text-warning me-1"></i>Lowongan Featured
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" name="is_urgent" id="is_urgent" 
                                                       <?= (isset($_POST['is_urgent'])) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="is_urgent">
                                                    <i class="fas fa-exclamation-triangle text-danger me-1"></i>Lowongan Urgent
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Buttons -->
                                <div class="d-flex justify-content-between">
                                    <a href="my-jobs.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>Kembali
                                    </a>
                                    <button type="submit" class="btn btn-post">
                                        <i class="fas fa-paper-plane me-1"></i>Post Lowongan
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
