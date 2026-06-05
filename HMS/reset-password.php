<?php
/**
 * Reset Password Page
 * Hospital Management System
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$error = '';
$success = '';

$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';
$email = isset($_GET['email']) ? sanitize($_GET['email']) : '';

// Validate simulation token
if (empty($token) || empty($email) || !isset($_SESSION['reset_token']) || !isset($_SESSION['reset_email']) ||
    $token !== $_SESSION['reset_token'] || $email !== $_SESSION['reset_email']) {
    $error = "This password reset link is invalid or has expired. Please request a new one.";
}

// Handle Password Update Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_submit'])) {
    if ($error === '') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($password) || empty($confirm_password)) {
            $error = "All fields are required.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            try {
                require_once __DIR__ . '/config/database.php';
                $role = $_SESSION['reset_role'];
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                if ($role === 'patient') {
                    $stmt = $pdo->prepare("UPDATE patients SET password = ? WHERE email = ?");
                    $stmt->execute([$hashed_password, $email]);
                } elseif ($role === 'doctor') {
                    $stmt = $pdo->prepare("UPDATE doctors SET password = ? WHERE email = ?");
                    $stmt->execute([$hashed_password, $email]);
                } elseif ($role === 'admin') {
                    $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
                    $stmt->execute([$hashed_password, $email]);
                }

                // Clear session tokens
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_role']);

                $_SESSION['success_message'] = "Password has been updated successfully. You can now log in.";
                header("Location: login.php?role=" . $role);
                exit();
            } catch (Exception $e) {
                $error = "An error occurred while updating the password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifeLine Portal - New Password</title>
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
                    <span class="text-white d-inline-flex align-items-center mb-2">
                        <i class="fa-solid fa-key fa-2x me-2 text-secondary"></i>
                        <span class="fs-4 fw-bold text-uppercase tracking-wider">LifeLine</span>
                    </span>
                    <h3 class="fw-bold mb-0">Set New Password</h3>
                    <p class="small opacity-75 mb-0 mt-1">Please enter your new secure password below</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (!empty($error) && !isset($_POST['reset_submit'])): ?>
                        <div class="alert alert-danger border-0 small mb-4" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
                        </div>
                        <div class="text-center">
                            <a href="forgot-password.php" class="btn btn-outline-primary btn-sm fw-bold"><i class="fa-solid fa-rotate-left me-1"></i> Request New Link</a>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger border-0 small mb-4" role="alert">
                                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form action="reset-password.php?token=<?php echo $token; ?>&email=<?php echo urlencode($email); ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-secondary">New Password</label>
                                <input type="password" class="form-control" name="password" required placeholder="Min 6 characters">
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-secondary">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required placeholder="Retype new password">
                            </div>

                            <button type="submit" name="reset_submit" class="btn btn-primary w-100 py-3 shadow-sm"><i class="fa-solid fa-check me-2"></i> Update Password</button>
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
