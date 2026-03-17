# Scmlds – VerlustRückholung

KI-gestützte Kapitalrückholung bei Anlagebetrug — PHP/MySQL application with admin backend, SEO management, AI-powered content generation, and a blog module.

---

## Requirements

- PHP 8.3 (with PHP-FPM)
- MySQL 5.7+ / MariaDB 10+
- **Nginx**
- [Composer](https://getcomposer.org/) for PHP dependencies

---

## Nginx Setup

This project includes a ready-to-use Nginx server block at **`nginx.conf`**.

**Server details:**
- Root: `/var/www/verlustrueckholung.de`
- PHP-FPM socket: `unix:/var/run/php/php8.3-fpm.sock`
- SSL: managed by Certbot

### 1. Install dependencies

```bash
composer install --no-dev
```

### 2. Set up the database

```bash
mysql -u root -p < database/schema.sql
```

### 3. Configure the application

Edit `config/config.php` with your database credentials, domain, and email settings.

### 4. Deploy the Nginx config

```bash
sudo cp nginx.conf /etc/nginx/sites-available/verlustrueckholung.de
sudo ln -s /etc/nginx/sites-available/verlustrueckholung.de \
           /etc/nginx/sites-enabled/verlustrueckholung.de
```

### 5. Obtain / renew TLS certificates (Let's Encrypt)

```bash
sudo certbot --nginx -d verlustrueckholung.de -d www.verlustrueckholung.de
```

### 6. Test and reload

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---


---

## Admin Panel

Navigate to `/admin/` and log in with the default credentials:

| Field    | Default value |
|----------|---------------|
| Username | `admin`       |
| Password | `password`    |

**⚠️ Change the password immediately after first login.**

Generate a new password hash:
```bash
php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"
```

### Admin features

| Section | URL | Description |
|---------|-----|-------------|
| Dashboard | `/admin/` | Stats overview |
| Leads | `/admin/leads.php` | View, filter, edit leads |
| Visitors | `/admin/visitors.php` | Visitor log with UTM data |
| Statistics | `/admin/statistics.php` | Charts and analytics |
| SEO | `/admin/seo.php` | Meta tags, OG, AI generation, checklist |
| Blog | `/admin/blog.php` | Manage blog posts |
| Design | `/admin/design.php` | Switch landing page theme |
| Settings | `/admin/settings.php` | SMTP, Telegram, general settings |

---

## AI SEO Generation

The SEO admin page (`/admin/seo.php → KI-Generierung tab`) uses **OpenAI gpt-4o-mini** to generate:
- Meta descriptions
- SEO keyword lists

To enable:
1. Get an API key at [platform.openai.com](https://platform.openai.com/api-keys)
2. Enter it in **Admin → SEO → KI-Generierung**

---

## Blog

Public blog at `/blog/` with individual posts at `/blog/{slug}`.

Each post has:
- Per-post SEO (meta title, description, keywords)
- JSON-LD `BlogPosting` structured data
- Open Graph / Twitter Card tags
- Social share buttons

Blog posts are created and edited at `/admin/blog_edit.php`.

---

## URL Structure

| URL | Handler |
|-----|---------|
| `/` | `index.php` → active theme |
| `/blog/` | `blog/index.php` |
| `/blog/{slug}` | `blog/post.php?slug={slug}` |
| `/sitemap.xml` | `sitemap.php` (dynamic, includes blog) |
| `/robots.txt` | static file |
| `/admin/*` | Admin panel |

---

## Security Notes

- Sensitive files (`config/config.php`, `includes/*.php`, `composer.json`) are blocked by both Nginx and `.htaccess`
- All admin pages require session authentication
- CSRF protection is implemented on all forms
- SQL injection prevented via PDO prepared statements
- XSS prevented via `htmlspecialchars()` on all output
