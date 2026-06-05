<?php
/**
 * Doctor Availability Schedule Manager
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('doctor');

require_once __DIR__ . '/../config/database.php';
$doctor_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Availability Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['availability_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $days = isset($_POST['available_days']) ? $_POST['available_days'] : [];
        $time_start = sanitize($_POST['available_time_start']);
        $time_end = sanitize($_POST['available_time_end']);

        if (empty($days)) {
            $error = "Please select at least one available weekday.";
        } elseif (empty($time_start) || empty($time_end)) {
            $error = "Please define consultation start and end hours.";
        } elseif (strtotime($time_start) >= strtotime($time_end)) {
            $error = "Consultation start time must be earlier than end time.";
        } else {
            try {
                $days_string = implode(',', array_map('sanitize', $days));
                
                $stmt = $pdo->prepare("UPDATE doctors SET available_days = ?, available_time_start = ?, available_time_end = ? WHERE id = ?");
                $stmt->execute([$days_string, $time_start, $time_end, $doctor_id]);

                $success = "Your weekly availability schedule has been updated successfully.";
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch current configurations
try {
    $stmt = $pdo->prepare("SELECT available_days, available_time_start, available_time_end FROM doctors WHERE id = ?");
    $stmt->execute([$doctor_id]);
    $schedule = $stmt->fetch();
} catch (Exception $e) {
    die("Database Connection Error.");
}

$current_days = explode(',', $schedule['available_days'] ?? '');
$weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Set Availability & Hours</h3>
        <p class="text-muted">Configure active weekdays and consultation hours for patient self-bookings.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-solid fa-clock text-primary me-2"></i>Weekly Schedule Settings</h5>

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

            <form action="availability.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <!-- Weekday Selectors -->
                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary mb-3">1. Select Available Weekdays</label>
                    <div class="row g-3">
                        <?php foreach ($weekdays as $day): ?>
                            <div class="col-6 col-sm-4 col-md-3">
                                <div class="form-check p-3 border rounded border-light-subtle bg-light-subtle h-100" style="border-radius: 8px;">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="available_days[]" value="<?php echo $day; ?>" id="check-<?php echo $day; ?>" <?php echo in_array($day, $current_days) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold text-dark small" for="check-<?php echo $day; ?>">
                                        <?php echo $day; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Hours Selectors -->
                <div class="mb-4 border-top pt-4">
                    <label class="form-label small fw-bold text-secondary mb-3">2. Daily Consultation Hours</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Consultation Shift Start</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-regular fa-clock text-muted"></i></span>
                                <input type="time" class="form-control" name="available_time_start" required value="<?php echo sanitize($schedule['available_time_start'] ?? '09:00:00'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Consultation Shift End</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-regular fa-clock text-muted"></i></span>
                                <input type="time" class="form-control" name="available_time_end" required value="<?php echo sanitize($schedule['available_time_end'] ?? '17:00:00'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-4 text-end">
                    <button type="submit" name="availability_submit" class="btn btn-primary px-5 py-2 shadow-sm"><i class="fa-solid fa-floppy-disk me-2"></i> Save Availability Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
