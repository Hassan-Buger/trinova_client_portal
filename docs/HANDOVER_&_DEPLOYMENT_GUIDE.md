# TriNova Client Portal — Handover & Production Deployment Guide

This guide provides technical instructions for hosting, configuring, securing, and maintaining the **TriNova Client Portal** on LAMP-compatible web servers.

---

## 1. System Requirements & Stack Overview

- **Server OS:** Ubuntu 22.04 LTS / Debian 12 / RHEL 9 (or standard LAMP hosting e.g. cPanel/Plesk)
- **Web Server:** Apache 2.4 (with `mod_rewrite` enabled) or Nginx 1.20+
- **PHP:** PHP 8.1 / 8.2 / 8.3 with extensions: `pdo_mysql`, `openssl`, `mbstring`, `fileinfo`, `json`
- **Database:** MySQL 8.0+ or MariaDB 10.6+ (`utf8mb4` character set)
- **SSL/TLS:** Valid HTTPS SSL certificate (Let's Encrypt / Cloudflare SSL)

---

## 2. Directory Structure & Storage Isolation

To maintain client confidentiality and compliance standards, uploaded documents **must reside outside the public web root**.

```
/var/www/
├── trinova/                    <-- Codebase root
│   ├── application/            <-- App controllers, models, views, core logic
│   ├── config/                 <-- Database SQL schema & configurations
│   ├── docs/                   <-- Handover documentation
│   ├── public/                 <-- PUBLIC WEB ROOT (index.php, CSS, JS, assets)
│   └── vendor/                 <-- Composer dependencies
└── trinova_storage/            <-- PRIVATE STORAGE (outside public web root)
    ├── logs/                   <-- System & audit logs
    └── uploads/                <-- Encrypted uploaded document artifacts
```

---

## 3. Deployment & Setup Checklist

### Step 1: Clone Codebase & Install Dependencies
```bash
cd /var/www/trinova
composer install --no-dev --optimize-autoloader
```

### Step 2: Configure Environment Variables
Copy `.env.production.example` to `.env` and fill in production secrets:
```bash
cp .env.production.example .env
nano .env
```

### Step 3: Initialize MySQL Database
Execute the schema setup script in MySQL:
```bash
mysql -u root -p < config/database.sql
```

### Step 4: Provision Storage Directory & Permissions
Create the storage folder outside web root and set Apache/Nginx web server ownership:
```bash
mkdir -p /var/www/trinova_storage/uploads /var/www/trinova_storage/logs
chown -R www-data:www-data /var/www/trinova_storage /var/www/trinova
chmod -R 750 /var/www/trinova_storage
```

### Step 5: Web Server Configuration (Apache VirtualHost Example)
Point `DocumentRoot` to `/var/www/trinova/public`:

```apache
<VirtualHost *:443>
    ServerName portal.trinova.co.uk
    DocumentRoot /var/www/trinova/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/portal.trinova.co.uk/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/portal.trinova.co.uk/privkey.pem

    <Directory /var/www/trinova/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "same-origin"
</VirtualHost>
```

---

## 4. Default Accounts & Staff Management

The default database seed includes accounts for all 4 named practice staff members and 1 client:

| Role | Name | Email | Default Password |
| :--- | :--- | :--- | :--- |
| **Staff** | Kirsty | `kirsty@trinova.co.uk` | `password123` |
| **Staff** | Jane | `jane@trinova.co.uk` | `password123` |
| **Staff** | Emma | `emma@trinova.co.uk` | `password123` |
| **Staff** | Jess | `jess@trinova.co.uk` | `password123` |
| **Client** | Nick Powell | `nick@powellelectrical.co.uk` | `password123` |

> [!IMPORTANT]
> **Production Mandatory Step**: Password hashes should be updated immediately upon production launch via the password reset flow.

---

## 5. Security & Maintenance Recommendations

1. **Session Timeout**: Portal enforces session timeouts after inactivity.
2. **CSRF Protection**: All form submissions enforce CSRF token validation.
3. **Audit Trail**: All authentication events, file uploads, file downloads, and status transitions are recorded in `audit_log`.
