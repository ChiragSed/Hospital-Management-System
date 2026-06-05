<?php
/**
 * Patient Appointment History & Reviews
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('patient');

require_once __DIR__ . '/../config/database.php';
$patient_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Appointment Cancellation
if (isset($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);
    try {
        // Double check ownership and status is pending
        $stmtCheck = $pdo->prepare("SELECT doctor_id, appointment_date, time_slot FROM appointments WHERE id = ? AND patient_id = ? AND status = 'Pending'");
        $stmtCheck->execute([$cancel_id, $patient_id]);
        $appt = $stmtCheck->fetch();

        if ($appt) {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'Rejected' WHERE id = ?");
            $stmt->execute([$cancel_id]);

            // Notify Doctor
            $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('doctor', ?, 'Appointment Cancelled', ?)");
            $stmtNot->execute([$appt['doctor_id'], "Patient " . $_SESSION['user_name'] . " has cancelled their appointment on " . format_date($appt['appointment_date']) . "."]);

            $_SESSION['success_message'] = "Appointment cancelled successfully.";
        } else {
            $_SESSION['error_message'] = "Cannot cancel. Appointment is not pending or does not belong to you.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error cancelling appointment.";
    }
    header("Location: appointments.php");
    exit();
}

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $doctor_id = intval($_POST['doctor_id']);
        $rating = intval($_POST['rating']);
        $comments = sanitize($_POST['comments']);

        if ($doctor_id <= 0 || $rating < 1 || $rating > 5) {
            $error = "Invalid rating scale selected.";
        } else {
            try {
                // Insert Feedback
                $stmt = $pdo->prepare("INSERT INTO feedback (patient_id, doctor_id, rating, comments) VALUES (?, ?, ?, ?)");
                $stmt->execute([$patient_id, $doctor_id, $rating, $comments]);
                $success = "Thank you! Your feedback has been registered.";
            } catch (Exception $e) {
                $error = "Failed to submit feedback. You may have already reviewed this visit.";
            }
        }
    }
}

// Fetch all appointments
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$appointments = [];

try {
    if (!empty($search_query)) {
        $stmtAppt = $pdo->prepare("
            SELECT a.*, d.name AS doctor_name, d.specialization, dept.name AS dept_name 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN departments dept ON a.department_id = dept.id 
            WHERE a.patient_id = ? 
              AND (d.name LIKE ? OR dept.name LIKE ? OR a.id LIKE ?)
            ORDER BY a.appointment_date DESC, a.time_slot DESC
        ");
        $like_search = "%" . $search_query . "%";
        $stmtAppt->execute([$patient_id, $like_search, $like_search, $like_search]);
    } else {
        $stmtAppt = $pdo->prepare("
            SELECT a.*, d.name AS doctor_name, d.specialization, dept.name AS dept_name 
            FROM appointments a 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN departments dept ON a.department_id = dept.id 
            WHERE a.patient_id = ? 
            ORDER BY a.appointment_date DESC, a.time_slot DESC
        ");
        $stmtAppt->execute([$patient_id]);
    }
    $appointments = $stmtAppt->fetchAll();
} catch (Exception $e) {
    // Fail silently
}

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h3 class="fw-bold text-dark mb-1">My Appointments History</h3>
        <p class="text-muted">Review booking logs, cancel pending visits, and leave clinician feedback.</p>
    </div>
    <div class="col-md-4">
        <!-- Search bar -->
        <form action="appointments.php" method="GET" class="mt-2 mt-md-0">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search doctor, dept, ID..." value="<?php echo sanitize($search_query); ?>">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if (!empty($search_query)): ?>
                    <a href="appointments.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </form>
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
            <i class="fa-regular fa-calendar-xmark fa-3x mb-3 opacity-50"></i>
            <h5 class="fw-bold mb-1">No Appointments Found</h5>
            <p class="mb-3">We couldn't find any appointment records for your account.</p>
            <a href="book-appointment.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-calendar-plus me-1"></i> Book Appointment Now</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle custom-table mb-0">
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Department</th>
                        <th>Doctor</th>
                        <th>Scheduled Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                        <?php
                        $statusClass = 'bg-warning text-dark';
                        if ($appt['status'] === 'Approved') $statusClass = 'bg-primary text-white';
                        elseif ($appt['status'] === 'Completed') $statusClass = 'bg-success text-white';
                        elseif ($appt['status'] === 'Rejected') $statusClass = 'bg-danger text-white';
                        ?>
                        <tr>
                            <td><strong>#AP-<?php echo intval($appt['id']); ?></strong></td>
                            <td><?php echo sanitize($appt['dept_name']); ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo sanitize($appt['doctor_name']); ?></div>
                                <div class="small text-muted" style="font-size:0.75rem"><?php echo sanitize($appt['specialization']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo format_date($appt['appointment_date']); ?></div>
                                <div class="small text-muted"><?php echo format_time($appt['time_slot']); ?></div>
                            </td>
                            <td><span class="badge <?php echo $statusClass; ?> px-2 py-1"><?php echo $appt['status']; ?></span></td>
                            <td class="text-end">
                                <?php if ($appt['status'] === 'Pending'): ?>
                                    <a href="appointments.php?cancel_id=<?php echo $appt['id']; ?>" class="btn btn-outline-danger btn-sm confirm-action" data-confirm-message="Are you sure you want to cancel this appointment request?">
                                        <i class="fa-regular fa-calendar-minus me-1"></i> Cancel
                                    </a>
                                <?php elseif ($appt['status'] === 'Completed'): ?>
                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#feedbackModal" data-doc-id="<?php echo $appt['doctor_id']; ?>" data-doc-name="<?php echo sanitize($appt['doctor_name']); ?>">
                                        <i class="fa-regular fa-star me-1"></i> Rate Doctor
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="feedbackModalLabel">Leave Clinic Feedback</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="appointments.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="doctor_id" id="modalDoctorId">
                    
                    <p class="text-muted small">Share your consultation experience with <strong id="modalDoctorName" class="text-dark"></strong> to help us improve our services.</p>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Rating</label>
                        <select class="form-select" name="rating" required>
                            <option value="5">⭐⭐⭐⭐⭐ (5 - Excellent)</option>
                            <option value="4">⭐⭐⭐⭐ (4 - Very Good)</option>
                            <option value="3">⭐⭐⭐ (3 - Average)</option>
                            <option value="2">⭐⭐ (2 - Below Average)</option>
                            <option value="1">⭐ (1 - Unsatisfactory)</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold text-secondary">Comments / Review</label>
                        <textarea class="form-control" name="comments" rows="4" placeholder="Write comments regarding treatment care..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light" style="border-bottom-left-radius:16px; border-bottom-right-radius:16px;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="feedback_submit" class="btn btn-primary"><i class="fa-regular fa-paper-plane me-1"></i> Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Dynamically assign values to Feedback Modal
    const feedbackModal = document.getElementById('feedbackModal');
    if (feedbackModal) {
        feedbackModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const docId = button.getAttribute('data-doc-id');
            const docName = button.getAttribute('data-doc-name');

            const inputDocId = feedbackModal.querySelector('#modalDoctorId');
            const spanDocName = feedbackModal.querySelector('#modalDoctorName');

            inputDocId.value = docId;
            spanDocName.textContent = docName;
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
