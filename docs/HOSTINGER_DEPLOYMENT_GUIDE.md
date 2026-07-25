# Hostinger Deployment Guide — TriNova Client Portal

This step-by-step guide walks you through deploying the **TriNova Client Portal** onto **Hostinger Shared Hosting** or **Hostinger Cloud/VPS** using Hostinger hPanel.

---

## Prerequisites Checklist
1. Hostinger Hosting Plan with PHP **8.2 or 8.3** enabled.
2. Active domain or Hostinger Temporary Domain (e.g. `your-domain.preview-domain.com`).
3. Access to Hostinger **hPanel** (File Manager & MySQL Databases).

---

## Step 1: Create MySQL Database in Hostinger hPanel

1. Log in to **Hostinger hPanel**.
2. Navigate to **Databases** → **MySQL Databases**.
3. Create a new database:
   - **Database Name:** e.g., `u123456789_trinova`
   - **Username:** e.g., `u123456789_admin`
   - **Password:** Generate a strong password (save this for `.env`).
4. Click **Create**.
5. Once created, click **Enter phpMyAdmin** next to the database.
6. In phpMyAdmin, click the **Import** tab at the top.
7. Click **Choose File**, select `config/database.sql` from your project repository, and click **Import** at the bottom.

---

## Step 2: Upload Files to Hostinger File Manager

1. In hPanel, go to **Files** → **File Manager** (`files.hostinger.com`).
2. Navigate to the root directory (one level above `public_html`, e.g. `/home/u123456789/`).
3. Create two new folders:
   - `trinova_app`
   - `trinova_storage` (inside `trinova_storage`, create subfolders `uploads` and `logs`).

4. **Upload Application Code to `trinova_app`**:
   - Zip your local project files (excluding `node_modules` and `.git`).
   - Upload the ZIP into `/home/u123456789/trinova_app/` and extract it.
   - Ensure `application/`, `config/`, `vendor/`, and `.env.example` are inside `trinova_app`.

5. **Upload Public Web Assets to `public_html`**:
   - Move or upload all contents from the project `public/` directory into Hostinger's `public_html/` folder:
     - `index.php`
     - `wordpress-login-widget.html`
     - `.htaccess` (make sure hidden files are visible in Hostinger File Manager)

---

## Step 3: Configure `.env` Parameters

1. Inside `trinova_app/`, create or edit `.env`:
```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-temp-domain.com
APP_SECRET=e7b4f8d9a2c3e1f0b4d5e6a7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7

# Hostinger MySQL Database Credentials
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=u123456789_trinova
DB_USER=u123456789_admin
DB_PASS=YourStrongDatabasePasswordHere

# Storage Path (Outside public web root for security)
STORAGE_DIR=/home/u123456789/trinova_storage

# Mailer Settings
MAIL_DRIVER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=YourMailPassword
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="TriNova Accounting Portal"
```

---

## Step 4: Update `public_html/index.php` Path References

Open `/public_html/index.php` in Hostinger File Manager and update lines 7 and 32 to point to `trinova_app`:

```php
<?php

/**
 * TriNova Client Portal - Front Controller
 */

require_once __DIR__ . '/../trinova_app/vendor/autoload.php';

use Application\Core\Application;
use Application\Middleware\AuthMiddleware;
use Application\Middleware\CsrfMiddleware;
use Application\Middleware\RoleMiddleware;
use Application\Middleware\SessionTimeoutMiddleware;
use Application\Controllers\Auth\AuthController;
use Application\Controllers\Auth\PasswordResetController;
use Application\Controllers\Client\DashboardController as ClientDashboardController;
use Application\Controllers\Client\DocumentController as ClientDocumentController;
use Application\Controllers\Client\MessageController as ClientMessageController;
use Application\Controllers\Client\RequestController as ClientRequestController;
use Application\Controllers\Client\DeadlineController as ClientDeadlineController;
use Application\Controllers\Client\MeetingController as ClientMeetingController;
use Application\Controllers\Client\ProfileController as ClientProfileController;
use Application\Controllers\Staff\DashboardController as StaffDashboardController;
use Application\Controllers\Staff\ClientController as StaffClientController;
use Application\Controllers\Staff\DocumentController as StaffDocumentController;
use Application\Controllers\Staff\RequestController as StaffRequestController;
use Application\Controllers\Staff\MessageController as StaffMessageController;
use Application\Controllers\Staff\DeadlineController as StaffDeadlineController;
use Application\Controllers\Staff\UserAdminController as StaffUserAdminController;
use Application\Controllers\Staff\AuditController as StaffAuditController;

// Simple dotenv loader fallback for environment variables
$envFile = dirname(__DIR__) . '/trinova_app/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim(trim($val), '"\'');
        }
    }
}

$app = new Application(dirname(__DIR__) . '/trinova_app');

// ... (rest of routes)
```

---

## Step 5: Verify `.htaccess` in Hostinger `public_html`

Ensure `/public_html/.htaccess` has URL rewriting enabled:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>
```

---

## Step 6: Select PHP Version in Hostinger

1. In hPanel, go to **Advanced** → **PHP Configuration**.
2. Select **PHP 8.2** or **PHP 8.3**.
3. Under **PHP Extensions**, ensure `pdo_mysql`, `openssl`, `mbstring`, `fileinfo`, and `curl` are enabled.
4. Click **Save**.

---

## Step 7: Test Your Live Portal

1. Visit your temporary domain in a browser: `https://your-temp-domain.com/login`
2. Log in using default credentials:
   - **Staff Login:** `kirsty@trinova.co.uk` / `password123`
   - **Client Login:** `nick@powellelectrical.co.uk` / `password123`
