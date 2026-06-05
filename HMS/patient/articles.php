<?php
/**
 * Wellness Articles Feed
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('patient');

require_once __DIR__ . '/../config/database.php';

$articles = [];
try {
    $stmt = $pdo->query("
        SELECT a.*, adm.name AS author_name 
        FROM articles a 
        JOIN admins adm ON a.admin_id = adm.id 
        ORDER BY a.created_at DESC
    ");
    $articles = $stmt->fetchAll();
} catch (Exception $e) {
    // Fail silently
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Wellness Hub & Articles</h3>
        <p class="text-muted">Browse nutrition guides, cardiovascular health tips, and wellness bulletins.</p>
    </div>
</div>

<?php if (empty($articles)): ?>
    <div class="card border-0 shadow-sm p-5 text-center" style="border-radius:16px;">
        <i class="fa-solid fa-book-open-reader fa-3x mb-3 text-muted opacity-30"></i>
        <h5 class="fw-bold mb-1">No Articles Published</h5>
        <p class="text-muted mb-0">Our clinical advisory board hasn't published any health tips yet.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($articles as $art): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-radius:16px;">
                    <!-- Visual banner based on category -->
                    <?php
                    $bgClass = 'bg-primary';
                    if ($art['category'] === 'Nutrition Advice') $bgClass = 'bg-success';
                    elseif ($art['category'] === 'Disease Awareness') $bgClass = 'bg-danger';
                    ?>
                    <div class="<?php echo $bgClass; ?> text-white p-4 text-center d-flex flex-column justify-content-center align-items-center" style="height: 140px;">
                        <span class="badge bg-white text-dark mb-2 px-3 py-1"><?php echo sanitize($art['category']); ?></span>
                        <small class="opacity-75"><i class="fa-regular fa-clock me-1"></i> <?php echo format_date($art['created_at']); ?></small>
                    </div>

                    <div class="card-body p-4 d-flex flex-column">
                        <h5 class="fw-bold text-dark mb-2"><?php echo sanitize($art['title']); ?></h5>
                        <p class="small text-muted mb-4"><?php echo substr(sanitize($art['content']), 0, 130); ?>...</p>
                        
                        <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-auto">
                            <span class="small text-muted" style="font-size:0.75rem;"><i class="fa-solid fa-pen-nib me-1"></i> By <?php echo sanitize($art['author_name']); ?></span>
                            <button class="btn btn-outline-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#readArticleModal" 
                                    data-title="<?php echo sanitize($art['title']); ?>" 
                                    data-category="<?php echo sanitize($art['category']); ?>"
                                    data-date="<?php echo format_date($art['created_at']); ?>"
                                    data-author="<?php echo sanitize($art['author_name']); ?>"
                                    data-content="<?php echo nl2br(sanitize($art['content'])); ?>">
                                Read More <i class="fa-solid fa-arrow-right-long ms-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Read Article Modal -->
<div class="modal fade" id="readArticleModal" tabindex="-1" aria-labelledby="readArticleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius:16px;">
            <div class="modal-header border-0 py-3 bg-light" style="border-top-left-radius:16px; border-top-right-radius:16px;">
                <div>
                    <span class="badge bg-primary mb-1" id="modalCategory"></span>
                    <h5 class="modal-title fw-bold text-dark" id="readArticleModalLabel"></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="max-height: 450px; overflow-y: auto;">
                <div class="d-flex justify-content-between align-items-center small text-muted border-bottom pb-2 mb-4">
                    <span><i class="fa-solid fa-user me-1"></i> Published by <strong class="text-dark" id="modalAuthor"></strong></span>
                    <span><i class="fa-solid fa-calendar-day me-1"></i> Date: <strong class="text-dark" id="modalDate"></strong></span>
                </div>
                <div class="text-dark lh-base" id="modalContent" style="font-size:0.95rem;"></div>
            </div>
            <div class="modal-footer border-0 p-3 bg-light" style="border-bottom-left-radius:16px; border-bottom-right-radius:16px;">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Bind content to Reading Modal
    const readModal = document.getElementById('readArticleModal');
    if (readModal) {
        readModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const title = button.getAttribute('data-title');
            const category = button.getAttribute('data-category');
            const date = button.getAttribute('data-date');
            const author = button.getAttribute('data-author');
            const content = button.getAttribute('data-content');

            readModal.querySelector('#readArticleModalLabel').textContent = title;
            readModal.querySelector('#modalCategory').textContent = category;
            readModal.querySelector('#modalAuthor').textContent = author;
            readModal.querySelector('#modalDate').textContent = date;
            readModal.querySelector('#modalContent').innerHTML = content;
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
