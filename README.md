# Scmlds – VerlustRückholung

KI-gestützte Kapitalrückholung bei Anlagebetrug — PHP/MySQL application with admin backend, SEO management, AI-powered content generation, and a blog module.

---

## Requirements

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10+
- **Nginx** (recommended) or Apache with `mod_rewrite`
- PHP-FPM (for Nginx)
- [Composer](https://getcomposer.org/) for PHP dependencies

---

## Nginx Setup (recommended)

This project includes a ready-to-use Nginx server block at **`nginx.conf`**.

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

### 4. Configure Nginx

```bash
# Open the config and replace the placeholder values:
#   - "verlustrueckholung.de"      → your domain
#   - "/var/www/verlustrueckholung" → absolute path to this directory
#   - "127.0.0.1:9000"             → your PHP-FPM socket/address
#     (e.g. unix:/run/php/php8.2-fpm.sock)
nano nginx.conf

# Copy to Nginx sites
sudo cp nginx.conf /etc/nginx/sites-available/your-site
sudo ln -s /etc/nginx/sites-available/your-site /etc/nginx/sites-enabled/your-site
```

### 5. Obtain TLS certificates (Let's Encrypt)

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 6. Test and reload

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### PHP-FPM socket (Unix socket alternative)

If your PHP-FPM uses a Unix socket instead of TCP, replace:
```nginx
fastcgi_pass 127.0.0.1:9000;
```
with (adjust PHP version as needed):
```nginx
fastcgi_pass unix:/run/php/php8.2-fpm.sock;
```

---

## Apache Setup (alternative)

A `.htaccess` is included in the repo root and `blog/` directory and works out of the box when `mod_rewrite` and `AllowOverride All` are enabled.

```apache
# In your Apache VirtualHost:
<Directory /var/www/verlustrueckholung>
    AllowOverride All
    Options -Indexes
</Directory>
```

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
