<?php
/**
 * Admin Feedback Oversight
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('admin');

require_once __DIR__ . '/../config/database.php';

$feedback = [];
$avg_rating = 0;
$total_reviews = 0;

try {
    // Fetch average rating
    $avg_rating = round($pdo->query("SELECT AVG(rating) FROM feedback")->fetchColumn(), 1);
    
    // Total reviews count
    $total_reviews = $pdo->query("SELECT COUNT(*) FROM feedback")->fetchColumn();

    // Fetch review logs
    $feedback = $pdo->query("
        SELECT f.*, p.name AS patient_name, d.name AS doctor_name, d.specialization 
        FROM feedback f
        JOIN patients p ON f.patient_id = p.id
        JOIN doctors d ON f.doctor_id = d.id
        ORDER BY f.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {}
 
include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Clinician Reviews & Feedback</h3>
        <p class="text-muted">Monitor patient satisfaction levels, check doctor reviews, and track clinical care quality.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Stat Indicators -->
    <div class="col-md-6">
        <div class="stat-card p-4 text-center h-100">
            <span class="text-muted small uppercase fw-bold">Overall Satisfaction</span>
            <div class="display-3 fw-bold text-primary mt-2 mb-1"><?php echo $avg_rating > 0 ? $avg_rating : 'N/A'; ?> / 5.0</div>
            <div class="text-warning mb-2">
                <?php
                $floor = floor($avg_rating);
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= $floor) {
                        echo '<i class="fa-solid fa-star"></i>';
                    } elseif ($i - 0.5 <= $avg_rating) {
                        echo '<i class="fa-solid fa-star-half-stroke"></i>';
                    } else {
                        echo '<i class="fa-regular fa-star"></i>';
                    }
                }
                ?>
            </div>
            <span class="small text-muted">Satisfaction rate based on collected feedback</span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card p-4 text-center h-100">
            <span class="text-muted small uppercase fw-bold">Total Reviews Submitted</span>
            <div class="display-3 fw-bold text-success mt-2 mb-1"><?php echo $total_reviews; ?></div>
            <div class="text-success mb-2"><i class="fa-solid fa-message fa-lg"></i></div>
            <span class="small text-muted">Reviews logged by patients after consultation checkouts</span>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
    <h5 class="fw-bold text-dark border-bottom pb-2 mb-4"><i class="fa-regular fa-comment-dots text-primary me-2"></i>Patient Reviews Log</h5>

    <?php if (empty($feedback)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa-regular fa-comments fa-3x mb-3 opacity-30"></i>
            <p class="mb-0">No patient feedback submitted yet.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($feedback as $f): ?>
                <div class="col-md-6 col-xxl-4">
                    <div class="border p-4 rounded-3 h-100" style="background-color: #f8fafc;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0"><?php echo sanitize($f['patient_name']); ?></h6>
                                <small class="text-muted" style="font-size:0.75rem;"><i class="fa-regular fa-calendar me-1"></i> <?php echo format_date($f['created_at']); ?></small>
                            </div>
                            <div class="text-warning small">
                                <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= intval($f['rating'])) {
                                        echo '<i class="fa-solid fa-star"></i>';
                                    } else {
                                        echo '<i class="fa-regular fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <p class="small text-muted mb-3 italic">"<?php echo sanitize($f['comments']); ?>"</p>
                        <div class="border-top pt-2 mt-auto small text-secondary">
                            <i class="fa-solid fa-user-doctor me-1"></i> Reviewed: <strong><?php echo sanitize(format_doctor_name($f['doctor_name'])); ?></strong> (<?php echo sanitize($f['specialization']); ?>)
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
