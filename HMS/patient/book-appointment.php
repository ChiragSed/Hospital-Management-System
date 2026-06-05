<?php
/**
 * Book Appointment Wizard
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('patient');

require_once __DIR__ . '/../config/database.php';
$patient_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Load departments for select dropdown
$departments = [];
try {
    $departments = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    // Fail silently
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $dept_id = intval($_POST['department_id']);
        $doctor_id = intval($_POST['doctor_id']);
        $date = sanitize($_POST['appointment_date']);
        $time = sanitize($_POST['time_slot']);
        $notes = sanitize($_POST['notes']);

        // Basic validation
        if ($dept_id <= 0 || $doctor_id <= 0 || empty($date) || empty($time)) {
            $error = "Please fill in all required fields.";
        } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
            $error = "Appointment date cannot be in the past.";
        } else {
            try {
                // Check if doctor is already booked at that exact date and time
                $stmtCheck = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND time_slot = ? AND status != 'Rejected'");
                $stmtCheck->execute([$doctor_id, $date, $time]);
                
                if ($stmtCheck->rowCount() > 0) {
                    $error = "The selected doctor is already booked for this time slot on the selected date. Please choose another time.";
                } else {
                    // Create appointment
                    $stmt = $pdo->prepare("
                        INSERT INTO appointments (patient_id, doctor_id, department_id, appointment_date, time_slot, status, notes) 
                        VALUES (?, ?, ?, ?, ?, 'Pending', ?)
                    ");
                    $stmt->execute([$patient_id, $doctor_id, $dept_id, $date, $time, $notes]);
                    $appt_id = $pdo->lastInsertId();

                    // Retrieve doctor name for notification details
                    $stmtDoc = $pdo->prepare("SELECT name FROM doctors WHERE id = ?");
                    $stmtDoc->execute([$doctor_id]);
                    $doctor_name = $stmtDoc->fetchColumn();

                    // Create Notifications
                    // 1. Patient Notification
                    $stmtNot = $pdo->prepare("
                        INSERT INTO notifications (user_type, user_id, title, message) 
                        VALUES ('patient', ?, 'Appointment Requested', ?)
                    ");
                    $stmtNot->execute([
                        $patient_id, 
                        "Your appointment request with Dr. {$doctor_name} for " . format_date($date) . " at " . format_time($time) . " has been submitted and is pending approval."
                    ]);

                    // 2. Doctor Notification
                    $stmtNotDoc = $pdo->prepare("
                        INSERT INTO notifications (user_type, user_id, title, message) 
                        VALUES ('doctor', ?, 'New Appointment Requested', ?)
                    ");
                    $stmtNotDoc->execute([
                        $doctor_id, 
                        "Patient " . $_SESSION['user_name'] . " has requested an appointment for " . format_date($date) . " at " . format_time($time) . "."
                    ]);

                    $_SESSION['success_message'] = "Appointment request has been submitted successfully!";
                    header("Location: appointments.php");
                    exit();
                }
            } catch (Exception $e) {
                $error = "Error booking appointment: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();

// Define available time slots for clinical appointments
$slots = [
    '09:00:00' => '09:00 AM',
    '09:30:00' => '09:30 AM',
    '10:00:00' => '10:00 AM',
    '10:30:00' => '10:30 AM',
    '11:00:00' => '11:00 AM',
    '11:30:00' => '11:30 AM',
    '13:00:00' => '01:00 PM',
    '13:30:00' => '01:30 PM',
    '14:00:00' => '02:00 PM',
    '14:30:00' => '02:30 PM',
    '15:00:00' => '03:00 PM',
    '15:30:00' => '03:30 PM',
    '16:00:00' => '04:00 PM',
    '16:30:00' => '04:30 PM',
];
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Book Consultation Appointment</h3>
        <p class="text-muted">Fill in the scheduling details below to reserve a consultation slot.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-solid fa-calendar-plus text-primary me-2"></i>Appointment Request Wizard</h5>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 small mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="book-appointment.php" method="POST" id="bookingForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <!-- Step 1: Select Department -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">1. Choose Department</label>
                    <select class="form-select" id="deptSelect" name="department_id" required onchange="loadDoctorsByDepartment(this.value, 'doctorSelect', '../')">
                        <option value="">-- Choose Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo sanitize($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 2: Select Doctor -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">2. Select Practitioner</label>
                    <select class="form-select" id="doctorSelect" name="doctor_id" required disabled>
                        <option value="">Select Department First</option>
                    </select>
                </div>

                <!-- Step 3: Select Date -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">3. Choose Appointment Date</label>
                    <input type="date" class="form-control" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <!-- Step 4: Time Slot -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">4. Pick Available Hour Slot</label>
                    <select class="form-select" name="time_slot" required>
                        <option value="">-- Choose Available Slot --</option>
                        <?php foreach ($slots as $val => $label): ?>
                            <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 5: Notes -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">5. Brief Symptoms / Medical Notes (Optional)</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Describe symptoms or reasons for your clinical checkout..."></textarea>
                </div>

                <div class="border-top pt-4">
                    <button type="submit" name="book_submit" class="btn btn-primary w-100 py-3 fw-bold"><i class="fa-solid fa-circle-check me-2"></i> Submit Appointment Booking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
