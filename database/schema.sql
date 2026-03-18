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
    lead_source VARCHAR(80) DEFAULT 'website',
    utm_source  VARCHAR(100) DEFAULT NULL,
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
-- (These columns are already in the CREATE TABLE above for fresh installs.)
-- The procedure below is MySQL 5.7+ / MariaDB 10+ compatible.
DROP PROCEDURE IF EXISTS _vr_migrate;
DELIMITER $$
CREATE PROCEDURE _vr_migrate()
BEGIN
    -- leads: country (pre-2024 upgrade)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'leads' AND column_name = 'country'
    ) THEN
        ALTER TABLE leads ADD COLUMN country VARCHAR(100) AFTER phone;
    END IF;

    -- leads: year_lost (pre-2024 upgrade)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'leads' AND column_name = 'year_lost'
    ) THEN
        ALTER TABLE leads ADD COLUMN year_lost SMALLINT UNSIGNED AFTER country;
    END IF;

    -- leads: lead_source (UTM / lead-gen feature)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'leads' AND column_name = 'lead_source'
    ) THEN
        ALTER TABLE leads ADD COLUMN lead_source VARCHAR(80) DEFAULT 'website' AFTER ip_address;
    END IF;

    -- leads: utm_source
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'leads' AND column_name = 'utm_source'
    ) THEN
        ALTER TABLE leads ADD COLUMN utm_source VARCHAR(100) DEFAULT NULL AFTER lead_source;
    END IF;

    -- visitor_logs: utm_source
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs' AND column_name = 'utm_source'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN utm_source VARCHAR(100) DEFAULT NULL AFTER referrer;
    END IF;

    -- visitor_logs: utm_medium
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs' AND column_name = 'utm_medium'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN utm_medium VARCHAR(100) DEFAULT NULL AFTER utm_source;
    END IF;

    -- visitor_logs: utm_campaign
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs' AND column_name = 'utm_campaign'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN utm_campaign VARCHAR(150) DEFAULT NULL AFTER utm_medium;
    END IF;

    -- visitor_logs: utm_content
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs' AND column_name = 'utm_content'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN utm_content VARCHAR(150) DEFAULT NULL AFTER utm_campaign;
    END IF;

    -- visitor_logs: utm_term
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs' AND column_name = 'utm_term'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN utm_term VARCHAR(150) DEFAULT NULL AFTER utm_content;
    END IF;

    -- visitor_logs: gclid
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs' AND column_name = 'gclid'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN gclid VARCHAR(200) DEFAULT NULL AFTER utm_term;
    END IF;

    -- visitor_logs: landing_page
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs' AND column_name = 'landing_page'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN landing_page VARCHAR(512) DEFAULT NULL AFTER gclid;
    END IF;
END$$
DELIMITER ;
CALL _vr_migrate();
DROP PROCEDURE IF EXISTS _vr_migrate;

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
    utm_source   VARCHAR(100) DEFAULT NULL,
    utm_medium   VARCHAR(100) DEFAULT NULL,
    utm_campaign VARCHAR(150) DEFAULT NULL,
    utm_content  VARCHAR(150) DEFAULT NULL,
    utm_term     VARCHAR(150) DEFAULT NULL,
    gclid        VARCHAR(200) DEFAULT NULL,
    landing_page VARCHAR(512) DEFAULT NULL,
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
('og_image',             'https://verlustrueckholung.de/assets/images/og-image.jpg', 'Open Graph Bild-URL (1200×630 px)',      'seo'),
('meta_description',     'VerlustRückholung hilft Opfern von Anlagebetrug ihr Kapital zurückzufordern. KI-gestützte Analyse, internationale Experten, 87% Erfolgsquote. Kostenlose Erstprüfung.', 'Meta-Beschreibung (max. 160 Zeichen)', 'seo'),
('meta_keywords',        'Geld zurückfordern, Anlagebetrug, Kapitalrückholung, Krypto-Betrug, Forex-Betrug, Scam Recovery, Verlust zurückfordern', 'Meta-Keywords (kommagetrennt)', 'seo'),
('robots_meta',          'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1', 'Robots Meta-Tag Inhalt', 'seo'),
('twitter_handle',       '',                                                                               'Twitter/X Handle (z. B. @VerlustRückholung)', 'seo'),
('google_analytics_id',  '',                                                                               'Google Analytics 4 Measurement-ID (G-XXXXXXXXXX)', 'seo'),
('openai_api_key',       '',                                                                               'OpenAI API-Key (für KI-SEO-Generierung)', 'seo');

-- ============================================================
-- Lead-generation: site-wide settings
-- ============================================================
INSERT IGNORE INTO settings (setting_key, setting_value, setting_label, setting_group) VALUES
('whatsapp_number',   '',  'WhatsApp-Nummer (intl. Format, z. B. 4915123456789)', 'general'),
('announcement_text', '',  'Ankündigungsleiste – Text (leer = ausgeblendet)',      'general'),
('announcement_url',  '',  'Ankündigungsleiste – Link-URL (optional)',             'general'),
('announcement_bg',   '#d32f2f', 'Ankündigungsleiste – Hintergrundfarbe',         'general');

-- ============================================================
-- Blog posts (for SEO content marketing)
-- ============================================================
CREATE TABLE IF NOT EXISTS blog_posts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) NOT NULL UNIQUE,
    excerpt         TEXT,
    content         LONGTEXT,
    meta_title      VARCHAR(255),
    meta_description TEXT,
    meta_keywords   TEXT,
    featured_image  VARCHAR(512),
    status          ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at    DATETIME DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- Mass-Mailing Module
-- ============================================================

-- SMTP account pool (multiple accounts for rotation)
CREATE TABLE IF NOT EXISTS mailing_smtp_accounts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    label        VARCHAR(100)                  NOT NULL DEFAULT '',
    host         VARCHAR(255)                  NOT NULL DEFAULT '',
    port         SMALLINT UNSIGNED             NOT NULL DEFAULT 587,
    username     VARCHAR(255)                  NOT NULL DEFAULT '',
    password     VARCHAR(255)                  NOT NULL DEFAULT '',
    secure       ENUM('tls','ssl','none')       NOT NULL DEFAULT 'tls',
    from_email   VARCHAR(255)                  NOT NULL DEFAULT '',
    from_name    VARCHAR(255)                  NOT NULL DEFAULT '',
    active       TINYINT(1)                    NOT NULL DEFAULT 1,
    emails_sent  INT UNSIGNED                  NOT NULL DEFAULT 0,
    last_used_at DATETIME                               DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configurable mailing parameters
CREATE TABLE IF NOT EXISTS mailing_settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_label VARCHAR(255),
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO mailing_settings (setting_key, setting_value, setting_label) VALUES
('emails_per_account',        '5',     'E-Mails pro SMTP-Account (dann rotieren)'),
('pause_between_emails_ms',   '3000',  'Pause zwischen E-Mails (Millisekunden)'),
('pause_between_accounts_ms', '15000', 'Pause zwischen SMTP-Wechsel (Millisekunden)'),
('max_daily_per_account',     '200',   'Max. E-Mails pro Account pro Tag'),
('unsubscribe_url',           '',      'Globaler Abmelde-Link (leer = auto-generiert)'),
('track_opens',               '0',     'Öffnungs-Tracking aktiv (1 = ja)');

-- Email templates
CREATE TABLE IF NOT EXISTS mailing_templates (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    subject      VARCHAR(255) NOT NULL DEFAULT '',
    body_html    LONGTEXT,
    body_text    TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campaigns
CREATE TABLE IF NOT EXISTS mailing_campaigns (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(255)  NOT NULL,
    template_id  INT           DEFAULT NULL,
    status       ENUM('draft','running','paused','completed','failed') NOT NULL DEFAULT 'draft',
    total        INT UNSIGNED  NOT NULL DEFAULT 0,
    sent         INT UNSIGNED  NOT NULL DEFAULT 0,
    failed       INT UNSIGNED  NOT NULL DEFAULT 0,
    opens        INT UNSIGNED  NOT NULL DEFAULT 0,
    current_smtp_account_id INT DEFAULT NULL,
    current_smtp_batch_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at   DATETIME DEFAULT NULL,
    finished_at  DATETIME DEFAULT NULL,
    FOREIGN KEY (template_id) REFERENCES mailing_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-recipient list (loaded from CSV or manual input)
CREATE TABLE IF NOT EXISTS mailing_recipients (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id     INT           NOT NULL,
    email           VARCHAR(255)  NOT NULL,
    name            VARCHAR(255)  DEFAULT '',
    scam_platform   VARCHAR(255)  DEFAULT '',
    status          ENUM('pending','sent','failed','bounced','unsubscribed') NOT NULL DEFAULT 'pending',
    smtp_account_id INT           DEFAULT NULL,
    sent_at         DATETIME      DEFAULT NULL,
    error_msg       VARCHAR(512)  DEFAULT NULL,
    open_token      VARCHAR(64)   DEFAULT NULL,
    opened_at       DATETIME      DEFAULT NULL,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES mailing_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: KryptoxPay professional German email template
INSERT IGNORE INTO mailing_templates (name, subject, body_html, body_text)
SELECT 'KryptoxPay – Professionell (DE)',
       'Wichtige Information zu Ihren digitalen Vermögenswerten',
       '<placeholder – see admin/mailing/templates.php for full HTML>',
       'Sehr geehrte/r {{name}},\n\nwir möchten Sie auf eine wichtige Möglichkeit hinweisen.\n\nBitte besuchen Sie uns unter: {{site_url}}\n\nMit freundlichen Grüßen,\n{{sender_name}}\n{{company_name}}\n\nAbmelden: {{unsubscribe_url}}'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM mailing_templates WHERE name = 'KryptoxPay – Professionell (DE)');
