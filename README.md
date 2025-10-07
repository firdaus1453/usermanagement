# 🚀 User Management Application

> Modern user management system built with Laravel 12, Filament 3, PostgreSQL, and Redis.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net)
[![Filament](https://img.shields.io/badge/Filament-3.x-FDAE4B)](https://filamentphp.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?logo=postgresql)](https://postgresql.org)
[![Redis](https://img.shields.io/badge/Redis-7-DC382D?logo=redis)](https://redis.io)

## 📋 Overview

Aplikasi **User Management** adalah sistem manajemen pengguna berbasis web yang dibangun dengan prinsip **clean code**, **security-first**, dan **best practices Laravel**. Aplikasi ini menyediakan fitur lengkap untuk mengelola user dengan role-based access control.

### ✨ Fitur Utama

-   ✅ **Authentication & Authorization**

    -   Login dengan rate limiting (anti brute-force)
    -   Role-based access control (Superadmin, Admin, Operator, Validator)
    -   Session management dengan Redis
    -   Hanya user aktif yang dapat login

-   ✅ **User Management (CRUD)**

    -   Create, Read, Update, Delete users
    -   Bulk delete operations
    -   Search & filter (by role, status)
    -   Sorting & pagination
    -   Password auto-hashing dengan anti double-hash

-   ✅ **Dashboard**

    -   Statistics user per role (Superadmin, Admin, Operator, Validator)
    -   Real-time data dengan auto-refresh
    -   Recent users widget

-   ✅ **Security Features**

    -   Rate limiting (5 login attempts/minute)
    -   Security headers (CSP, HSTS, X-Frame-Options, dll)
    -   Request ID tracking
    -   CSRF protection
    -   Password hashing (bcrypt)
    -   SQL injection prevention (Eloquent ORM)

-   ✅ **User Experience**

    -   Responsive design (390px mobile, 768px tablet, 1280px desktop)
    -   Dark mode dengan system preference detection
    -   Toast notifications
    -   Confirmation modals
    -   Empty states dengan CTA
    -   Inline validation

-   ✅ **Monitoring & Logging**
    -   Health endpoint (`/health`)
    -   JSON logging dengan context
    -   Request ID tracking
    -   Database & cache status monitoring

---

## 🛠️ Tech Stack

| Layer             | Technology                                               |
| ----------------- | -------------------------------------------------------- |
| **Framework**     | Laravel 12.x                                             |
| **Admin Panel**   | Filament 3.x (TALL: Tailwind, Alpine, Livewire, Laravel) |
| **Database**      | PostgreSQL 16                                            |
| **Cache/Session** | Redis 7                                                  |
| **PHP**           | 8.2 / 8.3 / 8.4                                          |
| **Frontend**      | Blade, Livewire 3, Alpine.js, Tailwind CSS               |
| **Container**     | Docker Compose                                           |
| **Testing**       | Pest                                                     |
| **Code Quality**  | Laravel Pint, Larastan                                   |

---

## 📦 Installation

### Prerequisites

Pastikan sudah terinstall:

-   **PHP** 8.2 atau lebih tinggi ([Download](https://www.php.net/downloads))
-   **Composer** 2.x ([Download](https://getcomposer.org/download/))
-   **Node.js** 20+ & npm ([Download](https://nodejs.org/))
-   **Docker Desktop** ([Download](https://www.docker.com/products/docker-desktop/))
-   **Git** ([Download](https://git-scm.com/downloads))

### Step 1: Clone Repository

```bash
git clone <repository-url> usermanagement
cd usermanagement
```

### Step 2: Start Docker Services

```bash
# Start PostgreSQL, Redis, dan DBGate
docker-compose up -d

# Verify services are running
docker-compose ps
```

Expected output:

```
NAME                        STATUS    PORTS
usermanagement_postgres     Up        0.0.0.0:5432->5432/tcp
usermanagement_redis        Up        0.0.0.0:6379->6379/tcp
usermanagement_dbgate       Up        0.0.0.0:3010->3000/tcp
```

### Step 3: Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### Step 4: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Edit `.env` file (jika perlu, default sudah OK):

```env
APP_NAME="User Management"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_usermanagement
DB_USERNAME=laravel
DB_PASSWORD=secret

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Step 5: Database Setup

```bash
# Run migrations
php artisan migrate

# Seed database dengan sample users
php artisan db:seed
```

### Step 6: Build Assets

```bash
# Build frontend assets
npm run build

# Or untuk development dengan hot reload:
npm run dev
```

### Step 7: Start Application

```bash
# Start Laravel development server
php artisan serve
```

Aplikasi akan berjalan di: **http://localhost:8000**

Admin panel: **http://localhost:8000/admin**

---

## 🔑 Default Credentials

Setelah seeding, Anda dapat login dengan akun berikut:

| Name           | Email                  | Password    | Role       | Status       | Access                                        |
| -------------- | ---------------------- | ----------- | ---------- | ------------ | --------------------------------------------- |
| Super Admin    | superadmin@example.com | Password123 | Superadmin | Active       | ✅ Full access (view, create, update, delete) |
| Admin User     | admin@example.com      | Password123 | Admin      | Active       | ✅ View, create, update (no delete)           |
| Operator User  | operator@example.com   | Password123 | Operator   | **Inactive** | ❌ Cannot login (for testing)                 |
| Validator User | validator@example.com  | Password123 | Validator  | Active       | ✅ Can login (no panel access)                |
| Test Admin     | test.admin@example.com | Password123 | Admin      | Active       | ✅ View, create, update (no delete)           |

> **Note**: Operator sengaja inactive untuk testing login validation.

---

## 🎯 Usage

### 1. Login

1. Buka browser ke **http://localhost:8000/admin**
2. Login dengan salah satu kredensial di atas
3. Jika user inactive, akan ditolak dengan pesan error

### 2. Dashboard

Setelah login, Anda akan melihat:

-   **Stats Cards**: Jumlah user per role (Superadmin, Admin, Operator, Validator)
-   **Recent Users**: 5 user terbaru yang dibuat

### 3. User Management

#### Create User

1. Klik **"Users"** di sidebar
2. Klik **"New User"** button
3. Isi form:
    - Name (required, max 100 characters)
    - Email (unique, required)
    - Password (min 8 characters, required)
    - Role (select from dropdown)
    - Active status (toggle, default: active)
4. Klik **"Create"**

#### Edit User

1. Klik icon **Edit** (pencil) di row user
2. Ubah data yang diperlukan
3. Password **optional** (kosongkan jika tidak mau ubah)
4. Klik **"Save"**

#### Delete User

1. Klik icon **Delete** (trash) di row user
2. Konfirmasi di modal
3. User akan dihapus

> **Note**: Hanya **Superadmin** yang bisa delete user!

#### Bulk Delete

1. Checkbox users yang mau dihapus
2. Pilih **"Delete selected"** dari dropdown
3. Konfirmasi
4. Users akan dihapus

#### Search & Filter

-   **Search**: Ketik email di search box
-   **Filter Role**: Pilih role dari dropdown filter
-   **Filter Status**: Active / Inactive / All

### 4. Dark Mode

-   Klik icon **moon/sun** di user menu
-   Pilih: Light / Dark / System
-   Preference tersimpan di localStorage

---

## 🗄️ Database Schema

### `users` Table

| Column           | Type         | Constraints                 | Description                            |
| ---------------- | ------------ | --------------------------- | -------------------------------------- |
| `user_id`        | INTEGER      | PRIMARY KEY, AUTO_INCREMENT | Primary key                            |
| `name`           | VARCHAR(100) | NOT NULL                    | User full name                         |
| `email`          | VARCHAR(255) | UNIQUE, NOT NULL            | User email                             |
| `password_hash`  | VARCHAR(255) | NOT NULL                    | Hashed password (bcrypt)               |
| `remember_token` | VARCHAR(100) | NULLABLE                    | Remember me token                      |
| `role`           | ENUM         | NOT NULL                    | superadmin, admin, operator, validator |
| `is_active`      | BOOLEAN      | DEFAULT true, NOT NULL      | Active status                          |
| `created_at`     | TIMESTAMP    | NULLABLE                    | Creation timestamp                     |
| `updated_at`     | TIMESTAMP    | NULLABLE                    | Last update timestamp                  |

**Indexes:**

-   `user_id` (PRIMARY KEY)
-   `email` (UNIQUE)
-   `email` (INDEX for search performance)
-   `role` (INDEX for filter performance)
-   `is_active` (INDEX for filter performance)

---

## 🧪 Testing

### Run All Tests

```bash
# Using Artisan
php artisan test

# Using Pest
./vendor/bin/pest

# With coverage
./vendor/bin/pest --coverage
```

### Run Specific Test

```bash
./vendor/bin/pest --filter UserManagementTest
```

### Test Coverage

-   ✅ User authentication (active/inactive)
-   ✅ Panel access control
-   ✅ CRUD operations
-   ✅ Policy authorization
-   ✅ Rate limiting
-   ✅ Health endpoint

---

## 🔒 Security

### Authentication & Authorization

-   **Session-based auth** dengan httpOnly cookies
-   **Role-based access control** via Laravel Policy
-   **Superadmin**: Full access
-   **Admin**: View, create, update (no delete)
-   **Operator/Validator**: No panel access

### Rate Limiting

-   **Login attempts**: 5 tries per minute per (email + session ID)
-   Menggunakan **email + session**, bukan IP (avoid false positives)
-   User-friendly error messages

### Security Headers

```
Content-Security-Policy
Strict-Transport-Security (HTTPS only)
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

### Password Security

-   **Bcrypt hashing** dengan cost 12
-   **Auto-hash mutator** dengan anti double-hash protection
-   **Never stored in plaintext**
-   **Hidden** dari serialization

### SQL Injection Prevention

-   **Eloquent ORM** dengan prepared statements
-   Parameter binding
-   No raw queries dengan user input

### CSRF Protection

-   Laravel CSRF tokens
-   Automatic validation di forms

---

## 📊 Monitoring

### Health Endpoint

```bash
curl http://localhost:8000/health
```

Response:

```json
{
    "status": "ok",
    "checks": {
        "app": "ok",
        "time": "2025-02-24T10:30:00+00:00",
        "database": "ok",
        "cache": "ok"
    }
}
```

### Logging

Logs tersimpan di `storage/logs/laravel.log` dalam format JSON:

```json
{
    "message": "User created",
    "context": {
        "request_id": "uuid-here",
        "actor_id": 1,
        "actor_email": "superadmin@example.com",
        "user_id": 5,
        "email": "new@example.com",
        "role": "operator"
    },
    "level": "info",
    "datetime": "2025-02-24T10:30:00+00:00"
}
```

---

## 🐳 Docker Commands

### Start Services

```bash
docker-compose up -d
```

### Stop Services

```bash
docker-compose down
```

### View Logs

```bash
# All services
docker-compose logs

# Specific service
docker-compose logs db
docker-compose logs redis

# Follow logs
docker-compose logs -f
```

### Restart Services

```bash
docker-compose restart
```

### Database Management (DBGate)

Akses DBGate di **http://localhost:3010**

-   Connection sudah auto-configured
-   GUI untuk manage PostgreSQL
-   Query editor, table browser, import/export

### PostgreSQL CLI

```bash
# Access PostgreSQL shell
docker-compose exec db psql -U laravel -d laravel_usermanagement

# Run query
docker-compose exec db psql -U laravel -d laravel_usermanagement -c "SELECT * FROM users;"
```

### Redis CLI

```bash
# Access Redis CLI
docker-compose exec redis redis-cli

# Test connection
docker-compose exec redis redis-cli ping
# Expected: PONG

# Monitor commands
docker-compose exec redis redis-cli MONITOR
```

---

## 🧹 Maintenance

### Clear Cache

```bash
# Clear all caches
php artisan optimize:clear

# Or individually:
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Cache for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Code Formatting

```bash
# Format code dengan Laravel Pint
./vendor/bin/pint

# Check only (no changes)
./vendor/bin/pint --test
```

### Static Analysis

```bash
# Run Larastan
./vendor/bin/phpstan analyse
```

### Fresh Migration

```bash
# Drop all tables, migrate, and seed
php artisan migrate:fresh --seed
```

---

## 📁 Project Structure

```
usermanagement/
├── app/
│   ├── Filament/
│   │   ├── Resources/
│   │   │   └── UserResource.php
│   │   └── Widgets/
│   │       ├── UsersByRoleOverview.php
│   │       └── RecentUsers.php
│   ├── Http/
│   │   ├── Middleware/
│   │   │   ├── RequestIdMiddleware.php
│   │   │   └── SecurityHeadersMiddleware.php
│   │   └── Kernel.php
│   ├── Models/
│   │   └── User.php
│   ├── Policies/
│   │   └── UserPolicy.php
│   └── Providers/
│       ├── AppServiceProvider.php
│       ├── AuthServiceProvider.php
│       └── Filament/
│           └── AdminPanelProvider.php
├── database/
│   ├── migrations/
│   │   └── xxxx_create_users_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── UserSeeder.php
├── routes/
│   └── web.php
├── tests/
│   └── Feature/
│       └── UserManagementTest.php
├── docker-compose.yml
├── .env.example
├── prd.md
└── README.md
```

---

## 🚀 Deployment

### Production Checklist

-   [ ] Set `APP_ENV=production`
-   [ ] Set `APP_DEBUG=false`
-   [ ] Generate new `APP_KEY`
-   [ ] Update database credentials
-   [ ] Use managed PostgreSQL (AWS RDS, DigitalOcean, etc)
-   [ ] Use managed Redis (AWS ElastiCache, etc)
-   [ ] Enable HTTPS (SSL certificate)
-   [ ] Set `SESSION_SECURE_COOKIE=true`
-   [ ] Configure proper CORS
-   [ ] Set up queue workers
-   [ ] Set up scheduled tasks (cron)
-   [ ] Configure logging (external service)
-   [ ] Set up monitoring (Sentry, Bugsnag, etc)
-   [ ] Configure backups
-   [ ] Set up CI/CD pipeline

### Environment Variables

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=your-production-db-host
DB_DATABASE=your-production-db
DB_USERNAME=your-production-user
DB_PASSWORD=your-secure-password

SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
```

---

## 🐛 Troubleshooting

### PostgreSQL Connection Error

```bash
# Check if PostgreSQL is running
docker-compose ps db

# Check logs
docker-compose logs db

# Restart service
docker-compose restart db

# Test connection
docker-compose exec db psql -U laravel -d laravel_usermanagement -c "SELECT 1;"
```

### Redis Connection Error

```bash
# Check if Redis is running
docker-compose ps redis

# Test connection
docker-compose exec redis redis-cli ping

# Restart service
docker-compose restart redis
```

### Filament Login Issues

```bash
# Clear auth cache
php artisan auth:clear-resets

# Clear session
php artisan session:flush

# Re-seed users
php artisan migrate:fresh --seed
```

### Permission Issues

```bash
# Fix storage permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
chown -R $USER:www-data storage bootstrap/cache

# Windows: Run as Administrator
```

### Port Already in Use

```bash
# Check what's using port 5432
lsof -i :5432  # Mac/Linux
netstat -ano | findstr :5432  # Windows

# Change port di docker-compose.yml
ports:
  - "5433:5432"  # Use different host port
```

---

## 📚 Documentation

-   [Laravel 12 Documentation](https://laravel.com/docs/12.x)
-   [Filament 3 Documentation](https://filamentphp.com/docs/3.x)
-   [PostgreSQL Documentation](https://www.postgresql.org/docs/)
-   [Redis Documentation](https://redis.io/docs/)
-   [Docker Compose Documentation](https://docs.docker.com/compose/)
-   [Tailwind CSS Documentation](https://tailwindcss.com/docs)

---

## 👨‍💻 Development

### Code Style

Project menggunakan Laravel Pint untuk code formatting:

```bash
# Format all files
./vendor/bin/pint

# Format specific directory
./vendor/bin/pint app/Models
```

### Commit Convention

Gunakan conventional commits:

```
feat: add bulk delete feature
fix: resolve login rate limiting issue
docs: update README installation steps
style: format code with Pint
refactor: extract user service
test: add user policy tests
chore: update dependencies
```

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'feat: add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

---

## 📄 License

This project is licensed under the MIT License.

---

## 🙏 Acknowledgments

-   [Laravel](https://laravel.com) - The PHP Framework for Web Artisans
-   [Filament](https://filamentphp.com) - Accelerated Laravel Development
-   [PostgreSQL](https://www.postgresql.org) - The World's Most Advanced Open Source Relational Database
-   [Redis](https://redis.io) - The Open Source, In-Memory Data Store

---

## 📞 Support

Jika ada pertanyaan atau issue:

1. Check [Troubleshooting](#troubleshooting) section
2. Read [Documentation](#documentation)
3. Open an issue di repository

---

**Built with ❤️ using Laravel 12, Filament 3, PostgreSQL, and Redis**
