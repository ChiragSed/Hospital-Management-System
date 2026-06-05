<?php
/**
 * Logout Page
 * Hospital Management System
 */

require_once __DIR__ . '/includes/auth.php';

logout_user();

// Flash success message
session_start();
$_SESSION['success_message'] = "You have been logged out successfully.";

header("Location: index.php");
exit();
