<?php
/**
 * Doctor Dashboard Portal
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('doctor');

require_once __DIR__ . '/../config/database.php';
$doctor_id = $_SESSION['user_id'];

// Handle global search filter from dashboard navbar
if (isset($_GET['global_search'])) {
    $search = urlencode(sanitize($_GET['global_search']));
    header("Location: patients.php?search=" . $search);
    exit();
}

// Fetch doctor details
try {
    $stmt = $pdo->prepare("
        SELECT d.*, dept.name AS dept_name 
        FROM doctors d 
        LEFT JOIN departments dept ON d.department_id = dept.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();
    
    // Sync profile picture
    if ($doctor) {
        $_SESSION['profile_pic'] = $doctor['profile_pic'];
    }
} catch (Exception $e) {
    die("Database connection failed.");
}

// Fetch statistics
$total_patients = 0;
$todays_appointments = 0;
$pending_requests = 0;
$completed_consultations = 0;

try {
    // Total Patients (Distinct patients seen)
    $stmtPat = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?");
    $stmtPat->execute([$doctor_id]);
    $total_patients = $stmtPat->fetchColumn();

    // Today's appointments
    $stmtToday = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE() AND status != 'Rejected'");
    $stmtToday->execute([$doctor_id]);
    $todays_appointments = $stmtToday->fetchColumn();

    // Pending Requests
    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'Pending'");
    $stmtPending->execute([$doctor_id]);
    $pending_requests = $stmtPending->fetchColumn();

    // Completed consultations
    $stmtComp = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'Completed'");
    $stmtComp->execute([$doctor_id]);
    $completed_consultations = $stmtComp->fetchColumn();
} catch (Exception $e) {}

// Fetch today's schedule
$todays_schedule = [];
try {
    $stmtSched = $pdo->prepare("
        SELECT a.*, p.name AS patient_name, p.age, p.gender 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.id 
        WHERE a.doctor_id = ? AND a.appointment_date = CURDATE() AND a.status != 'Rejected'
        ORDER BY a.time_slot ASC
    ");
    $stmtSched->execute([$doctor_id]);
    $todays_schedule = $stmtSched->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-header-gradient">
            <h2 class="fw-bold text-white mb-2">Hello, <?php echo sanitize($doctor['name']); ?></h2>
            <p class="text-white opacity-90">Welcome back to your clinical management board. Here is a summary of your schedules and patient records.</p>
        </div>
    </div>
</div>

<!-- Statistics Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small uppercase fw-bold">Total Patients</span>
                <div class="stat-icon icon-primary"><i class="fa-solid fa-users"></i></div>
            </div>
            <h2 class="fw-bold mb-1"><?php echo $total_patients; ?></h2>
            <a href="patients.php" class="small text-decoration-none">View patients registry</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small uppercase fw-bold">Today's Visits</span>
                <div class="stat-icon icon-secondary"><i class="fa-solid fa-calendar-day"></i></div>
            </div>
            <h2 class="fw-bold mb-1"><?php echo $todays_appointments; ?></h2>
            <span class="small text-muted">Appointments scheduled today</span>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small uppercase fw-bold">Pending Requests</span>
                <div class="stat-icon icon-warning"><i class="fa-solid fa-hourglass-half"></i></div>
            </div>
            <h2 class="fw-bold mb-1 text-warning"><?php echo $pending_requests; ?></h2>
            <a href="appointments.php" class="small text-decoration-none text-warning">Approve / Reject visits</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small uppercase fw-bold">Consultations Done</span>
                <div class="stat-icon icon-info"><i class="fa-solid fa-clipboard-check"></i></div>
            </div>
            <h2 class="fw-bold mb-1"><?php echo $completed_consultations; ?></h2>
            <span class="small text-muted">Diagnoses & prescriptions saved</span>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Doctor Profile Summary -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 text-center h-100" style="border-radius: 16px;">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <?php if (!empty($doctor['profile_pic'])): ?>
                    <img src="../uploads/profile_pics/<?php echo $doctor['profile_pic']; ?>" class="rounded-circle border border-4 border-light shadow" alt="Avatar" width="130" height="130" style="object-fit: cover;">
                <?php else: ?>
                    <div class="bg-primary-subtle text-primary rounded-circle border border-4 border-light shadow d-flex align-items-center justify-content-center" style="width: 130px; height: 130px;">
                        <i class="fa-solid fa-user-doctor fa-4x"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h5 class="fw-bold text-dark mb-1"><?php echo sanitize($doctor['name']); ?></h5>
            <span class="badge bg-secondary-subtle text-secondary px-3 py-1 mb-3"><?php echo sanitize($doctor['dept_name'] ?? 'General Physician'); ?></span>
            
            <div class="text-start border-top pt-3 small text-muted">
                <div class="mb-2"><strong>Qualification:</strong> <?php echo sanitize($doctor['qualification']); ?></div>
                <div class="mb-2"><strong>Specialization:</strong> <?php echo sanitize($doctor['specialization']); ?></div>
                <div class="mb-2"><strong>Experience:</strong> <?php echo intval($doctor['experience']); ?> Years</div>
                <div><strong>Consultation Fee:</strong> Rs. <?php echo number_format(floatval($doctor['consultation_fee'])); ?></div>
            </div>
            <div class="mt-4">
                <a href="profile.php" class="btn btn-outline-primary btn-sm w-100 fw-semibold"><i class="fa-solid fa-user-pen me-2"></i>Edit Professional Profile</a>
            </div>
        </div>
    </div>

    <!-- Today's Schedule -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-regular fa-clock text-primary me-2"></i>Today's Appointment Schedule</h5>
            
            <?php if (empty($todays_schedule)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-calendar-check fa-3x mb-3 opacity-30"></i>
                    <p class="mb-0">You have no appointment bookings scheduled for today.</p>
                    <a href="appointments.php" class="btn btn-outline-primary btn-sm mt-3"><i class="fa-solid fa-calendar-days me-1"></i> Manage Bookings</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Slot Time</th>
                                <th>Patient Name</th>
                                <th>Age / Gender</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todays_schedule as $appt): ?>
                                <?php
                                $statusClass = 'bg-warning text-dark';
                                if ($appt['status'] === 'Approved') $statusClass = 'bg-primary';
                                elseif ($appt['status'] === 'Completed') $statusClass = 'bg-success';
                                ?>
                                <tr>
                                    <td><strong><?php echo format_time($appt['time_slot']); ?></strong></td>
                                    <td><strong><?php echo sanitize($appt['patient_name']); ?></strong></td>
                                    <td><?php echo intval($appt['age']); ?> Yrs / <?php echo sanitize($appt['gender']); ?></td>
                                    <td><span class="badge <?php echo $statusClass; ?> px-2 py-1"><?php echo $appt['status']; ?></span></td>
                                    <td class="text-end">
                                        <?php if ($appt['status'] === 'Approved'): ?>
                                            <a href="consultation.php?appt_id=<?php echo $appt['id']; ?>" class="btn btn-success btn-sm fw-semibold">
                                                <i class="fa-solid fa-stethoscope me-1"></i> Consult Patient
                                            </a>
                                        <?php elseif ($appt['status'] === 'Completed'): ?>
                                            <span class="text-muted small"><i class="fa-solid fa-circle-check text-success me-1"></i> Checked Out</span>
                                        <?php else: ?>
                                            <a href="appointments.php" class="btn btn-outline-secondary btn-sm">Manage</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
