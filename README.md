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
