<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

log_activity('logout', 'User logged out');
admin_logout();
