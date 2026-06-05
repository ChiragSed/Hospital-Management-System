<?php
/**
 * Role-based Sidebar Layout
 * Hospital Management System
 */

// Self-healing path resolution
$base_path = file_exists('config/database.php') ? '' : '../';

$role = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
?>
<nav id="sidebar">
    <div class="sidebar-header d-flex align-items-center">
        <a href="<?php echo $base_path; ?>index.php" class="text-white text-decoration-none d-flex align-items-center">
            <i class="fa-solid fa-hospital-user text-secondary fa-xl me-2"></i>
            <span class="fs-5 fw-bold text-uppercase tracking-wider">LifeLine HMS</span>
        </a>
    </div>

    <ul class="list-unstyled components">
        <!-- Admin Navigation -->
        <?php if ($role === 'admin'): ?>
            <li class="<?php echo is_active_page('dashboard.php'); ?>">
                <a href="<?php echo $base_path; ?>admin/dashboard.php">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo is_active_page('doctors.php'); ?>">
                <a href="<?php echo $base_path; ?>admin/doctors.php">
                    <i class="fa-solid fa-user-doctor"></i> Manage Doctors
                </a>
            </li>
            <li class="<?php echo is_active_page('patients.php'); ?>">
                <a href="<?php echo $base_path; ?>admin/patients.php">
                    <i class="fa-solid fa-bed-pulse"></i> Manage Patients
                </a>
            </li>
            <li class="<?php echo is_active_page('departments.php'); ?>">
                <a href="<?php echo $base_path; ?>admin/departments.php">
                    <i class="fa-solid fa-briefcase-medical"></i> Departments
                </a>
            </li>
            <li class="<?php echo is_active_page('laboratories.php'); ?>">
                <a href="<?php echo $base_path; ?>admin/laboratories.php">
                    <i class="fa-solid fa-flask-vial"></i> Laboratories
                </a>
            </li>
            <li class="<?php echo is_active_page('appointments.php'); ?>">
                <a href="<?php echo $base_path; ?>admin/appointments.php">
                    <i class="fa-solid fa-calendar-check"></i> Appointments
                </a>
            </li>
            <li class="<?php echo is_active_page('feedback.php'); ?>">
                <a href="<?php echo $base_path; ?>admin/feedback.php">
                    <i class="fa-solid fa-comment-medical"></i> Feedback
                </a>
            </li>
            <li class="<?php echo is_active_page('articles.php'); ?>">
                <a href="<?php echo $base_path; ?>admin/articles.php">
                    <i class="fa-solid fa-newspaper"></i> Health Articles
                </a>
            </li>
            <li class="<?php echo is_active_page('reports.php'); ?>">
                <a href="<?php echo $base_path; ?>admin/reports.php">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Manage Reports
                </a>
            </li>

        <!-- Doctor Navigation -->
        <?php elseif ($role === 'doctor'): ?>
            <li class="<?php echo is_active_page('dashboard.php'); ?>">
                <a href="<?php echo $base_path; ?>doctor/dashboard.php">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo is_active_page('appointments.php'); ?>">
                <a href="<?php echo $base_path; ?>doctor/appointments.php">
                    <i class="fa-solid fa-calendar-check"></i> Appointments
                </a>
            </li>
            <li class="<?php echo is_active_page('patients.php'); ?>">
                <a href="<?php echo $base_path; ?>doctor/patients.php">
                    <i class="fa-solid fa-bed-pulse"></i> Patient Records
                </a>
            </li>
            <li class="<?php echo is_active_page('availability.php'); ?>">
                <a href="<?php echo $base_path; ?>doctor/availability.php">
                    <i class="fa-solid fa-clock"></i> Set Availability
                </a>
            </li>
            <li class="<?php echo is_active_page('profile.php'); ?>">
                <a href="<?php echo $base_path; ?>doctor/profile.php">
                    <i class="fa-solid fa-user-doctor"></i> My Profile
                </a>
            </li>

        <!-- Patient Navigation -->
        <?php elseif ($role === 'patient'): ?>
            <li class="<?php echo is_active_page('dashboard.php'); ?>">
                <a href="<?php echo $base_path; ?>patient/dashboard.php">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="<?php echo is_active_page('book-appointment.php'); ?>">
                <a href="<?php echo $base_path; ?>patient/book-appointment.php">
                    <i class="fa-solid fa-calendar-plus"></i> Book Appointment
                </a>
            </li>
            <li class="<?php echo is_active_page('book-lab.php'); ?>">
                <a href="<?php echo $base_path; ?>patient/book-lab.php">
                    <i class="fa-solid fa-flask"></i> Book Lab Test
                </a>
            </li>
            <li class="<?php echo is_active_page('appointments.php'); ?>">
                <a href="<?php echo $base_path; ?>patient/appointments.php">
                    <i class="fa-solid fa-clock-history"></i> My Appointments
                </a>
            </li>
            <li class="<?php echo is_active_page('lab-bookings.php'); ?>">
                <a href="<?php echo $base_path; ?>patient/lab-bookings.php">
                    <i class="fa-solid fa-clipboard-list"></i> Lab Bookings
                </a>
            </li>
            <li class="<?php echo is_active_page('medical-records.php'); ?>">
                <a href="<?php echo $base_path; ?>patient/medical-records.php">
                    <i class="fa-solid fa-file-medical"></i> Medical Records
                </a>
            </li>
            <li class="<?php echo is_active_page('emergency.php'); ?>">
                <a href="<?php echo $base_path; ?>patient/emergency.php">
                    <i class="fa-solid fa-truck-medical"></i> Emergency Services
                </a>
            </li>
            <li class="<?php echo is_active_page('articles.php'); ?>">
                <a href="<?php echo $base_path; ?>patient/articles.php">
                    <i class="fa-solid fa-book-medical"></i> Health Articles
                </a>
            </li>
            <li class="<?php echo is_active_page('profile.php'); ?>">
                <a href="<?php echo $base_path; ?>patient/profile.php">
                    <i class="fa-solid fa-user-gear"></i> Account Profile
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
