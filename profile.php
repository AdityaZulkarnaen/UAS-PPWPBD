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
$current_profile = get_user_profile($user_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_profile'])) {
    if (!validate_csrf()) {
        $_SESSION['error_message'] = "Token keamanan tidak valid.";
        header('Location: profile.php');
        exit();
    }
    
    // Prepare profile data
    $profile_data = [
        'first_name' => clean_input($_POST['first_name']),
        'last_name' => clean_input($_POST['last_name']),
        'phone' => clean_input($_POST['phone']),
        'address' => clean_input($_POST['address']),
        'city' => clean_input($_POST['city']),
        'province' => clean_input($_POST['province']),
        'postal_code' => clean_input($_POST['postal_code']),
        'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
        'gender' => !empty($_POST['gender']) ? $_POST['gender'] : null,
        'bio' => clean_input($_POST['bio']),
        'experience_years' => (int)$_POST['experience_years'],
        'education_level' => !empty($_POST['education_level']) ? $_POST['education_level'] : null,
        'salary_expectation_min' => !empty($_POST['salary_expectation_min']) ? (int)$_POST['salary_expectation_min'] : null,
        'salary_expectation_max' => !empty($_POST['salary_expectation_max']) ? (int)$_POST['salary_expectation_max'] : null,
        'preferred_location' => clean_input($_POST['preferred_location']),
        'preferred_job_type' => !empty($_POST['preferred_job_type']) ? $_POST['preferred_job_type'] : null,
        'job_status' => $_POST['job_status'],
        'portfolio_url' => clean_input($_POST['portfolio_url']),
        'linkedin_url' => clean_input($_POST['linkedin_url']),
        'github_url' => clean_input($_POST['github_url']),
        'website_url' => clean_input($_POST['website_url']),
        'profile_visibility' => $_POST['profile_visibility']
    ];
    
    // Handle skills (convert array to JSON)
    $skills = [];
    if (!empty($_POST['skills'])) {
        $skills = array_filter(array_map('trim', explode(',', $_POST['skills'])));
    }
    $profile_data['skills'] = format_skills_to_json($skills);
    
    // Handle languages
    $languages = [];
    if (!empty($_POST['languages'])) {
        $lang_array = array_filter(array_map('trim', explode(',', $_POST['languages'])));
        foreach ($lang_array as $lang) {
            $languages[] = ['name' => $lang, 'level' => 'intermediate']; // Default level
        }
    }
    $profile_data['languages'] = format_languages_to_json($languages);
    
    // Save profile
    if (save_user_profile($user_id, $profile_data)) {
        $_SESSION['success_message'] = "Profil berhasil disimpan!";
    } else {
        $_SESSION['error_message'] = "Gagal menyimpan profil.";
    }
    
    regenerate_csrf_token();
    header('Location: profile.php');
    exit();
}

// Get messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Calculate profile completion
$completion_percentage = calculate_profile_completion($current_profile);

// Parse skills and languages for display
$skills = parse_skills_from_json($current_profile['skills'] ?? '');
$languages = parse_languages_from_json($current_profile['languages'] ?? '');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - HireWay</title>    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/navbar.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
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

        .profile-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
        }

        .completion-bar {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            height: 8px;
            margin-top: 1rem;
        }

        .completion-progress {
            background: var(--success-color);
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn-modern {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            color: white;
        }

        .section-title {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .skills-input {
            position: relative;
        }

        .skills-help {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>    <!-- Navbar -->
    <?php include 'src/includes/navbar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="profile-card">
                        <!-- Profile Header -->
                        <div class="profile-header">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3><?= htmlspecialchars($current_profile['first_name'] ?? 'User') ?> <?= htmlspecialchars($current_profile['last_name'] ?? '') ?></h3>
                            <p class="mb-2"><?= htmlspecialchars(get_user_name()) ?></p>
                            <div class="d-flex justify-content-center align-items-center">
                                <span class="me-2">Kelengkapan Profil:</span>
                                <strong><?= $completion_percentage ?>%</strong>
                            </div>
                            <div class="completion-bar">
                                <div class="completion-progress" style="width: <?= $completion_percentage ?>%"></div>
                            </div>
                        </div>

                        <!-- Profile Form -->
                        <div class="p-4">
                            <form method="POST" id="profileForm">
                                <?= csrf_token_field() ?>
                                
                                <!-- Personal Information -->
                                <div class="mb-5">
                                    <h4 class="section-title">Informasi Pribadi</h4>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nama Depan *</label>
                                            <input type="text" name="first_name" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['first_name'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nama Belakang *</label>
                                            <input type="text" name="last_name" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['last_name'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nomor Telepon *</label>
                                            <input type="tel" name="phone" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['phone'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tanggal Lahir</label>
                                            <input type="date" name="date_of_birth" class="form-control" 
                                                   value="<?= $current_profile['date_of_birth'] ?? '' ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Jenis Kelamin</label>
                                            <select name="gender" class="form-select">
                                                <option value="">Pilih Jenis Kelamin</option>
                                                <option value="male" <?= ($current_profile['gender'] ?? '') == 'male' ? 'selected' : '' ?>>Laki-laki</option>
                                                <option value="female" <?= ($current_profile['gender'] ?? '') == 'female' ? 'selected' : '' ?>>Perempuan</option>
                                                <option value="other" <?= ($current_profile['gender'] ?? '') == 'other' ? 'selected' : '' ?>>Lainnya</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Status Pekerjaan</label>
                                            <select name="job_status" class="form-select">
                                                <option value="open_to_work" <?= ($current_profile['job_status'] ?? 'open_to_work') == 'open_to_work' ? 'selected' : '' ?>>Terbuka untuk Bekerja</option>
                                                <option value="employed" <?= ($current_profile['job_status'] ?? '') == 'employed' ? 'selected' : '' ?>>Sudah Bekerja</option>
                                                <option value="not_looking" <?= ($current_profile['job_status'] ?? '') == 'not_looking' ? 'selected' : '' ?>>Tidak Sedang Mencari</option>
                                            </select>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Alamat</label>
                                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($current_profile['address'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Kota</label>
                                            <input type="text" name="city" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['city'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Provinsi</label>
                                            <input type="text" name="province" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['province'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Kode Pos</label>
                                            <input type="text" name="postal_code" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['postal_code'] ?? '') ?>">
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Bio/Deskripsi Diri *</label>
                                            <textarea name="bio" class="form-control" rows="4" required 
                                                      placeholder="Ceritakan tentang diri Anda, pengalaman, dan tujuan karir..."><?= htmlspecialchars($current_profile['bio'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Professional Information -->
                                <div class="mb-5">
                                    <h4 class="section-title">Informasi Profesional</h4>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Pengalaman Kerja (Tahun) *</label>
                                            <select name="experience_years" class="form-select" required>
                                                <option value="">Pilih Pengalaman</option>
                                                <?php for ($i = 0; $i <= 20; $i++): ?>
                                                    <option value="<?= $i ?>" <?= ($current_profile['experience_years'] ?? 0) == $i ? 'selected' : '' ?>>
                                                        <?= $i == 0 ? 'Fresh Graduate' : $i . ' Tahun' ?>
                                                    </option>
                                                <?php endfor; ?>
                                                <option value="21" <?= ($current_profile['experience_years'] ?? 0) > 20 ? 'selected' : '' ?>>20+ Tahun</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tingkat Pendidikan</label>
                                            <select name="education_level" class="form-select">
                                                <option value="">Pilih Pendidikan</option>
                                                <option value="sma" <?= ($current_profile['education_level'] ?? '') == 'sma' ? 'selected' : '' ?>>SMA/SMK</option>
                                                <option value="diploma" <?= ($current_profile['education_level'] ?? '') == 'diploma' ? 'selected' : '' ?>>Diploma</option>
                                                <option value="s1" <?= ($current_profile['education_level'] ?? '') == 's1' ? 'selected' : '' ?>>S1</option>
                                                <option value="s2" <?= ($current_profile['education_level'] ?? '') == 's2' ? 'selected' : '' ?>>S2</option>
                                                <option value="s3" <?= ($current_profile['education_level'] ?? '') == 's3' ? 'selected' : '' ?>>S3</option>
                                                <option value="other" <?= ($current_profile['education_level'] ?? '') == 'other' ? 'selected' : '' ?>>Lainnya</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Ekspektasi Gaji Minimum (Rp)</label>
                                            <input type="number" name="salary_expectation_min" class="form-control" 
                                                   value="<?= $current_profile['salary_expectation_min'] ?? '' ?>" placeholder="5000000">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Ekspektasi Gaji Maksimum (Rp)</label>
                                            <input type="number" name="salary_expectation_max" class="form-control" 
                                                   value="<?= $current_profile['salary_expectation_max'] ?? '' ?>" placeholder="10000000">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Lokasi Kerja Diinginkan</label>
                                            <input type="text" name="preferred_location" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['preferred_location'] ?? '') ?>" 
                                                   placeholder="Jakarta, Remote, dll">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Jenis Pekerjaan Diinginkan</label>
                                            <select name="preferred_job_type" class="form-select">
                                                <option value="">Pilih Jenis Pekerjaan</option>
                                                <option value="full-time" <?= ($current_profile['preferred_job_type'] ?? '') == 'full-time' ? 'selected' : '' ?>>Full Time</option>
                                                <option value="part-time" <?= ($current_profile['preferred_job_type'] ?? '') == 'part-time' ? 'selected' : '' ?>>Part Time</option>
                                                <option value="contract" <?= ($current_profile['preferred_job_type'] ?? '') == 'contract' ? 'selected' : '' ?>>Kontrak</option>
                                                <option value="internship" <?= ($current_profile['preferred_job_type'] ?? '') == 'internship' ? 'selected' : '' ?>>Magang</option>
                                                <option value="freelance" <?= ($current_profile['preferred_job_type'] ?? '') == 'freelance' ? 'selected' : '' ?>>Freelance</option>
                                            </select>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Keahlian/Skills</label>
                                            <input type="text" name="skills" class="form-control" 
                                                   value="<?= htmlspecialchars(implode(', ', $skills)) ?>" 
                                                   placeholder="PHP, JavaScript, MySQL, Adobe Photoshop">
                                            <div class="skills-help">Pisahkan dengan koma (,)</div>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label">Bahasa yang Dikuasai</label>
                                            <input type="text" name="languages" class="form-control" 
                                                   value="<?= htmlspecialchars(implode(', ', array_column($languages, 'name'))) ?>" 
                                                   placeholder="Bahasa Indonesia, English, Mandarin">
                                            <div class="skills-help">Pisahkan dengan koma (,)</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Social Links -->
                                <div class="mb-5">
                                    <h4 class="section-title">Link Portfolio & Media Sosial</h4>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Portfolio Website</label>
                                            <input type="url" name="portfolio_url" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['portfolio_url'] ?? '') ?>" 
                                                   placeholder="https://portfolio.com">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">LinkedIn</label>
                                            <input type="url" name="linkedin_url" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['linkedin_url'] ?? '') ?>" 
                                                   placeholder="https://linkedin.com/in/username">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">GitHub</label>
                                            <input type="url" name="github_url" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['github_url'] ?? '') ?>" 
                                                   placeholder="https://github.com/username">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Website Pribadi</label>
                                            <input type="url" name="website_url" class="form-control" 
                                                   value="<?= htmlspecialchars($current_profile['website_url'] ?? '') ?>" 
                                                   placeholder="https://website.com">
                                        </div>
                                    </div>
                                </div>

                                <!-- Privacy Settings -->
                                <div class="mb-4">
                                    <h4 class="section-title">Pengaturan Privasi</h4>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Visibilitas Profil</label>
                                            <select name="profile_visibility" class="form-select">
                                                <option value="public" <?= ($current_profile['profile_visibility'] ?? 'public') == 'public' ? 'selected' : '' ?>>Publik</option>
                                                <option value="recruiter_only" <?= ($current_profile['profile_visibility'] ?? '') == 'recruiter_only' ? 'selected' : '' ?>>Hanya Recruiter</option>
                                                <option value="private" <?= ($current_profile['profile_visibility'] ?? '') == 'private' ? 'selected' : '' ?>>Privat</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="text-center">
                                    <button type="submit" name="save_profile" class="btn btn-modern btn-lg">
                                        <i class="fas fa-save me-2"></i>Simpan Profil
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

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const requiredFields = ['first_name', 'last_name', 'phone', 'bio', 'experience_years'];
            let isValid = true;
            let firstInvalidField = null;

            requiredFields.forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                firstInvalidField.focus();
                Swal.fire({
                    title: 'Form Tidak Lengkap',
                    text: 'Mohon lengkapi semua field yang wajib diisi (bertanda *)',
                    icon: 'warning',
                    confirmButtonColor: '#6366f1'
                });
            }
        });
    </script>
</body>
</html>
