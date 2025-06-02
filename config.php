<?php
// Konfigurasi database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'job_listing';

// Koneksi ke database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
?>