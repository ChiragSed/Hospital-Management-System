<?php
/**
 * Authentication and Session Management
 * Hospital Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    // Session cookies settings for security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Enable secure cookies if HTTPS is used
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

/**
 * Generate CSRF Token and store in session
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if a user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Redirect user if they are not logged in or do not have the required role
 * $allowed_roles can be a string (e.g. 'admin') or an array of strings (e.g. ['doctor', 'admin'])
 */
function check_role($allowed_roles) {
    if (!is_logged_in()) {
        $_SESSION['error_message'] = "Please log in to access this page.";
        header("Location: ../login.php");
        exit();
    }
    
    $roles = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
    
    if (!in_array($_SESSION['user_type'], $roles)) {
        $_SESSION['error_message'] = "Unauthorized access. You do not have permissions for this page.";
        
        // Redirect to their respective dashboards
        if ($_SESSION['user_type'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } elseif ($_SESSION['user_type'] === 'doctor') {
            header("Location: ../doctor/dashboard.php");
        } else {
            header("Location: ../patient/dashboard.php");
        }
        exit();
    }
}

/**
 * Logout the user and clear session
 */
function logout_user() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}
