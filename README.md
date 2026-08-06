# TriNova Accounting Client Portal (Version 1 MVP)

Secure, standalone client portal for UK accountancy practice TriNova Accounting.

## Local Setup Instructions

### 1. Requirements
- PHP 8.2 or higher (PDO MySQL enabled)
- MySQL 8.0 or higher
- Node.js (for Tailwind CLI asset compilation)

### 2. Installation
```bash
# Clone & environment setup
cp .env.example .env

# Install Node dependencies
npm install

# Build CSS assets
npm run build:css
```

### 3. Database Migration & Initial Seed Data
Import the complete schema and seed data into MySQL:
```bash
mysql -u root -p trinova_portal < config/database.sql
```

`database.sql` is the complete schema for a new installation. For an existing
database, take a backup and apply the migration files that have not yet been
run, including `client_csv_import_migration.sql`,
`client_csv_duplicate_tracking_migration.sql`, `company_directors_migration.sql`,
`director_import_migration.sql`, `delete_functionality_migration.sql`,
`notifications_migration.sql`, and `otp_migration.sql`.

Default Staff Accounts:
- **Test Staff One**: `staff.one@example.invalid` / Password: `password123`
- **Test Staff Two**: `staff.two@example.invalid` / Password: `password123`
- **Test Staff Three**: `staff.three@example.invalid` / Password: `password123`
- **Test Staff Four**: `staff.four@example.invalid` / Password: `password123`

Default Client Account:
- **Test Client Alpha**: `test.client.alpha@example.invalid` / Password: `password123`

### 4. Run Development Web Server
```bash
php -S localhost:8000 -t public/
```
Access the portal at `http://localhost:8000/login`.

When `APP_ENV=local` and `RESEND_API_KEY` is empty, outgoing messages are not
sent externally. They are captured in `storage/logs/mail.log`, including local
activation links and verification codes, so account creation can be tested
fully on localhost. Never use local mode on a production deployment.
