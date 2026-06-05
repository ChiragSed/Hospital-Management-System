<?php
/**
 * Unified Login Page
 * Hospital Management System
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: " . $_SESSION['user_type'] . "/dashboard.php");
    exit();
}

$active_tab = isset($_GET['role']) && in_array($_GET['role'], ['patient', 'doctor', 'admin']) ? $_GET['role'] : 'patient';
$error = '';

// Handle POST authentication request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed. Please try again.";
    } else {
        // Require database config
        require_once __DIR__ . '/config/database.php';

        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $role = sanitize($_POST['role']);
        $active_tab = $role; // Persist tab on error

        if (empty($email) || empty($password) || empty($role)) {
            $error = "All fields are required.";
        } else {
            try {
                if ($role === 'patient') {
                    $stmt = $pdo->prepare("SELECT * FROM patients WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password'])) {
                        if ($user['status'] !== 'active') {
                            $error = "Your account is currently inactive. Please contact support.";
                        } else {
                            // Log in patient
                            set_user_session($user, 'patient');
                            $_SESSION['success_message'] = "Welcome back, " . $user['name'] . "!";
                            header("Location: patient/dashboard.php");
                            exit();
                        }
                    } else {
                        $error = "Invalid email address or password.";
                    }
                    
                } elseif ($role === 'doctor') {
                    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password'])) {
                        if ($user['status'] === 'pending') {
                            $error = "Your account is pending administrator verification and approval.";
                        } elseif ($user['status'] === 'rejected') {
                            $error = "Your professional account application was rejected.";
                        } elseif ($user['status'] === 'inactive') {
                            $error = "Your doctor account is currently deactivated.";
                        } else {
                            // Log in doctor
                            set_user_session($user, 'doctor');
                            $_SESSION['success_message'] = "Good day, " . $user['name'] . "!";
                            header("Location: doctor/dashboard.php");
                            exit();
                        }
                    } else {
                        $error = "Invalid email address or password.";
                    }
                    
                } elseif ($role === 'admin') {
                    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password'])) {
                        if ($user['status'] !== 'active') {
                            $error = "Your admin access has been disabled.";
                        } else {
                            // Log in admin
                            set_user_session($user, 'admin');
                            $_SESSION['success_message'] = "System Administrator session initiated.";
                            header("Location: admin/dashboard.php");
                            exit();
                        }
                    } else {
                        $error = "Invalid email address or password.";
                    }
                }
            } catch (Exception $e) {
                $error = "An internal error occurred. Please make sure database setup is completed.";
            }
        }
    }
}

/**
 * Bind session variables upon login
 */
function set_user_session($user, $type) {
    session_regenerate_id(true); // Prevent Session Fixation
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $type;
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['profile_pic'] = isset($user['profile_pic']) ? $user['profile_pic'] : null;
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLine Portal - Log In</title>
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
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(42, 157, 143, 0.04);
            background: white;
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            border: 1px solid var(--border-color);
        }
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 40px 30px;
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
        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            background-color: #fcfdfd;
        }
        .form-control:focus {
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
            <div class="login-card mx-auto">
                <div class="login-header">
                    <a href="index.php" class="text-white text-decoration-none d-inline-flex align-items-center mb-3">
                        <i class="fa-solid fa-hospital-user fa-2x me-2 text-secondary"></i>
                        <span class="fs-4 fw-bold text-uppercase tracking-wider">LifeLine</span>
                    </a>
                    <h3 class="fw-bold mb-0">Secure Portal Log In</h3>
                    <p class="small opacity-75 mb-0 mt-1">Select your account type below to sign in</p>
                </div>
                
                <div class="p-4 p-md-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger border-0 small mb-4" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success border-0 small mb-4" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Role Pill Buttons -->
                    <ul class="nav nav-pills nav-fill nav-pills-custom mb-4 bg-light p-1 rounded-3" id="loginTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 <?php echo $active_tab === 'patient' ? 'active' : ''; ?>" id="patient-tab" data-role="patient" type="button" role="tab"><i class="fa-solid fa-user-injured me-1"></i> Patient</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 <?php echo $active_tab === 'doctor' ? 'active' : ''; ?>" id="doctor-tab" data-role="doctor" type="button" role="tab"><i class="fa-solid fa-user-doctor me-1"></i> Doctor</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link w-100 <?php echo $active_tab === 'admin' ? 'active' : ''; ?>" id="admin-tab" data-role="admin" type="button" role="tab"><i class="fa-solid fa-user-shield me-1"></i> Admin</button>
                        </li>
                    </ul>

                    <!-- Login Form -->
                    <form action="login.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="role" id="selectedRole" value="<?php echo $active_tab; ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                                <input type="email" class="form-control border-start-0" name="email" required placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-secondary mb-0">Password</label>
                                <a href="forgot-password.php" class="small text-decoration-none">Forgot Password?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                                <input type="password" class="form-control border-start-0" name="password" required placeholder="••••••••">
                            </div>
                        </div>

                        <button type="submit" name="login_submit" class="btn btn-primary w-100 py-3 mb-3 shadow-sm"><i class="fa-solid fa-right-to-bracket me-2"></i> Sign In</button>

                        <div class="text-center pt-2" id="registerPrompt">
                            <span class="small text-muted">New Patient? <a href="register.php" class="text-decoration-none fw-semibold">Create Account</a></span>
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
    // Tab selector script updating selected role
    const tabButtons = document.querySelectorAll('#loginTabs button');
    const roleInput = document.getElementById('selectedRole');
    const registerPrompt = document.getElementById('registerPrompt');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active from all
            tabButtons.forEach(btn => btn.classList.remove('active'));
            // Add active to current
            this.classList.add('active');
            
            const role = this.getAttribute('data-role');
            roleInput.value = role;

            // Toggle patient registration suggestion
            if (role === 'patient') {
                registerPrompt.style.display = 'block';
            } else if (role === 'doctor') {
                registerPrompt.innerHTML = '<span class="small text-muted">Want to join our clinical team? <a href="register.php?role=doctor" class="text-decoration-none fw-semibold">Apply Here</a></span>';
                registerPrompt.style.display = 'block';
            } else {
                registerPrompt.style.display = 'none';
            }
        });
    });

    // Run layout initializer based on loaded query parameter
    window.addEventListener('DOMContentLoaded', () => {
        const activeRole = roleInput.value;
        const activeBtn = document.querySelector(`#loginTabs button[data-role="${activeRole}"]`);
        if (activeBtn) {
            activeBtn.click();
        }
    });
</script>
</body>
</html>
