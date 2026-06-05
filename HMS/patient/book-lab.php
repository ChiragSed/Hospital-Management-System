<?php
/**
 * Book Laboratory Test Wizard
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('patient');

require_once __DIR__ . '/../config/database.php';
$patient_id = $_SESSION['user_id'];
$error = '';

// Step 1: Fetch distinct cities
$cities = [];
try {
    $cities = $pdo->query("SELECT DISTINCT city FROM laboratories ORDER BY city ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// Check selected step states
$selected_city = isset($_GET['city']) ? sanitize($_GET['city']) : '';
$selected_lab_id = isset($_GET['lab_id']) ? intval($_GET['lab_id']) : 0;

$labs = [];
$tests = [];
$lab_details = null;

if (!empty($selected_city)) {
    try {
        $stmtLabs = $pdo->prepare("SELECT * FROM laboratories WHERE city = ? ORDER BY name ASC");
        $stmtLabs->execute([$selected_city]);
        $labs = $stmtLabs->fetchAll();
    } catch (Exception $e) {}
}

if ($selected_lab_id > 0) {
    try {
        $stmtLab = $pdo->prepare("SELECT * FROM laboratories WHERE id = ?");
        $stmtLab->execute([$selected_lab_id]);
        $lab_details = $stmtLab->fetch();

        $stmtTests = $pdo->prepare("SELECT * FROM lab_tests WHERE lab_id = ? ORDER BY test_name ASC");
        $stmtTests->execute([$selected_lab_id]);
        $tests = $stmtTests->fetchAll();
    } catch (Exception $e) {}
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_lab_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $test_id = intval($_POST['lab_test_id']);
        $date = sanitize($_POST['booking_date']);

        if ($test_id <= 0 || empty($date)) {
            $error = "Please select a test and choosing booking date.";
        } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
            $error = "Booking date cannot be in the past.";
        } else {
            try {
                // Insert booking
                $stmt = $pdo->prepare("INSERT INTO lab_bookings (patient_id, lab_test_id, booking_date, status) VALUES (?, ?, ?, 'Pending')");
                $stmt->execute([$patient_id, $test_id, $date]);

                // Fetch details for notification
                $stmtTest = $pdo->prepare("SELECT lt.test_name, l.name AS lab_name FROM lab_tests lt JOIN laboratories l ON lt.lab_id = l.id WHERE lt.id = ?");
                $stmtTest->execute([$test_id]);
                $test_info = $stmtTest->fetch();

                // Notify Patient
                $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('patient', ?, 'Lab Panel Booked', ?)");
                $stmtNot->execute([
                    $patient_id,
                    "Your booking for " . sanitize($test_info['test_name']) . " at " . sanitize($test_info['lab_name']) . " on " . format_date($date) . " has been submitted successfully."
                ]);

                $_SESSION['success_message'] = "Laboratory test has been successfully booked!";
                header("Location: lab-bookings.php");
                exit();
            } catch (Exception $e) {
                $error = "Error booking lab test: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Book Diagnostic Lab Test</h3>
        <p class="text-muted">Search through city labs and book diagnostic panel tests online.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-solid fa-flask text-primary me-2"></i>Lab Booking Wizard</h5>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 small mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Wizard Flow form -->
            <form action="book-lab.php" method="GET" class="mb-4">
                <!-- Step 1: Select City -->
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Step 1: Choose Location City</label>
                    <select class="form-select" name="city" onchange="this.form.submit()">
                        <option value="">-- Select City --</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo $city; ?>" <?php echo $selected_city === $city ? 'selected' : ''; ?>><?php echo sanitize($city); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Step 2: Select Laboratory -->
                <?php if (!empty($selected_city)): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Step 2: Choose Laboratory Center</label>
                        <select class="form-select" name="lab_id" onchange="this.form.submit()">
                            <option value="">-- Select Laboratory --</option>
                            <?php foreach ($labs as $lab): ?>
                                <option value="<?php echo $lab['id']; ?>" <?php echo $selected_lab_id == $lab['id'] ? 'selected' : ''; ?>><?php echo sanitize($lab['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </form>

            <!-- Step 3: Choose Test Type and Book -->
            <?php if ($selected_lab_id > 0 && $lab_details): ?>
                <div class="alert alert-info border-0 p-3 mb-4" style="border-radius: 10px;">
                    <h6 class="fw-bold mb-1"><i class="fa-solid fa-circle-info me-2"></i>Laboratory Center Information</h6>
                    <ul class="list-unstyled small mb-0 mt-2">
                        <li><strong>Name:</strong> <?php echo sanitize($lab_details['name']); ?></li>
                        <li><strong>Address:</strong> <?php echo sanitize($lab_details['address']); ?>, <?php echo sanitize($lab_details['city']); ?></li>
                        <li><strong>Contact Phone:</strong> <?php echo sanitize($lab_details['phone']); ?></li>
                    </ul>
                </div>

                <form action="book-lab.php?city=<?php echo urlencode($selected_city); ?>&lab_id=<?php echo $selected_lab_id; ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Step 3: Select Diagnostic Test Type</label>
                        <select class="form-select" name="lab_test_id" required>
                            <option value="">-- Select Available Test --</option>
                            <?php foreach ($tests as $test): ?>
                                <option value="<?php echo $test['id']; ?>"><?php echo sanitize($test['test_name']); ?> - Rs. <?php echo number_format(floatval($test['price'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Step 4: Choose Panel Booking Date</label>
                        <input type="date" class="form-control" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="border-top pt-4">
                        <button type="submit" name="book_lab_submit" class="btn btn-primary w-100 py-3 fw-bold"><i class="fa-solid fa-clipboard-check me-2"></i> Confirm Diagnostic Lab Booking</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
