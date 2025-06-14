<?php
/**
 * CSRF Protection Helper Functions
 */

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token
 */
function get_csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF (legacy function name)
 */
function validate_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    return validate_csrf_token($token);
}

/**
 * Regenerate CSRF token
 */
function regenerate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF token field for forms
 */
function csrf_token_field() {
    $token = get_csrf_token();
    if (empty($token)) {
        $token = generate_csrf_token();
    }
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Generate CSRF meta tag for AJAX requests
 */
function csrf_meta_tag() {
    $token = get_csrf_token();
    if (empty($token)) {
        $token = generate_csrf_token();
    }
    return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
}
?>