<?php
/**
 * Shared Header Template
 * Hospital Management System
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

// Self-healing path resolution
$base_path = file_exists('config/database.php') ? '' : '../';

// Require database config
require_once $base_path . 'config/database.php';

// Verify login
if (!is_logged_in()) {
    header("Location: " . $base_path . "login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$user_name = $_SESSION['user_name'];
$profile_pic = $_SESSION['profile_pic'] ? $base_path . 'uploads/profile_pics/' . $_SESSION['profile_pic'] : $base_path . 'assets/images/default-avatar.png';

// Fetch unread notifications for the user
$unread_notifications = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_type = ? AND user_id = ? AND is_read = 0 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_type, $user_id]);
    $unread_notifications = $stmt->fetchAll();
    
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) FROM notifications 
        WHERE user_type = ? AND user_id = ? AND is_read = 0
    ");
    $stmtCount->execute([$user_type, $user_id]);
    $unread_count = $stmtCount->fetchColumn();
} catch (Exception $e) {
    $unread_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - HMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js (Loaded globally for dashboards) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
</head>
<body>

<!-- Toast Notification Container -->
<div class="toast-container toast-container-custom">
    <?php echo render_toasts(); ?>
</div>

<div id="wrapper">
    <!-- Include Sidebar -->
    <?php include $base_path . 'includes/sidebar.php'; ?>

    <!-- Page Content -->
    <div id="content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg top-navbar navbar-light bg-white sticky-top">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="btn navbar-btn">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                
                <!-- Global Search System - Role-Aware -->
                <?php
                    // Determine the correct search target and placeholder per role
                    if ($user_type === 'patient') {
                        $search_action = $base_path . 'patient/appointments.php';
                        $search_placeholder = 'Search appointments, doctors...';
                    } elseif ($user_type === 'doctor') {
                        $search_action = $base_path . 'doctor/patients.php';
                        $search_placeholder = 'Search patient name...';
                    } else {
                        $search_action = $base_path . 'admin/patients.php';
                        $search_placeholder = 'Search patients, doctors...';
                    }
                ?>
                <form class="d-none d-md-flex ms-3" action="<?php echo $search_action; ?>" method="GET">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input class="form-control bg-light border-0" type="search" name="search" placeholder="<?php echo $search_placeholder; ?>" aria-label="Search" style="width: 250px;">
                    </div>
                </form>

                <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
                    <ul class="nav navbar-nav ms-auto align-items-center flex-row">
                        <!-- Notification Bell Dropdown -->
                        <li class="nav-item dropdown me-3 position-relative">
                            <a class="nav-link dropdown-toggle no-caret position-relative" href="#" id="navbarNotif" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-regular fa-bell fa-lg text-dark"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="position-absolute translate-middle badge rounded-pill bg-danger" style="top: 8px; right: -2px; font-size: 0.55rem; padding: 3px 5px;">
                                        <?php echo $unread_count; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-0" aria-labelledby="navbarNotif" style="width: 290px; border-radius: 12px; overflow: hidden;">
                                <div class="p-3 bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-semibold">Notifications</h6>
                                    <span class="badge bg-white text-primary"><?php echo $unread_count; ?> New</span>
                                </div>
                                <div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                                    <?php if (empty($unread_notifications)): ?>
                                        <div class="text-center p-4 text-muted">
                                            <i class="fa-regular fa-bell-slash fa-2x mb-2 text-muted opacity-50"></i>
                                            <p class="mb-0 small">No new notifications</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($unread_notifications as $notif): ?>
                                            <a href="<?php echo $base_path; ?>includes/mark-notification.php?id=<?php echo $notif['id']; ?>" class="list-group-item list-group-item-action p-3">
                                                <div class="d-flex w-100 justify-content-between mb-1">
                                                    <span class="fw-bold small"><?php echo sanitize($notif['title']); ?></span>
                                                    <small class="text-muted" style="font-size: 0.7rem;"><?php echo format_date($notif['created_at'], 'H:i A'); ?></small>
                                                </div>
                                                <p class="mb-0 text-muted small text-truncate"><?php echo sanitize($notif['message']); ?></p>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($unread_count > 0): ?>
                                    <a href="<?php echo $base_path; ?>includes/mark-notification.php?all=1" class="dropdown-item text-center text-primary py-2 small fw-semibold border-top bg-light">
                                        Mark All as Read
                                    </a>
                                <?php endif; ?>
                            </ul>
                        </li>

                        <!-- User Profile Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center text-dark" href="#" id="navbarUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo $profile_pic; ?>" class="rounded-circle me-2 border border-2 border-light" alt="Avatar" width="36" height="36" style="object-fit: cover;">
                                <span class="d-none d-lg-inline fw-semibold"><?php echo sanitize($user_name); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2" aria-labelledby="navbarUser" style="border-radius: 12px;">
                                <li>
                                    <div class="px-3 py-2 border-bottom">
                                        <div class="fw-bold"><?php echo sanitize($user_name); ?></div>
                                        <div class="text-muted small" style="font-size: 0.8rem;"><?php echo ucfirst($user_type); ?> Portal</div>
                                    </div>
                                </li>
                                <li><a class="dropdown-item py-2" href="<?php echo $base_path; ?><?php echo $user_type; ?>/profile.php"><i class="fa-regular fa-user me-2 text-muted"></i> My Profile</a></li>
                                <?php if ($user_type === 'patient'): ?>
                                    <li><a class="dropdown-item py-2" href="<?php echo $base_path; ?>patient/appointments.php"><i class="fa-regular fa-calendar-check me-2 text-muted"></i> Appointments</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="<?php echo $base_path; ?>logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log Out</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="container-fluid p-4">
