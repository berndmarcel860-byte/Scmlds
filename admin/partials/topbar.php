<nav class="admin-topbar d-flex align-items-center justify-content-between px-4 py-2">
    <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle">
        <i class="bi bi-list fs-5"></i>
    </button>
    <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-muted small">
            <i class="bi bi-person-circle me-1"></i>
            <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
        </span>
        <a href="logout.php" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</nav>
<script>
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('adminSidebar').classList.toggle('show');
});
</script>
