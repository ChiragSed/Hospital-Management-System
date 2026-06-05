<?php
/**
 * Admin Audit Report System
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('admin');

require_once __DIR__ . '/../config/database.php';

$report_type = isset($_GET['report_type']) ? sanitize($_GET['report_type']) : 'daily';
$report_date = isset($_GET['report_date']) ? sanitize($_GET['report_date']) : date('Y-m-d');
$report_month = isset($_GET['report_month']) ? sanitize($_GET['report_month']) : date('Y-m');

$report_data = [];
$report_title = '';

try {
    if ($report_type === 'daily') {
        $report_title = "Daily Appointments Audit Report - " . format_date($report_date);
        
        $stmt = $pdo->prepare("
            SELECT a.*, p.name AS patient_name, d.name AS doctor_name, dept.name AS dept_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN departments dept ON a.department_id = dept.id 
            WHERE a.appointment_date = ?
            ORDER BY a.time_slot ASC
        ");
        $stmt->execute([$report_date]);
        $report_data = $stmt->fetchAll();

    } elseif ($report_type === 'monthly') {
        $year = date('Y', strtotime($report_month));
        $month = date('m', strtotime($report_month));
        $report_title = "Monthly Appointments Audit Report - " . date('F Y', strtotime($report_month));

        $stmt = $pdo->prepare("
            SELECT a.*, p.name AS patient_name, d.name AS doctor_name, dept.name AS dept_name 
            FROM appointments a 
            JOIN patients p ON a.patient_id = p.id 
            JOIN doctors d ON a.doctor_id = d.id 
            JOIN departments dept ON a.department_id = dept.id 
            WHERE YEAR(a.appointment_date) = ? AND MONTH(a.appointment_date) = ?
            ORDER BY a.appointment_date ASC, a.time_slot ASC
        ");
        $stmt->execute([$year, $month]);
        $report_data = $stmt->fetchAll();

    } elseif ($report_type === 'departments') {
        $report_title = "Clinic Specialties Summary Report";

        $report_data = $pdo->query("
            SELECT dept.name AS dept_name, 
                   COUNT(DISTINCT d.id) AS doctor_count, 
                   COUNT(a.id) AS appointment_count 
            FROM departments dept 
            LEFT JOIN doctors d ON d.department_id = dept.id 
            LEFT JOIN appointments a ON a.department_id = dept.id 
            GROUP BY dept.id
            ORDER BY appointment_count DESC
        ")->fetchAll();

    } elseif ($report_type === 'doctors') {
        $report_title = "Doctor Consultations & Earnings Audit";

        $report_data = $pdo->query("
            SELECT d.name AS doctor_name, 
                   dept.name AS dept_name, 
                   COUNT(a.id) AS consultation_count, 
                   SUM(CASE WHEN a.status='Completed' THEN d.consultation_fee ELSE 0 END) AS earnings 
            FROM doctors d 
            JOIN departments dept ON d.department_id = dept.id 
            LEFT JOIN appointments a ON a.doctor_id = d.id 
            GROUP BY d.id
            ORDER BY earnings DESC
        ")->fetchAll();

    } elseif ($report_type === 'laboratories') {
        $report_title = "Laboratory Bookings & Diagnostics Revenue Report";

        $report_data = $pdo->query("
            SELECT l.name AS lab_name, 
                   l.city, 
                   COUNT(lb.id) AS booking_count, 
                   SUM(CASE WHEN lb.status='Completed' THEN lt.price ELSE 0 END) AS earnings 
            FROM laboratories l 
            JOIN lab_tests lt ON lt.lab_id = l.id 
            LEFT JOIN lab_bookings lb ON lb.lab_test_id = lt.id 
            GROUP BY l.id
            ORDER BY earnings DESC
        ")->fetchAll();
    }
} catch (Exception $e) {
    // Fail silently
}

include __DIR__ . '/../includes/header.php';
?>

<!-- Filter Controls (Hidden on Print) -->
<div class="row mb-4 no-print">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Clinic Audit Reports</h3>
        <p class="text-muted">Generate records summaries, check doctor performance, and monitor clinic revenue.</p>
    </div>
</div>

<div class="card border-0 shadow-sm p-4 mb-4 no-print" style="border-radius: 16px;">
    <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-filter text-primary me-2"></i>Report Filter Controls</h5>
    <form action="reports.php" method="GET">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary">Report Category</label>
                <select class="form-select" name="report_type" id="reportTypeSelect">
                    <option value="daily" <?php echo $report_type === 'daily' ? 'selected' : ''; ?>>Daily Appointments</option>
                    <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Monthly Appointments</option>
                    <option value="departments" <?php echo $report_type === 'departments' ? 'selected' : ''; ?>>Specialty Departments</option>
                    <option value="doctors" <?php echo $report_type === 'doctors' ? 'selected' : ''; ?>>Physicians Earnings</option>
                    <option value="laboratories" <?php echo $report_type === 'laboratories' ? 'selected' : ''; ?>>Laboratory Earnings</option>
                </select>
            </div>
            
            <div class="col-md-3" id="dateFilterGroup" style="display: <?php echo $report_type === 'daily' ? 'block' : 'none'; ?>;">
                <label class="form-label small fw-bold text-secondary">Choose Date</label>
                <input type="date" class="form-control" name="report_date" value="<?php echo $report_date; ?>">
            </div>

            <div class="col-md-3" id="monthFilterGroup" style="display: <?php echo $report_type === 'monthly' ? 'block' : 'none'; ?>;">
                <label class="form-label small fw-bold text-secondary">Choose Month</label>
                <input type="month" class="form-control" name="report_month" value="<?php echo $report_month; ?>">
            </div>

            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="fa-solid fa-rotate me-1"></i> Compile Report
                </button>
            </div>
            <div class="col-md-3">
                <button type="button" onclick="window.print()" class="btn btn-outline-success w-100 py-2 fw-semibold">
                    <i class="fa-solid fa-print me-1"></i> Print / Save PDF
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Printed Report Document Container -->
<div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: 16px;" id="reportPrintArea">
    <!-- Clinic Letterhead (Only visible on print, or styled nicely on screen) -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fa-solid fa-hospital-user text-primary me-2"></i>LIFELINE CLINIC</h4>
            <span class="small text-muted">Clinical Audit Records | Baneshwor, Kathmandu</span>
        </div>
        <div class="text-end">
            <span class="small text-muted d-block">Report Compiled On:</span>
            <strong><?php echo date('M d, Y | H:i A'); ?></strong>
        </div>
    </div>

    <h4 class="fw-bold text-dark text-center mb-4 text-uppercase decoration-underline"><?php echo $report_title; ?></h4>

    <?php if (empty($report_data)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-regular fa-folder-open fa-3x mb-3 opacity-30"></i>
            <p class="mb-0">No records found for the selected criteria.</p>
        </div>
    <?php else: ?>
        <!-- Report Table Layouts based on category -->
        
        <?php if ($report_type === 'daily' || $report_type === 'monthly'): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" style="font-size:0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Appt ID</th>
                            <th>Patient Name</th>
                            <th>Physician</th>
                            <th>Department</th>
                            <th>Date / Slot</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td><strong>#AP-<?php echo $row['id']; ?></strong></td>
                                <td><?php echo sanitize($row['patient_name']); ?></td>
                                <td><?php echo sanitize(format_doctor_name($row['doctor_name'])); ?></td>
                                <td><?php echo sanitize($row['dept_name']); ?></td>
                                <td>
                                    <div><?php echo format_date($row['appointment_date']); ?></div>
                                    <span class="text-muted small"><?php echo format_time($row['time_slot']); ?></span>
                                </td>
                                <td><?php echo sanitize($row['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($report_type === 'departments'): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" style="font-size:0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Department Clinic</th>
                            <th>Staff Doctors</th>
                            <th>Total Consultations Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_docs = 0;
                        $total_appts = 0;
                        foreach ($report_data as $row): 
                            $total_docs += $row['doctor_count'];
                            $total_appts += $row['appointment_count'];
                        ?>
                            <tr>
                                <td><strong><?php echo sanitize($row['dept_name']); ?></strong></td>
                                <td><?php echo intval($row['doctor_count']); ?> Doctors</td>
                                <td><?php echo intval($row['appointment_count']); ?> Bookings</td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-dark">
                            <td><strong>Total Summarized</strong></td>
                            <td><strong><?php echo $total_docs; ?> Doctors</strong></td>
                            <td><strong><?php echo $total_appts; ?> Bookings</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        <?php elseif ($report_type === 'doctors'): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" style="font-size:0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Doctor Name</th>
                            <th>Assigned Specialty</th>
                            <th>Completed Consultations</th>
                            <th>Total Consultation Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_consults = 0;
                        $total_earnings = 0;
                        foreach ($report_data as $row): 
                            $total_consults += $row['consultation_count'];
                            $total_earnings += floatval($row['earnings']);
                        ?>
                            <tr>
                                <td><strong><?php echo sanitize(format_doctor_name($row['doctor_name'])); ?></strong></td>
                                <td><?php echo sanitize($row['dept_name']); ?></td>
                                <td><?php echo intval($row['consultation_count']); ?> Checked Out</td>
                                <td class="text-success fw-bold">Rs. <?php echo number_format(floatval($row['earnings'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-dark">
                            <td colspan="2"><strong>Total Summarized</strong></td>
                            <td><strong><?php echo $total_consults; ?> Consultations</strong></td>
                            <td class="text-success"><strong>Rs. <?php echo number_format($total_earnings); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        <?php elseif ($report_type === 'laboratories'): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" style="font-size:0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Laboratory Center</th>
                            <th>Location City</th>
                            <th>Completed Panel Bookings</th>
                            <th>Laboratory Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_bookings = 0;
                        $total_earnings = 0;
                        foreach ($report_data as $row): 
                            $total_bookings += $row['booking_count'];
                            $total_earnings += floatval($row['earnings']);
                        ?>
                            <tr>
                                <td><strong><?php echo sanitize($row['lab_name']); ?></strong></td>
                                <td><?php echo sanitize($row['city']); ?></td>
                                <td><?php echo intval($row['booking_count']); ?> Completed</td>
                                <td class="text-success fw-bold">Rs. <?php echo number_format(floatval($row['earnings'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-dark">
                            <td colspan="2"><strong>Total Summarized</strong></td>
                            <td><strong><?php echo $total_bookings; ?> Bookings</strong></td>
                            <td class="text-success"><strong>Rs. <?php echo number_format($total_earnings); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    // Toggle date/month filters based on report selection
    const reportTypeSelect = document.getElementById('reportTypeSelect');
    const dateFilterGroup = document.getElementById('dateFilterGroup');
    const monthFilterGroup = document.getElementById('monthFilterGroup');

    if (reportTypeSelect) {
        reportTypeSelect.addEventListener('change', function() {
            if (this.value === 'daily') {
                dateFilterGroup.style.display = 'block';
                monthFilterGroup.style.display = 'none';
            } else if (this.value === 'monthly') {
                dateFilterGroup.style.display = 'none';
                monthFilterGroup.style.display = 'block';
            } else {
                dateFilterGroup.style.display = 'none';
                monthFilterGroup.style.display = 'none';
            }
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
