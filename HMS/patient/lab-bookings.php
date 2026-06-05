<?php
/**
 * Lab Bookings List
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('patient');

require_once __DIR__ . '/../config/database.php';
$patient_id = $_SESSION['user_id'];

// Handle Booking Cancellation
if (isset($_GET['cancel_id'])) {
    $cancel_id = intval($_GET['cancel_id']);
    try {
        // Double check ownership and status is pending
        $stmtCheck = $pdo->prepare("SELECT id FROM lab_bookings WHERE id = ? AND patient_id = ? AND status = 'Pending'");
        $stmtCheck->execute([$cancel_id, $patient_id]);
        
        if ($stmtCheck->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE lab_bookings SET status = 'Cancelled' WHERE id = ?");
            $stmt->execute([$cancel_id]);
            $_SESSION['success_message'] = "Lab booking cancelled successfully.";
        } else {
            $_SESSION['error_message'] = "Cannot cancel. Booking is not pending or does not belong to you.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error cancelling lab booking.";
    }
    header("Location: lab-bookings.php");
    exit();
}

// Fetch all lab bookings
$bookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT lb.*, lt.test_name, lt.price, l.name AS lab_name, l.city, l.phone
        FROM lab_bookings lb
        JOIN lab_tests lt ON lb.lab_test_id = lt.id
        JOIN laboratories l ON lt.lab_id = l.id
        WHERE lb.patient_id = ?
        ORDER BY lb.booking_date DESC, lb.created_at DESC
    ");
    $stmt->execute([$patient_id]);
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
    // Fail silently
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">My Lab Bookings & Findings</h3>
        <p class="text-muted">Monitor your laboratory tests, check scheduling confirmations, and view clinical PDF reports.</p>
    </div>
</div>

<div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
    <?php if (empty($bookings)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-flask-vial fa-3x mb-3 opacity-50"></i>
            <h5 class="fw-bold mb-1">No Lab Bookings Found</h5>
            <p class="mb-3">You have not scheduled any laboratory tests yet.</p>
            <a href="book-lab.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-flask me-1"></i> Book Lab Test Now</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle custom-table mb-0">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Laboratory Center</th>
                        <th>Diagnostic Panel</th>
                        <th>Booking Date</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $book): ?>
                        <?php
                        $statusClass = 'bg-warning text-dark';
                        if ($book['status'] === 'Confirmed') $statusClass = 'bg-primary text-white';
                        elseif ($book['status'] === 'Completed') $statusClass = 'bg-success text-white';
                        elseif ($book['status'] === 'Cancelled') $statusClass = 'bg-danger text-white';
                        ?>
                        <tr>
                            <td><strong>#LB-<?php echo intval($book['id']); ?></strong></td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo sanitize($book['lab_name']); ?></div>
                                <div class="small text-muted" style="font-size:0.75rem"><i class="fa-solid fa-phone me-1"></i> <?php echo sanitize($book['phone']); ?> (<?php echo sanitize($book['city']); ?>)</div>
                            </td>
                            <td><strong><?php echo sanitize($book['test_name']); ?></strong></td>
                            <td><?php echo format_date($book['booking_date']); ?></td>
                            <td><strong>$<?php echo floatval($book['price']); ?></strong></td>
                            <td><span class="badge <?php echo $statusClass; ?> px-2 py-1"><?php echo $book['status']; ?></span></td>
                            <td class="text-end">
                                <?php if ($book['status'] === 'Completed' && !empty($book['report_file'])): ?>
                                    <a href="../uploads/lab_reports/<?php echo $book['report_file']; ?>" target="_blank" class="btn btn-success btn-sm fw-semibold">
                                        <i class="fa-regular fa-file-pdf me-1"></i> View Report
                                    </a>
                                <?php elseif ($book['status'] === 'Pending'): ?>
                                    <a href="lab-bookings.php?cancel_id=<?php echo $book['id']; ?>" class="btn btn-outline-danger btn-sm confirm-action" data-confirm-message="Are you sure you want to cancel this laboratory test booking?">
                                        <i class="fa-solid fa-circle-minus me-1"></i> Cancel
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">Confirmed / Processing</span>
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
