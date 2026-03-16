<aside class="admin-sidebar d-flex flex-column" id="adminSidebar">
    <div class="sidebar-brand p-3 d-flex align-items-center gap-2">
        <i class="bi bi-shield-check text-warning fs-4"></i>
        <span class="fw-bold text-white fs-5">VerlustRückholung</span>
    </div>
    <nav class="flex-grow-1 mt-2">
        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a href="index.php" class="nav-link sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="leads.php" class="nav-link sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'leads.php' ? 'active' : '' ?>">
                    <i class="bi bi-people me-2"></i>Leads
                </a>
            </li>
            <li class="nav-item">
                <a href="visitors.php" class="nav-link sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'visitors.php' ? 'active' : '' ?>">
                    <i class="bi bi-eye me-2"></i>Besucher-Log
                </a>
            </li>
            <li class="nav-item">
                <a href="statistics.php" class="nav-link sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'statistics.php' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart-line me-2"></i>Statistiken
                </a>
            </li>
            <li class="nav-item">
                <a href="export.php" class="nav-link sidebar-link">
                    <i class="bi bi-download me-2"></i>Export
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                    <i class="bi bi-gear me-2"></i>Einstellungen
                </a>
            </li>
        </ul>
    </nav>
    <div class="p-3 border-top border-secondary">
        <a href="../index.php" class="nav-link sidebar-link mb-2" target="_blank">
            <i class="bi bi-globe me-2"></i>Website
        </a>
        <a href="logout.php" class="nav-link sidebar-link text-danger">
            <i class="bi bi-box-arrow-right me-2"></i>Abmelden
        </a>
    </div>
</aside>
