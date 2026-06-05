<?php
/**
 * Doctor Consultation and Prescription Builder
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('doctor');

require_once __DIR__ . '/../config/database.php';
$doctor_id = $_SESSION['user_id'];
$error = '';

$appt_id = isset($_GET['appt_id']) ? intval($_GET['appt_id']) : 0;

if ($appt_id <= 0) {
    die("Invalid Appointment ID.");
}

try {
    // Fetch appointment and patient details
    $stmt = $pdo->prepare("
        SELECT a.*, p.name AS patient_name, p.age, p.gender, p.blood_group, p.height, p.weight, p.bmi
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.id = ? AND a.doctor_id = ? AND a.status = 'Approved'
    ");
    $stmt->execute([$appt_id, $doctor_id]);
    $appt = $stmt->fetch();

    if (!$appt) {
        die("Appointment not found or already completed.");
    }
} catch (Exception $e) {
    die("Database Connection Error.");
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consult_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $diagnosis = sanitize($_POST['diagnosis']);
        $treatment_plan = sanitize($_POST['treatment_plan']);
        $notes = sanitize($_POST['notes']);
        
        $med_names = isset($_POST['med_name']) ? $_POST['med_name'] : [];
        $med_dosages = isset($_POST['med_dosage']) ? $_POST['med_dosage'] : [];
        $med_frequencies = isset($_POST['med_frequency']) ? $_POST['med_frequency'] : [];
        $med_durations = isset($_POST['med_duration']) ? $_POST['med_duration'] : [];

        if (empty($diagnosis) || empty($treatment_plan)) {
            $error = "Diagnosis and treatment plans are required.";
        } else {
            try {
                $pdo->beginTransaction();

                $patient_id = $appt['patient_id'];
                $today = date('Y-m-d');

                // 1. Insert Diagnosis
                $stmtDiag = $pdo->prepare("
                    INSERT INTO diagnoses (appointment_id, patient_id, doctor_id, diagnosis, treatment_plan, notes) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmtDiag->execute([$appt_id, $patient_id, $doctor_id, $diagnosis, $treatment_plan, $notes]);
                $diag_id = $pdo->lastInsertId();

                // 2. Format and Insert Prescription
                $medicines_list = [];
                for ($i = 0; $i < count($med_names); $i++) {
                    if (!empty(trim($med_names[$i]))) {
                        $medicines_list[] = [
                            'name' => sanitize($med_names[$i]),
                            'dosage' => sanitize($med_dosages[$i] ?? ''),
                            'frequency' => sanitize($med_frequencies[$i] ?? ''),
                            'duration' => sanitize($med_durations[$i] ?? '')
                        ];
                    }
                }
                
                $medicines_json = json_encode($medicines_list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                
                $stmtPres = $pdo->prepare("
                    INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, prescription_date, symptoms, medicines, instructions) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtPres->execute([$appt_id, $patient_id, $doctor_id, $today, $appt['notes'], $medicines_json, $treatment_plan]);
                $pres_id = $pdo->lastInsertId();

                // 3. Populate Medical Records Links
                $stmtRec = $pdo->prepare("
                    INSERT INTO medical_records (patient_id, record_type, reference_id, description, date_recorded) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtRec->execute([$patient_id, 'Diagnosis', $diag_id, "Diagnosed with '{$diagnosis}' by Dr. " . $_SESSION['user_name'], $today]);
                $stmtRec->execute([$patient_id, 'Prescription', $pres_id, "Prescribed medications for visit by Dr. " . $_SESSION['user_name'], $today]);

                // 4. Update Appointment status to 'Completed'
                $stmtAppt = $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ?");
                $stmtAppt->execute([$appt_id]);

                // 5. Notify Patient
                $stmtNot = $pdo->prepare("
                    INSERT INTO notifications (user_type, user_id, title, message) 
                    VALUES ('patient', ?, 'New Prescription Released', ?)
                ");
                $stmtNot->execute([
                    $patient_id,
                    "Dr. " . $_SESSION['user_name'] . " has completed your checkout. Your diagnosis findings and digital prescriptions are now available."
                ]);

                $pdo->commit();
                
                $_SESSION['success_message'] = "Consultation successfully completed! Patient has been checked out.";
                header("Location: dashboard.php");
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Transaction failed: " . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();
$bmi_info = get_bmi_status($appt['bmi']);
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Patient Consultation Center</h3>
        <p class="text-muted font-monospace">Appointment ID: #AP-<?php echo $appt_id; ?> | Consulting Patient: <?php echo sanitize($appt['patient_name']); ?></p>
    </div>
</div>

<div class="row g-4">
    <!-- Patient Medical Info Sidebar -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-heart-pulse text-danger me-2"></i>Patient Health Index</h5>
            
            <div class="text-center py-3 border-bottom mb-3">
                <div class="bg-primary-subtle text-primary rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2" style="width:70px; height:70px;">
                    <i class="fa-solid fa-user-injured fa-3x"></i>
                </div>
                <h6 class="fw-bold mb-1"><?php echo sanitize($appt['patient_name']); ?></h6>
                <span class="small text-muted"><?php echo intval($appt['age']); ?> Years | <?php echo sanitize($appt['gender']); ?></span>
            </div>

            <div class="row text-center mb-3">
                <div class="col-6 border-end">
                    <span class="text-muted small d-block">Blood Group</span>
                    <strong class="text-dark"><?php echo sanitize($appt['blood_group']); ?></strong>
                </div>
                <div class="col-6">
                    <span class="text-muted small d-block">BMI Status</span>
                    <span class="badge <?php echo $bmi_info['class']; ?>"><?php echo $bmi_info['status']; ?></span>
                </div>
            </div>

            <div class="row text-center border-top pt-3">
                <div class="col-6 border-end">
                    <span class="text-muted small d-block">Height</span>
                    <strong><?php echo floatval($appt['height']) > 0 ? floatval($appt['height']) . ' cm' : '--'; ?></strong>
                </div>
                <div class="col-6">
                    <span class="text-muted small d-block">Weight</span>
                    <strong><?php echo floatval($appt['weight']) > 0 ? floatval($appt['weight']) . ' kg' : '--'; ?></strong>
                </div>
            </div>
            
            <?php if (!empty($appt['notes'])): ?>
                <div class="alert alert-light border border-light-subtle small mt-4 mb-0">
                    <strong>Reported Symptoms:</strong><br>
                    <?php echo sanitize($appt['notes']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Consultation / prescription sheet -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius:16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-solid fa-file-signature text-primary me-2"></i>Compile Diagnosis & Prescription</h5>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 small mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="consultation.php?appt_id=<?php echo $appt_id; ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Chief Diagnosis</label>
                    <input type="text" class="form-control" name="diagnosis" required placeholder="e.g. Acute Viral Pharyngitis" value="<?php echo isset($_POST['diagnosis']) ? sanitize($_POST['diagnosis']) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Treatment Plan / Special Instructions</label>
                    <textarea class="form-control" name="treatment_plan" rows="3" required placeholder="Describe resting advice, physical therapy or diet recommendations..."><?php echo isset($_POST['treatment_plan']) ? sanitize($_POST['treatment_plan']) : ''; ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">Internal Diagnostic Notes (Optional)</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Record internal clinical findings..."><?php echo isset($_POST['notes']) ? sanitize($_POST['notes']) : ''; ?></textarea>
                </div>

                <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-pills text-success me-2"></i>Prescribe Medications (Rx)</h5>
                
                <!-- Dynamic Medicines table -->
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle" id="medicineTable">
                        <thead class="table-light">
                            <tr>
                                <th>Medicine Name & Strength</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Duration</th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="text" class="form-control form-control-sm" name="med_name[]" placeholder="Paracetamol 500mg" required></td>
                                <td><input type="text" class="form-control form-control-sm" name="med_dosage[]" placeholder="1 tablet" required></td>
                                <td><input type="text" class="form-control form-control-sm" name="med_frequency[]" placeholder="Three times daily" required></td>
                                <td><input type="text" class="form-control form-control-sm" name="med_duration[]" placeholder="5 Days" required></td>
                                <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm border-0 delete-row-btn"><i class="fa-solid fa-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <button type="button" class="btn btn-outline-success btn-sm mb-4 fw-semibold" id="addRowBtn">
                    <i class="fa-solid fa-circle-plus me-1"></i> Add Medication Row
                </button>

                <div class="border-top pt-4">
                    <button type="submit" name="consult_submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm"><i class="fa-solid fa-clipboard-check me-2"></i> Submit Consultation & Checkout Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const addRowBtn = document.getElementById('addRowBtn');
    const medTableBody = document.querySelector('#medicineTable tbody');

    // Add medication row dynamically
    addRowBtn.addEventListener('click', function() {
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td><input type="text" class="form-control form-control-sm" name="med_name[]" placeholder="Amoxicillin 500mg" required></td>
            <td><input type="text" class="form-control form-control-sm" name="med_dosage[]" placeholder="1 capsule" required></td>
            <td><input type="text" class="form-control form-control-sm" name="med_frequency[]" placeholder="Twice daily (12h)" required></td>
            <td><input type="text" class="form-control form-control-sm" name="med_duration[]" placeholder="7 Days" required></td>
            <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm border-0 delete-row-btn"><i class="fa-solid fa-trash"></i></button></td>
        `;
        medTableBody.appendChild(newRow);
        bindDeleteEvents();
    });

    function bindDeleteEvents() {
        const deleteButtons = document.querySelectorAll('.delete-row-btn');
        deleteButtons.forEach(btn => {
            btn.onclick = function() {
                // Keep at least one row in table
                if (medTableBody.rows.length > 1) {
                    this.closest('tr').remove();
                } else {
                    alert("Prescription requires at least one medication entry.");
                }
            };
        });
    }

    // Initialize row delete triggers
    bindDeleteEvents();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
