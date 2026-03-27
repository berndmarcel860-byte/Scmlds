<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

admin_check();
ensure_blog_table();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $pid = (int) ($_POST['post_id'] ?? 0);
    if ($pid) {
        delete_blog_post($pid);
        log_activity('delete_blog_post', "Blog post #$pid deleted");
    }
    header('Location: blog.php?' . http_build_query($_GET));
    exit;
}

$page     = max(1, (int) ($_GET['page']   ?? 1));
$per_page = 20;
$filters  = [
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
];

$result = get_blog_posts($filters, $page, $per_page);
$posts  = $result['data'];
$total  = $result['total'];
$pages  = $result['pages'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog – VerlustRückholung Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-journal-richtext me-2 text-primary"></i>Blog</h4>
                <p class="text-muted small mb-0"><?= $total ?> Beiträge gesamt</p>
            </div>
            <a href="blog_edit.php" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Neuer Beitrag
            </a>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Suche</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Titel oder Zusammenfassung..."
                               value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Alle</option>
                            <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Veröffentlicht</option>
                            <option value="draft"     <?= $filters['status'] === 'draft'     ? 'selected' : '' ?>>Entwurf</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">
                            <i class="bi bi-search me-1"></i>Filtern
                        </button>
                        <a href="blog.php" class="btn btn-light btn-sm"><i class="bi bi-x"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Blog Posts Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Titel</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Veröffentlicht</th>
                                <th>Erstellt</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td class="text-muted small"><?= (int)$post['id'] ?></td>
                                <td class="fw-semibold">
                                    <a href="blog_edit.php?id=<?= (int)$post['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <?php if ($post['excerpt']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(mb_substr($post['excerpt'], 0, 80), ENT_QUOTES, 'UTF-8') ?>…</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small font-monospace">
                                    /blog/<?= htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <?php if ($post['status'] === 'published'): ?>
                                        <span class="badge bg-success">Veröffentlicht</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Entwurf</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted small">
                                    <?= $post['published_at'] ? date('d.m.Y', strtotime($post['published_at'])) : '–' ?>
                                </td>
                                <td class="text-muted small">
                                    <?= date('d.m.Y', strtotime($post['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="blog_edit.php?id=<?= (int)$post['id'] ?>"
                                           class="btn btn-sm btn-outline-primary" title="Bearbeiten">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($post['status'] === 'published'): ?>
                                        <a href="../blog/<?= htmlspecialchars($post['slug'], ENT_QUOTES, 'UTF-8') ?>"
                                           class="btn btn-sm btn-outline-secondary" title="Vorschau" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger" title="Löschen"
                                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                data-post-id="<?= (int)$post['id'] ?>"
                                                data-post-title="<?= htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($posts)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-journal fs-1 d-block mb-2"></i>
                                Noch keine Blog-Beiträge vorhanden.
                                <a href="blog_edit.php" class="d-block mt-2">Ersten Beitrag erstellen</a>
                            </td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($pages > 1): ?>
            <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
                <div class="text-muted small">Seite <?= $page ?> von <?= $pages ?> (<?= $total ?> Beiträge)</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php for ($p = 1; $p <= $pages; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Beitrag löschen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Möchten Sie den Beitrag <strong id="deletePostTitle"></strong> wirklich unwiderruflich löschen?
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <form method="POST">
                    <input type="hidden" name="post_id" id="deletePostId">
                    <button type="submit" name="delete_post" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Endgültig löschen
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('deletePostTitle').textContent = btn.getAttribute('data-post-title');
    document.getElementById('deletePostId').value = btn.getAttribute('data-post-id');
});
</script>
</body>
</html>
