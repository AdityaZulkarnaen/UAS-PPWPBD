<?php

function validate_user_input($data) {
    $errors = [];
    if (empty($data['username'])) {
        $errors[] = 'Username is required.';
    }
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (empty($data['password']) || strlen($data['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    return $errors;
}

// Legacy function aliases for backward compatibility
function is_user_logged_in() {
    require_once __DIR__ . '/session_manager.php';
    return is_logged_in();
}

function get_current_user_id() {
    require_once __DIR__ . '/session_manager.php';
    return get_user_id();
}

function get_current_user_name() {
    require_once __DIR__ . '/session_manager.php';
    return get_user_name();
}
?>