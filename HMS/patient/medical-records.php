<?php
/**
 * Consolidated Medical Records Index
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('patient');

require_once __DIR__ . '/../config/database.php';
$patient_id = $_SESSION['user_id'];

$diagnoses = [];
$prescriptions = [];
$lab_reports = [];
$visits = [];

try {
    // 1. Fetch Diagnoses
    $stmtDiag = $pdo->prepare("
        SELECT dg.*, d.name AS doctor_name, d.specialization 
        FROM diagnoses dg 
        JOIN doctors d ON dg.doctor_id = d.id 
        WHERE dg.patient_id = ? 
        ORDER BY dg.created_at DESC
    ");
    $stmtDiag->execute([$patient_id]);
    $diagnoses = $stmtDiag->fetchAll();

    // 2. Fetch Prescriptions
    $stmtPres = $pdo->prepare("
        SELECT pr.*, d.name AS doctor_name, d.specialization 
        FROM prescriptions pr 
        JOIN doctors d ON pr.doctor_id = d.id 
        WHERE pr.patient_id = ? 
        ORDER BY pr.created_at DESC
    ");
    $stmtPres->execute([$patient_id]);
    $prescriptions = $stmtPres->fetchAll();

    // 3. Fetch Completed Lab Reports
    $stmtLab = $pdo->prepare("
        SELECT lb.*, lt.test_name, l.name AS lab_name 
        FROM lab_bookings lb 
        JOIN lab_tests lt ON lb.lab_test_id = lt.id 
        JOIN laboratories l ON lt.lab_id = l.id 
        WHERE lb.patient_id = ? AND lb.status = 'Completed' 
        ORDER BY lb.booking_date DESC
    ");
    $stmtLab->execute([$patient_id]);
    $lab_reports = $stmtLab->fetchAll();

    // 4. Fetch Visit History
    $stmtVisit = $pdo->prepare("
        SELECT a.*, d.name AS doctor_name, dept.name AS dept_name 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        JOIN departments dept ON a.department_id = dept.id 
        WHERE a.patient_id = ? 
        ORDER BY a.appointment_date DESC, a.time_slot DESC
    ");
    $stmtVisit->execute([$patient_id]);
    $visits = $stmtVisit->fetchAll();

} catch (Exception $e) {
    // Fail silently
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">My Medical Records</h3>
        <p class="text-muted">Access your clinical history, diagnoses, prescriptions, and diagnostic lab findings.</p>
    </div>
</div>

<div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
    <!-- Records Tab Navigation -->
    <ul class="nav nav-tabs nav-tabs-custom border-bottom mb-4" id="recordsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-3 py-2" id="diagnoses-tab" data-bs-toggle="tab" data-bs-target="#diagnoses-pane" type="button" role="tab" aria-selected="true">
                <i class="fa-solid fa-notes-medical text-primary me-2"></i> Diagnoses (<?php echo count($diagnoses); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-3 py-2" id="prescriptions-tab" data-bs-toggle="tab" data-bs-target="#prescriptions-pane" type="button" role="tab" aria-selected="false">
                <i class="fa-solid fa-prescription-bottle-medical text-success me-2"></i> Prescriptions (<?php echo count($prescriptions); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-3 py-2" id="labs-tab" data-bs-toggle="tab" data-bs-target="#labs-pane" type="button" role="tab" aria-selected="false">
                <i class="fa-solid fa-flask-vial text-info me-2"></i> Lab Reports (<?php echo count($lab_reports); ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-3 py-2" id="visits-tab" data-bs-toggle="tab" data-bs-target="#visits-pane" type="button" role="tab" aria-selected="false">
                <i class="fa-solid fa-clock-history text-secondary me-2"></i> Visit History (<?php echo count($visits); ?>)
            </button>
        </li>
    </ul>

    <!-- Records Tab Content -->
    <div class="tab-content" id="recordsTabContent">
        <!-- Diagnoses Pane -->
        <div class="tab-pane fade show active" id="diagnoses-pane" role="tabpanel" tabindex="0">
            <?php if (empty($diagnoses)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-heart-circle-exclamation fa-3x mb-3 opacity-30"></i>
                    <p class="mb-0">No diagnosis records listed.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($diagnoses as $diag): ?>
                        <div class="col-md-6">
                            <div class="card border border-light shadow-sm p-4 h-100" style="border-radius:12px; background-color:#fafafb;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-primary px-3 py-1">Diagnosis ID: #DG-<?php echo $diag['id']; ?></span>
                                    <span class="text-muted small"><i class="fa-solid fa-calendar me-1"></i> <?php echo format_date($diag['created_at']); ?></span>
                                </div>
                                <h5 class="fw-bold text-dark mb-2"><?php echo sanitize($diag['diagnosis']); ?></h5>
                                <p class="small text-muted mb-3"><strong>Treatment Plan:</strong> <?php echo sanitize($diag['treatment_plan']); ?></p>
                                <?php if (!empty($diag['notes'])): ?>
                                    <div class="alert alert-light border border-light-subtle small mb-3 p-2 rounded">
                                        <strong>Physician Notes:</strong> <?php echo sanitize($diag['notes']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="border-top pt-2 mt-auto small text-secondary">
                                    <i class="fa-solid fa-user-doctor me-1"></i> Diagnosed by <strong><?php echo sanitize($diag['doctor_name']); ?></strong> (<?php echo sanitize($diag['specialization']); ?>)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Prescriptions Pane -->
        <div class="tab-pane fade" id="prescriptions-pane" role="tabpanel" tabindex="0">
            <?php if (empty($prescriptions)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-file-prescription fa-3x mb-3 opacity-30"></i>
                    <p class="mb-0">No active prescription sheets listed.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($prescriptions as $pres): ?>
                        <div class="col-12">
                            <div class="card border border-light shadow-sm p-4" style="border-radius:12px; background-color:#fafafb;">
                                <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-3">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-0">Prescription Sheet #RX-<?php echo $pres['id']; ?></h5>
                                        <span class="small text-muted">Consultation Date: <?php echo format_date($pres['prescription_date']); ?></span>
                                    </div>
                                    <div class="mt-2 mt-sm-0">
                                        <a href="view-prescription.php?id=<?php echo $pres['id']; ?>" target="_blank" class="btn btn-outline-success btn-sm fw-semibold">
                                            <i class="fa-solid fa-print me-1"></i> View / Print Rx
                                        </a>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <span class="text-muted small d-block">Diagnosed Symptoms</span>
                                        <strong><?php echo sanitize($pres['symptoms'] ?: 'N/A'); ?></strong>
                                    </div>
                                    <div class="col-md-8">
                                        <span class="text-muted small d-block">Special Instructions</span>
                                        <span class="text-dark small"><?php echo sanitize($pres['instructions'] ?: 'None'); ?></span>
                                    </div>
                                </div>

                                <h6 class="fw-bold text-secondary mb-2">Prescribed Medications</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle bg-white mb-0" style="font-size: 0.85rem;">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Medicine Name</th>
                                                <th>Dosage</th>
                                                <th>Frequency</th>
                                                <th>Duration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $medicines = json_decode($pres['medicines'], true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($medicines)):
                                                foreach ($medicines as $med):
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo sanitize($med['name']); ?></strong></td>
                                                    <td><?php echo sanitize($med['dosage']); ?></td>
                                                    <td><?php echo sanitize($med['frequency']); ?></td>
                                                    <td><?php echo sanitize($med['duration']); ?></td>
                                                </tr>
                                            <?php
                                                endforeach;
                                            else:
                                                // Fallback if stored as text
                                            ?>
                                                <tr>
                                                    <td colspan="4"><?php echo nl2br(sanitize($pres['medicines'])); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="border-top pt-3 mt-3 small text-secondary">
                                    <i class="fa-solid fa-user-doctor me-1"></i> Prescribed by <strong><?php echo sanitize($pres['doctor_name']); ?></strong> (<?php echo sanitize($pres['specialization']); ?>)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Lab Findings Pane -->
        <div class="tab-pane fade" id="labs-pane" role="tabpanel" tabindex="0">
            <?php if (empty($lab_reports)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-flask-vial fa-3x mb-3 opacity-30"></i>
                    <p class="mb-0">No completed lab reports found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Laboratory Center</th>
                                <th>Diagnostic Panel</th>
                                <th>Uploaded Date</th>
                                <th class="text-end">Report Finding</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lab_reports as $rep): ?>
                                <tr>
                                    <td><strong>#LB-<?php echo $rep['id']; ?></strong></td>
                                    <td><?php echo sanitize($rep['lab_name']); ?></td>
                                    <td><strong><?php echo sanitize($rep['test_name']); ?></strong></td>
                                    <td><?php echo format_date($rep['uploaded_at'] ?? $rep['booking_date']); ?></td>
                                    <td class="text-end">
                                        <a href="../uploads/lab_reports/<?php echo $rep['report_file']; ?>" target="_blank" class="btn btn-success btn-sm fw-semibold">
                                            <i class="fa-regular fa-file-pdf me-1"></i> Download PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Visit History Pane -->
        <div class="tab-pane fade" id="visits-pane" role="tabpanel" tabindex="0">
            <?php if (empty($visits)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-calendar-xmark fa-3x mb-3 opacity-30"></i>
                    <p class="mb-0">No clinic visit records found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle custom-table mb-0">
                        <thead>
                            <tr>
                                <th>Visit ID</th>
                                <th>Department</th>
                                <th>Consultant Doctor</th>
                                <th>Visit Date</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visits as $v): ?>
                                <?php
                                $statusClass = 'bg-warning text-dark';
                                if ($v['status'] === 'Approved') $statusClass = 'bg-primary';
                                elseif ($v['status'] === 'Completed') $statusClass = 'bg-success';
                                elseif ($v['status'] === 'Rejected') $statusClass = 'bg-danger';
                                ?>
                                <tr>
                                    <td><strong>#AP-<?php echo $v['id']; ?></strong></td>
                                    <td><?php echo sanitize($v['dept_name']); ?></td>
                                    <td><?php echo sanitize($v['doctor_name']); ?></td>
                                    <td><?php echo format_date($v['appointment_date']); ?></td>
                                    <td><span class="badge <?php echo $statusClass; ?> px-2 py-1"><?php echo $v['status']; ?></span></td>
                                    <td class="small text-muted" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize($v['notes'] ?: 'None'); ?></td>
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
