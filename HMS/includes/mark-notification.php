<?php
/**
 * Mark Notifications as Read
 * Hospital Management System
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!is_logged_in()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "../{$user_type}/dashboard.php";

if (isset($_GET['all']) && $_GET['all'] == 1) {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_type = ? AND user_id = ?");
        $stmt->execute([$user_type, $user_id]);
    } catch (Exception $e) {
        // Fail silently
    }
} elseif (isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_type = ? AND user_id = ?");
        $stmt->execute([$notif_id, $user_type, $user_id]);
    } catch (Exception $e) {
        // Fail silently
    }
}

header("Location: " . $redirect_url);
exit();
