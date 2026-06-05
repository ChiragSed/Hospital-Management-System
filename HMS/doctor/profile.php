<?php
/**
 * Doctor Profile Manager
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('doctor');

require_once __DIR__ . '/../config/database.php';
$doctor_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $qualification = sanitize($_POST['qualification']);
        $specialization = sanitize($_POST['specialization']);
        $experience = intval($_POST['experience']);
        $fee = floatval($_POST['consultation_fee']);

        if (empty($name) || empty($phone) || empty($qualification) || empty($specialization) || $experience < 0 || $fee < 0) {
            $error = "All professional credential fields are required.";
        } else {
            try {
                // Upload avatar
                $profile_pic_filename = null;
                if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                    $target_dir = __DIR__ . '/../uploads/profile_pics/';
                    $upload = upload_file($_FILES['profile_pic'], $target_dir, ['jpg', 'jpeg', 'png']);
                    
                    if ($upload['status']) {
                        $profile_pic_filename = $upload['filename'];
                    } else {
                        $error = "File Upload Error: " . $upload['error'];
                    }
                }

                if ($error === '') {
                    if ($profile_pic_filename !== null) {
                        $stmt = $pdo->prepare("
                            UPDATE doctors SET 
                                name = ?, phone = ?, qualification = ?, specialization = ?, 
                                experience = ?, consultation_fee = ?, profile_pic = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $phone, $qualification, $specialization, $experience, $fee, $profile_pic_filename, $doctor_id]);
                        $_SESSION['profile_pic'] = $profile_pic_filename;
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE doctors SET 
                                name = ?, phone = ?, qualification = ?, specialization = ?, 
                                experience = ?, consultation_fee = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $phone, $qualification, $specialization, $experience, $fee, $doctor_id]);
                    }

                    $_SESSION['user_name'] = $name;
                    $success = "Your professional profile has been updated successfully.";
                }
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch current details
try {
    $stmt = $pdo->prepare("
        SELECT d.*, dept.name AS dept_name 
        FROM doctors d 
        LEFT JOIN departments dept ON d.department_id = dept.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();
} catch (Exception $e) {
    die("Database Connection Error.");
}

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Professional Clinician Profile</h3>
        <p class="text-muted">Manage your clinical descriptions, credentials, rates, and schedule options.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Left Profile Card -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4 text-center" style="border-radius: 16px;">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <?php if (!empty($doctor['profile_pic'])): ?>
                    <img src="../uploads/profile_pics/<?php echo $doctor['profile_pic']; ?>" class="rounded-circle border border-4 border-light shadow" alt="Avatar" width="150" height="150" style="object-fit: cover;">
                <?php else: ?>
                    <div class="bg-primary-subtle text-primary rounded-circle border border-4 border-light shadow d-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                        <i class="fa-solid fa-user-doctor fa-5x"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h4 class="fw-bold text-dark mb-1"><?php echo sanitize($doctor['name']); ?></h4>
            <span class="badge bg-secondary-subtle text-secondary px-3 py-1 mb-2"><?php echo sanitize($doctor['dept_name'] ?? 'General Practitioner'); ?></span>
            <p class="text-muted small mb-0">Clinic verification status: <strong class="text-success"><i class="fa-solid fa-circle-check"></i> Approved</strong></p>
        </div>
    </div>

    <!-- Right Profile Editor Form -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-solid fa-user-pen text-primary me-2"></i>Edit Clinical Profile Details</h5>
            
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

            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <h6 class="fw-bold text-secondary mb-3">General Information</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Physician Name</label>
                        <input type="text" class="form-control" name="name" required value="<?php echo sanitize($doctor['name']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Email Address (Cannot change)</label>
                        <input type="email" class="form-control bg-light" readonly value="<?php echo sanitize($doctor['email']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" required value="<?php echo sanitize($doctor['phone']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Profile Avatar</label>
                        <input type="file" class="form-control" name="profile_pic" accept="image/*">
                        <div class="form-text small" style="font-size:0.75rem">Upload JPG/PNG image up to 5MB.</div>
                    </div>
                </div>

                <h6 class="fw-bold text-secondary mb-3">Clinical Credentials & Fees</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Assigned Department (Cannot change)</label>
                        <input type="text" class="form-control bg-light" readonly value="<?php echo sanitize($doctor['dept_name'] ?? 'Unassigned'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Medical Qualification</label>
                        <input type="text" class="form-control" name="qualification" required value="<?php echo sanitize($doctor['qualification']); ?>" placeholder="MD - Cardiology, FACC">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Clinical Specialization</label>
                        <input type="text" class="form-control" name="specialization" required value="<?php echo sanitize($doctor['specialization']); ?>" placeholder="Interventional Cardiology">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Experience (Years)</label>
                        <input type="number" class="form-control" name="experience" required min="0" max="60" value="<?php echo intval($doctor['experience']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Consultation Fee (Rs.)</label>
                        <input type="number" class="form-control" name="consultation_fee" required min="0" step="10" value="<?php echo floatval($doctor['consultation_fee']); ?>">
                    </div>
                </div>

                <div class="border-top pt-4">
                    <button type="submit" name="profile_submit" class="btn btn-primary px-5 py-2 shadow-sm"><i class="fa-solid fa-floppy-disk me-2"></i> Save Profile Details</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/header.php'; ?>
