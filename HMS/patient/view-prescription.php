<?php
/**
 * Print-Ready Clinic Prescription Sheet
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Verify login (either Patient, Doctor, or Admin can view a prescription)
if (!is_logged_in()) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

$pres_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($pres_id <= 0) {
    die("Invalid Prescription ID.");
}

try {
    // Fetch prescription details
    $stmt = $pdo->prepare("
        SELECT pr.*, 
               p.name AS patient_name, p.age AS patient_age, p.gender AS patient_gender, p.blood_group AS patient_blood, p.phone AS patient_phone,
               d.name AS doctor_name, d.qualification, d.specialization, d.phone AS doctor_phone,
               dept.name AS department_name
        FROM prescriptions pr
        JOIN patients p ON pr.patient_id = p.id
        JOIN doctors d ON pr.doctor_id = d.id
        LEFT JOIN departments dept ON d.department_id = dept.id
        WHERE pr.id = ?
    ");
    $stmt->execute([$pres_id]);
    $pres = $stmt->fetch();

    if (!$pres) {
        die("Prescription not found.");
    }

    // Role safety check: Patients can only view their own prescriptions
    if ($_SESSION['user_type'] === 'patient' && $_SESSION['user_id'] != $pres['patient_id']) {
        die("Unauthorized access. This prescription belongs to another patient account.");
    }

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Sheet #RX-<?php echo $pres['id']; ?> - LifeLine HMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #F8F9FA;
            color: #334155;
            padding: 20px;
        }
        .medical-letterhead {
            background-color: white;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 40px;
            max-width: 850px;
            margin: 20px auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            position: relative;
        }
        .clinic-brand {
            border-bottom: 3px double #0d6efd;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .prescription-rx {
            font-size: 2.2rem;
            font-weight: 800;
            color: #0d6efd;
            font-family: 'Georgia', serif;
            margin-bottom: 15px;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #94a3b8;
            margin-top: 50px;
            padding-top: 5px;
            text-align: center;
        }
        
        /* Print rules */
        @media print {
            body {
                background-color: white !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .medical-letterhead {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>

<!-- Control buttons banner -->
<div class="container text-center mb-3 no-print">
    <button onclick="window.print()" class="btn btn-success px-4 fw-bold shadow-sm me-2">
        <i class="fa-solid fa-print me-2"></i> Print Prescription / Save PDF
    </button>
    <?php if ($_SESSION['user_type'] === 'patient'): ?>
        <a href="medical-records.php" class="btn btn-outline-secondary px-4 fw-bold shadow-sm">
            <i class="fa-solid fa-circle-chevron-left me-2"></i> Back to Portal
        </a>
    <?php else: ?>
        <button onclick="window.close()" class="btn btn-outline-secondary px-4 fw-bold shadow-sm">
            <i class="fa-solid fa-xmark me-2"></i> Close Window
        </button>
    <?php endif; ?>
</div>

<div class="medical-letterhead">
    <!-- Clinic Brand Header -->
    <div class="clinic-brand">
        <div class="row align-items-center">
            <div class="col-sm-6 text-center text-sm-start mb-3 mb-sm-0">
                <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-hospital-user text-primary me-2"></i>LIFELINE CLINIC</h3>
                <p class="small text-muted mb-0">Baneshwor, Kathmandu, Nepal<br>Phone: +977-1-4433221 | info@lifeline.com</p>
            </div>
            <div class="col-sm-6 text-center text-sm-end">
                <h5 class="fw-bold text-primary mb-1"><?php echo sanitize($pres['doctor_name']); ?></h5>
                <p class="small text-muted mb-0">
                    <strong><?php echo sanitize($pres['specialization']); ?></strong><br>
                    <?php echo sanitize($pres['qualification']); ?><br>
                    Phone: <?php echo sanitize($pres['doctor_phone']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Patient Details Banner -->
    <div class="bg-light p-3 rounded mb-4" style="font-size:0.9rem;">
        <div class="row g-2">
            <div class="col-6 col-sm-3">
                <span class="text-muted d-block">Patient Name:</span>
                <strong><?php echo sanitize($pres['patient_name']); ?></strong>
            </div>
            <div class="col-6 col-sm-3">
                <span class="text-muted d-block">Age / Gender:</span>
                <strong><?php echo intval($pres['patient_age']); ?> Years / <?php echo sanitize($pres['patient_gender']); ?></strong>
            </div>
            <div class="col-6 col-sm-3">
                <span class="text-muted d-block">Blood Group:</span>
                <strong><?php echo sanitize($pres['patient_blood']); ?></strong>
            </div>
            <div class="col-6 col-sm-3 text-sm-end">
                <span class="text-muted d-block">Rx Date:</span>
                <strong><?php echo format_date($pres['prescription_date']); ?></strong>
            </div>
        </div>
    </div>

    <!-- Rx Body -->
    <div class="prescription-body min-vh-25">
        <div class="prescription-rx">℞</div>

        <!-- Symptoms -->
        <?php if (!empty($pres['symptoms'])): ?>
            <div class="mb-4">
                <h6 class="fw-bold text-secondary mb-1">Chief Symptoms & Diagnosis:</h6>
                <p class="text-dark mb-0"><?php echo nl2br(sanitize($pres['symptoms'])); ?></p>
            </div>
        <?php endif; ?>

        <!-- Medicines Table -->
        <div class="mb-4">
            <h6 class="fw-bold text-secondary mb-2">Prescribed Medications:</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 40%">Medicine Name & Strength</th>
                            <th style="width: 15%">Dosage</th>
                            <th style="width: 25%">Frequency</th>
                            <th style="width: 15%">Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $medicines = json_decode($pres['medicines'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($medicines)):
                            $count = 1;
                            foreach ($medicines as $med):
                        ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><strong><?php echo sanitize($med['name']); ?></strong></td>
                                <td><?php echo sanitize($med['dosage']); ?></td>
                                <td><?php echo sanitize($med['frequency']); ?></td>
                                <td><?php echo sanitize($med['duration']); ?></td>
                            </tr>
                        <?php
                            endforeach;
                        else:
                        ?>
                            <tr>
                                <td>1</td>
                                <td colspan="4"><strong><?php echo nl2br(sanitize($pres['medicines'])); ?></strong></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Instructions -->
        <?php if (!empty($pres['instructions'])): ?>
            <div class="mb-5">
                <h6 class="fw-bold text-secondary mb-1">Special Advice / Instructions:</h6>
                <p class="text-dark mb-0"><?php echo nl2br(sanitize($pres['instructions'])); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer Signature -->
    <div class="d-flex justify-content-end mt-5">
        <div>
            <div class="signature-line">
                <span class="small text-muted">Physician Signature</span>
            </div>
            <div class="text-center small fw-semibold text-dark mt-1"><?php echo sanitize(format_doctor_name($pres['doctor_name'])); ?></div>
        </div>
    </div>
</div>

</body>
</html>
