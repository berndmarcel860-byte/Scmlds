-- ============================================================
-- Scmlds - Fund Recovery Service Database Schema
-- ============================================================
-- IMPORTANT: Change the default admin password immediately after first login!
-- Default credentials: username=admin / password=password
-- Run: UPDATE admin_users SET password_hash=PASSWORD_HASH WHERE username='admin';
-- ============================================================

CREATE DATABASE IF NOT EXISTS scmlds CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE scmlds;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    full_name VARCHAR(255),
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Platform/Scam categories
CREATE TABLE IF NOT EXISTS scam_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lead submissions
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    amount_lost DECIMAL(15, 2),
    platform_category VARCHAR(100),
    case_description TEXT,
    status ENUM('Neu', 'In Bearbeitung', 'Kontaktiert', 'Erfolgreich', 'Abgelehnt') DEFAULT 'Neu',
    admin_notes TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed data
-- ============================================================

-- Default admin user (password: password — CHANGE THIS IMMEDIATELY after first login!)
-- Generate a new hash: php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"
INSERT INTO admin_users (username, password_hash, email, full_name) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@scmlds.de', 'Administrator');

-- Scam categories
INSERT INTO scam_categories (name, description) VALUES
('Krypto-Betrug', 'Betrügerische Kryptowährungs-Investitionsplattformen'),
('Forex-Betrug', 'Gefälschte Forex-Handelsplattformen und Broker'),
('Fake-Broker', 'Unregulierte und betrügerische Investment-Broker'),
('Romance-Scam mit Investitionsbetrug', 'Romantik-Betrug kombiniert mit gefälschten Investitionen'),
('Binäre Optionen', 'Binäre Optionen und Online-Trading-Betrug'),
('Andere', 'Sonstige Anlage- und Investitionsbetrug');
