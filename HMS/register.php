<?php
/**
 * Role-based Registration Portal
 * Hospital Management System
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (is_logged_in()) {
    header("Location: " . $_SESSION['user_type'] . "/dashboard.php");
    exit();
}

$active_tab = isset($_GET['role']) && $_GET['role'] === 'doctor' ? 'doctor' : 'patient';
$error = '';
$success = '';

// Load departments for doctor sign-up
$departments = [];
try {
    require_once __DIR__ . '/config/database.php';
    $departments = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    // Fail silently, use fallback below if empty
}

if (empty($departments)) {
    $departments = [
        ['id' => 1, 'name' => 'Cardiology'],
        ['id' => 2, 'name' => 'Neurology'],
        ['id' => 3, 'name' => 'Orthopedics'],
        ['id' => 4, 'name' => 'Pediatrics'],
        ['id' => 5, 'name' => 'Dermatology'],
        ['id' => 6, 'name' => 'General Medicine']
    ];
}

// Handle Registration Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed. Please try again.";
    } else {
        require_once __DIR__ . '/config/database.php';
        $role = sanitize($_POST['role']);
        $active_tab = $role;

        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
            $error = "All standard fields are required.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            try {
                // Check if email already exists in admins, doctors, or patients
                $stmtCheckAdmin = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
                $stmtCheckAdmin->execute([$email]);
                $stmtCheckDoc = $pdo->prepare("SELECT id FROM doctors WHERE email = ?");
                $stmtCheckDoc->execute([$email]);
                $stmtCheckPat = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
                $stmtCheckPat->execute([$email]);

                if ($stmtCheckAdmin->rowCount() > 0 || $stmtCheckDoc->rowCount() > 0 || $stmtCheckPat->rowCount() > 0) {
                    $error = "This email address is already registered.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    if ($role === 'patient') {
                        $age = intval($_POST['age']);
                        $gender = sanitize($_POST['gender']);
                        $blood_group = sanitize($_POST['blood_group']);
                        $address = sanitize($_POST['address']);
                        $emergency_name = sanitize($_POST['emergency_contact_name']);
                        $emergency_phone = sanitize($_POST['emergency_contact_phone']);

                        if ($age <= 0 || empty($gender) || empty($blood_group) || empty($address) || empty($emergency_name) || empty($emergency_phone)) {
                            $error = "All patient details are required.";
                        } else {
                            // Insert Patient
                            $stmt = $pdo->prepare("
                                INSERT INTO patients (name, email, password, age, gender, blood_group, phone, address, emergency_contact_name, emergency_contact_phone, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
                            ");
                            $stmt->execute([$name, $email, $hashed_password, $age, $gender, $blood_group, $phone, $address, $emergency_name, $emergency_phone]);
                            $patient_id = $pdo->lastInsertId();

                            // Create Welcome Notification
                            $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('patient', ?, 'Welcome to LifeLine!', 'Your patient account has been created successfully. You can now log in and book appointments.')");
                            $stmtNotif->execute([$patient_id]);

                            $success = "Registration successful! You can now log in using the Patient tab.";
                            // Reset form fields
                            $_POST = array();
                        }
                    } elseif ($role === 'doctor') {
                        $dept_id = intval($_POST['department_id']);
                        $qualification = sanitize($_POST['qualification']);
                        $specialization = sanitize($_POST['specialization']);
                        $experience = intval($_POST['experience']);
                        $fee = floatval($_POST['consultation_fee']);

                        if ($dept_id <= 0 || empty($qualification) || empty($specialization) || $experience < 0 || $fee < 0) {
                            $error = "All clinical credentials are required.";
                        } else {
                            // Insert Doctor with pending status
                            $stmt = $pdo->prepare("
                                INSERT INTO doctors (name, email, password, phone, department_id, qualification, specialization, experience, consultation_fee, status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                            ");
                            $stmt->execute([$name, $email, $hashed_password, $phone, $dept_id, $qualification, $specialization, $experience, $fee]);
                            $doctor_id = $pdo->lastInsertId();

                            // Notify Admin
                            $stmtNotif = $pdo->prepare("
                                INSERT INTO notifications (user_type, user_id, title, message) 
                                VALUES ('admin', 1, 'New Doctor Verification Required', ?)
                            ");
                            $stmtNotif->execute(["Dr. {$name} has registered and is pending approval."]);

                            $success = "Application submitted successfully! Your credentials are now pending verification by an administrator.";
                            // Reset form fields
                            $_POST = array();
                        }
                    }
                }
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage() . ". Ensure database has been set up.";
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLine Portal - Create Account</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--body-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(42, 157, 143, 0.04);
            background: white;
            overflow: hidden;
            width: 100%;
            max-width: 650px;
            border: 1px solid var(--border-color);
        }
        .register-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 35px;
            text-align: center;
        }
        .nav-pills-custom .nav-link {
            border-radius: 8px;
            color: var(--text-secondary);
            font-weight: 600;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        .nav-pills-custom .nav-link.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            background-color: #fcfdfd;
        }
        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 4px rgba(42, 157, 143, 0.12);
            border-color: var(--primary-color);
            background-color: #ffffff;
        }
        .btn-primary {
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            background-color: var(--primary-color);
            border: none;
            box-shadow: 0 4px 12px rgba(42, 157, 143, 0.15);
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
            box-shadow: 0 6px 16px rgba(42, 157, 143, 0.25);
        }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 px-3">
            <div class="register-card mx-auto">
                <div class="register-header">
                    <a href="index.php" class="text-white text-decoration-none d-inline-flex align-items-center mb-2">
                        <i class="fa-solid fa-hospital-user fa-2x me-2 text-secondary"></i>
                        <span class="fs-4 fw-bold text-uppercase tracking-wider">LifeLine</span>
                    </a>
                    <h3 class="fw-bold mb-0">Portal Registration</h3>
                    <p class="small opacity-75 mb-0 mt-1">Submit credentials to register your portal account</p>
                </div>

                <div class="p-4 p-md-5">
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

                    <!-- Role Pill Selection -->
                    <ul class="nav nav-pills nav-fill nav-pills-custom mb-4 bg-light p-1 rounded-3" id="regTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 <?php echo $active_tab === 'patient' ? 'active' : ''; ?>" id="patient-reg-tab" data-role="patient" type="button" role="tab"><i class="fa-solid fa-user-injured me-1"></i> Register Patient</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 <?php echo $active_tab === 'doctor' ? 'active' : ''; ?>" id="doctor-reg-tab" data-role="doctor" type="button" role="tab"><i class="fa-solid fa-user-doctor me-1"></i> Register Doctor</button>
                        </li>
                    </ul>

                    <form action="register.php" method="POST" id="regForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="role" id="selectedRole" value="<?php echo $active_tab; ?>">

                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">General Information</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Full Name</label>
                                <input type="text" class="form-control" name="name" required placeholder="John Doe" value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Email Address</label>
                                <input type="email" class="form-control" name="email" required placeholder="john@example.com" value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Phone Number</label>
                                <input type="tel" class="form-control" name="phone" required placeholder="+1 (555) 123-4567" value="<?php echo isset($_POST['phone']) ? sanitize($_POST['phone']) : ''; ?>">
                            </div>
                        </div>

                        <!-- Patient Specific Section -->
                        <div id="patientFields" style="display: <?php echo $active_tab === 'patient' ? 'block' : 'none'; ?>;">
                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Patient Health Profile</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">Age</label>
                                    <input type="number" class="form-control" name="age" min="1" max="120" placeholder="25" value="<?php echo isset($_POST['age']) ? intval($_POST['age']) : ''; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">Gender</label>
                                    <select class="form-select" name="gender">
                                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-secondary">Blood Group</label>
                                    <select class="form-select" name="blood_group">
                                        <option value="A+" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] === 'A+') ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] === 'A-') ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] === 'B+') ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] === 'B-') ? 'selected' : ''; ?>>B-</option>
                                        <option value="O+" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] === 'O+') ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] === 'O-') ? 'selected' : ''; ?>>O-</option>
                                        <option value="AB+" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-secondary">Residential Address</label>
                                    <textarea class="form-control" name="address" rows="2" placeholder="Baneshwor, Kathmandu, Nepal"><?php echo isset($_POST['address']) ? sanitize($_POST['address']) : ''; ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Emergency Contact Name</label>
                                    <input type="text" class="form-control" name="emergency_contact_name" placeholder="Sujata Sharma" value="<?php echo isset($_POST['emergency_contact_name']) ? sanitize($_POST['emergency_contact_name']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Emergency Contact Phone</label>
                                    <input type="tel" class="form-control" name="emergency_contact_phone" placeholder="+977-9801234567" value="<?php echo isset($_POST['emergency_contact_phone']) ? sanitize($_POST['emergency_contact_phone']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Doctor Specific Section -->
                        <div id="doctorFields" style="display: <?php echo $active_tab === 'doctor' ? 'block' : 'none'; ?>;">
                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Clinical Credentials</h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Assigned Department</label>
                                    <select class="form-select" name="department_id">
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>><?php echo sanitize($dept['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Medical Qualification</label>
                                    <input type="text" class="form-control" name="qualification" placeholder="MD - Cardiology, FACC" value="<?php echo isset($_POST['qualification']) ? sanitize($_POST['qualification']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-secondary">Clinical Specialization</label>
                                    <input type="text" class="form-control" name="specialization" placeholder="Interventional Cardiology" value="<?php echo isset($_POST['specialization']) ? sanitize($_POST['specialization']) : ''; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Experience (Years)</label>
                                    <input type="number" class="form-control" name="experience" min="0" max="60" placeholder="10" value="<?php echo isset($_POST['experience']) ? intval($_POST['experience']) : ''; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-secondary">Consultation Fee ($)</label>
                                    <input type="number" class="form-control" name="consultation_fee" min="0" step="10" placeholder="100" value="<?php echo isset($_POST['consultation_fee']) ? floatval($_POST['consultation_fee']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Security & Sign In</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Password</label>
                                <input type="password" class="form-control" name="password" required placeholder="Min 6 characters">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-secondary">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" required placeholder="Retype password">
                            </div>
                        </div>

                        <button type="submit" name="register_submit" class="btn btn-primary w-100 py-3 mb-3 shadow-sm"><i class="fa-solid fa-user-plus me-2"></i> Register Account</button>

                        <div class="text-center pt-2">
                            <span class="small text-muted">Already have a portal account? <a href="login.php" class="text-decoration-none fw-semibold">Sign In Here</a></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const tabs = document.querySelectorAll('#regTabs button');
    const roleInput = document.getElementById('selectedRole');
    const patientFields = document.getElementById('patientFields');
    const doctorFields = document.getElementById('doctorFields');
    
    // Select inputs to dynamically toggle standard HTML required attributes based on selected tab
    const pInputs = patientFields.querySelectorAll('input, select, textarea');
    const dInputs = doctorFields.querySelectorAll('input, select');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const role = this.getAttribute('data-role');
            roleInput.value = role;

            if (role === 'patient') {
                patientFields.style.display = 'block';
                doctorFields.style.display = 'none';
                
                // Toggle required tags
                pInputs.forEach(i => i.setAttribute('required', ''));
                dInputs.forEach(i => i.removeAttribute('required'));
            } else {
                patientFields.style.display = 'none';
                doctorFields.style.display = 'block';
                
                pInputs.forEach(i => i.removeAttribute('required'));
                dInputs.forEach(i => i.setAttribute('required', ''));
            }
        });
    });

    // Run layout initializer on load
    window.addEventListener('DOMContentLoaded', () => {
        const activeRole = roleInput.value;
        const activeBtn = document.querySelector(`#regTabs button[data-role="${activeRole}"]`);
        if (activeBtn) {
            activeBtn.click();
        }
    });
</script>
</body>
</html>
