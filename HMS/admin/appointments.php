<?php
/**
 * Admin Appointment Manager
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('admin');

require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

// Handle Appointment Updates (Approve / Reject / Delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appt_id = intval($_GET['id']);
    $action = sanitize($_GET['action']);
    
    try {
        // Fetch details for notification
        $stmtCheck = $pdo->prepare("
            SELECT a.*, p.name AS patient_name, d.name AS doctor_name 
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            WHERE a.id = ?
        ");
        $stmtCheck->execute([$appt_id]);
        $appt = $stmtCheck->fetch();

        if ($appt) {
            $doctor_name = format_doctor_name($appt['doctor_name']);
            $patient_name = $appt['patient_name'];
            $patient_id = $appt['patient_id'];
            $doctor_id = $appt['doctor_id'];
            $date_str = format_date($appt['appointment_date']);
            $time_str = format_time($appt['time_slot']);

            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'Approved' WHERE id = ?");
                $stmt->execute([$appt_id]);
                
                // Notify Patient
                $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('patient', ?, 'Appointment Approved', ?)");
                $stmtNot->execute([$patient_id, "The system administrator has approved your appointment with {$doctor_name} for {$date_str} at {$time_str}."]);
                
                // Notify Doctor
                $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('doctor', ?, 'Appointment Approved', ?)");
                $stmtNot->execute([$doctor_id, "The system administrator has approved your appointment request with patient {$patient_name} for {$date_str} at {$time_str}."]);

                $_SESSION['success_message'] = "Appointment #AP-{$appt_id} approved successfully.";
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'Rejected' WHERE id = ?");
                $stmt->execute([$appt_id]);
                
                // Notify Patient
                $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('patient', ?, 'Appointment Rejected', ?)");
                $stmtNot->execute([$patient_id, "We regret to inform you that the system administrator has rejected your appointment request with {$doctor_name} on {$date_str}."]);

                // Notify Doctor
                $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('doctor', ?, 'Appointment Cancelled', ?)");
                $stmtNot->execute([$doctor_id, "The system administrator has rejected/cancelled the appointment request from Patient {$patient_name} on {$date_str}."]);

                $_SESSION['success_message'] = "Appointment #AP-{$appt_id} rejected.";
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
                $stmt->execute([$appt_id]);
                $_SESSION['success_message'] = "Appointment #AP-{$appt_id} record deleted from system.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid appointment details.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Database error updating appointment: " . $e->getMessage();
    }
    header("Location: appointments.php");
    exit();
}

// Fetch all appointments with optional search
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$appointments = [];

try {
    if (!empty($search_query)) {
        $stmt = $pdo->prepare("
            SELECT a.*, p.name AS patient_name, p.age, p.gender, p.blood_group, p.phone AS patient_phone,
                   d.name AS doctor_name, d.specialization AS doctor_specialization,
                   dept.name AS dept_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN departments dept ON a.department_id = dept.id
            WHERE p.name LIKE ? OR d.name LIKE ? OR dept.name LIKE ?
            ORDER BY CASE WHEN a.status = 'Pending' THEN 1 ELSE 2 END, a.appointment_date DESC, a.time_slot ASC
        ");
        $like = "%" . $search_query . "%";
        $stmt->execute([$like, $like, $like]);
    } else {
        $stmt = $pdo->query("
            SELECT a.*, p.name AS patient_name, p.age, p.gender, p.blood_group, p.phone AS patient_phone,
                   d.name AS doctor_name, d.specialization AS doctor_specialization,
                   dept.name AS dept_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN departments dept ON a.department_id = dept.id
            ORDER BY CASE WHEN a.status = 'Pending' THEN 1 ELSE 2 END, a.appointment_date DESC, a.time_slot ASC
        ");
    }
    $appointments = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h3 class="fw-bold text-dark mb-1">Manage Clinic Appointments</h3>
        <p class="text-muted">Monitor scheduled bookings globally, search by patient or physician, or manage statuses.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <form action="appointments.php" method="GET">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search patient, doctor, department..." value="<?php echo sanitize($search_query); ?>">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if (!empty($search_query)): ?>
                    <a href="appointments.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
    <?php if (empty($appointments)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-regular fa-calendar-xmark fa-3x mb-3 opacity-30"></i>
            <h5 class="fw-bold mb-1">No Appointments Found</h5>
            <p class="mb-0">We couldn't find any appointment records matching your criteria.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle custom-table mb-0">
                <thead>
                    <tr>
                        <th>Appt ID</th>
                        <th>Patient Details</th>
                        <th>Assigned Physician</th>
                        <th>Scheduled Date</th>
                        <th>Reason / Notes</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                        <?php
                        $statusClass = 'bg-warning text-dark';
                        if ($appt['status'] === 'Approved') $statusClass = 'bg-primary';
                        elseif ($appt['status'] === 'Completed') $statusClass = 'bg-success';
                        elseif ($appt['status'] === 'Rejected') $statusClass = 'bg-danger';
                        
                        $formatted_doc_name = format_doctor_name($appt['doctor_name']);
                        ?>
                        <tr>
                            <td><strong>#AP-<?php echo intval($appt['id']); ?></strong></td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo sanitize($appt['patient_name']); ?></div>
                                <div class="small text-muted" style="font-size:0.75rem;">
                                    <?php echo intval($appt['age']); ?> Yrs (<?php echo sanitize($appt['gender']); ?>) | Blood: <?php echo sanitize($appt['blood_group']); ?> | <?php echo sanitize($appt['patient_phone']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo sanitize($formatted_doc_name); ?></div>
                                <div class="small text-muted" style="font-size:0.75rem;"><?php echo sanitize($appt['dept_name']); ?> (<?php echo sanitize($appt['doctor_specialization']); ?>)</div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo format_date($appt['appointment_date']); ?></div>
                                <div class="small text-muted"><?php echo format_time($appt['time_slot']); ?></div>
                            </td>
                            <td><span class="small text-muted"><?php echo sanitize($appt['notes'] ?: 'None'); ?></span></td>
                            <td><span class="badge <?php echo $statusClass; ?> px-2 py-1"><?php echo $appt['status']; ?></span></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <?php if ($appt['status'] === 'Pending'): ?>
                                        <a href="appointments.php?action=approve&id=<?php echo $appt['id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </a>
                                        <a href="appointments.php?action=reject&id=<?php echo $appt['id']; ?>" class="btn btn-outline-danger btn-sm confirm-action" data-confirm-message="Are you sure you want to reject this request?">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </a>
                                    <?php endif; ?>
                                    <a href="appointments.php?action=delete&id=<?php echo $appt['id']; ?>" class="btn btn-danger btn-sm confirm-action" data-confirm-message="Are you sure you want to completely delete this appointment record? This action cannot be undone.">
                                        <i class="fa-solid fa-trash-can"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
