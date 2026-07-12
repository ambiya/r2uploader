# R2 Uploader

PHP web app to upload and manage files in **Cloudflare R2** (S3-compatible API). Built with a browser UI for dynamic bucket management, role-based authentication (Admin, Editor), and public CDN URLs.

## Features

- **Upload** multiple files from your device or a single file from a remote URL
- **Browse** objects by folder prefix (`?action=list`)
- **Delete** and **rename** objects
- **Auto-retention**: when uploading into a folder, keep only the **N newest** files (configurable via `FOLDER_MAX_FILES`; older ones are removed)
- Light/dark theme UI with **Multi-language support** (English and Indonesian)
- SSRF checks on remote URL uploads
- **Role-based Authentication**: SQLite-backed user management with Admin and Editor roles.
- **Dynamic Settings**: Manage your R2 buckets and application configuration directly from the browser UI.
- **Activity Dashboard**: Track user actions like uploads, deletes, and logins (Admin only).

## Requirements

- PHP **8.0+** with extensions: `curl`, `openssl`, `mbstring`, `pdo`, `pdo_sqlite`
- [Composer](https://getcomposer.org/)
- Cloudflare R2 bucket(s) and API token (S3-compatible access key)

## Installation

```bash
git clone <repository-url> r2uploader
cd r2uploader
composer install --no-dev
cp .env.example .env
```

Edit `.env` with your initial credentials. **Do not commit `.env`.** After the first run, application settings and buckets can be managed directly from the Admin settings page in the UI.

Make sure the `data/` directory is writable by your web server, as the SQLite database will be created there:
```bash
chmod 775 data/
```

## Configuration

Initial setup uses `.env` for bootstrapping credentials, but they (and bucket definitions) can later be managed in the web UI via the **Settings** menu.

| Variable | Description |
|----------|-------------|
| `APP_DEBUG` | Set to `true` to show PHP errors in the browser (use `false` in production) |
| `AUTH_USER` | Initial Admin username (used to seed the database on first run) |
| `AUTH_PASS` | Initial Admin password (used to seed the database on first run) |
| `R2_ACCOUNT_ID` | Cloudflare account ID |
| `R2_ACCESS_KEY_ID` | R2 S3 API access key ID |
| `R2_SECRET_ACCESS_KEY` | R2 S3 API secret |
| `FOLDER_MAX_FILES` | Auto-retention limit for uploads into a folder (set `0` to disable; default `0`) |
| `ALLOWED_EXTENSIONS` | Comma-separated list of allowed file extensions (e.g. `jpg,png,pdf`). If empty, all extensions are allowed. |

Create R2 API tokens in the Cloudflare dashboard under **R2 → Manage API tokens** with read/write access to the target buckets.

## Local development

From the project root, start the PHP built-in server pointing to the `public/` directory:

```bash
php -S localhost:8080 -t public/
```

Open `http://localhost:8080` and sign in. On the first run, it will migrate the `AUTH_USER` and `AUTH_PASS` from `.env` to create the initial Admin user.

For verbose PHP errors while developing, set `APP_DEBUG=true` in `.env`.

For large uploads, you may need to raise PHP limits in `php.ini`:

```ini
upload_max_filesize = 512M
post_max_size = 512M
max_execution_time = 300
```

## Production deployment

Point the web server **document root** at the `public/` directory so `public/index.php` handles all requests.

### Apache

Ensure `mod_rewrite` is enabled. A `.htaccess` file is already provided in the `public/` directory to route requests to `index.php`.

Keep your `.env` and `data/` directory outside of the document root (which is achieved by setting the document root to `public/`).

### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/r2uploader/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Deny access to hidden files just in case
    location ~ /\. {
        deny all;
    }
}
```

Use HTTPS in production.

## Usage

| URL | Purpose |
|-----|---------|
| `/` | Landing page (Login or Upload redirect) |
| `/?action=upload` | Upload form (remote URL or multiple files) |
| `/?action=list&type=<bucket_id>` | List a specific bucket (e.g. apps, games) |
| `/?action=list&type=<bucket_id>&prefix=Folder/` | List inside a prefix |
| `/?action=api_list` | Fetch files as JSON (Internal API) |
| `/?action=dashboard` | View activity logs (Admin only) |
| `/?action=users` | Manage users (Admin only) |
| `/?action=settings` | Manage system settings and R2 buckets (Admin only) |

Delete and rename are triggered from the file list UI (POST).

## Project layout

```
public/            # Web root (index.php, CSS, JS)
src/               # Application logic (Controllers, Services, Auth, Routing)
templates/         # HTML views and UI components
lang/              # Language files for i18n localization
data/              # SQLite database storage (must be writable)
.env.example       # Environment template
composer.json      # PHP dependencies (aws-sdk-php, phpdotenv)
```

## Security notes

- Point your web server to `public/` to prevent access to source files, `data/` database, and `.env`.
- Keep `.env` out of version control (see `.gitignore`).
- Change the admin password or create a new user after the initial migration.
- Keep `APP_DEBUG=false` in production.
- Remote URL upload follows redirects; review hardening if the app is exposed on the public internet.
