-- ============================================================
-- VerlustRückholung – Fund Recovery Service Database Schema
-- ============================================================
-- IMPORTANT: Change the default admin password immediately after first login!
-- Default credentials: username=admin / password=password
-- Run: php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"
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
    country VARCHAR(100),
    year_lost SMALLINT UNSIGNED,
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
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'info@verlustrueckholung.de', 'Administrator')
ON DUPLICATE KEY UPDATE email=VALUES(email);

-- Scam categories
INSERT IGNORE INTO scam_categories (name, description) VALUES
('Krypto-Betrug', 'Betrügerische Kryptowährungs-Investitionsplattformen'),
('Forex-Betrug', 'Gefälschte Forex-Handelsplattformen und Broker'),
('Fake-Broker', 'Unregulierte und betrügerische Investment-Broker'),
('Romance-Scam mit Investitionsbetrug', 'Romantik-Betrug kombiniert mit gefälschten Investitionen'),
('Binäre Optionen', 'Binäre Optionen und Online-Trading-Betrug'),
('Andere', 'Sonstige Anlage- und Investitionsbetrug');

-- Migration: add country and year_lost if upgrading from older schema
ALTER TABLE leads ADD COLUMN IF NOT EXISTS country VARCHAR(100) AFTER phone;
ALTER TABLE leads ADD COLUMN IF NOT EXISTS year_lost SMALLINT UNSIGNED AFTER country;

-- ============================================================
-- Site Settings (key-value store, admin-editable)
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_label VARCHAR(255),
    setting_group VARCHAR(100) DEFAULT 'general',
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SMTP credentials (editable from admin)
CREATE TABLE IF NOT EXISTS smtp_settings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    host       VARCHAR(255)                       NOT NULL DEFAULT '',
    port       SMALLINT UNSIGNED                  NOT NULL DEFAULT 587,
    username   VARCHAR(255)                       NOT NULL DEFAULT '',
    password   VARCHAR(255)                       NOT NULL DEFAULT '',
    secure     ENUM('tls','ssl','none')            NOT NULL DEFAULT 'tls',
    debug      TINYINT                            NOT NULL DEFAULT 0,
    from_email VARCHAR(255)                       NOT NULL DEFAULT '',
    from_name  VARCHAR(255)                       NOT NULL DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Telegram notification credentials
CREATE TABLE IF NOT EXISTS telegram_settings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    bot_token  VARCHAR(255) NOT NULL DEFAULT '',
    chat_id    VARCHAR(100) NOT NULL DEFAULT '',
    active     TINYINT(1)   NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed settings defaults
-- ============================================================
INSERT IGNORE INTO settings (setting_key, setting_value, setting_label, setting_group) VALUES
('company_name',              'VerlustRückholung',                                               'Firmenname',                          'general'),
('site_url',                  'https://verlustrueckholung.de',                                   'Website-URL',                         'general'),
('admin_email',               'info@verlustrueckholung.de',                                      'Admin-E-Mail',                        'general'),
('from_email',                'noreply@verlustrueckholung.de',                                   'Absender-E-Mail',                     'general'),
('from_name',                 'VerlustRückholung',                                               'Absender-Name',                       'general'),
('page_title',                'VerlustRückholung – KI-gestützte Kapitalrückholung bei Anlagebetrug', 'Seiten-Titel',                   'general'),
('modal_delay_seconds',       '60',                                                              'Sekunden bis Modal erscheint',        'general'),
('send_email_on_submission',  '1',                                                               'E-Mail nach Formular-Absendung senden', 'notifications'),
('telegram_notification_active', '0',                                                           'Telegram-Benachrichtigung aktiv',     'notifications');

INSERT IGNORE INTO smtp_settings (host, port, username, password, secure, from_email, from_name) VALUES
('smtp.verlustrueckholung.de', 587, 'noreply@verlustrueckholung.de', '', 'tls', 'noreply@verlustrueckholung.de', 'VerlustRückholung');

INSERT IGNORE INTO telegram_settings (bot_token, chat_id, active) VALUES
('', '', 0);

-- Visitor tracking logs
CREATE TABLE IF NOT EXISTS visitor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    referrer VARCHAR(512) DEFAULT '',
    user_agent VARCHAR(512) DEFAULT '',
    time_on_site INT UNSIGNED DEFAULT 0,  -- seconds
    submitted_lead TINYINT(1) NOT NULL DEFAULT 0,
    lead_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Design chooser setting (added for multi-theme support)
-- ============================================================
INSERT IGNORE INTO settings (setting_key, setting_value, setting_label, setting_group) VALUES
('active_design', 'index2', 'Aktives Seitendesign', 'design');

-- E-mail verification via OTP (added for real-email enforcement)
INSERT IGNORE INTO settings (setting_key, setting_value, setting_label, setting_group) VALUES
('email_verification_required', '0', 'E-Mail-Verifizierung via Code erforderlich', 'general');

-- SEO / Open Graph settings
INSERT IGNORE INTO settings (setting_key, setting_value, setting_label, setting_group) VALUES
('og_image', 'https://verlustrueckholung.de/assets/images/og-image.jpg', 'Open Graph Bild-URL (1200×630 px)', 'seo');

-- ============================================================
-- Lead-generation: UTM tracking columns
-- ============================================================

-- UTM parameters on visitor_logs (one row per session/page visit)
ALTER TABLE visitor_logs
    ADD COLUMN IF NOT EXISTS utm_source   VARCHAR(100) DEFAULT NULL AFTER referrer,
    ADD COLUMN IF NOT EXISTS utm_medium   VARCHAR(100) DEFAULT NULL AFTER utm_source,
    ADD COLUMN IF NOT EXISTS utm_campaign VARCHAR(150) DEFAULT NULL AFTER utm_medium,
    ADD COLUMN IF NOT EXISTS utm_content  VARCHAR(150) DEFAULT NULL AFTER utm_campaign,
    ADD COLUMN IF NOT EXISTS utm_term     VARCHAR(150) DEFAULT NULL AFTER utm_content,
    ADD COLUMN IF NOT EXISTS gclid        VARCHAR(200) DEFAULT NULL AFTER utm_term,
    ADD COLUMN IF NOT EXISTS landing_page VARCHAR(512) DEFAULT NULL AFTER gclid;

-- Store the primary acquisition channel + UTM source on each lead
ALTER TABLE leads
    ADD COLUMN IF NOT EXISTS lead_source VARCHAR(80) DEFAULT 'website' AFTER ip_address,
    ADD COLUMN IF NOT EXISTS utm_source  VARCHAR(100) DEFAULT NULL     AFTER lead_source;

-- ============================================================
-- Lead-generation: site-wide settings
-- ============================================================
INSERT IGNORE INTO settings (setting_key, setting_value, setting_label, setting_group) VALUES
('whatsapp_number',   '',  'WhatsApp-Nummer (intl. Format, z. B. 4915123456789)', 'general'),
('announcement_text', '',  'Ankündigungsleiste – Text (leer = ausgeblendet)',      'general'),
('announcement_url',  '',  'Ankündigungsleiste – Link-URL (optional)',             'general'),
('announcement_bg',   '#d32f2f', 'Ankündigungsleiste – Hintergrundfarbe',         'general');

