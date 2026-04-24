-- ============================================================
-- VerlustRückholung – Idempotent Migration Script
-- ============================================================
-- This file can be run against any database state – fresh install
-- or any existing version – and will bring the schema up to date
-- without errors.  Run it with:
--
--   mysql -u <user> -p < database/migrate.sql
--
-- All table CREATE statements use IF NOT EXISTS.
-- All column additions are guarded by IF NOT EXISTS checks inside
-- a stored procedure so the script is safe to re-run.
--
-- Compatible with MySQL 5.7+ and MariaDB 10.2+
-- ============================================================

-- ── 1. Database ───────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS scmlds
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE scmlds;

-- ── 2. Core tables (CREATE IF NOT EXISTS) ─────────────────────

CREATE TABLE IF NOT EXISTS admin_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email         VARCHAR(255),
    full_name     VARCHAR(255),
    last_login    DATETIME,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scam_categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    first_name        VARCHAR(100) NOT NULL,
    last_name         VARCHAR(100) NOT NULL,
    email             VARCHAR(255) NOT NULL,
    phone             VARCHAR(50),
    country           VARCHAR(100),
    year_lost         SMALLINT UNSIGNED,
    amount_lost       DECIMAL(15, 2),
    platform_category VARCHAR(100),
    case_description  TEXT,
    status            ENUM('Neu','In Bearbeitung','Kontaktiert','Erfolgreich','Abgelehnt') DEFAULT 'Neu',
    admin_notes       TEXT,
    ip_address        VARCHAR(45),
    lead_source       VARCHAR(80)  DEFAULT 'website',
    utm_source        VARCHAR(100) DEFAULT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    admin_id   INT,
    action     VARCHAR(255) NOT NULL,
    details    TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_label VARCHAR(255),
    setting_group VARCHAR(100) DEFAULT 'general',
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS smtp_settings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    host       VARCHAR(255)            NOT NULL DEFAULT '',
    port       SMALLINT UNSIGNED       NOT NULL DEFAULT 587,
    username   VARCHAR(255)            NOT NULL DEFAULT '',
    password   VARCHAR(255)            NOT NULL DEFAULT '',
    secure     ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
    debug      TINYINT                 NOT NULL DEFAULT 0,
    from_email VARCHAR(255)            NOT NULL DEFAULT '',
    from_name  VARCHAR(255)            NOT NULL DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS telegram_settings (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    bot_token VARCHAR(255) NOT NULL DEFAULT '',
    chat_id   VARCHAR(100) NOT NULL DEFAULT '',
    active    TINYINT(1)   NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visitor_logs (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    ip_address     VARCHAR(45)  NOT NULL,
    referrer       VARCHAR(512) DEFAULT '',
    utm_source     VARCHAR(100) DEFAULT NULL,
    utm_medium     VARCHAR(100) DEFAULT NULL,
    utm_campaign   VARCHAR(150) DEFAULT NULL,
    utm_content    VARCHAR(150) DEFAULT NULL,
    utm_term       VARCHAR(150) DEFAULT NULL,
    gclid          VARCHAR(200) DEFAULT NULL,
    landing_page   VARCHAR(512) DEFAULT NULL,
    user_agent     VARCHAR(512) DEFAULT '',
    time_on_site   INT UNSIGNED NOT NULL DEFAULT 0,
    submitted_lead TINYINT(1)   NOT NULL DEFAULT 0,
    lead_id        INT DEFAULT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(255) NOT NULL,
    slug             VARCHAR(255) NOT NULL UNIQUE,
    excerpt          TEXT,
    content          LONGTEXT,
    meta_title       VARCHAR(255),
    meta_description TEXT,
    meta_keywords    TEXT,
    featured_image   VARCHAR(512),
    status           ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at     DATETIME DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Mass-Mailing module tables ─────────────────────────────

CREATE TABLE IF NOT EXISTS mailing_smtp_accounts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    label        VARCHAR(100)            NOT NULL DEFAULT '',
    host         VARCHAR(255)            NOT NULL DEFAULT '',
    port         SMALLINT UNSIGNED       NOT NULL DEFAULT 587,
    username     VARCHAR(255)            NOT NULL DEFAULT '',
    password     VARCHAR(255)            NOT NULL DEFAULT '',
    secure       ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',
    from_email   VARCHAR(255)            NOT NULL DEFAULT '',
    from_name    VARCHAR(255)            NOT NULL DEFAULT '',
    active       TINYINT(1)              NOT NULL DEFAULT 1,
    emails_sent  INT UNSIGNED            NOT NULL DEFAULT 0,
    last_used_at DATETIME                         DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mailing_settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_label VARCHAR(255),
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mailing_templates (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    subject    VARCHAR(255) NOT NULL DEFAULT '',
    body_html  LONGTEXT,
    body_text  TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mailing_campaigns (
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    name                     VARCHAR(255) NOT NULL,
    template_id              INT          DEFAULT NULL,
    status                   ENUM('draft','running','paused','completed','failed') NOT NULL DEFAULT 'draft',
    total                    INT UNSIGNED  NOT NULL DEFAULT 0,
    sent                     INT UNSIGNED  NOT NULL DEFAULT 0,
    failed                   INT UNSIGNED  NOT NULL DEFAULT 0,
    opens                    INT UNSIGNED  NOT NULL DEFAULT 0,
    current_smtp_account_id  INT           DEFAULT NULL,
    current_smtp_batch_count INT UNSIGNED  NOT NULL DEFAULT 0,
    created_at               TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at               DATETIME DEFAULT NULL,
    finished_at              DATETIME DEFAULT NULL,
    FOREIGN KEY (template_id) REFERENCES mailing_templates(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- mailing_recipients: full definition including all columns added across migrations.
-- New installs get the complete table; existing installs are handled by the ALTER
-- procedure in section 4 below.
CREATE TABLE IF NOT EXISTS mailing_recipients (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id     INT           NOT NULL,
    email           VARCHAR(255)  NOT NULL,
    name            VARCHAR(255)  DEFAULT '',
    scam_platform   VARCHAR(255)  DEFAULT '',
    email_validity  ENUM('valid','invalid') NOT NULL DEFAULT 'valid',
    status          ENUM('pending','sent','failed','bounced','unsubscribed') NOT NULL DEFAULT 'pending',
    smtp_account_id INT           DEFAULT NULL,
    sent_at         DATETIME      DEFAULT NULL,
    error_msg       VARCHAR(512)  DEFAULT NULL,
    open_token      VARCHAR(64)   DEFAULT NULL,
    opened_at       DATETIME      DEFAULT NULL,
    click_token     VARCHAR(64)   DEFAULT NULL,
    clicked_at      DATETIME      DEFAULT NULL,
    click_count     INT UNSIGNED  NOT NULL DEFAULT 0,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES mailing_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Idempotent ALTER TABLE migrations ──────────────────────
-- All column additions are guarded by information_schema checks so
-- the script is safe to run on databases that already have them.

DROP PROCEDURE IF EXISTS _vr_migrate_all;
DELIMITER $$
CREATE PROCEDURE _vr_migrate_all()
BEGIN

    -- ── leads ────────────────────────────────────────────────

    -- leads.country  (pre-2024 upgrade)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'leads'
          AND column_name = 'country'
    ) THEN
        ALTER TABLE leads ADD COLUMN country VARCHAR(100) AFTER phone;
    END IF;

    -- leads.year_lost  (pre-2024 upgrade)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'leads'
          AND column_name = 'year_lost'
    ) THEN
        ALTER TABLE leads ADD COLUMN year_lost SMALLINT UNSIGNED AFTER country;
    END IF;

    -- leads.lead_source  (UTM / lead-gen feature)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'leads'
          AND column_name = 'lead_source'
    ) THEN
        ALTER TABLE leads ADD COLUMN lead_source VARCHAR(80) DEFAULT 'website' AFTER ip_address;
    END IF;

    -- leads.utm_source
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'leads'
          AND column_name = 'utm_source'
    ) THEN
        ALTER TABLE leads ADD COLUMN utm_source VARCHAR(100) DEFAULT NULL AFTER lead_source;
    END IF;

    -- ── visitor_logs ─────────────────────────────────────────

    -- visitor_logs.utm_source
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs'
          AND column_name = 'utm_source'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN utm_source VARCHAR(100) DEFAULT NULL AFTER referrer;
    END IF;

    -- visitor_logs.utm_medium
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs'
          AND column_name = 'utm_medium'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN utm_medium VARCHAR(100) DEFAULT NULL AFTER utm_source;
    END IF;

    -- visitor_logs.utm_campaign
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs'
          AND column_name = 'utm_campaign'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN utm_campaign VARCHAR(150) DEFAULT NULL AFTER utm_medium;
    END IF;

    -- visitor_logs.utm_content
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs'
          AND column_name = 'utm_content'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN utm_content VARCHAR(150) DEFAULT NULL AFTER utm_campaign;
    END IF;

    -- visitor_logs.utm_term
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs'
          AND column_name = 'utm_term'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN utm_term VARCHAR(150) DEFAULT NULL AFTER utm_content;
    END IF;

    -- visitor_logs.gclid
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs'
          AND column_name = 'gclid'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN gclid VARCHAR(200) DEFAULT NULL AFTER utm_term;
    END IF;

    -- visitor_logs.landing_page
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'visitor_logs'
          AND column_name = 'landing_page'
    ) THEN
        ALTER TABLE visitor_logs ADD COLUMN landing_page VARCHAR(512) DEFAULT NULL AFTER gclid;
    END IF;

    -- ── mailing_recipients ───────────────────────────────────

    -- mailing_recipients.scam_platform  (batch-send feature)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'mailing_recipients'
          AND column_name = 'scam_platform'
    ) THEN
        ALTER TABLE mailing_recipients
            ADD COLUMN scam_platform VARCHAR(255) DEFAULT '' AFTER name;
    END IF;

    -- mailing_recipients.email_validity  (validity-aware import)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'mailing_recipients'
          AND column_name = 'email_validity'
    ) THEN
        ALTER TABLE mailing_recipients
            ADD COLUMN email_validity ENUM('valid','invalid') NOT NULL DEFAULT 'valid'
            AFTER scam_platform;
    END IF;

    -- mailing_recipients.click_token  (click tracking)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'mailing_recipients'
          AND column_name = 'click_token'
    ) THEN
        ALTER TABLE mailing_recipients
            ADD COLUMN click_token VARCHAR(64) DEFAULT NULL
            AFTER opened_at;
    END IF;

    -- mailing_recipients.clicked_at  (click tracking)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'mailing_recipients'
          AND column_name = 'clicked_at'
    ) THEN
        ALTER TABLE mailing_recipients
            ADD COLUMN clicked_at DATETIME DEFAULT NULL
            AFTER click_token;
    END IF;

    -- mailing_recipients.click_count  (click tracking)
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = 'mailing_recipients'
          AND column_name = 'click_count'
    ) THEN
        ALTER TABLE mailing_recipients
            ADD COLUMN click_count INT UNSIGNED NOT NULL DEFAULT 0
            AFTER clicked_at;
    END IF;

END$$
DELIMITER ;

CALL _vr_migrate_all();
DROP PROCEDURE IF EXISTS _vr_migrate_all;

-- ── 5. Seed data (all idempotent via INSERT IGNORE / ON DUPLICATE KEY) ──

-- Default admin (password: "password" – change immediately!)
INSERT INTO admin_users (username, password_hash, email, full_name) VALUES
('admin',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'info@verlustrueckholung.de',
 'Administrator')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- Scam categories
INSERT IGNORE INTO scam_categories (name, description) VALUES
('Krypto-Betrug',                       'Betrügerische Kryptowährungs-Investitionsplattformen'),
('Forex-Betrug',                        'Gefälschte Forex-Handelsplattformen und Broker'),
('Fake-Broker',                         'Unregulierte und betrügerische Investment-Broker'),
('Romance-Scam mit Investitionsbetrug', 'Romantik-Betrug kombiniert mit gefälschten Investitionen'),
('Binäre Optionen',                     'Binäre Optionen und Online-Trading-Betrug'),
('Andere',                              'Sonstige Anlage- und Investitionsbetrug');

-- Site settings
INSERT IGNORE INTO settings (setting_key, setting_value, setting_label, setting_group) VALUES
('company_name',               'VerlustRückholung',                                                    'Firmenname',                                'general'),
('site_url',                   'https://verlustrueckholung.de',                                        'Website-URL',                               'general'),
('admin_email',                'info@verlustrueckholung.de',                                           'Admin-E-Mail',                              'general'),
('from_email',                 'noreply@verlustrueckholung.de',                                        'Absender-E-Mail',                           'general'),
('from_name',                  'VerlustRückholung',                                                    'Absender-Name',                             'general'),
('page_title',                 'VerlustRückholung – KI-gestützte Kapitalrückholung bei Anlagebetrug', 'Seiten-Titel',                              'general'),
('modal_delay_seconds',        '60',                                                                   'Sekunden bis Modal erscheint',              'general'),
('whatsapp_number',            '',                                                                     'WhatsApp-Nummer (intl. Format)',             'general'),
('announcement_text',          '',                                                                     'Ankündigungsleiste – Text',                 'general'),
('announcement_url',           '',                                                                     'Ankündigungsleiste – Link-URL',             'general'),
('announcement_bg',            '#d32f2f',                                                              'Ankündigungsleiste – Hintergrundfarbe',     'general'),
('send_email_on_submission',   '1',                                                                    'E-Mail nach Formular-Absendung senden',     'notifications'),
('telegram_notification_active','0',                                                                   'Telegram-Benachrichtigung aktiv',           'notifications'),
('active_design',              'index2',                                                               'Aktives Seitendesign',                      'design'),
('email_verification_required','0',                                                                    'E-Mail-Verifizierung via Code erforderlich','general'),
('og_image',                   'https://verlustrueckholung.de/assets/images/og-image.jpg',             'Open Graph Bild-URL (1200×630 px)',         'seo'),
('meta_description',           'VerlustRückholung hilft Opfern von Anlagebetrug ihr Kapital zurückzufordern. KI-gestützte Analyse, internationale Experten, 87% Erfolgsquote. Kostenlose Erstprüfung.', 'Meta-Beschreibung (max. 160 Zeichen)', 'seo'),
('meta_keywords',              'Geld zurückfordern, Anlagebetrug, Kapitalrückholung, Krypto-Betrug, Forex-Betrug, Scam Recovery, Verlust zurückfordern', 'Meta-Keywords (kommagetrennt)', 'seo'),
('robots_meta',                'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1', 'Robots Meta-Tag Inhalt',            'seo'),
('twitter_handle',             '',                                                                     'Twitter/X Handle',                          'seo'),
('google_analytics_id',        '',                                                                     'Google Analytics 4 Measurement-ID',         'seo'),
('openai_api_key',             '',                                                                     'OpenAI API-Key',                            'seo');

-- SMTP settings
INSERT IGNORE INTO smtp_settings (host, port, username, password, secure, from_email, from_name) VALUES
('smtp.verlustrueckholung.de', 587, 'noreply@verlustrueckholung.de', '', 'tls',
 'noreply@verlustrueckholung.de', 'VerlustRückholung');

-- Telegram settings
INSERT IGNORE INTO telegram_settings (bot_token, chat_id, active) VALUES ('', '', 0);

-- Mailing settings
INSERT IGNORE INTO mailing_settings (setting_key, setting_value, setting_label) VALUES
('emails_per_account',        '5',     'E-Mails pro SMTP-Account (dann rotieren)'),
('pause_between_emails_ms',   '3000',  'Pause zwischen E-Mails (Millisekunden)'),
('pause_between_accounts_ms', '15000', 'Pause zwischen SMTP-Wechsel (Millisekunden)'),
('max_daily_per_account',     '200',   'Max. E-Mails pro Account pro Tag'),
('unsubscribe_url',           '',      'Globaler Abmelde-Link (leer = auto-generiert)'),
('track_opens',               '0',     'Öffnungs-Tracking aktiv (1 = ja)');

-- Seed default email template (placeholder body — real HTML is generated by PHP on first visit)
INSERT IGNORE INTO mailing_templates (name, subject, body_html, body_text)
SELECT 'KryptoxPay – Professionell (DE)',
       'Wichtige Information zu Ihren digitalen Vermögenswerten',
       '<placeholder – see admin/mailing/templates.php for full HTML>',
       'Sehr geehrte/r {{name}},\n\nwir möchten Sie auf eine wichtige Möglichkeit hinweisen.\n\nBitte besuchen Sie uns unter: {{site_url}}\n\nMit freundlichen Grüßen,\n{{sender_name}}\n{{company_name}}\n\nAbmelden: {{unsubscribe_url}}'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM mailing_templates WHERE name = 'KryptoxPay – Professionell (DE)'
);

-- mailing_campaigns: auto_send_active (background runner flag)
DO BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE table_schema = DATABASE() AND table_name = 'mailing_campaigns' AND column_name = 'auto_send_active'
    ) THEN
        ALTER TABLE mailing_campaigns ADD COLUMN auto_send_active TINYINT(1) NOT NULL DEFAULT 0 AFTER current_smtp_batch_count;
    END IF;
END;

-- ── End of migration ──────────────────────────────────────────
