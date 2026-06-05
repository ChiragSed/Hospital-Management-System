<?php
/**
 * Admin Health Articles Blog CMS
 * Hospital Management System
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

check_role('admin');

require_once __DIR__ . '/../config/database.php';
$admin_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Edit variables
$edit_mode = false;
$edit_id = 0;
$edit_title = '';
$edit_content = '';
$edit_category = 'Health Tips';

if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$edit_id]);
        $art = $stmt->fetch();
        if ($art) {
            $edit_mode = true;
            $edit_title = $art['title'];
            $edit_content = $art['content'];
            $edit_category = $art['category'];
        }
    } catch (Exception $e) {}
}

// Handle Delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$delete_id]);
        $_SESSION['success_message'] = "Article deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error deleting article.";
    }
    header("Location: articles.php");
    exit();
}

// Handle Add / Edit Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['article_submit'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF verification failed.";
    } else {
        $title = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $category = sanitize($_POST['category']);
        $db_id = intval($_POST['db_id'] ?? 0);

        if (empty($title) || empty($content) || empty($category)) {
            $error = "All article fields (title, content, category) are required.";
        } else {
            try {
                if ($db_id > 0) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE articles SET title = ?, content = ?, category = ? WHERE id = ?");
                    $stmt->execute([$title, $content, $category, $db_id]);
                    $success = "Article updated successfully!";
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO articles (admin_id, title, content, category) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$admin_id, $title, $content, $category]);
                    $success = "Article published successfully!";
                }
                
                $_POST = array();
                $edit_mode = false;
            } catch (Exception $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all articles
$articles = [];
try {
    $articles = $pdo->query("
        SELECT a.*, adm.name AS author_name 
        FROM articles a
        JOIN admins adm ON a.admin_id = adm.id
        ORDER BY a.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/header.php';
$csrf_token = generate_csrf_token();
$categories = ['Health Tips', 'Nutrition Advice', 'Disease Awareness'];
?>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="fw-bold text-dark mb-1">Health Articles Publisher</h3>
        <p class="text-muted">Compose health publications, edit blogs, and manage wellness categories.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Article Editor Card -->
    <div class="col-lg-5 col-xl-4">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4">
                <i class="fa-solid fa-file-pen text-primary me-2"></i><?php echo $edit_mode ? 'Edit Article Details' : 'Publish New Article'; ?>
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

            <form action="articles.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="db_id" value="<?php echo $edit_id; ?>">

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Article Title</label>
                    <input type="text" class="form-control" name="title" required placeholder="e.g. Benefits of Cardiorespiratory Exercise" value="<?php echo sanitize($edit_title); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Wellness Category</label>
                    <select class="form-select" name="category" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $edit_category === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">Article Content Body</label>
                    <textarea class="form-control" name="content" rows="8" required placeholder="Write clinical articles, bullet tips or general nutrition advice..."><?php echo sanitize($edit_content); ?></textarea>
                </div>

                <button type="submit" name="article_submit" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm">
                    <i class="fa-regular fa-paper-plane me-2"></i> Save and Publish
                </button>
                <?php if ($edit_mode): ?>
                    <a href="articles.php" class="btn btn-outline-secondary w-100 mt-2 py-2 fw-semibold">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Active Articles List -->
    <div class="col-lg-7 col-xl-8">
        <div class="card border-0 shadow-sm p-4" style="border-radius: 16px;">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-newspaper text-primary me-2"></i>Active Health Publications</h5>

            <?php if (empty($articles)): ?>
                <div class="text-center py-4 text-muted">
                    <p class="mb-0">No wellness articles published yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle custom-table mb-0" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Article Title</th>
                                <th>Author</th>
                                <th>Date Published</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $art): ?>
                                <?php
                                $badge = 'bg-primary-subtle text-primary';
                                if ($art['category'] === 'Nutrition Advice') $badge = 'bg-success-subtle text-success';
                                elseif ($art['category'] === 'Disease Awareness') $badge = 'bg-danger-subtle text-danger';
                                ?>
                                <tr>
                                    <td><span class="badge <?php echo $badge; ?> px-2 py-1"><?php echo $art['category']; ?></span></td>
                                    <td><strong><?php echo sanitize($art['title']); ?></strong></td>
                                    <td><?php echo sanitize($art['author_name']); ?></td>
                                    <td><?php echo format_date($art['created_at']); ?></td>
                                    <td class="text-end">
                                        <a href="articles.php?edit_id=<?php echo $art['id']; ?>" class="btn btn-outline-primary btn-xs py-0 px-2"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <a href="articles.php?delete_id=<?php echo $art['id']; ?>" class="btn btn-outline-danger btn-xs py-0 px-2 confirm-action" data-confirm-message="Are you sure you want to delete this health article?"><i class="fa-regular fa-trash-can"></i></a>
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
