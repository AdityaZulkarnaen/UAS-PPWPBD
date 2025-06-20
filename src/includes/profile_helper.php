<?php
/**
 * Profile Helper Functions
 * Functions for managing user profiles, applications, and categories
 */

require_once __DIR__ . '/../../config.php';

/**
 * Get user profile data with all related information
 */
function get_user_profile($user_id) {
    global $pdo;
    
    $query = "
        SELECT 
            u.id as user_id,
            u.name,
            u.email,
            u.phone as user_phone,
            u.profile_image,
            u.created_at as user_created_at,
            up.id as profile_id,
            up.full_name,
            up.date_of_birth,
            up.gender,
            up.address,
            up.city,
            up.province,
            up.postal_code,
            up.bio,
            up.experience_years,
            up.education_level,
            up.skills,
            up.preferred_job_type,
            up.preferred_location,
            up.salary_expectation_min,
            up.salary_expectation_max,
            up.cv_file,
            up.portfolio_url,
            up.linkedin_url,
            up.github_url,
            up.availability,
            up.created_at as profile_created_at,
            up.updated_at as profile_updated_at
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    if (!$result) {
        return null;
    }
    
    // Split full_name into first_name and last_name for backward compatibility
    $full_name = $result['full_name'] ?? $result['name'] ?? '';
    $name_parts = explode(' ', trim($full_name), 2);
    
    $profile = [
        'user_id' => $result['user_id'],
        'name' => $result['name'],
        'email' => $result['email'],
        'phone' => $result['user_phone'],
        'profile_image' => $result['profile_image'],
        'user_created_at' => $result['user_created_at'],
        'profile_id' => $result['profile_id'],
        'full_name' => $result['full_name'],
        'first_name' => $name_parts[0] ?? '',
        'last_name' => $name_parts[1] ?? '',
        'date_of_birth' => $result['date_of_birth'],
        'gender' => $result['gender'],
        'address' => $result['address'],
        'city' => $result['city'],
        'province' => $result['province'],
        'postal_code' => $result['postal_code'],
        'bio' => $result['bio'],
        'experience_years' => $result['experience_years'] ?? 0,
        'education_level' => $result['education_level'],
        'skills' => $result['skills'],
        'preferred_job_type' => $result['preferred_job_type'],
        'preferred_location' => $result['preferred_location'],
        'salary_expectation_min' => $result['salary_expectation_min'],
        'salary_expectation_max' => $result['salary_expectation_max'],
        'cv_file' => $result['cv_file'],
        'portfolio_url' => $result['portfolio_url'],
        'linkedin_url' => $result['linkedin_url'],
        'github_url' => $result['github_url'],
        'availability' => $result['availability'],
        'profile_created_at' => $result['profile_created_at'],
        'profile_updated_at' => $result['profile_updated_at'],
        // Additional fields for form compatibility
        'website_url' => '', // Add this field to database if needed
        'job_status' => $result['availability'] ?? 'open_to_work',
        'profile_visibility' => 'public', // Add this field to database if needed
        'languages' => '[]' // Add this field to database if needed
    ];
    
    return $profile;
}

/**
 * Save user profile data
 */
function save_user_profile($user_id, $profile_data) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Update users table
        $user_update_query = "
            UPDATE users 
            SET phone = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ";
        $stmt = $pdo->prepare($user_update_query);
        $stmt->execute([
            $profile_data['phone'] ?? null,
            $user_id
        ]);
        
        // Combine first_name and last_name into full_name
        $full_name = trim(($profile_data['first_name'] ?? '') . ' ' . ($profile_data['last_name'] ?? ''));
        
        // Check if profile exists
        $check_query = "SELECT id FROM user_profiles WHERE user_id = ?";
        $stmt = $pdo->prepare($check_query);
        $stmt->execute([$user_id]);
        $profile_exists = $stmt->fetch();
        
        if ($profile_exists) {
            // Update existing profile
            $update_query = "
                UPDATE user_profiles 
                SET full_name = ?,
                    date_of_birth = ?,
                    gender = ?,
                    address = ?,
                    city = ?,
                    province = ?,
                    postal_code = ?,
                    bio = ?,
                    experience_years = ?,
                    education_level = ?,
                    skills = ?,
                    preferred_job_type = ?,
                    preferred_location = ?,
                    salary_expectation_min = ?,
                    salary_expectation_max = ?,
                    portfolio_url = ?,
                    linkedin_url = ?,
                    github_url = ?,
                    availability = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ";
            
            $stmt = $pdo->prepare($update_query);
            $stmt->execute([
                $full_name,
                $profile_data['date_of_birth'],
                $profile_data['gender'],
                $profile_data['address'],
                $profile_data['city'],
                $profile_data['province'],
                $profile_data['postal_code'],
                $profile_data['bio'],
                $profile_data['experience_years'],
                $profile_data['education_level'],
                $profile_data['skills'],
                $profile_data['preferred_job_type'],
                $profile_data['preferred_location'],
                $profile_data['salary_expectation_min'],
                $profile_data['salary_expectation_max'],
                $profile_data['portfolio_url'],
                $profile_data['linkedin_url'],
                $profile_data['github_url'],
                map_job_status_to_availability($profile_data['job_status'] ?? 'open_to_work'),
                $user_id
            ]);
        } else {
            // Insert new profile
            $insert_query = "
                INSERT INTO user_profiles (
                    user_id, full_name, date_of_birth, gender, address, city, province, 
                    postal_code, bio, experience_years, education_level, skills, 
                    preferred_job_type, preferred_location, salary_expectation_min, 
                    salary_expectation_max, portfolio_url, linkedin_url, github_url, 
                    availability, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ";
            
            $stmt = $pdo->prepare($insert_query);
            $stmt->execute([
                $user_id,
                $full_name,
                $profile_data['date_of_birth'],
                $profile_data['gender'],
                $profile_data['address'],
                $profile_data['city'],
                $profile_data['province'],
                $profile_data['postal_code'],
                $profile_data['bio'],
                $profile_data['experience_years'],
                $profile_data['education_level'],
                $profile_data['skills'],
                $profile_data['preferred_job_type'],
                $profile_data['preferred_location'],
                $profile_data['salary_expectation_min'],
                $profile_data['salary_expectation_max'],
                $profile_data['portfolio_url'],
                $profile_data['linkedin_url'],
                $profile_data['github_url'],
                map_job_status_to_availability($profile_data['job_status'] ?? 'open_to_work')
            ]);
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error saving user profile: " . $e->getMessage());
        return false;
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
        $limit = (int) $limit; // Ensure integer
        $sql .= " LIMIT " . $limit;
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
        $limit = (int) $limit; // Ensure integer
        $sql .= " LIMIT " . $limit;
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
    
    // Create data array for additional information
    $data = [];
    if ($related_id !== null) {
        $data['related_id'] = $related_id;
    }
    if ($related_type !== null) {
        $data['related_type'] = $related_type;
    }
    if ($action_url !== null) {
        $data['action_url'] = $action_url;
    }
    
    $data_json = !empty($data) ? json_encode($data) : null;
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type, data) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$user_id, $title, $message, $type, $data_json]);
}

/**
 * Get user notifications
 */
function get_user_notifications($user_id, $unread_only = false, $limit = 20) {
    global $pdo;
    
    // Ensure limit is an integer to prevent SQL injection
    $limit = (int) $limit;
    
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$user_id];
    
    if ($unread_only) {
        $sql .= " AND is_read = 0";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT " . $limit;
    
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
 * Calculate profile completion percentage
 */
function calculate_profile_completion($profile) {
    if (!$profile) {
        return 0;
    }
    
    $required_fields = [
        'first_name' => !empty($profile['first_name']),
        'last_name' => !empty($profile['last_name']),
        'phone' => !empty($profile['phone']),
        'bio' => !empty($profile['bio']),
        'experience_years' => isset($profile['experience_years']),
        'city' => !empty($profile['city']),
        'province' => !empty($profile['province']),
        'preferred_location' => !empty($profile['preferred_location']),
        'education_level' => !empty($profile['education_level']),
        'skills' => !empty($profile['skills']),
        'date_of_birth' => !empty($profile['date_of_birth']),
        'gender' => !empty($profile['gender']),
        'preferred_job_type' => !empty($profile['preferred_job_type']),
        'salary_expectation_min' => !empty($profile['salary_expectation_min']),
        'portfolio_url' => !empty($profile['portfolio_url']),
        'linkedin_url' => !empty($profile['linkedin_url'])
    ];
    
    $completed_fields = array_sum($required_fields);
    $total_fields = count($required_fields);
    
    return round(($completed_fields / $total_fields) * 100);
}

/**
 * Format skills to JSON for database storage
 */
function format_skills_to_json($skills_array) {
    if (empty($skills_array)) {
        return '[]';
    }
    
    $clean_skills = array_filter(array_map('trim', $skills_array));
    return json_encode(array_values($clean_skills), JSON_UNESCAPED_UNICODE);
}

/**
 * Parse skills from JSON
 */
function parse_skills_from_json($skills_json) {
    if (empty($skills_json)) {
        return [];
    }
    
    $skills = json_decode($skills_json, true);
    return is_array($skills) ? $skills : [];
}

/**
 * Format languages to JSON for database storage
 */
function format_languages_to_json($languages_array) {
    if (empty($languages_array)) {
        return '[]';
    }
    
    return json_encode($languages_array, JSON_UNESCAPED_UNICODE);
}

/**
 * Parse languages from JSON
 */
function parse_languages_from_json($languages_json) {
    if (empty($languages_json)) {
        return [];
    }
    
    $languages = json_decode($languages_json, true);
    return is_array($languages) ? $languages : [];
}

/**
 * Map job status to availability enum
 */
function map_job_status_to_availability($job_status) {
    switch ($job_status) {
        case 'open_to_work':
            return 'immediately';
        case 'employed':
            return 'one_month';
        case 'not_looking':
            return 'one_month';
        default:
            return 'immediately';
    }
}

/**
 * Get user's applications with job details and pagination
 */
function get_user_applications_paginated($user_id, $limit = 10, $offset = 0) {
    global $pdo;
    
    try {
        $query = "
            SELECT 
                a.id,
                a.job_id,
                a.status,
                a.cover_letter,
                a.applied_at,
                a.updated_at,
                j.title as job_title,
                j.company,
                j.location,
                j.job_type,
                j.salary,
                j.salary_min,
                j.salary_max,
                time_ago(a.applied_at) as applied_time_ago
            FROM applications a
            JOIN jobs j ON a.job_id = j.id            WHERE a.user_id = ?
            ORDER BY a.applied_at DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset . "
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        
        $results = $stmt->fetchAll();
        
        // Format salary for each result
        foreach ($results as &$result) {
            $result['formatted_salary'] = format_salary_range($result['salary_min'], $result['salary_max']);
        }
        
        return $results;
        
    } catch (Exception $e) {
        error_log("Error getting user applications: " . $e->getMessage());
        return [];
    }
}

/**
 * Toggle job bookmark
 */
function toggle_job_bookmark($user_id, $job_id) {
    global $pdo;
    
    try {
        if (is_job_bookmarked($user_id, $job_id)) {
            $query = "DELETE FROM bookmarks WHERE user_id = ? AND job_id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$user_id, $job_id]);
            return 'removed';
        } else {
            $query = "INSERT INTO bookmarks (user_id, job_id, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$user_id, $job_id]);
            return 'added';
        }
    } catch (Exception $e) {
        error_log("Error toggling bookmark: " . $e->getMessage());
        return false;
    }
}

/**
 * Get profile statistics
 */
function get_profile_statistics($user_id) {
    global $pdo;
    
    $stats = [
        'total_applications' => 0,
        'pending_applications' => 0,
        'accepted_applications' => 0,
        'total_bookmarks' => 0,
        'profile_views' => 0
    ];
    
    try {
        // Total applications
        $query = "SELECT COUNT(*) as total FROM applications WHERE user_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        $stats['total_applications'] = $stmt->fetch()['total'] ?? 0;
        
        // Pending applications
        $query = "SELECT COUNT(*) as total FROM applications WHERE user_id = ? AND status = 'pending'";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        $stats['pending_applications'] = $stmt->fetch()['total'] ?? 0;
        
        // Accepted applications
        $query = "SELECT COUNT(*) as total FROM applications WHERE user_id = ? AND status = 'accepted'";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        $stats['accepted_applications'] = $stmt->fetch()['total'] ?? 0;
        
        // Total bookmarks
        $query = "SELECT COUNT(*) as total FROM bookmarks WHERE user_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        $stats['total_bookmarks'] = $stmt->fetch()['total'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Error getting profile statistics: " . $e->getMessage());
    }
    
    return $stats;
}

/**
 * Get recommended jobs for user
 */
function get_recommended_jobs($user_id, $limit = 10) {
    global $pdo;
    
    try {
        $query = "
            SELECT 
                j.id,
                j.title,
                j.slug,
                j.company,
                j.location,
                j.city,
                j.province,
                j.job_type,
                j.work_location,
                j.salary_min,
                j.salary_max,
                j.experience_required,
                j.is_featured,
                j.is_urgent,
                j.views_count,
                j.applications_count,
                calculate_job_popularity(j.id) as popularity_score,
                time_ago(j.created_at) as posted_time,
                j.created_at,
                j.application_deadline
            FROM jobs j
            WHERE j.is_active = TRUE 
            AND j.status = 'published'
            AND j.application_deadline > CURDATE()
            AND j.id NOT IN (
                SELECT job_id FROM applications WHERE user_id = ?
            )
            ORDER BY                j.is_featured DESC,
                calculate_job_popularity(j.id) DESC,
                j.created_at DESC
            LIMIT " . (int)$limit . "
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        
        $results = $stmt->fetchAll();
        
        // Format salary for each result
        foreach ($results as &$result) {
            $result['formatted_salary'] = format_salary_range($result['salary_min'], $result['salary_max']);
        }
        
        return $results;
        
    } catch (Exception $e) {
        error_log("Error getting recommended jobs: " . $e->getMessage());
        return [];
    }
}

/**
 * Upload and save profile image
 */
function upload_profile_image($user_id, $uploaded_file) {
    // Define upload directory
    $upload_dir = __DIR__ . '/../../uploads/profiles/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($uploaded_file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF.'];
    }
    
    if ($uploaded_file['size'] > $max_size) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 2MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($uploaded_file['tmp_name'], $filepath)) {
        // Update database
        global $pdo;
        $query = "UPDATE users SET profile_image = ? WHERE id = ?";
        $stmt = $pdo->prepare($query);
        
        if ($stmt->execute([$filename, $user_id])) {
            return ['success' => true, 'filename' => $filename];
        } else {
            // Remove uploaded file if database update failed
            unlink($filepath);
            return ['success' => false, 'message' => 'Gagal menyimpan ke database.'];
        }
    } else {
        return ['success' => false, 'message' => 'Gagal mengupload file.'];
    }
}

/**
 * Delete profile image
 */
function delete_profile_image($user_id) {
    global $pdo;
    
    // Get current profile image
    $query = "SELECT profile_image FROM users WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    if ($result && $result['profile_image']) {
        $filepath = __DIR__ . '/../../uploads/profiles/' . $result['profile_image'];
        
        // Delete file if exists
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Update database
        $query = "UPDATE users SET profile_image = NULL WHERE id = ?";
        $stmt = $pdo->prepare($query);
        return $stmt->execute([$user_id]);
    }
    
    return true;
}

/**
 * Get job alerts for user
 */
function get_user_job_alerts($user_id, $active_only = false) {
    global $pdo;
    
    $sql = "SELECT ja.*, c.name as category_name FROM job_alerts ja 
            LEFT JOIN categories c ON ja.category_id = c.id 
            WHERE ja.user_id = ?";
    $params = [$user_id];
    
    if ($active_only) {
        $sql .= " AND ja.is_active = 1";
    }
    
    $sql .= " ORDER BY ja.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create job alert
 */
function create_job_alert($user_id, $title, $keywords = null, $location = null, $category_id = null, $job_type = null, $salary_min = null, $frequency = 'weekly') {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO job_alerts (user_id, title, keywords, location, category_id, job_type, salary_min, frequency) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$user_id, $title, $keywords, $location, $category_id, $job_type, $salary_min, $frequency]);
}

/**
 * Find matching jobs for alert
 */
function find_matching_jobs($alert, $since_date = null) {
    global $pdo;
    
    $sql = "SELECT * FROM jobs WHERE is_active = 1 AND status = 'published'";
    $params = [];
    
    // Add keywords filter
    if (!empty($alert['keywords'])) {
        $keywords = explode(',', $alert['keywords']);
        $keyword_conditions = [];
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (!empty($keyword)) {
                $keyword_conditions[] = "(title LIKE ? OR description LIKE ? OR requirements LIKE ?)";
                $params[] = "%$keyword%";
                $params[] = "%$keyword%";
                $params[] = "%$keyword%";
            }
        }
        if (!empty($keyword_conditions)) {
            $sql .= " AND (" . implode(' OR ', $keyword_conditions) . ")";
        }
    }
    
    // Add location filter
    if (!empty($alert['location'])) {
        $sql .= " AND (location LIKE ? OR city LIKE ? OR province LIKE ?)";
        $params[] = "%{$alert['location']}%";
        $params[] = "%{$alert['location']}%";
        $params[] = "%{$alert['location']}%";
    }
    
    // Add category filter
    if (!empty($alert['category_id'])) {
        $sql .= " AND category_id = ?";
        $params[] = $alert['category_id'];
    }
    
    // Add job type filter
    if (!empty($alert['job_type'])) {
        $sql .= " AND job_type = ?";
        $params[] = $alert['job_type'];
    }
    
    // Add salary filter
    if (!empty($alert['salary_min'])) {
        $sql .= " AND (salary_min >= ? OR salary_max >= ?)";
        $params[] = $alert['salary_min'];
        $params[] = $alert['salary_min'];
    }
    
    // Add date filter for new jobs
    if ($since_date) {
        $sql .= " AND created_at >= ?";
        $params[] = $since_date;
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Send job alert notification
 */
function send_job_alert_notification($user_id, $alert, $matching_jobs) {
    global $pdo;
    
    if (empty($matching_jobs)) {
        return false;
    }
    
    $job_count = count($matching_jobs);
    $title = "Lowongan Baru Sesuai Alert: {$alert['title']}";
    $message = "Ditemukan {$job_count} lowongan baru yang sesuai dengan job alert Anda.";
    
    // Create notification
    create_notification($user_id, $title, $message, 'job_alert');
    
    // Update last_sent for the alert
    $stmt = $pdo->prepare("UPDATE job_alerts SET last_sent = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$alert['id']]);
    
    return true;
}

/**
 * Process job alerts (for cron job)
 */
function process_job_alerts() {
    global $pdo;
    
    $processed = 0;
    
    // Get all active alerts that need to be processed
    $stmt = $pdo->prepare("
        SELECT ja.*, u.email, u.name as user_name 
        FROM job_alerts ja 
        JOIN users u ON ja.user_id = u.id 
        WHERE ja.is_active = 1 
        AND (
            (ja.frequency = 'daily' AND (ja.last_sent IS NULL OR ja.last_sent < DATE_SUB(NOW(), INTERVAL 1 DAY))) OR
            (ja.frequency = 'weekly' AND (ja.last_sent IS NULL OR ja.last_sent < DATE_SUB(NOW(), INTERVAL 1 WEEK))) OR
            (ja.frequency = 'monthly' AND (ja.last_sent IS NULL OR ja.last_sent < DATE_SUB(NOW(), INTERVAL 1 MONTH)))
        )
    ");
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($alerts as $alert) {
        // Calculate since date based on frequency
        $since_date = null;
        switch ($alert['frequency']) {
            case 'daily':
                $since_date = date('Y-m-d H:i:s', strtotime('-1 day'));
                break;
            case 'weekly':
                $since_date = date('Y-m-d H:i:s', strtotime('-1 week'));
                break;
            case 'monthly':
                $since_date = date('Y-m-d H:i:s', strtotime('-1 month'));
                break;
        }
        
        // Find matching jobs
        $matching_jobs = find_matching_jobs($alert, $since_date);
          if (!empty($matching_jobs)) {
            // Send notification
            send_job_alert_notification($alert['user_id'], $alert, $matching_jobs);
            
            // Update last_sent timestamp
            $update_stmt = $pdo->prepare("UPDATE job_alerts SET last_sent = NOW() WHERE id = ?");
            $update_stmt->execute([$alert['id']]);
            
            $processed++;
        }
    }
    
    return $processed;
}

/**
 * Check job alerts for a newly posted job and send notifications
 */
function check_job_alerts_for_new_job($job_data) {
    global $pdo;
    
    // Get all active job alerts
    $stmt = $pdo->prepare("
        SELECT ja.*, u.name as user_name, u.email 
        FROM job_alerts ja 
        JOIN users u ON ja.user_id = u.id 
        WHERE ja.is_active = 1
    ");
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($alerts as $alert) {
        $is_match = false;
        
        // Check keywords match
        if (!empty($alert['keywords'])) {
            $keywords = array_map('trim', explode(',', $alert['keywords']));
            foreach ($keywords as $keyword) {
                if (!empty($keyword)) {
                    if (stripos($job_data['title'], $keyword) !== false || 
                        stripos($job_data['description'], $keyword) !== false || 
                        (isset($job_data['requirements']) && stripos($job_data['requirements'], $keyword) !== false)) {
                        $is_match = true;
                        break;
                    }
                }
            }
        } else {
            // If no keywords specified, consider it a potential match
            $is_match = true;
        }
        
        // If keywords match, check other criteria
        if ($is_match) {
            // Check location
            if (!empty($alert['location'])) {
                if (stripos($job_data['location'], $alert['location']) === false) {
                    $is_match = false;
                }
            }
            
            // Check category
            if ($is_match && !empty($alert['category_id'])) {
                if ($job_data['category_id'] != $alert['category_id']) {
                    $is_match = false;
                }
            }
            
            // Check job type
            if ($is_match && !empty($alert['job_type'])) {
                if ($job_data['job_type'] != $alert['job_type']) {
                    $is_match = false;
                }
            }
            
            // Check salary
            if ($is_match && !empty($alert['salary_min'])) {
                if (!empty($job_data['salary_min']) && $job_data['salary_min'] < $alert['salary_min']) {
                    $is_match = false;
                } elseif (!empty($job_data['salary_max']) && $job_data['salary_max'] < $alert['salary_min']) {
                    $is_match = false;
                }
            }
        }
        
        // If job matches the alert, send notification
        if ($is_match) {
            $title = "Lowongan Baru Tersedia! 💼";
            $message = "Ada lowongan baru yang sesuai dengan job alert Anda:\n\n";
            $message .= "**{$job_data['title']}** di {$job_data['company']}\n";
            $message .= "Lokasi: {$job_data['location']}\n";
            if (!empty($job_data['salary'])) {
                $message .= "Gaji: {$job_data['salary']}\n";
            }
            $message .= "\nKlik untuk melihat detail dan melamar!";
            
            create_notification(
                $alert['user_id'],
                $title,
                $message,
                'job_alert',
                $job_data['id'],
                'job',
                'job-detail.php?id=' . $job_data['id']
            );
            
            // Update last_sent for the alert
            $update_stmt = $pdo->prepare("UPDATE job_alerts SET last_sent = NOW() WHERE id = ?");
            $update_stmt->execute([$alert['id']]);        }
    }
}
