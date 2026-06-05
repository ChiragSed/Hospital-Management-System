<?php
/**
 * Doctor Patient Registry and Files
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('doctor');

require_once __DIR__ . '/../config/database.php';
$doctor_id = $_SESSION['user_id'];

$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$patients = [];

try {
    // Fetch unique patients who have scheduled with this doctor
    if (!empty($search_query)) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.* 
            FROM patients p
            JOIN appointments a ON a.patient_id = p.id
            WHERE a.doctor_id = ? AND p.name LIKE ?
            ORDER BY p.name ASC
        ");
        $stmt->execute([$doctor_id, "%" . $search_query . "%"]);
    } else {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.* 
            FROM patients p
            JOIN appointments a ON a.patient_id = p.id
            WHERE a.doctor_id = ?
            ORDER BY p.name ASC
        ");
        $stmt->execute([$doctor_id]);
    }
    $patients = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h3 class="fw-bold text-dark mb-1">Patient Registry & dossiers</h3>
        <p class="text-muted">Access patient clinical health cards, check histories, and view previous lab results.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <form action="patients.php" method="GET" class="mt-2 mt-md-0">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search patient name..." value="<?php echo sanitize($search_query); ?>">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if (!empty($search_query)): ?>
                    <a href="patients.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
    <?php if (empty($patients)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-bed-pulse fa-3x mb-3 opacity-30"></i>
            <h5 class="fw-bold mb-1">No Patients Registered</h5>
            <p class="mb-0">You have no patient records listed under your consultations directory.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle custom-table mb-0">
                <thead>
                    <tr>
                        <th style="width: 5%;"></th>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Age / Gender</th>
                        <th>Contact info</th>
                        <th>Emergency Contact</th>
                        <th class="text-end">Medical Dossier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $pat): ?>
                        <tr>
                            <!-- Toggle details button -->
                            <td>
                                <button class="btn btn-light btn-sm rounded-circle border toggle-drawer-btn" type="button" data-bs-toggle="collapse" data-bs-target="#drawer-pat-<?php echo $pat['id']; ?>" aria-expanded="false">
                                    <i class="fa-solid fa-plus text-primary"></i>
                                </button>
                            </td>
                            <td><strong>#PT-<?php echo intval($pat['id']); ?></strong></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($pat['profile_pic'])): ?>
                                        <img src="../uploads/profile_pics/<?php echo $pat['profile_pic']; ?>" class="rounded-circle me-2" alt="Avatar" width="36" height="36" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-primary-subtle text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 0.8rem;">
                                            <i class="fa-solid fa-user-injured"></i>
                                        </div>
                                    <?php endif; ?>
                                    <strong class="text-dark"><?php echo sanitize($pat['name']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo intval($pat['age']); ?> Yrs / <?php echo sanitize($pat['gender']); ?></td>
                            <td>
                                <div class="small fw-semibold"><?php echo sanitize($pat['phone']); ?></div>
                                <div class="small text-muted" style="font-size:0.75rem"><?php echo sanitize($pat['email']); ?></div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?php echo sanitize($pat['emergency_contact_name']); ?></div>
                                <div class="small text-danger" style="font-size:0.75rem;"><i class="fa-solid fa-phone me-1"></i> <?php echo sanitize($pat['emergency_contact_phone']); ?></div>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-secondary px-3 py-1">Blood: <?php echo sanitize($pat['blood_group']); ?></span>
                            </td>
                        </tr>

                        <!-- Drawer Collapsible Dossier Row -->
                        <tr class="p-0 border-0">
                            <td colspan="7" class="p-0 border-0">
                                <div class="collapse border-bottom" id="drawer-pat-<?php echo $pat['id']; ?>">
                                    <div class="p-4" style="background-color: #f8fafc;">
                                        <div class="row g-4">
                                            <!-- Mini Health Index metrics -->
                                            <div class="col-lg-3">
                                                <div class="bg-white p-3 rounded-3 shadow-sm border border-light-subtle text-center">
                                                    <span class="text-muted small d-block">BMI (Body Mass Index)</span>
                                                    <h3 class="fw-bold text-primary mb-1"><?php echo floatval($pat['bmi']) > 0 ? floatval($pat['bmi']) : 'N/A'; ?></h3>
                                                    <?php $bmi_info = get_bmi_status($pat['bmi']); ?>
                                                    <span class="badge <?php echo $bmi_info['class']; ?> mb-3"><?php echo $bmi_info['status']; ?></span>
                                                    
                                                    <div class="row border-top pt-2 text-center g-0 small">
                                                        <div class="col-6 border-end">
                                                            <span class="text-muted d-block" style="font-size:0.7rem">Height</span>
                                                            <strong><?php echo floatval($pat['height']) > 0 ? floatval($pat['height']) . 'cm' : '--'; ?></strong>
                                                        </div>
                                                        <div class="col-6">
                                                            <span class="text-muted d-block" style="font-size:0.7rem">Weight</span>
                                                            <strong><?php echo floatval($pat['weight']) > 0 ? floatval($pat['weight']) . 'kg' : '--'; ?></strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- History list tabs -->
                                            <div class="col-lg-9">
                                                <div class="bg-white p-4 rounded-3 shadow-sm border border-light-subtle">
                                                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Clinical Dossier: <?php echo sanitize($pat['name']); ?></h6>
                                                    
                                                    <?php
                                                    // Query patient's diagnoses
                                                    $stmtDiag = $pdo->prepare("SELECT * FROM diagnoses WHERE patient_id = ? ORDER BY created_at DESC LIMIT 3");
                                                    $stmtDiag->execute([$pat['id']]);
                                                    $p_diags = $stmtDiag->fetchAll();

                                                    // Query prescriptions
                                                    $stmtPres = $pdo->prepare("SELECT * FROM prescriptions WHERE patient_id = ? ORDER BY created_at DESC LIMIT 3");
                                                    $stmtPres->execute([$pat['id']]);
                                                    $p_pres = $stmtPres->fetchAll();

                                                    // Query completed lab tests
                                                    $stmtLabs = $pdo->prepare("
                                                        SELECT lb.*, lt.test_name, l.name AS lab_name 
                                                        FROM lab_bookings lb 
                                                        JOIN lab_tests lt ON lb.lab_test_id = lt.id 
                                                        JOIN laboratories l ON lt.lab_id = l.id 
                                                        WHERE lb.patient_id = ? AND lb.status = 'Completed' 
                                                        ORDER BY lb.booking_date DESC 
                                                        LIMIT 3
                                                    ");
                                                    $stmtLabs->execute([$pat['id']]);
                                                    $p_labs = $stmtLabs->fetchAll();
                                                    ?>

                                                    <ul class="nav nav-pills nav-pills-custom mb-3 bg-light p-1 rounded" id="pills-tab-<?php echo $pat['id']; ?>" role="tablist">
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link active py-1 px-3 small" id="pills-diag-tab-<?php echo $pat['id']; ?>" data-bs-toggle="pill" data-bs-target="#pills-diag-<?php echo $pat['id']; ?>" type="button" role="tab">Diagnoses (<?php echo count($p_diags); ?>)</button>
                                                        </li>
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link py-1 px-3 small" id="pills-pres-tab-<?php echo $pat['id']; ?>" data-bs-toggle="pill" data-bs-target="#pills-pres-<?php echo $pat['id']; ?>" type="button" role="tab">Prescriptions (<?php echo count($p_pres); ?>)</button>
                                                        </li>
                                                        <li class="nav-item" role="presentation">
                                                            <button class="nav-link py-1 px-3 small" id="pills-labs-tab-<?php echo $pat['id']; ?>" data-bs-toggle="pill" data-bs-target="#pills-labs-<?php echo $pat['id']; ?>" type="button" role="tab">Lab Panels (<?php echo count($p_labs); ?>)</button>
                                                        </li>
                                                    </ul>

                                                    <div class="tab-content" id="pills-tabContent-<?php echo $pat['id']; ?>">
                                                        <!-- Diagnoses tab -->
                                                        <div class="tab-pane fade show active" id="pills-diag-<?php echo $pat['id']; ?>" role="tabpanel">
                                                            <?php if (empty($p_diags)): ?>
                                                                <span class="text-muted small">No previous diagnoses log.</span>
                                                            <?php else: ?>
                                                                <ul class="list-group list-group-flush small">
                                                                    <?php foreach ($p_diags as $d): ?>
                                                                        <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0">
                                                                            <span class="text-primary fw-bold">[<?php echo format_date($d['created_at']); ?>]</span>
                                                                            <strong><?php echo sanitize($d['diagnosis']); ?></strong> - Plan: <?php echo sanitize($d['treatment_plan']); ?>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php endif; ?>
                                                        </div>

                                                        <!-- Prescriptions Tab -->
                                                        <div class="tab-pane fade" id="pills-pres-<?php echo $pat['id']; ?>" role="tabpanel">
                                                            <?php if (empty($p_pres)): ?>
                                                                <span class="text-muted small">No past prescriptions.</span>
                                                            <?php else: ?>
                                                                <ul class="list-group list-group-flush small">
                                                                    <?php foreach ($p_pres as $pr): ?>
                                                                        <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0 d-flex justify-content-between align-items-center">
                                                                            <div>
                                                                                <span class="text-success fw-bold">[<?php echo format_date($pr['prescription_date']); ?>]</span>
                                                                                Symptoms: <em><?php echo sanitize($pr['symptoms'] ?: 'N/A'); ?></em>
                                                                            </div>
                                                                            <a href="../patient/view-prescription.php?id=<?php echo $pr['id']; ?>" target="_blank" class="btn btn-outline-success btn-xs py-0 px-2 fw-semibold" style="font-size:0.75rem;">View Rx</a>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php endif; ?>
                                                        </div>

                                                        <!-- Lab reports tab -->
                                                        <div class="tab-pane fade" id="pills-labs-<?php echo $pat['id']; ?>" role="tabpanel">
                                                            <?php if (empty($p_labs)): ?>
                                                                <span class="text-muted small">No completed laboratory findings.</span>
                                                            <?php else: ?>
                                                                <ul class="list-group list-group-flush small">
                                                                    <?php foreach ($p_labs as $lb): ?>
                                                                        <li class="list-group-item bg-transparent px-0 py-2 border-bottom-0 d-flex justify-content-between align-items-center">
                                                                            <div>
                                                                                <span class="text-info fw-bold">[<?php echo format_date($lb['booking_date']); ?>]</span>
                                                                                <strong><?php echo sanitize($lb['test_name']); ?></strong> (<?php echo sanitize($lb['lab_name']); ?>)
                                                                            </div>
                                                                            <a href="../uploads/lab_reports/<?php echo $lb['report_file']; ?>" target="_blank" class="btn btn-outline-info btn-xs py-0 px-2 fw-semibold" style="font-size:0.75rem;">View Report</a>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    // Toggle icon indicator on drawer click
    const collapseElements = document.querySelectorAll('.collapse');
    collapseElements.forEach(el => {
        el.addEventListener('show.bs.collapse', function () {
            const btn = document.querySelector(`[data-bs-target="#${this.id}"]`);
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-minus text-secondary"></i>';
            }
        });
        el.addEventListener('hide.bs.collapse', function () {
            const btn = document.querySelector(`[data-bs-target="#${this.id}"]`);
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-plus text-primary"></i>';
            }
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
