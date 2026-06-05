<?php
/**
 * Emergency Services & Ambulance Simulator
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('patient');

require_once __DIR__ . '/../config/database.php';
$patient_id = $_SESSION['user_id'];

// Fetch patient's emergency contact info
try {
    $stmt = $pdo->prepare("SELECT name, phone, emergency_contact_name, emergency_contact_phone FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
} catch (Exception $e) {
    die("Database Connection Error.");
}

// Handle simulated ambulance request
$simulated_request = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_ambulance'])) {
    $simulated_request = true;
    $_SESSION['success_message'] = "Ambulance dispatcher alerted! Trauma unit notified.";
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Emergency Care & Ambulance Dispatch</h3>
        <p class="text-muted">Simulate ambulance requests, verify your primary emergency contacts, and access hotlines.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Ambulance Dispatcher Simulator -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm p-4 p-md-5 h-100" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-solid fa-truck-medical text-danger me-2"></i>Ambulance Request Simulator</h5>

            <?php if ($simulated_request): ?>
                <div class="alert alert-danger border-0 p-4 mb-4 text-center" style="border-radius:12px;">
                    <div class="spinner-grow text-danger mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h4 class="fw-bold mb-2">Trauma Unit Dispatched!</h4>
                    <p class="mb-0 small">Emergency ambulance vehicle <strong>#AMB-108</strong> is active and in route to your registered address:</p>
                    <div class="bg-white text-dark p-2 rounded my-3 border border-danger-subtle font-monospace small">
                        <?php
                        // Fetch address
                        $stmtAddr = $pdo->prepare("SELECT address FROM patients WHERE id = ?");
                        $stmtAddr->execute([$patient_id]);
                        echo sanitize($stmtAddr->fetchColumn());
                        ?>
                    </div>
                    <h5 class="fw-bold mb-0 text-danger animate-pulse"><i class="fa-solid fa-clock-rotate-left me-1"></i> Estimated Time of Arrival: <span id="eta-countdown">09:00</span></h5>
                </div>
                <div class="text-center">
                    <a href="emergency.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-arrow-rotate-left me-1"></i> Reset Simulator</a>
                </div>
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    let timerElement = document.getElementById("eta-countdown");
                    if (timerElement) {
                        let totalSeconds = 9 * 60; // 9 minutes
                        let interval = setInterval(function() {
                            totalSeconds--;
                            if (totalSeconds <= 0) {
                                timerElement.innerText = "Arrived";
                                clearInterval(interval);
                            } else {
                                let minutes = Math.floor(totalSeconds / 60);
                                let seconds = totalSeconds % 60;
                                timerElement.innerText = 
                                    String(minutes).padStart(2, '0') + ":" + 
                                    String(seconds).padStart(2, '0');
                            }
                        }, 1000);
                    }
                });
                </script>
            <?php else: ?>
                <p class="text-muted">In case of a critical medical crisis (stroke, cardiac arrest, major trauma), click the button below. This simulates a real-world dispatch call by signaling our clinic dispatcher and planning routes instantly.</p>
                <form action="emergency.php" method="POST" class="mt-4 text-center">
                    <button type="submit" name="request_ambulance" class="btn btn-danger btn-lg py-3 px-5 fw-bold shadow-lg" style="border-radius:10px;">
                        <i class="fa-solid fa-kit-medical me-2 animate-bounce"></i> Dispatch Ambulance Now
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Emergency Contacts and Hotlines -->
    <div class="col-lg-5">
        <div class="d-flex flex-column gap-4 h-100">
            <!-- Patient's Own emergency contacts -->
            <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-user-shield text-primary me-2"></i>My Emergency Contacts</h5>
                <p class="text-muted small">In case of emergency, LifeLine Clinic will contact the following individual immediately:</p>
                <div class="bg-light p-3 rounded" style="font-size:0.9rem;">
                    <div class="mb-2">
                        <span class="text-muted small d-block">Contact Person:</span>
                        <strong class="text-dark"><?php echo sanitize($patient['emergency_contact_name']); ?></strong>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Emergency Phone:</span>
                        <strong class="text-danger"><i class="fa-solid fa-phone me-1"></i> <?php echo sanitize($patient['emergency_contact_phone']); ?></strong>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    <a href="profile.php" class="small text-decoration-none fw-semibold">Edit Emergency Contact <i class="fa-solid fa-angle-right"></i></a>
                </div>
            </div>

            <!-- Emergency Phone list -->
            <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-phone-volume text-danger me-2"></i>Critical Hotline Directories</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Nepal Ambulance Service</h6>
                            <span class="small text-muted">National emergency ambulance dispatch</span>
                        </div>
                        <span class="badge bg-danger fs-6">102</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Nepal Police</h6>
                            <span class="small text-muted">Security and emergency response</span>
                        </div>
                        <span class="badge bg-danger fs-6">100</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Fire Brigade</h6>
                            <span class="small text-muted">Fire and rescue services</span>
                        </div>
                        <span class="badge bg-danger fs-6">101</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
