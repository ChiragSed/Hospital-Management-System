<?php
/**
 * Admin Patient Moderator Directory
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('admin');

require_once __DIR__ . '/../config/database.php';

// Handle account status toggles
if (isset($_GET['action']) && isset($_GET['id'])) {
    $pat_id = intval($_GET['id']);
    $action = sanitize($_GET['action']);

    try {
        if ($action === 'deactivate') {
            $stmt = $pdo->prepare("UPDATE patients SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$pat_id]);
            $_SESSION['success_message'] = "Patient account deactivated successfully.";
        } elseif ($action === 'activate') {
            $stmt = $pdo->prepare("UPDATE patients SET status = 'active' WHERE id = ?");
            $stmt->execute([$pat_id]);
            $_SESSION['success_message'] = "Patient account reactivated.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error updating patient account.";
    }
    header("Location: patients.php");
    exit();
}

// Fetch Patients
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$patients = [];

try {
    if (!empty($search_query)) {
        $stmt = $pdo->prepare("
            SELECT * FROM patients 
            WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? 
            ORDER BY name ASC
        ");
        $like = "%" . $search_query . "%";
        $stmt->execute([$like, $like, $like]);
    } else {
        $stmt = $pdo->query("SELECT * FROM patients ORDER BY name ASC");
    }
    $patients = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h3 class="fw-bold text-dark mb-1">Manage Hospital Patients</h3>
        <p class="text-muted">Browse patient records, activate/deactivate account portal access.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <form action="patients.php" method="GET">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search name, email, phone..." value="<?php echo sanitize($search_query); ?>">
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
            <i class="fa-solid fa-users-slash fa-3x mb-3 opacity-30"></i>
            <h5 class="fw-bold mb-1">No Patients Registered</h5>
            <p class="mb-0">We couldn't find any patient records matching that query.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle custom-table mb-0">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Age / Gender</th>
                        <th>Blood Group</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Emergency Contact</th>
                        <th>Status</th>
                        <th class="text-end">Account Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $pat): ?>
                        <?php
                        $statusClass = 'bg-success';
                        if ($pat['status'] === 'inactive') $statusClass = 'bg-secondary';
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($pat['profile_pic'])): ?>
                                        <img src="../uploads/profile_pics/<?php echo $pat['profile_pic']; ?>" class="rounded-circle me-2" alt="Avatar" width="36" height="36" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-primary-subtle text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-size: 0.8rem;">
                                            <i class="fa-solid fa-user-injured"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong class="text-dark d-block"><?php echo sanitize($pat['name']); ?></strong>
                                        <span class="small text-muted" style="font-size:0.75rem;"><?php echo sanitize($pat['email']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo intval($pat['age']); ?> Yrs / <?php echo sanitize($pat['gender']); ?></td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?php echo sanitize($pat['blood_group']); ?></span></td>
                            <td class="small" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize($pat['address']); ?></td>
                            <td class="small"><?php echo sanitize($pat['phone']); ?></td>
                            <td>
                                <div class="small fw-semibold"><?php echo sanitize($pat['emergency_contact_name']); ?></div>
                                <div class="small text-muted" style="font-size:0.75rem;"><i class="fa-solid fa-phone me-1"></i> <?php echo sanitize($pat['emergency_contact_phone']); ?></div>
                            </td>
                            <td><span class="badge <?php echo $statusClass; ?> px-2 py-1"><?php echo ucfirst($pat['status']); ?></span></td>
                            <td class="text-end">
                                <?php if ($pat['status'] === 'active'): ?>
                                    <a href="patients.php?action=deactivate&id=<?php echo $pat['id']; ?>" class="btn btn-outline-secondary btn-sm confirm-action" data-confirm-message="Are you sure you want to suspend this patient account?">
                                        <i class="fa-solid fa-user-slash"></i> Deactivate
                                    </a>
                                <?php else: ?>
                                    <a href="patients.php?action=activate&id=<?php echo $pat['id']; ?>" class="btn btn-outline-success btn-sm">
                                        <i class="fa-solid fa-user-check"></i> Activate
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
