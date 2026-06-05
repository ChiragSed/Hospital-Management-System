<?php
/**
 * Forgot Password Page
 * Hospital Management System
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$success = '';
$error = '';
$demo_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        require_once __DIR__ . '/config/database.php';
        $email = sanitize($_POST['email']);

        if (empty($email)) {
            $error = "Email address is required.";
        } else {
            try {
                // Check if email exists
                $stmtPat = $pdo->prepare("SELECT id, name FROM patients WHERE email = ?");
                $stmtPat->execute([$email]);
                $user = $stmtPat->fetch();
                $role = 'patient';

                if (!$user) {
                    $stmtDoc = $pdo->prepare("SELECT id, name FROM doctors WHERE email = ?");
                    $stmtDoc->execute([$email]);
                    $user = $stmtDoc->fetch();
                    $role = 'doctor';
                }

                if (!$user) {
                    $stmtAdm = $pdo->prepare("SELECT id, name FROM admins WHERE email = ?");
                    $stmtAdm->execute([$email]);
                    $user = $stmtAdm->fetch();
                    $role = 'admin';
                }

                if ($user) {
                    // Generate secure mock token
                    $token = bin2hex(random_bytes(16));
                    
                    // In a live server, we would save this token to a `password_resets` table with an expiry date
                    // and send it via mail(). For this demonstration, we store it in the session
                    // and output a clickable demo link.
                    $_SESSION['reset_token'] = $token;
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['reset_role'] = $role;

                    $success = "A secure password recovery email has been dispatched to your address.";
                    $demo_link = "reset-password.php?token={$token}&email=" . urlencode($email);
                } else {
                    $error = "We could not find an account associated with that email address.";
                }
            } catch (Exception $e) {
                $error = "Database error. Please run the setup utility.";
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
    <title>LifeLine Portal - Password Reset</title>
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
        .reset-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(42, 157, 143, 0.04);
            background: white;
            overflow: hidden;
            width: 100%;
            max-width: 480px;
            border: 1px solid var(--border-color);
        }
        .reset-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            padding: 35px;
            text-align: center;
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
            <div class="reset-card mx-auto">
                <div class="reset-header">
                    <a href="index.php" class="text-white text-decoration-none d-inline-flex align-items-center mb-2">
                        <i class="fa-solid fa-hospital-user fa-2x me-2 text-secondary"></i>
                        <span class="fs-4 fw-bold text-uppercase tracking-wider">LifeLine</span>
                    </a>
                    <h3 class="fw-bold mb-0">Recover Password</h3>
                    <p class="small opacity-75 mb-0 mt-1">Enter your registered email to receive reset instructions</p>
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
                        <?php if (!empty($demo_link)): ?>
                            <div class="alert alert-info border-0 small mb-4" role="alert">
                                <i class="fa-solid fa-circle-info me-2"></i> <strong>Demo Simulator:</strong><br>
                                <span class="text-muted d-block mb-2">Since this is running locally, click the simulation link below to open the reset form:</span>
                                <a href="<?php echo $demo_link; ?>" class="btn btn-outline-info btn-sm fw-bold"><i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Reset Form</a>
                            </div>
                        <?php endif; ?>
                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none small fw-semibold"><i class="fa-solid fa-arrow-left me-1"></i> Back to Log In</a>
                        </div>
                    <?php else: ?>
                        <form action="forgot-password.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-secondary">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                                    <input type="email" class="form-control border-start-0" name="email" required placeholder="name@example.com">
                                </div>
                            </div>

                            <button type="submit" name="forgot_submit" class="btn btn-primary w-100 py-3 mb-3 shadow-sm"><i class="fa-solid fa-paper-plane me-2"></i> Send Reset Link</button>

                            <div class="text-center pt-2">
                                <a href="login.php" class="text-decoration-none small fw-semibold"><i class="fa-solid fa-arrow-left me-1"></i> Back to Log In</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
