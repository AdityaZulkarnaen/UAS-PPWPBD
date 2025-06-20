<?php
// Function to start a session
function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Function to check if a user is logged in
function is_logged_in() {
    start_session();
    return isset($_SESSION['user_id']);
}

// Function to get the logged-in user's ID
function get_user_id() {
    start_session();
    return $_SESSION['user_id'] ?? null;
}

// Function to get the logged-in user's name
function get_user_name() {
    start_session();
    return $_SESSION['user_name'] ?? null;
}

// Function to check if user is admin
function is_admin() {
    start_session();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to check if user is employer
function is_employer() {
    start_session();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'employer';
}

// Function to check if user is jobseeker
function is_jobseeker() {
    start_session();
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'jobseeker';
}

// Function to get user role
function get_user_role() {
    start_session();
    return $_SESSION['role'] ?? 'jobseeker';
}

// Function to destroy the session
function destroy_session() {
    start_session();
    session_unset();
    session_destroy();
    
    // Remove session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
}

// Function to regenerate session ID
function regenerate_session_id() {
    start_session();
    session_regenerate_id(true);
}

// Function to verify the session
function verify_session() {
    start_secure_session();
    
    if (!isset($_SESSION['user_id'])) {
        header('Location: /HireWay/src/auth/login.php');
        exit;
    }
    
    // Optional: Add session timeout check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        destroy_session();
        header('Location: /HireWay/src/auth/login.php?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

// Function to require login (redirect if not logged in)
function require_login() {
    if (!is_logged_in()) {
        header('Location: /HireWay/src/auth/login.php');
        exit;
    }
}

// Function to require admin access
function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: /HireWay/index.php');
        exit;
    }
}

// Function to start user session after login
function start_user_session($user_id, $user_name, $role = 'jobseeker') {
    start_secure_session();
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $user_name;
    $_SESSION['username'] = $user_name; // Untuk backward compatibility
    $_SESSION['role'] = $role;
    $_SESSION['is_admin'] = ($role === 'admin') ? 1 : 0; // Untuk backward compatibility
    $_SESSION['last_activity'] = time();
    regenerate_session_id();
}

function start_secure_session() {
    // Secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}
?>