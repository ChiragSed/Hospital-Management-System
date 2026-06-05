<?php
/**
 * Patient Dashboard Portal
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('patient');

require_once __DIR__ . '/../config/database.php';
$patient_id = $_SESSION['user_id'];

// Global Search filter redirect logic
if (isset($_GET['global_search'])) {
    $search = urlencode(sanitize($_GET['global_search']));
    header("Location: appointments.php?search=" . $search);
    exit();
}

// Fetch Patient profile and health tracker metrics
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
    
    // Sync session profile pic if updated
    if ($patient) {
        $_SESSION['profile_pic'] = $patient['profile_pic'];
    }
} catch (Exception $e) {
    die("Database query error. Make sure database is seeded.");
}

// Fetch statistics
$total_appointments = 0;
$pending_lab_tests = 0;
$active_prescriptions = 0;
$last_visit_date = 'N/A';

try {
    // Total Appointments
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ?");
    $stmtCount->execute([$patient_id]);
    $total_appointments = $stmtCount->fetchColumn();

    // Pending Lab tests
    $stmtCountLab = $pdo->prepare("SELECT COUNT(*) FROM lab_bookings WHERE patient_id = ? AND status IN ('Pending', 'Confirmed')");
    $stmtCountLab->execute([$patient_id]);
    $pending_lab_tests = $stmtCountLab->fetchColumn();

    // Active Prescriptions (e.g. from the last 30 days)
    $stmtCountPres = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ? AND prescription_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stmtCountPres->execute([$patient_id]);
    $active_prescriptions = $stmtCountPres->fetchColumn();

    // Last completed appointment date
    $stmtLastVisit = $pdo->prepare("SELECT appointment_date FROM appointments WHERE patient_id = ? AND status = 'Completed' ORDER BY appointment_date DESC LIMIT 1");
    $stmtLastVisit->execute([$patient_id]);
    $last_visit = $stmtLastVisit->fetchColumn();
    if ($last_visit) {
        $last_visit_date = format_date($last_visit);
    }
} catch (Exception $e) {
    // Fail silently
}

// Fetch recent appointments
$recent_appointments = [];
try {
    $stmtAppt = $pdo->prepare("
        SELECT a.*, d.name AS doctor_name, dept.name AS dept_name 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        JOIN departments dept ON a.department_id = dept.id 
        WHERE a.patient_id = ? 
        ORDER BY a.appointment_date DESC, a.time_slot DESC 
        LIMIT 3
    ");
    $stmtAppt->execute([$patient_id]);
    $recent_appointments = $stmtAppt->fetchAll();
} catch (Exception $e) {
    // Fail silently
}

// Start Layout Header
include __DIR__ . '/../includes/header.php';

// Calculate BMI details
$height = isset($patient['height']) ? floatval($patient['height']) : 0;
$weight = isset($patient['weight']) ? floatval($patient['weight']) : 0;
$bmi = isset($patient['bmi']) ? floatval($patient['bmi']) : 0;
$bmi_info = get_bmi_status($bmi);
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="dashboard-header-gradient">
            <h2 class="fw-bold text-white mb-2">Welcome Back, <?php echo sanitize($patient['name']); ?></h2>
            <p class="text-white opacity-90">Manage your clinical consultations, laboratory diagnostic panels, and health metrics. Have a peaceful day ahead.</p>
        </div>
    </div>
</div>

<!-- Statistics Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small uppercase fw-bold">Total Appointments</span>
                <div class="stat-icon icon-primary"><i class="fa-solid fa-calendar-check"></i></div>
            </div>
            <h2 class="fw-bold mb-1"><?php echo $total_appointments; ?></h2>
            <a href="appointments.php" class="small text-decoration-none">View consultation log</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small uppercase fw-bold">Pending Lab Panels</span>
                <div class="stat-icon icon-secondary"><i class="fa-solid fa-flask-vial"></i></div>
            </div>
            <h2 class="fw-bold mb-1"><?php echo $pending_lab_tests; ?></h2>
            <a href="lab-bookings.php" class="small text-decoration-none">View laboratory reports</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small uppercase fw-bold">Recent Prescriptions</span>
                <div class="stat-icon icon-info"><i class="fa-solid fa-prescription-bottle-medical"></i></div>
            </div>
            <h2 class="fw-bold mb-1"><?php echo $active_prescriptions; ?></h2>
            <a href="medical-records.php" class="small text-decoration-none">View medical history</a>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small uppercase fw-bold">Last Consultation</span>
                <div class="stat-icon icon-warning"><i class="fa-solid fa-clock-history"></i></div>
            </div>
            <h2 class="fw-bold mb-1 fs-3"><?php echo $last_visit_date; ?></h2>
            <span class="small text-muted">Clinic checkout date</span>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Health Tracker BMI Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-heart-pulse text-danger me-2"></i>Health Dashboard</h5>
            <div class="text-center py-3">
                <div class="display-3 fw-bold text-primary mb-1"><?php echo $bmi > 0 ? $bmi : 'N/A'; ?></div>
                <span class="badge <?php echo $bmi_info['class']; ?> px-3 py-2 fs-6 mb-3"><?php echo $bmi_info['status']; ?></span>
                <p class="text-muted small mb-4">BMI score is computed from your uploaded physical parameters.</p>
            </div>
            <div class="row border-top pt-3 g-2 text-center">
                <div class="col-6 border-end">
                    <span class="text-muted small d-block">Height</span>
                    <strong class="text-dark fs-5"><?php echo $height > 0 ? $height . ' cm' : '--'; ?></strong>
                </div>
                <div class="col-6">
                    <span class="text-muted small d-block">Weight</span>
                    <strong class="text-dark fs-5"><?php echo $weight > 0 ? $weight . ' kg' : '--'; ?></strong>
                </div>
            </div>
            <div class="mt-4 text-center">
                <a href="profile.php" class="btn btn-outline-primary btn-sm w-100 fw-semibold"><i class="fa-solid fa-user-pen me-2"></i>Update Health Profile</a>
            </div>
        </div>
    </div>

    <!-- Dashboard Quick Shortcut Grid -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-solid fa-cubes text-primary me-2"></i>Portal Features & Shortcuts</h5>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <a href="book-appointment.php" class="text-decoration-none">
                        <div class="feature-card p-3 text-center">
                            <div class="feature-icon-wrapper mx-auto"><i class="fa-solid fa-calendar-plus"></i></div>
                            <span class="fw-bold text-dark small">Book Appointment</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="book-lab.php" class="text-decoration-none">
                        <div class="feature-card p-3 text-center">
                            <div class="feature-icon-wrapper mx-auto"><i class="fa-solid fa-flask"></i></div>
                            <span class="fw-bold text-dark small">Book Lab Test</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="appointments.php" class="text-decoration-none">
                        <div class="feature-card p-3 text-center">
                            <div class="feature-icon-wrapper mx-auto"><i class="fa-solid fa-calendar-days"></i></div>
                            <span class="fw-bold text-dark small">My Appointments</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="medical-records.php" class="text-decoration-none">
                        <div class="feature-card p-3 text-center">
                            <div class="feature-icon-wrapper mx-auto"><i class="fa-solid fa-file-prescription"></i></div>
                            <span class="fw-bold text-dark small">My Prescriptions</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="medical-records.php" class="text-decoration-none">
                        <div class="feature-card p-3 text-center">
                            <div class="feature-icon-wrapper mx-auto"><i class="fa-solid fa-file-medical"></i></div>
                            <span class="fw-bold text-dark small">Medical Records</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="emergency.php" class="text-decoration-none">
                        <div class="feature-card p-3 text-center">
                            <div class="feature-icon-wrapper mx-auto"><i class="fa-solid fa-truck-medical"></i></div>
                            <span class="fw-bold text-dark small">Emergency Services</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="articles.php" class="text-decoration-none">
                        <div class="feature-card p-3 text-center">
                            <div class="feature-icon-wrapper mx-auto"><i class="fa-solid fa-newspaper"></i></div>
                            <span class="fw-bold text-dark small">Health Articles</span>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="profile.php" class="text-decoration-none">
                        <div class="feature-card p-3 text-center">
                            <div class="feature-icon-wrapper mx-auto"><i class="fa-solid fa-user-gear"></i></div>
                            <span class="fw-bold text-dark small">Account Profile</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Appointments log -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-regular fa-calendar-check text-primary me-2"></i>Recent Appointments</h5>
                <a href="appointments.php" class="small text-decoration-none">View all</a>
            </div>
            
            <?php if (empty($recent_appointments)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa-regular fa-calendar-xmark fa-2x mb-2 text-muted opacity-50"></i>
                    <p class="mb-0">You have no appointment records scheduled.</p>
                    <a href="book-appointment.php" class="btn btn-primary btn-sm mt-3"><i class="fa-solid fa-calendar-plus me-1"></i> Book Now</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Appointment ID</th>
                                <th>Department</th>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_appointments as $appt): ?>
                                <?php
                                $statusClass = 'bg-warning text-dark';
                                if ($appt['status'] === 'Approved') $statusClass = 'bg-primary text-white';
                                elseif ($appt['status'] === 'Completed') $statusClass = 'bg-success text-white';
                                elseif ($appt['status'] === 'Rejected') $statusClass = 'bg-danger text-white';
                                ?>
                                <tr>
                                    <td><strong>#AP-<?php echo intval($appt['id']); ?></strong></td>
                                    <td><?php echo sanitize($appt['dept_name']); ?></td>
                                    <td><?php echo sanitize($appt['doctor_name']); ?></td>
                                    <td>
                                        <div class="small fw-semibold"><?php echo format_date($appt['appointment_date']); ?></div>
                                        <div class="small text-muted"><?php echo format_time($appt['time_slot']); ?></div>
                                    </td>
                                    <td><span class="badge <?php echo $statusClass; ?> px-2 py-1"><?php echo $appt['status']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar Mini Article Feed -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-book-medical text-primary me-2"></i>Wellness Hub</h5>
            
            <?php
            // Fetch 2 latest articles
            $side_articles = [];
            try {
                $stmtArt = $pdo->query("SELECT * FROM articles ORDER BY created_at DESC LIMIT 2");
                $side_articles = $stmtArt->fetchAll();
            } catch (Exception $e) {}
            
            if (empty($side_articles)):
            ?>
                <p class="text-muted small">No wellness articles available at the moment.</p>
            <?php else: ?>
                <?php foreach ($side_articles as $art): ?>
                    <div class="mb-3 border-bottom pb-3">
                        <span class="badge bg-secondary-subtle text-secondary small mb-1"><?php echo sanitize($art['category']); ?></span>
                        <h6 class="fw-bold text-dark mb-1"><a href="articles.php" class="text-reset text-decoration-none"><?php echo sanitize($art['title']); ?></a></h6>
                        <p class="small text-muted mb-0"><?php echo substr(sanitize($art['content']), 0, 90); ?>...</p>
                    </div>
                <?php endforeach; ?>
                <div class="text-center pt-2">
                    <a href="articles.php" class="small text-decoration-none fw-semibold">View all articles <i class="fa-solid fa-arrow-right-long ms-1"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
