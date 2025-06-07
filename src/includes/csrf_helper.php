<?php
/**
 * CSRF Helper Functions
 * File: src/includes/csrf_helper.php
 */

/**
 * Generate CSRF token
 * @return string
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get current CSRF token
 * @return string|null
 */
function get_csrf_token() {
    return $_SESSION['csrf_token'] ?? null;
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerate CSRF token (use after successful form submission)
 * @return string
 */
function regenerate_csrf_token() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token as hidden input field
 * @return string
 */
function csrf_token_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from POST data
 * @return bool
 */
function validate_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true; // Skip validation for non-POST requests
    }
    
    $submitted_token = $_POST['csrf_token'] ?? '';
    return verify_csrf_token($submitted_token);
}
?>