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
                <a href="design.php" class="nav-link sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'design.php' ? 'active' : '' ?>">
                    <i class="bi bi-palette me-2"></i>Design wählen
                </a>
            </li>
            <li class="nav-item">
                <a href="seo.php" class="nav-link sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'seo.php' ? 'active' : '' ?>">
                    <i class="bi bi-search me-2"></i>SEO
                </a>
            </li>
            <li class="nav-item">
                <a href="blog.php" class="nav-link sidebar-link <?= in_array(basename($_SERVER['PHP_SELF']), ['blog.php','blog_edit.php']) ? 'active' : '' ?>">
                    <i class="bi bi-journal-richtext me-2"></i>Blog
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                    <i class="bi bi-gear me-2"></i>Einstellungen
                </a>
            </li>

            <!-- ── E-Mail-Marketing ── -->
            <?php
            $in_mailing = strpos($_SERVER['PHP_SELF'], '/mailing/') !== false;
            ?>
            <li class="nav-item mt-2">
                <a href="mailing/index.php"
                   class="nav-link sidebar-link d-flex align-items-center justify-content-between <?= $in_mailing ? 'active' : '' ?>"
                   data-bs-toggle="collapse" data-bs-target="#mailingSubnav" aria-expanded="<?= $in_mailing ? 'true' : 'false' ?>">
                    <span><i class="bi bi-envelope-paper me-2"></i>E-Mail-Marketing</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse <?= $in_mailing ? 'show' : '' ?>" id="mailingSubnav">
                    <ul class="nav flex-column ps-4 pt-1 pb-1">
                        <li class="nav-item">
                            <a href="mailing/index.php"
                               class="nav-link sidebar-link py-1 small <?= basename($_SERVER['PHP_SELF']) === 'index.php' && $in_mailing ? 'active' : '' ?>">
                                <i class="bi bi-grid me-2"></i>Kampagnen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="mailing/smtp_accounts.php"
                               class="nav-link sidebar-link py-1 small <?= basename($_SERVER['PHP_SELF']) === 'smtp_accounts.php' ? 'active' : '' ?>">
                                <i class="bi bi-server me-2"></i>SMTP-Accounts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="mailing/templates.php"
                               class="nav-link sidebar-link py-1 small <?= basename($_SERVER['PHP_SELF']) === 'templates.php' ? 'active' : '' ?>">
                                <i class="bi bi-file-earmark-richtext me-2"></i>E-Mail-Templates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="mailing/leads.php"
                               class="nav-link sidebar-link py-1 small <?= basename($_SERVER['PHP_SELF']) === 'leads.php' && $in_mailing ? 'active' : '' ?>">
                                <i class="bi bi-people me-2"></i>Leads / Kontakte
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="mailing/index.php#stats"
                               class="nav-link sidebar-link py-1 small <?= basename($_SERVER['PHP_SELF']) === 'stats.php' && $in_mailing ? 'active' : '' ?>">
                                <i class="bi bi-bar-chart me-2"></i>Statistiken
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="mailing/settings.php"
                               class="nav-link sidebar-link py-1 small <?= basename($_SERVER['PHP_SELF']) === 'settings.php' && $in_mailing ? 'active' : '' ?>">
                                <i class="bi bi-sliders me-2"></i>Einstellungen
                            </a>
                        </li>
                    </ul>
                </div>
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
