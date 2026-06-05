<?php
/**
 * Patient Profile Manager
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('patient');

require_once __DIR__ . '/../config/database.php';
$patient_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle Profile Updates Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $name = sanitize($_POST['name']);
        $phone = sanitize($_POST['phone']);
        $age = intval($_POST['age']);
        $gender = sanitize($_POST['gender']);
        $blood_group = sanitize($_POST['blood_group']);
        $address = sanitize($_POST['address']);
        $emergency_name = sanitize($_POST['emergency_contact_name']);
        $emergency_phone = sanitize($_POST['emergency_contact_phone']);
        
        $height = floatval($_POST['height']);
        $weight = floatval($_POST['weight']);
        
        if (empty($name) || empty($phone) || empty($address) || empty($emergency_name) || empty($emergency_phone) || $age <= 0) {
            $error = "All fields except height/weight are required.";
        } else {
            try {
                // Calculate BMI
                $bmi = 0;
                if ($height > 0 && $weight > 0) {
                    $bmi = calculate_bmi($weight, $height);
                }

                // Handle file upload if profile picture uploaded
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
                            UPDATE patients SET 
                                name = ?, phone = ?, age = ?, gender = ?, blood_group = ?, 
                                address = ?, emergency_contact_name = ?, emergency_contact_phone = ?, 
                                height = ?, weight = ?, bmi = ?, profile_pic = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $phone, $age, $gender, $blood_group, $address, $emergency_name, $emergency_phone, $height, $weight, $bmi, $profile_pic_filename, $patient_id]);
                        
                        // Update active session
                        $_SESSION['profile_pic'] = $profile_pic_filename;
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE patients SET 
                                name = ?, phone = ?, age = ?, gender = ?, blood_group = ?, 
                                address = ?, emergency_contact_name = ?, emergency_contact_phone = ?, 
                                height = ?, weight = ?, bmi = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $phone, $age, $gender, $blood_group, $address, $emergency_name, $emergency_phone, $height, $weight, $bmi, $patient_id]);
                    }

                    $_SESSION['user_name'] = $name;
                    $success = "Your profile information has been successfully updated.";
                }
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch current details
try {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
} catch (Exception $e) {
    die("Database Connection Error.");
}

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">My Account Profile</h3>
        <p class="text-muted">Manage your personal demographics and upload your medical parameters.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <!-- Avatar card -->
        <div class="card border-0 shadow-sm p-4 text-center" style="border-radius: 16px;">
            <div class="position-relative d-inline-block mx-auto mb-3">
                <?php if (!empty($patient['profile_pic'])): ?>
                    <img src="../uploads/profile_pics/<?php echo $patient['profile_pic']; ?>" class="rounded-circle border border-4 border-light shadow" alt="Avatar" width="150" height="150" style="object-fit: cover;">
                <?php else: ?>
                    <div class="bg-primary-subtle text-primary rounded-circle border border-4 border-light shadow d-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                        <i class="fa-solid fa-user-injured fa-5x"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h4 class="fw-bold text-dark mb-1"><?php echo sanitize($patient['name']); ?></h4>
            <span class="badge bg-primary px-3 py-1 mb-2">Patient</span>
            <p class="text-muted small mb-0">Member since <?php echo format_date($patient['created_at']); ?></p>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-solid fa-user-pen text-primary me-2"></i>Edit Profile Details</h5>
            
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

                <h6 class="fw-bold text-secondary mb-3">Account Details</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Full Name</label>
                        <input type="text" class="form-control" name="name" required value="<?php echo sanitize($patient['name']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Email Address (Cannot change)</label>
                        <input type="email" class="form-control bg-light" readonly value="<?php echo sanitize($patient['email']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Phone Number</label>
                        <input type="tel" class="form-control" name="phone" required value="<?php echo sanitize($patient['phone']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Profile Avatar</label>
                        <input type="file" class="form-control" name="profile_pic" accept="image/*">
                        <div class="form-text small" style="font-size:0.75rem">Upload JPG/PNG image up to 5MB.</div>
                    </div>
                </div>

                <h6 class="fw-bold text-secondary mb-3">Demographics & Physical Metrics</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Age (Years)</label>
                        <input type="number" class="form-control" name="age" required min="1" max="120" value="<?php echo intval($patient['age']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="Male" <?php echo $patient['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $patient['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $patient['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted">Blood Group</label>
                        <select class="form-select" name="blood_group">
                            <option value="A+" <?php echo $patient['blood_group'] === 'A+' ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo $patient['blood_group'] === 'A-' ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo $patient['blood_group'] === 'B+' ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo $patient['blood_group'] === 'B-' ? 'selected' : ''; ?>>B-</option>
                            <option value="O+" <?php echo $patient['blood_group'] === 'O+' ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo $patient['blood_group'] === 'O-' ? 'selected' : ''; ?>>O-</option>
                            <option value="AB+" <?php echo $patient['blood_group'] === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo $patient['blood_group'] === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Height (cm)</label>
                        <input type="number" class="form-control" name="height" step="0.1" min="0" placeholder="175" value="<?php echo floatval($patient['height']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Weight (kg)</label>
                        <input type="number" class="form-control" name="weight" step="0.1" min="0" placeholder="70" value="<?php echo floatval($patient['weight']); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted">Home Address</label>
                        <textarea class="form-control" name="address" rows="2" required><?php echo sanitize($patient['address']); ?></textarea>
                    </div>
                </div>

                <h6 class="fw-bold text-secondary mb-3">Emergency Contact Details</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Contact Name</label>
                        <input type="text" class="form-control" name="emergency_contact_name" required value="<?php echo sanitize($patient['emergency_contact_name']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted">Contact Phone</label>
                        <input type="tel" class="form-control" name="emergency_contact_phone" required value="<?php echo sanitize($patient['emergency_contact_phone']); ?>">
                    </div>
                </div>

                <div class="border-top pt-4">
                    <button type="submit" name="profile_submit" class="btn btn-primary px-5 py-2 shadow-sm"><i class="fa-solid fa-floppy-disk me-2"></i> Save Profile Details</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
