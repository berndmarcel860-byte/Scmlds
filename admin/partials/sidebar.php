<?php
// Determine the current page for active-link highlighting
$_sb_self  = $_SERVER['PHP_SELF'] ?? '';
$_sb_base  = basename($_sb_self);
$_in_mail  = strpos($_sb_self, '/mailing/') !== false;
$_in_pages = $_sb_base === 'pages.php' && strpos($_sb_self, '/admin/') !== false;
?>
<aside class="admin-sidebar d-flex flex-column" id="adminSidebar">
    <div class="sidebar-brand p-3 d-flex align-items-center gap-2">
        <i class="bi bi-shield-check text-warning fs-4"></i>
        <span class="fw-bold text-white fs-5">VerlustRückholung</span>
    </div>
    <nav class="flex-grow-1 mt-2">
        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a href="/admin/index.php" class="nav-link sidebar-link <?= $_sb_base === 'index.php' && !$_in_mail ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/leads.php" class="nav-link sidebar-link <?= $_sb_base === 'leads.php' && !$_in_mail ? 'active' : '' ?>">
                    <i class="bi bi-people me-2"></i>Leads
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/visitors.php" class="nav-link sidebar-link <?= $_sb_base === 'visitors.php' ? 'active' : '' ?>">
                    <i class="bi bi-eye me-2"></i>Besucher-Log
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/statistics.php" class="nav-link sidebar-link <?= $_sb_base === 'statistics.php' ? 'active' : '' ?>">
                    <i class="bi bi-bar-chart-line me-2"></i>Statistiken
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/export.php" class="nav-link sidebar-link <?= $_sb_base === 'export.php' ? 'active' : '' ?>">
                    <i class="bi bi-download me-2"></i>Export
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/design.php" class="nav-link sidebar-link <?= $_sb_base === 'design.php' ? 'active' : '' ?>">
                    <i class="bi bi-palette me-2"></i>Design wählen
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/pages.php" class="nav-link sidebar-link <?= $_in_pages ? 'active' : '' ?>">
                    <i class="bi bi-file-text me-2"></i>Seiten
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/seo.php" class="nav-link sidebar-link <?= $_sb_base === 'seo.php' && !$_in_mail ? 'active' : '' ?>">
                    <i class="bi bi-search me-2"></i>SEO
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/blog.php" class="nav-link sidebar-link <?= in_array($_sb_base, ['blog.php','blog_edit.php']) ? 'active' : '' ?>">
                    <i class="bi bi-journal-richtext me-2"></i>Blog
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/settings.php" class="nav-link sidebar-link <?= $_sb_base === 'settings.php' && !$_in_mail ? 'active' : '' ?>">
                    <i class="bi bi-gear me-2"></i>Einstellungen
                </a>
            </li>

            <!-- ── E-Mail-Marketing ── -->
            <li class="nav-item mt-2">
                <a href="/admin/mailing/index.php"
                   class="nav-link sidebar-link d-flex align-items-center justify-content-between <?= $_in_mail ? 'active' : '' ?>"
                   data-bs-toggle="collapse" data-bs-target="#mailingSubnav" aria-expanded="<?= $_in_mail ? 'true' : 'false' ?>">
                    <span><i class="bi bi-envelope-paper me-2"></i>E-Mail-Marketing</span>
                    <i class="bi bi-chevron-down small"></i>
                </a>
                <div class="collapse <?= $_in_mail ? 'show' : '' ?>" id="mailingSubnav">
                    <ul class="nav flex-column ps-4 pt-1 pb-1">
                        <li class="nav-item">
                            <a href="/admin/mailing/index.php"
                               class="nav-link sidebar-link py-1 small <?= $_sb_base === 'index.php' && $_in_mail ? 'active' : '' ?>">
                                <i class="bi bi-grid me-2"></i>Kampagnen
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/mailing/smtp_accounts.php"
                               class="nav-link sidebar-link py-1 small <?= $_sb_base === 'smtp_accounts.php' ? 'active' : '' ?>">
                                <i class="bi bi-server me-2"></i>SMTP-Accounts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/mailing/templates.php"
                               class="nav-link sidebar-link py-1 small <?= $_sb_base === 'templates.php' ? 'active' : '' ?>">
                                <i class="bi bi-file-earmark-richtext me-2"></i>E-Mail-Templates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/mailing/leads.php"
                               class="nav-link sidebar-link py-1 small <?= $_sb_base === 'leads.php' && $_in_mail ? 'active' : '' ?>">
                                <i class="bi bi-people me-2"></i>Leads / Kontakte
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/mailing/warmup.php"
                               class="nav-link sidebar-link py-1 small <?= $_sb_base === 'warmup.php' ? 'active' : '' ?>">
                                <i class="bi bi-fire me-2"></i>IP-Warmup
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/mailing/stats.php"
                               class="nav-link sidebar-link py-1 small <?= $_sb_base === 'stats.php' && $_in_mail ? 'active' : '' ?>">
                                <i class="bi bi-bar-chart me-2"></i>Statistiken
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="/admin/mailing/settings.php"
                               class="nav-link sidebar-link py-1 small <?= $_sb_base === 'settings.php' && $_in_mail ? 'active' : '' ?>">
                                <i class="bi bi-sliders me-2"></i>Einstellungen
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </nav>
    <div class="p-3 border-top border-secondary">
        <a href="/" class="nav-link sidebar-link mb-2" target="_blank">
            <i class="bi bi-globe me-2"></i>Website
        </a>
        <a href="/admin/logout.php" class="nav-link sidebar-link text-danger">
            <i class="bi bi-box-arrow-right me-2"></i>Abmelden
        </a>
    </div>
</aside>
