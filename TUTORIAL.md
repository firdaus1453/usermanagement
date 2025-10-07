# 🎓 Tutorial: Membuat Aplikasi User Management dengan Laravel 12 & Filament 4

Tutorial lengkap step-by-step untuk membuat aplikasi User Management dari nol sampai production-ready.

> **Estimasi Waktu:** 3-4 jam  
> **Level:** Intermediate  
> **Prerequisites:** PHP 8.2+, Composer, Node.js, PostgreSQL

---

## 📋 Daftar Isi

1. [Persiapan Awal](#1-persiapan-awal)
2. [Setup Laravel 12](#2-setup-laravel-12)
3. [Setup Database](#3-setup-database)
4. [Install Filament 4](#4-install-filament-4)
5. [Membuat Model & Migration](#5-membuat-model--migration)
6. [Membuat Seeder](#6-membuat-seeder)
7. [Membuat Filament Resource](#7-membuat-filament-resource)
8. [Membuat Policy](#8-membuat-policy)
9. [Membuat Dashboard Widget](#9-membuat-dashboard-widget)
10. [Implementasi Rate Limiting](#10-implementasi-rate-limiting)
11. [Implementasi Logging](#11-implementasi-logging)
12. [Implementasi Security](#12-implementasi-security)
13. [Testing & Finalisasi](#13-testing--finalisasi)

---

## 1. Persiapan Awal

### 1.1 Pastikan Prerequisites Terinstall

```bash
# Check PHP version (minimal 8.2)
php -v

# Check Composer
composer -V

# Check Node.js (minimal 20+)
node -v
npm -v

# Check PostgreSQL
psql --version
```

### 1.2 Tools yang Dibutuhkan

-   **Code Editor:** VS Code, PHPStorm, atau sejenisnya
-   **Terminal:** Command line untuk menjalankan perintah
-   **Database Client:** TablePlus, DBeaver, atau DBGate
-   **Browser:** Chrome atau Firefox untuk testing

---

## 2. Setup Laravel 12

### 2.1 Create Project Laravel

```bash
# Buat project Laravel baru
composer create-project laravel/laravel:^12.0 usermanagement

# Masuk ke direktori project
cd usermanagement

# Generate APP_KEY
php artisan key:generate
```

### 2.2 Setup Environment

Edit file `.env`:

```env
APP_NAME="User Management"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_usermanagement
DB_USERNAME=laravel
DB_PASSWORD=secret

# Cache & Session (file-based, simple & cukup untuk development)
CACHE_STORE=file
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### 2.3 Setup Docker Compose (Optional tapi Recommended)

Buat file `docker-compose.yml`:

```yaml
version: "3.8"

services:
    # PostgreSQL Database
    db:
        image: postgres:16-alpine
        container_name: usermanagement_postgres
        restart: unless-stopped
        ports:
            - "5432:5432"
        environment:
            POSTGRES_DB: laravel_usermanagement
            POSTGRES_USER: laravel
            POSTGRES_PASSWORD: secret
        volumes:
            - postgres_data:/var/lib/postgresql/data
        networks:
            - usermanagement_network
        healthcheck:
            test: ["CMD-SHELL", "pg_isready -U laravel"]
            interval: 10s
            timeout: 5s
            retries: 5

    # DBGate - Database Management UI
    dbgate:
        image: dbgate/dbgate:latest
        container_name: usermanagement_dbgate
        restart: unless-stopped
        ports:
            - "3010:3000"
        environment:
            CONNECTIONS: postgres
            LABEL_postgres: PostgreSQL
            SERVER_postgres: db
            USER_postgres: laravel
            PASSWORD_postgres: secret
            PORT_postgres: 5432
            DATABASE_postgres: laravel_usermanagement
            ENGINE_postgres: postgres@dbgate-plugin-postgres
        depends_on:
            - db
        networks:
            - usermanagement_network
        volumes:
            - dbgate_data:/root/.dbgate

volumes:
    postgres_data:
        driver: local
    dbgate_data:
        driver: local

networks:
    usermanagement_network:
        driver: bridge
```

Jalankan Docker:

```bash
docker-compose up -d

# Cek status
docker-compose ps
```

---

## 3. Setup Database

### 3.1 Buat Database (jika tidak pakai Docker)

```bash
# Masuk ke PostgreSQL
psql -U postgres

# Buat database
CREATE DATABASE laravel_usermanagement;

# Buat user
CREATE USER laravel WITH PASSWORD 'secret';

# Berikan privileges
GRANT ALL PRIVILEGES ON DATABASE laravel_usermanagement TO laravel;

# Keluar
\q
```

### 3.2 Test Koneksi Database

```bash
php artisan migrate:status
```

Jika berhasil, Anda akan melihat list migrations (meski belum dijalankan).

---

## 4. Install Filament 4

### 4.1 Install Filament Packages

```bash
# Install Filament
composer require filament/filament:"^4.0"

# Install Filament Panel
php artisan filament:install --panels
```

Saat ditanya:

-   Panel ID: `admin` (Enter)
-   Create user: `no` (kita akan buat manual)

### 4.2 Build Assets

```bash
npm install
npm run build
```

### 4.3 Test Filament

Jalankan server:

```bash
php artisan serve
```

Buka browser: `http://localhost:8000/admin`

Anda akan melihat halaman login Filament (tapi belum bisa login karena belum ada user).

---

## 5. Membuat Model & Migration

### 5.1 Buat Migration Users

Edit file `database/migrations/0001_01_01_000000_create_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('name', 100);
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->rememberToken();
            $table->enum('role', ['superadmin', 'admin', 'operator', 'validator']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes untuk performa
            $table->index('email');
            $table->index('role');
            $table->index('is_active');
        });

        // Buat tabel sessions untuk session driver database
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
```

### 5.2 Update User Model

Edit `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Override getAuthPassword untuk password_hash
    public function getAuthPassword(): string
    {
        return $this->password_hash ?? '';
    }

    // Mutator untuk auto-hash password
    public function setPasswordHashAttribute(?string $value): void
    {
        if (filled($value)) {
            $this->attributes['password_hash'] =
                str_starts_with((string) $value, '$2y$')
                ? $value
                : Hash::make($value);
        }
    }

    // Scope untuk user aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope untuk filter by role
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // Filament Panel Access Control
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active === true;
    }

    // Helper methods
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->email ?? 'Unknown User';
    }
}
```

### 5.3 Jalankan Migration

```bash
php artisan migrate
```

---

## 6. Membuat Seeder

### 6.1 Buat UserSeeder

```bash
php artisan make:seeder UserSeeder
```

Edit `database/seeders/UserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing users (development only)
        if (app()->environment('local')) {
            User::query()->delete();
        }

        // Superadmin - Full access
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password_hash' => 'Password123',
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // Admin - CRUD access, no delete
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password_hash' => 'Password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Operator - Inactive (untuk testing)
        User::create([
            'name' => 'Operator User',
            'email' => 'operator@example.com',
            'password_hash' => 'Password123',
            'role' => 'operator',
            'is_active' => false,
        ]);

        // Validator - Active
        User::create([
            'name' => 'Validator User',
            'email' => 'validator@example.com',
            'password_hash' => 'Password123',
            'role' => 'validator',
            'is_active' => true,
        ]);

        // Test Admin
        User::create([
            'name' => 'Test Admin',
            'email' => 'test.admin@example.com',
            'password_hash' => 'Password123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->command->info('✅ Users seeded successfully!');
        $this->command->table(
            ['Name', 'Email', 'Role', 'Active'],
            User::all(['name', 'email', 'role', 'is_active'])->toArray()
        );
    }
}
```

### 6.2 Update DatabaseSeeder

Edit `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}
```

### 6.3 Jalankan Seeder

```bash
php artisan db:seed
```

---

## 7. Membuat Filament Resource

### 7.1 Generate Resource

```bash
php artisan make:filament-resource User --generate
```

### 7.2 Update UserResource

Edit `app/Filament/Resources/UserResource.php`:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->autocomplete('name')
                            ->label('Full Name')
                            ->placeholder('Enter full name'),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->autocomplete('off')
                            ->label('Email Address')
                            ->placeholder('user@example.com'),

                        TextInput::make('password_hash')
                            ->password()
                            ->required(fn(string $context): bool => $context === 'create')
                            ->dehydrated(fn($state) => filled($state))
                            ->minLength(8)
                            ->maxLength(255)
                            ->autocomplete('new-password')
                            ->label('Password')
                            ->placeholder('Minimum 8 characters')
                            ->helperText(
                                fn(string $context): string =>
                                $context === 'edit'
                                    ? 'Leave blank to keep current password'
                                    : 'Minimum 8 characters'
                            ),

                        Select::make('role')
                            ->options([
                                'superadmin' => 'Superadmin',
                                'admin' => 'Admin',
                                'operator' => 'Operator',
                                'validator' => 'Validator',
                            ])
                            ->required()
                            ->native(false)
                            ->label('Role'),

                        Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->label('Active Status')
                            ->helperText('Only active users can login'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied!'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->colors([
                        'danger' => 'superadmin',
                        'warning' => 'admin',
                        'success' => 'operator',
                        'info' => 'validator',
                    ])
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'superadmin' => 'Superadmin',
                        'admin' => 'Admin',
                        'operator' => 'Operator',
                        'validator' => 'Validator',
                    ])
                    ->label('Role')
                    ->native(false),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only')
                    ->native(false),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->iconButton(),
                Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Actions\BulkAction::make('delete')
                    ->label('Delete selected')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->delete())
                    ->color('danger')
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('user_id', 'desc')
            ->emptyStateHeading('No users yet')
            ->emptyStateDescription('Create your first user to get started.')
            ->emptyStateIcon('heroicon-o-users')
            ->striped()
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
```

### 7.3 Update CreateUser Page

Edit `app/Filament/Resources/UserResource/Pages/CreateUser.php`:

```php
<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}
```

---

## 8. Membuat Policy

### 8.1 Generate Policy

```bash
php artisan make:policy UserPolicy --model=User
```

### 8.2 Update UserPolicy

Edit `app/Policies/UserPolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin']);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin']);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasAnyRole(['superadmin', 'admin']);
    }

    public function delete(User $user, User $model): bool
    {
        // Prevent self-deletion
        if ($user->user_id === $model->user_id) {
            return false;
        }

        return $user->hasRole('superadmin');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('superadmin');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('superadmin');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('superadmin');
    }
}
```

---

## 9. Membuat Dashboard Widget

### 9.1 Generate Widget

```bash
php artisan make:filament-widget UsersByRoleOverview --stats-overview
```

### 9.2 Update Widget

Edit `app/Filament/Widgets/UsersByRoleOverview.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersByRoleOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Super Admins', User::where('role', 'superadmin')->count())
                ->description(User::where('role', 'superadmin')->where('is_active', true)->count() . ' active')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('danger')
                ->chart($this->getWeeklyTrend('superadmin')),

            Stat::make('Admins', User::where('role', 'admin')->count())
                ->description(User::where('role', 'admin')->where('is_active', true)->count() . ' active')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning')
                ->chart($this->getWeeklyTrend('admin')),

            Stat::make('Operators', User::where('role', 'operator')->count())
                ->description(User::where('role', 'operator')->where('is_active', true)->count() . ' active')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('success')
                ->chart($this->getWeeklyTrend('operator')),

            Stat::make('Validators', User::where('role', 'validator')->count())
                ->description(User::where('role', 'validator')->where('is_active', true)->count() . ' active')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info')
                ->chart($this->getWeeklyTrend('validator')),
        ];
    }

    protected function getWeeklyTrend(string $role): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $data[] = User::where('role', $role)
                ->where('created_at', '<=', $date->endOfDay())
                ->count();
        }
        return $data;
    }
}
```

### 9.3 Register Widget

Edit `app/Providers/Filament/AdminPanelProvider.php`:

```php
->widgets([
    \App\Filament\Widgets\UsersByRoleOverview::class,
])
```

---

## 10. Implementasi Rate Limiting

### 10.1 Update AppServiceProvider

Edit `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        // Rate limiting untuk login
        RateLimiter::for('web-login', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', 'unknown')));
            $sessId = $request->session()->getId() ?: 'no-session';

            return Limit::perMinute(5)->by("web-login:{$email}|{$sessId}");
        });

        // Rate limiting untuk API (jika diperlukan)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->user_id ?: $request->ip());
        });
    }
}
```

---

## 11. Implementasi Logging

### 11.1 Konfigurasi Logging (JSON Format)

Laravel sudah memiliki sistem logging yang powerful menggunakan Monolog. Kita akan konfigurasi logging dengan JSON format untuk memudahkan parsing dan monitoring.

Edit `config/logging.php` dan tambahkan channel baru:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => explode(',', (string) env('LOG_STACK', 'daily,json')),
        'ignore_exceptions' => false,
    ],

    // ... channel lainnya ...

    // Tambahkan channel JSON untuk structured logging
    'json' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => env('LOG_DAILY_DAYS', 14),
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
        'formatter_with' => [
            'includeStacktraces' => true,
        ],
        'replace_placeholders' => true,
    ],

    // Channel khusus untuk audit/activity logs
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 90, // Simpan audit log lebih lama
        'formatter' => \Monolog\Formatter\JsonFormatter::class,
        'replace_placeholders' => true,
    ],
],
```

### 11.2 Update Environment

Edit `.env` untuk menggunakan stack logging:

```env
LOG_CHANNEL=stack
LOG_STACK=daily,json
LOG_LEVEL=debug
LOG_DAILY_DAYS=14
```

Untuk production:

```env
LOG_CHANNEL=stack
LOG_STACK=daily,json
LOG_LEVEL=warning
LOG_DAILY_DAYS=30
```

### 11.3 Cara Menggunakan Logging

Logging sudah terintegrasi dengan RequestId middleware yang akan kita buat. Berikut contoh penggunaan:

#### Basic Logging

```php
use Illuminate\Support\Facades\Log;

// Debug - Informasi detail untuk debugging
Log::debug('User viewing dashboard', ['user_id' => auth()->id()]);

// Info - Informasi umum
Log::info('User logged in', ['email' => $user->email]);

// Notice - Event normal tapi penting
Log::notice('Password changed', ['user_id' => $user->user_id]);

// Warning - Peringatan, ada yang tidak normal
Log::warning('Failed login attempt', ['email' => $request->email]);

// Error - Runtime error yang tidak menghentikan aplikasi
Log::error('Database connection failed', ['exception' => $e->getMessage()]);

// Critical - Kondisi kritis
Log::critical('Disk space low', ['available' => $availableSpace]);

// Alert - Perlu action segera
Log::alert('Rate limit exceeded significantly', ['ip' => $request->ip()]);

// Emergency - System tidak bisa digunakan
Log::emergency('Application crash', ['trace' => $e->getTraceAsString()]);
```

#### Logging dengan Context (Best Practice)

```php
// Context otomatis ditambahkan oleh RequestId middleware:
// - request_id
// - user_id
// - ip
// - method
// - uri

// Tambahan context manual:
Log::info('User created', [
    'new_user_id' => $user->user_id,
    'created_by' => auth()->id(),
    'role' => $user->role,
]);
```

#### Logging di Controller/Service

```php
// app/Filament/Resources/UserResource/Pages/CreateUser.php
protected function afterCreate(): void
{
    Log::channel('audit')->info('User created', [
        'user_id' => $this->record->user_id,
        'email' => $this->record->email,
        'role' => $this->record->role,
        'created_by' => auth()->id(),
    ]);
}

// app/Filament/Resources/UserResource/Pages/EditUser.php
protected function afterSave(): void
{
    Log::channel('audit')->info('User updated', [
        'user_id' => $this->record->user_id,
        'changes' => $this->record->getChanges(),
        'updated_by' => auth()->id(),
    ]);
}

// app/Policies/UserPolicy.php
public function delete(User $user, User $model): bool
{
    $canDelete = $user->hasRole('superadmin') && $user->user_id !== $model->user_id;

    if ($canDelete) {
        Log::channel('audit')->warning('User deletion authorized', [
            'target_user_id' => $model->user_id,
            'deleted_by' => $user->user_id,
        ]);
    }

    return $canDelete;
}
```

### 11.4 Monitoring Logs

#### View Logs Real-time

```bash
# Follow all logs
tail -f storage/logs/laravel.log

# Follow specific log level (error saja)
tail -f storage/logs/laravel.log | grep '"level":"error"'

# Follow audit logs
tail -f storage/logs/audit.log

# Search specific user activity
grep "user_id.*123" storage/logs/audit.log | jq .
```

#### Parse JSON Logs dengan jq

```bash
# Install jq (JSON processor)
# macOS
brew install jq

# Ubuntu/Debian
sudo apt-get install jq

# View logs formatted
tail -50 storage/logs/laravel.log | grep "^{" | jq .

# Filter by level
cat storage/logs/laravel.log | grep "^{" | jq 'select(.level == "error")'

# Filter by user
cat storage/logs/audit.log | grep "^{" | jq 'select(.context.user_id == 1)'

# Count errors per day
cat storage/logs/laravel.log | grep "^{" | jq -r '.datetime' | cut -d'T' -f1 | sort | uniq -c
```

### 11.5 Log Rotation

Laravel sudah menggunakan `daily` driver yang otomatis rotate logs setiap hari. File lama akan dihapus sesuai `LOG_DAILY_DAYS`.

Struktur file logs:

```
storage/logs/
├── laravel.log (hari ini)
├── laravel-2025-10-06.log
├── laravel-2025-10-05.log
├── audit.log (hari ini)
├── audit-2025-10-06.log
└── audit-2025-10-05.log
```

---

## 12. Implementasi Security

### 12.1 Buat RequestId Middleware

```bash
php artisan make:middleware RequestId
```

Edit `app/Http/Middleware/RequestId.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id') ?: (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);

        Log::withContext([
            'request_id' => $requestId,
            'user_id' => $request->user()?->user_id,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'uri' => $request->getRequestUri(),
        ]);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
```

### 12.2 Buat SecurityHeaders Middleware

```bash
php artisan make:middleware SecurityHeaders
```

Edit `app/Http/Middleware/SecurityHeaders.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->secure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
```

### 12.3 Register Middleware

Edit `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->append([
        \App\Http\Middleware\RequestId::class,
        \App\Http\Middleware\SecurityHeaders::class,
    ]);
})
```

### 12.4 Buat Health Endpoint

Edit `routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/health', function () {
    $checks = [
        'app' => 'ok',
        'time' => now()->toIso8601String(),
    ];

    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = 'down';
        \Log::error('Health check database failure', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    $status = in_array('down', $checks, true) ? 'degraded' : 'ok';

    return response()->json([
        'status' => $status,
        'checks' => $checks,
    ], $status === 'ok' ? 200 : 503);
})->name('health');
```

---

## 13. Testing & Finalisasi

### 13.1 Clear Cache

```bash
php artisan optimize:clear
```

### 13.2 Test Aplikasi

```bash
# Jalankan server
php artisan serve

# Buka browser
http://localhost:8000
```

### 13.3 Checklist Testing

-   [ ] **Login**

    -   [ ] Login dengan superadmin@example.com / Password123
    -   [ ] Login dengan user inactive (harus gagal)
    -   [ ] Test "Ingat Saya" checkbox
    -   [ ] Test rate limiting (5 kali gagal)

-   [ ] **Dashboard**

    -   [ ] Lihat widget statistics per role
    -   [ ] Pastikan angka sesuai dengan data

-   [ ] **Users Management**

    -   [ ] Lihat list users
    -   [ ] Search user by email/name
    -   [ ] Filter by role
    -   [ ] Filter by status (Active/Inactive)
    -   [ ] Sort kolom (ID, Name, Email, etc)

-   [ ] **Create User**

    -   [ ] Buat user baru dengan semua field
    -   [ ] Validasi email unique
    -   [ ] Validasi password minimal 8 karakter
    -   [ ] Password otomatis di-hash

-   [ ] **Edit User**

    -   [ ] Edit user existing
    -   [ ] Password optional (kosong = tidak berubah)
    -   [ ] Update role & status

-   [ ] **Delete User**

    -   [ ] Delete satu user (konfirmasi muncul)
    -   [ ] Bulk delete multiple users
    -   [ ] Coba delete diri sendiri (harus gagal)

-   [ ] **Authorization**

    -   [ ] Login sebagai admin@example.com
    -   [ ] Admin bisa view/create/edit (tidak bisa delete)
    -   [ ] Logout dan login sebagai superadmin
    -   [ ] Superadmin bisa delete

-   [ ] **Dark Mode**

    -   [ ] Toggle dark mode
    -   [ ] Preference tersimpan setelah refresh

-   [ ] **Health Endpoint**

    -   [ ] Akses http://localhost:8000/health
    -   [ ] Pastikan return status "ok"

-   [ ] **Logging**
    -   [ ] Cek file `storage/logs/laravel.log` ada
    -   [ ] Test logging dengan `Log::info('Test log');`
    -   [ ] Pastikan format JSON
    -   [ ] Cek audit log di `storage/logs/audit.log`

### 13.4 Production Preparation

```bash
# Set production environment
APP_ENV=production
APP_DEBUG=false

# Cache untuk performa
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build production assets
npm run build
```

---

## 🎉 Selesai!

Aplikasi User Management Anda sudah siap digunakan!

### Kredensial Login Default:

| Email                  | Password    | Role       |
| ---------------------- | ----------- | ---------- |
| superadmin@example.com | Password123 | Superadmin |
| admin@example.com      | Password123 | Admin      |

### Fitur yang Sudah Terimplementasi:

✅ Authentication & Authorization  
✅ CRUD Users (Create, Read, Update, Delete)  
✅ Role-based Access Control  
✅ Search & Filter  
✅ Bulk Delete  
✅ Dashboard Statistics  
✅ Rate Limiting  
✅ Structured Logging (JSON Format)  
✅ Audit Logs  
✅ Security Headers  
✅ Request ID Tracking  
✅ Health Monitoring  
✅ Dark Mode  
✅ Remember Me

---

## 📚 Referensi

-   [Laravel Documentation](https://laravel.com/docs/12.x)
-   [Filament Documentation](https://filamentphp.com/docs/4.x)
-   [PostgreSQL Documentation](https://www.postgresql.org/docs/)
-   [Tailwind CSS Documentation](https://tailwindcss.com/docs)

---

## 🐛 Troubleshooting

### Error: Class not found

```bash
composer dump-autoload
php artisan optimize:clear
```

### Error: Database connection

```bash
# Check PostgreSQL running
docker-compose ps

# Restart services
docker-compose restart db
```

### Error: Filament icons not loading

```bash
npm run build
php artisan filament:optimize-clear
```

### Error: 500 Internal Server Error

```bash
# Check logs
tail -f storage/logs/laravel.log

# Fix permissions
chmod -R 775 storage bootstrap/cache
```

### Error: Session tidak tersimpan

```bash
# Pastikan tabel sessions sudah ada
php artisan migrate

# Clear session
php artisan session:flush
```

---

## 💡 Tips & Best Practices

### Performance Tips:

1. **Cache Configuration** untuk production:

    ```bash
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    ```

2. **Database Indexing** sudah diimplementasi di migration

3. **Eager Loading** jika ada relasi (belum diperlukan di app ini)

### Security Tips:

1. **Selalu gunakan HTTPS** di production
2. **Update `.env` production** dengan nilai yang aman
3. **Backup database** secara berkala
4. **Monitor logs** di `storage/logs/`

### Development Tips:

1. **Gunakan `php artisan tinker`** untuk testing cepat
2. **Install Laravel Debugbar** untuk development:
    ```bash
    composer require barryvdh/laravel-debugbar --dev
    ```
3. **Gunakan `dd()` atau `dump()`** untuk debugging

### Logging Tips:

1. **Gunakan log level yang tepat:**
    - `debug`: Detail untuk debugging
    - `info`: Informasi umum (user action)
    - `warning`: Ada yang tidak normal tapi tidak critical
    - `error`: Error yang perlu di-fix
    - `critical`: Kondisi kritis
2. **Selalu tambahkan context:**
    ```php
    Log::info('User login', ['user_id' => auth()->id()]);
    ```
3. **Gunakan channel audit untuk user actions:**
    ```php
    Log::channel('audit')->info('User deleted', ['user_id' => $user->user_id]);
    ```
4. **Monitor logs secara berkala:**
    ```bash
    tail -f storage/logs/laravel.log | grep '"level":"error"'
    ```

---

## 🚀 Next Steps (Optional)

Jika ingin mengembangkan aplikasi lebih lanjut:

1. **Implementasi Email Verification**
2. **Two-Factor Authentication (2FA)**
3. **Activity Logs / Audit Trail**
4. **Export Users ke CSV/Excel**
5. **User Profile Management**
6. **Password Reset via Email**
7. **API Endpoints dengan Laravel Sanctum**
8. **Unit & Feature Tests dengan Pest**

---

**Happy Coding! 🚀**
