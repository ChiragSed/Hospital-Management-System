<?php
/**
 * Global Helper Functions
 * Hospital Management System
 */

/**
 * Sanitize user input to prevent XSS (Cross Site Scripting)
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format dates in Nepal standard format DD/MM/YYYY
 */
function format_date($date, $format = 'd/m/Y') {
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format time slot (e.g. 09:00:00 -> 09:00 AM)
 */
function format_time($time) {
    if (empty($time)) return 'N/A';
    return date('g:i A', strtotime($time));
}

/**
 * Format doctor name to avoid duplicate "Dr. Dr. [Name]"
 */
function format_doctor_name($name) {
    if (empty($name)) return 'N/A';
    $name = trim($name);
    if (stripos($name, 'Dr.') === 0) {
        return $name;
    }
    return 'Dr. ' . $name;
}

/**
 * Format amount in Nepalese Rupee (NPR)
 * @param float $amount
 * @param bool $compact Use compact notation (e.g. Rs. 1,200)
 */
function format_currency($amount, $compact = false) {
    if ($compact) {
        return 'Rs. ' . number_format((float)$amount);
    }
    return 'Rs. ' . number_format((float)$amount, 2);
}


/**
 * Calculate BMI
 * Weight in kg, Height in cm
 */
function calculate_bmi($weight, $height) {
    if (empty($weight) || empty($height) || $height <= 0) {
        return 0;
    }
    $height_m = $height / 100;
    return round($weight / ($height_m * $height_m), 2);
}

/**
 * Get BMI status and badge color class
 */
function get_bmi_status($bmi) {
    if ($bmi <= 0) return ['status' => 'N/A', 'class' => 'bg-secondary'];
    if ($bmi < 18.5) return ['status' => 'Underweight', 'class' => 'bg-warning text-dark'];
    if ($bmi < 25.0) return ['status' => 'Normal Weight', 'class' => 'bg-success'];
    if ($bmi < 30.0) return ['status' => 'Overweight', 'class' => 'bg-warning text-dark'];
    return ['status' => 'Obese', 'class' => 'bg-danger'];
}

/**
 * Check if the current nav item is active
 */
function is_active_page($page_name) {
    $current_file = basename($_SERVER['PHP_SELF']);
    return ($current_file === $page_name) ? 'active' : '';
}

/**
 * Render Toast alert notifications from sessions
 */
function render_toasts() {
    $html = '';
    if (isset($_SESSION['success_message'])) {
        $html .= '<div class="toast align-items-center text-white bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">';
        $html .= '  <div class="d-flex">';
        $html .= '    <div class="toast-body"><i class="fa-solid fa-circle-check me-2"></i> ' . $_SESSION['success_message'] . '</div>';
        $html .= '    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>';
        $html .= '  </div>';
        $html .= '</div>';
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        $html .= '<div class="toast align-items-center text-white bg-danger border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">';
        $html .= '  <div class="d-flex">';
        $html .= '    <div class="toast-body"><i class="fa-solid fa-circle-xmark me-2"></i> ' . $_SESSION['error_message'] . '</div>';
        $html .= '    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>';
        $html .= '  </div>';
        $html .= '</div>';
        unset($_SESSION['error_message']);
    }
    
    return $html;
}

/**
 * Secure file upload utility
 * Handles profile pictures and PDF lab reports
 */
function upload_file($file, $target_dir, $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'], $max_size = 5242880) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['status' => false, 'error' => 'File upload error or no file uploaded.'];
    }

    $filename = basename($file['name']);
    $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // Validate extension
    if (!in_array($file_ext, $allowed_extensions)) {
        return ['status' => false, 'error' => 'Invalid file extension. Allowed types: ' . implode(', ', $allowed_extensions)];
    }

    // Validate size (default 5MB)
    if ($file['size'] > $max_size) {
        return ['status' => false, 'error' => 'File exceeds maximum allowed size (5MB).'];
    }

    // Sanitize filename to prevent directory traversal / file system exploits
    $safe_name = preg_replace("/[^a-zA-Z0-9_-]/", "", pathinfo($filename, PATHINFO_FILENAME));
    $new_filename = $safe_name . '_' . time() . '.' . $file_ext;
    
    // Ensure trailing slash on target dir
    $target_dir = rtrim($target_dir, '/') . '/';
    $target_file = $target_dir . $new_filename;

    // Create target dir if not exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['status' => true, 'filename' => $new_filename, 'filepath' => $target_file];
    } else {
        return ['status' => false, 'error' => 'Failed to move uploaded file. Check directory permissions.'];
    }
}
