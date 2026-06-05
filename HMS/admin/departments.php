<?php
/**
 * Admin Department CRUD Panel
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('admin');

require_once __DIR__ . '/../config/database.php';
$error = '';
$success = '';

// Edit state check
$edit_mode = false;
$edit_id = 0;
$edit_name = '';
$edit_desc = '';
$edit_icon = 'fa-briefcase-medical';

if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
        $stmt->execute([$edit_id]);
        $dept = $stmt->fetch();
        if ($dept) {
            $edit_mode = true;
            $edit_name = $dept['name'];
            $edit_desc = $dept['description'];
            $edit_icon = $dept['icon'];
        }
    } catch (Exception $e) {}
}

// Handle Delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success_message'] = "Department deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Cannot delete department. Medical staff or appointments are linked to it.";
    }
    header("Location: departments.php");
    exit();
}

// Handle Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dept_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $icon = sanitize($_POST['icon']);
        $db_id = intval($_POST['db_id'] ?? 0);

        if (empty($name) || empty($description)) {
            $error = "Department name and description are required.";
        } else {
            try {
                if ($db_id > 0) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE departments SET name = ?, description = ?, icon = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $icon, $db_id]);
                    $success = "Department updated successfully!";
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO departments (name, description, icon) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $description, $icon]);
                    $success = "Department added successfully!";
                }
                
                // Clear post states
                $_POST = array();
                $edit_mode = false;
            } catch (Exception $e) {
                $error = "Error: Department name must be unique. details: " . $e->getMessage();
            }
        }
    }
}

// Fetch all departments
$departments = [];
try {
    $departments = $pdo->query("SELECT * FROM departments ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();

// Common FA medical icon choices
$icons = [
    'fa-briefcase-medical' => 'Briefcase Medical',
    'fa-heart-pulse' => 'Heart Pulse (Cardiology)',
    'fa-brain' => 'Brain (Neurology)',
    'fa-bone' => 'Bone (Orthopedics)',
    'fa-baby' => 'Baby (Pediatrics)',
    'fa-hand-dots' => 'Hand Dots (Dermatology)',
    'fa-ear-listen' => 'Ear (ENT)',
    'fa-person-dress' => 'Dress (Gynecology)',
    'fa-stethoscope' => 'Stethoscope (General Medicine)',
    'fa-head-side-virus' => 'Head Side Virus (Psychiatry)',
    'fa-tooth' => 'Tooth (Dental)',
    'fa-flask' => 'Flask (Lab)'
];
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Manage Medical Departments</h3>
        <p class="text-muted">Configure active specialty clinics, add bios, and update directory icons.</p>
    </div>
</div>

<div class="row g-4">
    <!-- CRUD Editor Form -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4">
                <i class="fa-solid fa-square-plus text-primary me-2"></i><?php echo $edit_mode ? 'Edit Department' : 'Add New Department'; ?>
            </h5>

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

            <form action="departments.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="db_id" value="<?php echo $edit_id; ?>">

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Department Name</label>
                    <input type="text" class="form-control" name="name" required placeholder="e.g. Cardiology" value="<?php echo sanitize($edit_name); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Directory Vector Icon</label>
                    <select class="form-select" name="icon" required>
                        <?php foreach ($icons as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo $edit_icon === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">Department Description</label>
                    <textarea class="form-control" name="description" rows="4" required placeholder="Outline general treatments and clinical care guidelines..."><?php echo sanitize($edit_desc); ?></textarea>
                </div>

                <button type="submit" name="dept_submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Department Details
                </button>
                <?php if ($edit_mode): ?>
                    <a href="departments.php" class="btn btn-outline-secondary w-100 mt-2 py-2 fw-semibold">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Departments List -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-list-ul text-primary me-2"></i>Active Specialties Directory</h5>
            
            <?php if (empty($departments)): ?>
                <div class="text-center py-4 text-muted">
                    <p class="mb-0">No department clinic records created.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle custom-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 10%">Icon</th>
                                <th style="width: 25%">Name</th>
                                <th style="width: 45%">Description</th>
                                <th class="text-end" style="width: 20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                                <tr>
                                    <td>
                                        <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width:40px; height:40px; font-size: 1.25rem;">
                                            <i class="fa-solid <?php echo sanitize($dept['icon'] ?: 'fa-briefcase-medical'); ?>"></i>
                                        </div>
                                    </td>
                                    <td><strong><?php echo sanitize($dept['name']); ?></strong></td>
                                    <td><p class="small text-muted mb-0" style="max-height: 48px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?php echo sanitize($dept['description']); ?></p></td>
                                    <td class="text-end">
                                        <a href="departments.php?edit_id=<?php echo $dept['id']; ?>" class="btn btn-outline-primary btn-sm me-1"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <a href="departments.php?delete_id=<?php echo $dept['id']; ?>" class="btn btn-outline-danger btn-sm confirm-action" data-confirm-message="Delete this department? Warning: This will unlink associated doctor portfolios."><i class="fa-regular fa-trash-can"></i></a>
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
