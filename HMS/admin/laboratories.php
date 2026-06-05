<?php
/**
 * Admin Laboratory & Tests Pricing CMS
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('admin');

require_once __DIR__ . '/../config/database.php';
$error = '';
$success = '';

// Edit laboratory variables
$edit_lab_mode = false;
$edit_lab_id = 0;
$edit_lab_name = '';
$edit_lab_city = '';
$edit_lab_address = '';
$edit_lab_phone = '';

// Edit test variables
$edit_test_mode = false;
$edit_test_id = 0;
$edit_test_lab_id = 0;
$edit_test_name = '';
$edit_test_price = 0.00;
$edit_test_desc = '';

// Check edit triggers
if (isset($_GET['edit_lab'])) {
    $edit_lab_id = intval($_GET['edit_lab']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM laboratories WHERE id = ?");
        $stmt->execute([$edit_lab_id]);
        $lab = $stmt->fetch();
        if ($lab) {
            $edit_lab_mode = true;
            $edit_lab_name = $lab['name'];
            $edit_lab_city = $lab['city'];
            $edit_lab_address = $lab['address'];
            $edit_lab_phone = $lab['phone'];
        }
    } catch (Exception $e) {}
}

if (isset($_GET['edit_test'])) {
    $edit_test_id = intval($_GET['edit_test']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM lab_tests WHERE id = ?");
        $stmt->execute([$edit_test_id]);
        $tst = $stmt->fetch();
        if ($tst) {
            $edit_test_mode = true;
            $edit_test_lab_id = $tst['lab_id'];
            $edit_test_name = $tst['test_name'];
            $edit_test_price = $tst['price'];
            $edit_test_desc = $tst['description'];
        }
    } catch (Exception $e) {}
}

// Handle deletions
if (isset($_GET['delete_lab'])) {
    $delete_lab_id = intval($_GET['delete_lab']);
    try {
        $stmt = $pdo->prepare("DELETE FROM laboratories WHERE id = ?");
        $stmt->execute([$delete_lab_id]);
        $_SESSION['success_message'] = "Laboratory deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Cannot delete lab. Active tests or bookings are linked to it.";
    }
    header("Location: laboratories.php");
    exit();
}

if (isset($_GET['delete_test'])) {
    $delete_test_id = intval($_GET['delete_test']);
    try {
        $stmt = $pdo->prepare("DELETE FROM lab_tests WHERE id = ?");
        $stmt->execute([$delete_test_id]);
        $_SESSION['success_message'] = "Lab test deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Cannot delete. Test bookings are active for this diagnostic panel.";
    }
    header("Location: laboratories.php");
    exit();
}

// Handle Lab Submit (Insert / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lab_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $name = sanitize($_POST['lab_name']);
        $city = sanitize($_POST['lab_city']);
        $address = sanitize($_POST['lab_address']);
        $phone = sanitize($_POST['lab_phone']);
        $db_id = intval($_POST['db_id'] ?? 0);

        if (empty($name) || empty($city) || empty($address) || empty($phone)) {
            $error = "All laboratory details are required.";
        } else {
            try {
                if ($db_id > 0) {
                    $stmt = $pdo->prepare("UPDATE laboratories SET name = ?, city = ?, address = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$name, $city, $address, $phone, $db_id]);
                    $success = "Laboratory center updated successfully!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO laboratories (name, city, address, phone) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $city, $address, $phone]);
                    $success = "Laboratory center registered!";
                }
                $_POST = array();
                $edit_lab_mode = false;
            } catch (Exception $e) {
                $error = "Error saving laboratory details.";
            }
        }
    }
}

// Handle Test Submit (Insert / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $lab_id = intval($_POST['lab_id']);
        $name = sanitize($_POST['test_name']);
        $price = floatval($_POST['test_price']);
        $desc = sanitize($_POST['description']);
        $db_id = intval($_POST['db_id'] ?? 0);

        if ($lab_id <= 0 || empty($name) || $price < 0) {
            $error = "Test name, laboratory mapping, and price are required.";
        } else {
            try {
                if ($db_id > 0) {
                    $stmt = $pdo->prepare("UPDATE lab_tests SET lab_id = ?, test_name = ?, price = ?, description = ? WHERE id = ?");
                    $stmt->execute([$lab_id, $name, $price, $desc, $db_id]);
                    $success = "Lab test configurations updated!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO lab_tests (lab_id, test_name, price, description) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$lab_id, $name, $price, $desc]);
                    $success = "Lab test registered successfully!";
                }
                $_POST = array();
                $edit_test_mode = false;
            } catch (Exception $e) {
                $error = "Error registering lab test details.";
            }
        }
    }
}

// Query all labs and tests
$laboratories = [];
$lab_tests = [];

try {
    $laboratories = $pdo->query("SELECT * FROM laboratories ORDER BY city ASC, name ASC")->fetchAll();
    
    $lab_tests = $pdo->query("
        SELECT lt.*, l.name AS lab_name, l.city 
        FROM lab_tests lt
        JOIN laboratories l ON lt.lab_id = l.id
        ORDER BY l.name ASC, lt.test_name ASC
    ")->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Manage Diagnostic Laboratories</h3>
        <p class="text-muted">Register diagnostics labs across cities, set test panels and pricing rates.</p>
    </div>
</div>

<div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 16px;">
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

    <!-- Section 1: Laboratories Manager -->
    <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-solid fa-flask text-primary me-2"></i>Laboratory Centers</h5>
    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="border p-3 rounded" style="background-color: #f8fafc;">
                <h6 class="fw-bold text-dark border-bottom pb-1 mb-3"><?php echo $edit_lab_mode ? 'Edit Laboratory' : 'Add Laboratory'; ?></h6>
                <form action="laboratories.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="db_id" value="<?php echo $edit_lab_id; ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Laboratory Name</label>
                        <input type="text" class="form-control form-control-sm" name="lab_name" required placeholder="e.g. Metro Diagnostic Lab" value="<?php echo sanitize($edit_lab_name); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Location City</label>
                        <input type="text" class="form-control form-control-sm" name="lab_city" required placeholder="e.g. Kathmandu" value="<?php echo sanitize($edit_lab_city); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Contact Phone</label>
                        <input type="tel" class="form-control form-control-sm" name="lab_phone" required placeholder="+977-1-4433221" value="<?php echo sanitize($edit_lab_phone); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Physical Address</label>
                        <textarea class="form-control form-control-sm" name="lab_address" rows="2" required placeholder="Ring Road, Kathmandu"><?php echo sanitize($edit_lab_address); ?></textarea>
                    </div>
                    <button type="submit" name="lab_submit" class="btn btn-primary btn-sm w-100 py-2 fw-semibold">Save Center Details</button>
                    <?php if ($edit_lab_mode): ?>
                        <a href="laboratories.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if (empty($laboratories)): ?>
                <span class="text-muted small">No laboratories registered.</span>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-hover align-middle custom-table mb-0" style="font-size:0.85rem;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>City</th>
                                <th>Address</th>
                                <th>Phone</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($laboratories as $l): ?>
                                <tr>
                                    <td><strong><?php echo sanitize($l['name']); ?></strong></td>
                                    <td><?php echo sanitize($l['city']); ?></td>
                                    <td class="text-muted"><?php echo sanitize($l['address']); ?></td>
                                    <td><?php echo sanitize($l['phone']); ?></td>
                                    <td class="text-end">
                                        <a href="laboratories.php?edit_lab=<?php echo $l['id']; ?>" class="btn btn-outline-primary btn-xs py-0 px-2"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <a href="laboratories.php?delete_lab=<?php echo $l['id']; ?>" class="btn btn-outline-danger btn-xs py-0 px-2 confirm-action" data-confirm-message="Delete this laboratory center? Note: Linked tests will be deleted."><i class="fa-regular fa-trash-can"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section 2: Lab Tests / Pricing Manager -->
    <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-solid fa-tags text-primary me-2"></i>Diagnostic Tests Catalog</h5>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="border p-3 rounded" style="background-color: #f8fafc;">
                <h6 class="fw-bold text-dark border-bottom pb-1 mb-3"><?php echo $edit_test_mode ? 'Edit Diagnostic Test' : 'Add Diagnostic Test'; ?></h6>
                <form action="laboratories.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="db_id" value="<?php echo $edit_test_id; ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Laboratory Location Map</label>
                        <select class="form-select form-select-sm" name="lab_id" required>
                            <option value="">-- Choose Center --</option>
                            <?php foreach ($laboratories as $l): ?>
                                <option value="<?php echo $l['id']; ?>" <?php echo $edit_test_lab_id == $l['id'] ? 'selected' : ''; ?>><?php echo sanitize($l['name']); ?> (<?php echo sanitize($l['city']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Test Name</label>
                        <input type="text" class="form-control form-control-sm" name="test_name" required placeholder="e.g. Lipid Profile" value="<?php echo sanitize($edit_test_name); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Billing Price (Rs.)</label>
                        <input type="number" class="form-control form-control-sm" name="test_price" required min="0" step="0.5" placeholder="45.00" value="<?php echo floatval($edit_test_price); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Test Description</label>
                        <textarea class="form-control form-control-sm" name="description" rows="2" placeholder="Describe measures and preparation required..."><?php echo sanitize($edit_test_desc); ?></textarea>
                    </div>
                    <button type="submit" name="test_submit" class="btn btn-primary btn-sm w-100 py-2 fw-semibold">Save Test Details</button>
                    <?php if ($edit_test_mode): ?>
                        <a href="laboratories.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <?php if (empty($lab_tests)): ?>
                <span class="text-muted small">No diagnostic tests registered.</span>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover align-middle custom-table mb-0" style="font-size:0.85rem;">
                        <thead>
                            <tr>
                                <th>Diagnostic Center</th>
                                <th>Test name</th>
                                <th>Price</th>
                                <th>Description</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lab_tests as $t): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?php echo sanitize($t['lab_name']); ?></div>
                                        <span class="small text-muted" style="font-size:0.75rem"><?php echo sanitize($t['city']); ?></span>
                                    </td>
                                    <td><strong><?php echo sanitize($t['test_name']); ?></strong></td>
                                    <td class="fw-bold text-success">Rs. <?php echo number_format(floatval($t['price'])); ?></td>
                                    <td class="small text-muted" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo sanitize($t['description'] ?: 'None'); ?></td>
                                    <td class="text-end">
                                        <a href="laboratories.php?edit_test=<?php echo $t['id']; ?>" class="btn btn-outline-primary btn-xs py-0 px-2"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <a href="laboratories.php?delete_test=<?php echo $t['id']; ?>" class="btn btn-outline-danger btn-xs py-0 px-2 confirm-action" data-confirm-message="Delete this diagnostic test?"><i class="fa-regular fa-trash-can"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
