<?php
session_start();

require_once __DIR__ . '/../includes/session_manager.php';

// Middleware to check if user is logged in
function auth_middleware() {
    require_login();
}

// Middleware to check if user is admin
function admin_middleware() {
    require_admin();
}

// Middleware to redirect logged-in users away from auth pages
function guest_middleware() {
    if (is_logged_in()) {
        header('Location: /HireWay/index.php');
        exit;
    }
}
?>