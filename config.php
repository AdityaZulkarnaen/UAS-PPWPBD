<?php
// Konfigurasi database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hireway'; // Ganti dari 'job_listing' ke 'hireway'

// Koneksi ke database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fungsi untuk membersihkan input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk format tanggal
function format_date($date) {
    return date('d M Y', strtotime($date));
}

// Fungsi untuk hashing password
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Fungsi untuk memverifikasi password
function verify_password($password, $hashed_password) {
    return password_verify($password, $hashed_password);
}

// Fungsi untuk menggunakan custom functions dari database
function calculate_job_match_score($user_id, $job_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT calculate_job_match_score(?, ?) as match_score");
    $stmt->execute([$user_id, $job_id]);
    $result = $stmt->fetch();
    return $result['match_score'] ?? 0;
}

function calculate_job_popularity($job_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT calculate_job_popularity(?) as popularity");
    $stmt->execute([$job_id]);
    $result = $stmt->fetch();
    return $result['popularity'] ?? 0;
}

function generate_slug($text) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT generate_slug(?) as slug");
    $stmt->execute([$text]);
    $result = $stmt->fetch();
    return $result['slug'] ?? '';
}

function format_salary_range($salary_min, $salary_max) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT format_salary(?, ?) as formatted_salary");
    $stmt->execute([$salary_min, $salary_max]);
    $result = $stmt->fetch();
    return $result['formatted_salary'] ?? 'Negotiable';
}

function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Baru saja';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' menit yang lalu';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' hari yang lalu';
    } elseif ($time < 31536000) {
        $months = floor($time / 2592000);
        return $months . ' bulan yang lalu';
    } else {
        $years = floor($time / 31536000);
        return $years . ' tahun yang lalu';
    }
}
?>