<?php
/**
 * Profile Helper Functions
 * Functions for managing user profiles, applications, and categories
 */

require_once __DIR__ . '/../../config.php';

/**
 * Get user profile by user ID
 */
function get_user_profile($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT up.*, u.username, u.email, u.created_at as user_created_at
        FROM user_profiles up 
        LEFT JOIN users u ON up.user_id = u.id 
        WHERE up.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Create or update user profile
 */
function save_user_profile($user_id, $profile_data) {
    global $pdo;
    
    // Check if profile exists
    $existing = get_user_profile($user_id);
    
    if ($existing) {
        // Update existing profile
        $stmt = $pdo->prepare("
            UPDATE user_profiles SET 
                first_name = ?, last_name = ?, phone = ?, address = ?, 
                city = ?, province = ?, postal_code = ?, date_of_birth = ?, 
                gender = ?, bio = ?, experience_years = ?, education_level = ?,
                salary_expectation_min = ?, salary_expectation_max = ?,
                preferred_location = ?, preferred_job_type = ?, job_status = ?,
                portfolio_url = ?, linkedin_url = ?, github_url = ?, website_url = ?,
                skills = ?, languages = ?, profile_visibility = ?
            WHERE user_id = ?
        ");
        
        return $stmt->execute([
            $profile_data['first_name'], $profile_data['last_name'], $profile_data['phone'],
            $profile_data['address'], $profile_data['city'], $profile_data['province'],
            $profile_data['postal_code'], $profile_data['date_of_birth'], $profile_data['gender'],
            $profile_data['bio'], $profile_data['experience_years'], $profile_data['education_level'],
            $profile_data['salary_expectation_min'], $profile_data['salary_expectation_max'],
            $profile_data['preferred_location'], $profile_data['preferred_job_type'],
            $profile_data['job_status'], $profile_data['portfolio_url'], $profile_data['linkedin_url'],
            $profile_data['github_url'], $profile_data['website_url'], $profile_data['skills'],
            $profile_data['languages'], $profile_data['profile_visibility'], $user_id
        ]);
    } else {
        // Create new profile
        $stmt = $pdo->prepare("
            INSERT INTO user_profiles (
                user_id, first_name, last_name, phone, address, city, province, 
                postal_code, date_of_birth, gender, bio, experience_years, 
                education_level, salary_expectation_min, salary_expectation_max,
                preferred_location, preferred_job_type, job_status, portfolio_url,
                linkedin_url, github_url, website_url, skills, languages, profile_visibility
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $user_id, $profile_data['first_name'], $profile_data['last_name'],
            $profile_data['phone'], $profile_data['address'], $profile_data['city'],
            $profile_data['province'], $profile_data['postal_code'], $profile_data['date_of_birth'],
            $profile_data['gender'], $profile_data['bio'], $profile_data['experience_years'],
            $profile_data['education_level'], $profile_data['salary_expectation_min'],
            $profile_data['salary_expectation_max'], $profile_data['preferred_location'],
            $profile_data['preferred_job_type'], $profile_data['job_status'],
            $profile_data['portfolio_url'], $profile_data['linkedin_url'], $profile_data['github_url'],
            $profile_data['website_url'], $profile_data['skills'], $profile_data['languages'],
            $profile_data['profile_visibility']
        ]);
    }
}

/**
 * Get all categories
 */
function get_all_categories($active_only = true) {
    global $pdo;
    
    $sql = "SELECT * FROM categories";
    if ($active_only) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY name ASC";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get category by ID or slug
 */
function get_category($identifier, $by_slug = false) {
    global $pdo;
    
    $field = $by_slug ? 'slug' : 'id';
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE {$field} = ?");
    $stmt->execute([$identifier]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Submit job application
 */
function submit_application($job_id, $user_id, $application_data) {
    global $pdo;
    
    try {
        // Check if user already applied
        $existing = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
        $existing->execute([$job_id, $user_id]);
        
        if ($existing->fetch()) {
            return ['success' => false, 'message' => 'Anda sudah melamar pada lowongan ini.'];
        }
        
        // Insert application
        $stmt = $pdo->prepare("
            INSERT INTO applications (job_id, user_id, cover_letter, resume_path, 
                                    portfolio_url, expected_salary, available_start_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $job_id, $user_id, $application_data['cover_letter'],
            $application_data['resume_path'], $application_data['portfolio_url'],
            $application_data['expected_salary'], $application_data['available_start_date']
        ]);
        
        if ($success) {
            // Create notification for employer (if needed)
            // You can implement this later
            
            return ['success' => true, 'message' => 'Lamaran berhasil dikirim!'];
        } else {
            return ['success' => false, 'message' => 'Gagal mengirim lamaran.'];
        }
        
    } catch (PDOException $e) {
        error_log("Application submission error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan sistem.'];
    }
}

/**
 * Get user applications
 */
function get_user_applications($user_id, $status = null, $limit = null) {
    global $pdo;
    
    $sql = "
        SELECT a.*, j.title, j.company, j.location, j.salary, j.job_type, j.created_at as job_posted
        FROM applications a
        LEFT JOIN jobs j ON a.job_id = j.id
        WHERE a.user_id = ?
    ";
    
    $params = [$user_id];
    
    if ($status) {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY a.applied_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get applications for a job
 */
function get_job_applications($job_id, $status = null) {
    global $pdo;
    
    $sql = "
        SELECT a.*, u.username, u.email, up.first_name, up.last_name, up.phone,
               CONCAT(up.first_name, ' ', up.last_name) as full_name
        FROM applications a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE a.job_id = ?
    ";
    
    $params = [$job_id];
    
    if ($status) {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY a.applied_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Update application status
 */
function update_application_status($application_id, $status, $notes = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE applications 
        SET status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    
    return $stmt->execute([$status, $notes, $application_id]);
}

/**
 * Toggle bookmark for a job
 */
function toggle_bookmark($user_id, $job_id) {
    global $pdo;
    
    // Check if bookmark exists
    $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$user_id, $job_id]);
    
    if ($stmt->fetch()) {
        // Remove bookmark
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ? AND job_id = ?");
        $stmt->execute([$user_id, $job_id]);
        return ['bookmarked' => false, 'message' => 'Lowongan dihapus dari favorit'];
    } else {
        // Add bookmark
        $stmt = $pdo->prepare("INSERT INTO bookmarks (user_id, job_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $job_id]);
        return ['bookmarked' => true, 'message' => 'Lowongan ditambahkan ke favorit'];
    }
}

/**
 * Check if job is bookmarked by user
 */
function is_job_bookmarked($user_id, $job_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE user_id = ? AND job_id = ?");
    $stmt->execute([$user_id, $job_id]);
    return $stmt->fetch() !== false;
}

/**
 * Get user bookmarks
 */
function get_user_bookmarks($user_id, $limit = null) {
    global $pdo;
    
    $sql = "
        SELECT j.*, b.created_at as bookmarked_at
        FROM bookmarks b
        LEFT JOIN jobs j ON b.job_id = j.id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
    ";
    
    $params = [$user_id];
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Add job view tracking
 */
function track_job_view($job_id, $user_id = null, $ip_address = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO job_views (job_id, user_id, ip_address, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    return $stmt->execute([$job_id, $user_id, $ip_address]);
}

/**
 * Get job statistics
 */
function get_job_stats($job_id) {
    global $pdo;
    
    $stats = [];
    
    // Get view count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_views WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $stats['views'] = $stmt->fetchColumn();
    
    // Get application count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $stats['applications'] = $stmt->fetchColumn();
    
    // Get bookmark count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $stats['bookmarks'] = $stmt->fetchColumn();
    
    return $stats;
}

/**
 * Create notification
 */
function create_notification($user_id, $title, $message, $type = 'system', $related_id = null, $related_type = 'system', $action_url = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_id, related_type, action_url) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$user_id, $title, $message, $type, $related_id, $related_type, $action_url]);
}

/**
 * Get user notifications
 */
function get_user_notifications($user_id, $unread_only = false, $limit = 20) {
    global $pdo;
    
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$user_id];
    
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id, $user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND user_id = ?
    ");
    
    return $stmt->execute([$notification_id, $user_id]);
}

/**
 * Get unread notifications count
 */
function get_unread_notifications_count($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

/**
 * Format skills array to JSON
 */
function format_skills_to_json($skills_array) {
    return json_encode($skills_array, JSON_UNESCAPED_UNICODE);
}

/**
 * Parse skills from JSON
 */
function parse_skills_from_json($skills_json) {
    if (empty($skills_json)) return [];
    return json_decode($skills_json, true) ?? [];
}

/**
 * Format languages array to JSON
 */
function format_languages_to_json($languages_array) {
    return json_encode($languages_array, JSON_UNESCAPED_UNICODE);
}

/**
 * Parse languages from JSON
 */
function parse_languages_from_json($languages_json) {
    if (empty($languages_json)) return [];
    return json_decode($languages_json, true) ?? [];
}

/**
 * Calculate profile completion percentage
 */
function calculate_profile_completion($profile) {
    if (!$profile) return 0;
    
    $required_fields = [
        'first_name', 'last_name', 'phone', 'bio', 'experience_years'
    ];
    
    $optional_fields = [
        'address', 'city', 'date_of_birth', 'education_level', 
        'preferred_location', 'skills', 'portfolio_url'
    ];
    
    $completed_required = 0;
    $completed_optional = 0;
    
    foreach ($required_fields as $field) {
        if (!empty($profile[$field])) {
            $completed_required++;
        }
    }
    
    foreach ($optional_fields as $field) {
        if (!empty($profile[$field])) {
            $completed_optional++;
        }
    }
    
    // Required fields worth 70%, optional fields worth 30%
    $required_percentage = ($completed_required / count($required_fields)) * 70;
    $optional_percentage = ($completed_optional / count($optional_fields)) * 30;
    
    return round($required_percentage + $optional_percentage);
}

/**
 * Get application status badge HTML
 */
function get_application_status_badge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Menunggu</span>',
        'reviewed' => '<span class="badge bg-info">Ditinjau</span>',
        'shortlisted' => '<span class="badge bg-primary">Shortlist</span>',
        'interview' => '<span class="badge bg-secondary">Interview</span>',
        'accepted' => '<span class="badge bg-success">Diterima</span>',
        'rejected' => '<span class="badge bg-danger">Ditolak</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

/**
 * Get profile completion badge
 */
function get_profile_completion_badge($percentage) {
    if ($percentage >= 90) {
        return '<span class="badge bg-success">Profil Lengkap</span>';
    } elseif ($percentage >= 70) {
        return '<span class="badge bg-primary">Profil Baik</span>';
    } elseif ($percentage >= 50) {
        return '<span class="badge bg-warning">Profil Cukup</span>';
    } else {
        return '<span class="badge bg-danger">Profil Belum Lengkap</span>';
    }
}
?>
