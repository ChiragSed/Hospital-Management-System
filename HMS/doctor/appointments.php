<?php
/**
 * Doctor Appointment Manager
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('doctor');

require_once __DIR__ . '/../config/database.php';
$doctor_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Appointment Updates (Approve / Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appt_id = intval($_GET['id']);
    $action = sanitize($_GET['action']);
    
    try {
        // Verify doctor owns appointment
        $stmtCheck = $pdo->prepare("SELECT id, patient_id, appointment_date, time_slot FROM appointments WHERE id = ? AND doctor_id = ?");
        $stmtCheck->execute([$appt_id, $doctor_id]);
        $appt = $stmtCheck->fetch();

        if ($appt) {
            $doctor_name = $_SESSION['user_name'];
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'Approved' WHERE id = ?");
                $stmt->execute([$appt_id]);
                
                // Notify Patient
                $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('patient', ?, 'Appointment Approved', ?)");
                $stmtNot->execute([
                    $appt['patient_id'],
                    "Dr. {$doctor_name} has approved your appointment request for " . format_date($appt['appointment_date']) . " at " . format_time($appt['time_slot']) . "."
                ]);
                
                $_SESSION['success_message'] = "Appointment request approved.";
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'Rejected' WHERE id = ?");
                $stmt->execute([$appt_id]);
                
                // Notify Patient
                $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('patient', ?, 'Appointment Rejected', ?)");
                $stmtNot->execute([
                    $appt['patient_id'],
                    "We regret to inform you that your appointment request with Dr. {$doctor_name} for " . format_date($appt['appointment_date']) . " has been rejected."
                ]);
                
                $_SESSION['success_message'] = "Appointment request rejected.";
            }
        } else {
            $_SESSION['error_message'] = "Invalid appointment details.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Database error updating appointment.";
    }
    header("Location: appointments.php");
    exit();
}

// Handle Rescheduling Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reschedule_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $appt_id = intval($_POST['appointment_id']);
        $new_date = sanitize($_POST['appointment_date']);
        $new_time = sanitize($_POST['time_slot']);

        if ($appt_id <= 0 || empty($new_date) || empty($new_time)) {
            $error = "Please fill in all rescheduling details.";
        } elseif (strtotime($new_date) < strtotime(date('Y-m-d'))) {
            $error = "Rescheduling date cannot be in the past.";
        } else {
            try {
                // Verify ownership
                $stmtCheck = $pdo->prepare("SELECT patient_id FROM appointments WHERE id = ? AND doctor_id = ?");
                $stmtCheck->execute([$appt_id, $doctor_id]);
                $patient_id = $stmtCheck->fetchColumn();

                if ($patient_id) {
                    $stmtUpdate = $pdo->prepare("UPDATE appointments SET appointment_date = ?, time_slot = ?, status = 'Approved' WHERE id = ?");
                    $stmtUpdate->execute([$new_date, $new_time, $appt_id]);

                    $doctor_name = $_SESSION['user_name'];
                    // Notify Patient
                    $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('patient', ?, 'Appointment Rescheduled', ?)");
                    $stmtNot->execute([
                        $patient_id,
                        "Your appointment with Dr. {$doctor_name} has been rescheduled to " . format_date($new_date) . " at " . format_time($new_time) . "."
                    ]);

                    $success = "Appointment successfully rescheduled!";
                } else {
                    $error = "Invalid appointment details.";
                }
            } catch (Exception $e) {
                $error = "Failed to reschedule: " . $e->getMessage();
            }
        }
    }
}

// Fetch all doctor's appointments
$appointments = [];
try {
    $stmt = $pdo->prepare("
        SELECT a.*, p.name AS patient_name, p.age, p.gender, p.blood_group, p.phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ?
        ORDER BY CASE WHEN a.status = 'Pending' THEN 1 ELSE 2 END, a.appointment_date ASC, a.time_slot ASC
    ");
    $stmt->execute([$doctor_id]);
    $appointments = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();

// Define available time slots
$slots = [
    '09:00:00' => '09:00 AM', '09:30:00' => '09:30 AM', '10:00:00' => '10:00 AM', '10:30:00' => '10:30 AM',
    '11:00:00' => '11:00 AM', '11:30:00' => '11:30 AM', '13:00:00' => '01:00 PM', '13:30:00' => '01:30 PM',
    '14:00:00' => '02:00 PM', '14:30:00' => '02:30 PM', '15:00:00' => '03:00 PM', '15:30:00' => '03:30 PM',
    '16:00:00' => '04:00 PM', '16:30:00' => '04:30 PM',
];
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Manage Appointments</h3>
        <p class="text-muted">Review patient requests, confirm clinic slots, or reschedule consultations.</p>
    </div>
</div>

<div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger border-0 small mb-4" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success border-0 small mb-4" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($appointments)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-regular fa-calendar-xmark fa-3x mb-3 opacity-30"></i>
            <p class="mb-0">You have no appointment records scheduled.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle custom-table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Patient Details</th>
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
                        ?>
                        <tr>
                            <td><strong>#AP-<?php echo intval($appt['id']); ?></strong></td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo sanitize($appt['patient_name']); ?></div>
                                <div class="small text-muted" style="font-size:0.75rem;">
                                    <?php echo intval($appt['age']); ?> Yrs (<?php echo sanitize($appt['gender']); ?>) | Blood: <?php echo sanitize($appt['blood_group']); ?>
                                </div>
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
                                    <?php elseif ($appt['status'] === 'Approved'): ?>
                                        <a href="consultation.php?appt_id=<?php echo $appt['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-stethoscope"></i> Consult
                                        </a>
                                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#rescheduleModal" data-appt-id="<?php echo $appt['id']; ?>" data-patient-name="<?php echo sanitize($appt['patient_name']); ?>">
                                            <i class="fa-solid fa-clock-rotate-left"></i> Reschedule
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">Checked Out / Closed</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="rescheduleModalLabel">Reschedule Appointment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="appointments.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="appointment_id" id="modalApptId">
                    
                    <p class="text-muted small">Move the appointment slot for patient <strong id="modalPatientName" class="text-dark"></strong>.</p>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">New Appointment Date</label>
                        <input type="date" class="form-control" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-secondary">New Time Hour Slot</label>
                        <select class="form-select" name="time_slot" required>
                            <option value="">-- Choose New Slot --</option>
                            <?php foreach ($slots as $val => $label): ?>
                                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light" style="border-bottom-left-radius:16px; border-bottom-right-radius:16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="reschedule_submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Reschedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const rescheduleModal = document.getElementById('rescheduleModal');
    if (rescheduleModal) {
        rescheduleModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const apptId = button.getAttribute('data-appt-id');
            const patientName = button.getAttribute('data-patient-name');

            const inputApptId = rescheduleModal.querySelector('#modalApptId');
            const spanPatientName = rescheduleModal.querySelector('#modalPatientName');

            inputApptId.value = apptId;
            spanPatientName.textContent = patientName;
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
