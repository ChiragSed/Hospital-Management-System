<?php
/**
 * Admin Doctor Account Moderator
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('admin');

require_once __DIR__ . '/../config/database.php';
$error = '';
$success = '';

// Handle status updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $doc_id = intval($_GET['id']);
    $action = sanitize($_GET['action']);

    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE doctors SET status = 'approved' WHERE id = ?");
            $stmt->execute([$doc_id]);
            
            // Notify Doctor
            $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('doctor', ?, 'Profile Approved!', 'Your doctor account application has been verified and approved by the director. You can now log in.')");
            $stmtNot->execute([$doc_id]);
            $_SESSION['success_message'] = "Doctor account approved successfully.";
            
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE doctors SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$doc_id]);
            
            // Notify Doctor
            $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('doctor', ?, 'Profile Application Rejected', 'Your credentials verification failed. Please contact clinical support.')");
            $stmtNot->execute([$doc_id]);
            $_SESSION['success_message'] = "Doctor application rejected.";
            
        } elseif ($action === 'deactivate') {
            $stmt = $pdo->prepare("UPDATE doctors SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$doc_id]);
            
            // Notify Doctor
            $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('doctor', ?, 'Account Suspended', 'Your account has been deactivated by the system administrator.')");
            $stmtNot->execute([$doc_id]);
            $_SESSION['success_message'] = "Doctor account deactivated.";
            
        } elseif ($action === 'activate') {
            $stmt = $pdo->prepare("UPDATE doctors SET status = 'approved' WHERE id = ?");
            $stmt->execute([$doc_id]);
            
            // Notify Doctor
            $stmtNot = $pdo->prepare("INSERT INTO notifications (user_type, user_id, title, message) VALUES ('doctor', ?, 'Account Restored', 'Your professional access has been reactivated.')");
            $stmtNot->execute([$doc_id]);
            $_SESSION['success_message'] = "Doctor account reactivated.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Database error executing account update.";
    }
    header("Location: doctors.php");
    exit();
}

// Fetch Doctors list
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$doctors = [];

try {
    if (!empty($search_query)) {
        $stmt = $pdo->prepare("
            SELECT d.*, dept.name AS dept_name 
            FROM doctors d 
            LEFT JOIN departments dept ON d.department_id = dept.id 
            WHERE d.name LIKE ? OR dept.name LIKE ?
            ORDER BY CASE WHEN d.status = 'pending' THEN 1 ELSE 2 END, d.name ASC
        ");
        $like = "%" . $search_query . "%";
        $stmt->execute([$like, $like]);
    } else {
        $stmt = $pdo->query("
            SELECT d.*, dept.name AS dept_name 
            FROM doctors d 
            LEFT JOIN departments dept ON d.department_id = dept.id 
            ORDER BY CASE WHEN d.status = 'pending' THEN 1 ELSE 2 END, d.name ASC
        ");
    }
    $doctors = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h3 class="fw-bold text-dark mb-1">Manage Clinical Doctors</h3>
        <p class="text-muted">Approve pending applications, suspend/deactivate accounts, and edit details.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <form action="doctors.php" method="GET">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search doctor or department..." value="<?php echo sanitize($search_query); ?>">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if (!empty($search_query)): ?>
                    <a href="doctors.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
    <?php if (empty($doctors)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-user-slash fa-3x mb-3 opacity-30"></i>
            <h5 class="fw-bold mb-1">No Doctors Listed</h5>
            <p class="mb-0">We couldn't find any doctor records matched in the directory.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle custom-table mb-0">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Department</th>
                        <th>Credentials</th>
                        <th>Experience / Fee</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 25%;">Moderator Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctors as $doc): ?>
                        <?php
                        $statusClass = 'bg-warning text-dark';
                        if ($doc['status'] === 'approved') $statusClass = 'bg-success';
                        elseif ($doc['status'] === 'rejected') $statusClass = 'bg-danger';
                        elseif ($doc['status'] === 'inactive') $statusClass = 'bg-secondary';
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($doc['profile_pic'])): ?>
                                        <img src="../uploads/profile_pics/<?php echo $doc['profile_pic']; ?>" class="rounded-circle me-2" alt="Avatar" width="36" height="36" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-primary-subtle text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 0.8rem;">
                                            <i class="fa-solid fa-user-doctor"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong class="text-dark d-block"><?php echo sanitize($doc['name']); ?></strong>
                                        <span class="small text-muted" style="font-size:0.75rem;"><?php echo sanitize($doc['email']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="fw-semibold"><?php echo sanitize($doc['dept_name'] ?? 'Unassigned'); ?></span></td>
                            <td>
                                <div class="small fw-semibold"><?php echo sanitize($doc['specialization']); ?></div>
                                <div class="small text-muted" style="font-size:0.75rem;"><?php echo sanitize($doc['qualification']); ?></div>
                            </td>
                            <td>
                                <div class="small"><?php echo intval($doc['experience']); ?> Yrs Exp</div>
                                <div class="small fw-bold text-dark">Rs. <?php echo number_format(floatval($doc['consultation_fee'])); ?> Fee</div>
                            </td>
                            <td class="small"><?php echo sanitize($doc['phone']); ?></td>
                            <td><span class="badge <?php echo $statusClass; ?> px-2 py-1"><?php echo ucfirst($doc['status']); ?></span></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <?php if ($doc['status'] === 'pending'): ?>
                                        <a href="doctors.php?action=approve&id=<?php echo $doc['id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-check"></i> Approve
                                        </a>
                                        <a href="doctors.php?action=reject&id=<?php echo $doc['id']; ?>" class="btn btn-outline-danger btn-sm confirm-action" data-confirm-message="Are you sure you want to reject this doctor credentials?">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </a>
                                    <?php elseif ($doc['status'] === 'approved'): ?>
                                        <a href="doctors.php?action=deactivate&id=<?php echo $doc['id']; ?>" class="btn btn-outline-secondary btn-sm confirm-action" data-confirm-message="Deactivate this doctor profile? They will not be able to log in.">
                                            <i class="fa-solid fa-ban"></i> Deactivate
                                        </a>
                                    <?php else: ?>
                                        <a href="doctors.php?action=activate&id=<?php echo $doc['id']; ?>" class="btn btn-outline-success btn-sm">
                                            <i class="fa-solid fa-circle-check"></i> Activate
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
