# PRD — Aplikasi User Management (Filament)

## 1) Ringkasan Eksekutif

Aplikasi **User Management** berbasis **Laravel 12** menargetkan panel admin web dengan fitur **Login**, **Dashboard** (statistik jumlah user per role), dan **CRUD Users**. Arsitektur mengikuti **MVC Laravel best practice** dengan pemisahan concern yang jelas, menggunakan **Filament v4** (TALL: Tailwind v4, Alpine.js, Livewire 3) untuk antarmuka admin. **Rate limiting** diterapkan memakai **mekanisme resmi Laravel** (RateLimiter API + middleware `throttle`) dan **Dark Mode** disediakan (mengikuti tema sistem + toggle + persistensi).

---

## 2) Tujuan & Kriteria Keberhasilan

### 2.1 Tujuan

-   Menyediakan **Login** yang aman dan andal untuk panel admin.
-   Menyediakan **Dashboard** yang menampilkan **jumlah user per role**.
-   Menyediakan **CRUD Users** lengkap dengan pencarian, filter, sort, pagination, dan aksi massal.
-   Menerapkan **rate limiting** menggunakan **fitur bawaan Laravel**.
-   Menyediakan **Dark Mode** (system-aware + toggle user).

### 2.2 Kriteria Keberhasilan

-   Login berhasil **hanya** untuk user **aktif**.
-   Dashboard menampilkan kartu statistik untuk **admin**, **operator**, **validator**.
-   CRUD berfungsi dan **responsif** pada 390/768/1280 px.
-   **Rate limit** login aktif via `throttle` (menggunakan **RateLimiter** Laravel).
-   Dark Mode: mengikuti sistem, bisa di-toggle, tersimpan, tanpa FOUC.
-   README dan screenshot tersedia.

---

## 3) Ruang Lingkup

### 3.1 In-Scope

-   Panel admin `/admin`: Login, Dashboard, Users (List/Index, Create, Edit, Delete, Bulk Delete).
-   **Rate limiting** login memakai **Laravel RateLimiter** + middleware `throttle`.
-   **Dark Mode** untuk keseluruhan panel.
-   **Logging** JSON dan endpoint **/health** (cek DB).

### 3.2 Out-of-Scope

-   API publik, SSO/2FA, impor/ekspor CSV, audit trail UI, multi-tenant, notifikasi real-time.

---

## 4) Persona

-   **Superadmin**: penuh (termasuk delete).
-   **Admin**: viewAny/create/update.
-   **Operator/Validator**: tidak mengakses panel (ruang lingkup saat ini).

---

## 5) Arsitektur & Teknologi

### 5.1 Stack

-   **Laravel 12 (PHP ≥ 8.2)**
-   **Filament v4** (TALL: Tailwind v4, Alpine, Livewire 3)
-   **Eloquent**
-   **Auth guard `web`** (session/cookie)
-   **Dev tools**: Laravel Pint (format), Larastan (phpstan), Pest (tests)
-   **Tailwind Dark Mode**: `darkMode: 'class'`

### 5.2 Separation of Concerns

-   **Controller** tipis (koordinasi).
-   **Service** sederhana (contoh: `UserService` untuk CRUD).
-   **Form Request** untuk validasi.
-   **Model** fokus data (mutator, scope) + akses panel.
-   **Policy** untuk otorisasi role-based.
-   **Filament Resource** untuk form & tabel.

---

## 6) Model Data

### 6.1 Skema `users`

| Kolom          | Tipe                                                   | Keterangan          |
| -------------- | ------------------------------------------------------ | ------------------- |
| user_id        | INT(10) (AI, PK)                                       | Primary key         |
| name           | VARCHAR(100), NOT NULL                                 | Nama User           |
| email          | VARCHAR(255), UNIQUE, NOT NULL                         | Email unik          |
| password_hash  | VARCHAR(255), NOT NULL                                 | Hash password       |
| remember_token | VARCHAR(100), NULL                                     | Token "Ingat Saya"  |
| role           | ENUM(superadmin, admin, operator, validator), NOT NULL | Peran               |
| is_active      | TINYINT(1), DEFAULT 1, NOT NULL                        | 1=aktif, 0=nonaktif |
| created_at     | DATETIME, NULL                                         | Waktu dibuat        |
| updated_at     | DATETIME, NULL                                         | Waktu diperbarui    |

### 6.2 Perilaku Model `User`

-   `getAuthPassword()` → `password_hash`.
-   **Mutator** `setPasswordHashAttribute($value)` → input **plain** di-hash (hindari double-hash).
-   **Scope** `active()` → `where('is_active', 1)`.
-   **Filament** `canAccessPanel()` → hanya `is_active == 1`.

---

## 7) Spesifikasi Fungsional

### 7.1 Login (Panel)

-   Lokasi: `/admin/login` (Filament).
-   Syarat: email + password valid & user **aktif**.
-   Gagal login: pesan ramah + **backoff** 100–300 ms (mengurangi brute force).
-   **Rate limiting**: gunakan **Laravel RateLimiter** + middleware `throttle` (lihat §9).
-   Keamanan session: cookie httpOnly, SameSite=lax, Secure (produksi).

### 7.2 Dashboard

-   Halaman: `/admin`.
-   Widget **UsersByRoleOverview** (Stats Overview).
-   Kartu statistik: **Admin**, **Operator**, **Validator** (total; opsional tampil **Aktif / Total**).

### 7.3 Users — List/Index

-   **Responsif** (390/768/1280) + horizontal scroll bila sempit.
-   **Search**: by `email`.
-   **Filter**:

    -   Role: `superadmin | admin | operator | validator`
    -   Status: `Aktif | Nonaktif | Semua`

-   **Sort**: default `ID desc`, kolom lain sortable.
-   **Pagination**: default 10 per halaman.
-   **Kolom**: `ID`, `Email`, `Role` (badge), `Status` (badge), `Dibuat` (relative time).
-   **Row actions**: **Edit**, **Delete**.
-   **Bulk actions**: **Bulk Delete** (konfirmasi).
-   **Empty state** + CTA “Tambah User”.
-   **Global Search** (header) → ketik email/ID untuk lompat cepat.

### 7.4 Create User

-   Field: `name` (required), `email` (unique, required), `password` (plain → hash via mutator, required), `role` (enum, required), `is_active` (toggle; default aktif).
-   Validasi inline (FormRequest).
-   Aksi: **Simpan / Batal**.
-   Toast notifikasi sukses.

### 7.5 Edit User

-   Field sama. **Password opsional** (kosong → tidak diubah).
-   Validasi + toast sukses.
-   Kembali ke list setelah simpan.

### 7.6 Delete User

-   Konfirmasi modal.
-   **Bulk Delete** dengan konfirmasi.
-   Feedback sukses & refresh tabel.

### 7.7 Keamanan & Policy

-   Akses panel hanya user **aktif**.
-   **Policy**:

    -   `viewAny/create/update` → **superadmin | admin**
    -   `delete` → **superadmin**

-   **Audit ringan**: log saat create/update/delete (sertakan `request_id` & `actor_id`).

### 7.8 Dark Mode

-   Default mengikuti **system theme**.
-   **Toggle**: Light / Dark / System (persist di `localStorage`).
-   **Anti-FOUC**: skrip inisialisasi tema dieksekusi **sebelum render**.
-   Kontras minimal **WCAG AA** untuk teks utama.

---

## 8) Non-Fungsional

-   **Responsiveness**: stabil pada 390/768/1280.
-   **Keamanan**: `.env` produksi (`APP_DEBUG=false`), cookie Secure, SameSite=lax, CSRF aktif.
-   **Kinerja**: pagination server-side; query ringan untuk widget.
-   **Monitoring**: logging **JSON** (daily) + `X-Request-Id`; endpoint **/health** cek DB (`ok/degraded`).

---

## 9) Rate Limiting (Laravel 12 — Built-In)

Menggunakan **`Illuminate\Support\Facades\RateLimiter`** dan middleware **`throttle`**.

### 9.1 Kunci Pembeda (tanpa IP)

-   Untuk login (belum autentikasi): gunakan kombinasi **email yang dinormalisasi + session ID** guna menghindari false block pada jaringan IP bersama.

    -   `email_normalized = mb_strtolower(trim($request->input('email')))`.
    -   `session_id = $request->session()->getId()`.

### 9.2 Definisi Limiter

Didefinisikan di **`app/Providers/RouteServiceProvider.php`**:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function configureRateLimiting(): void
{
    RateLimiter::for('web-login', function (Request $request) {
        $email  = mb_strtolower(trim((string) $request->input('email', 'unknown')));
        $sessId = $request->session()->getId() ?: 'no-session';
        // contoh: 5 percobaan per menit per (email+session)
        return Limit::perMinute(5)->by("web-login:{$email}|{$sessId}");
    });
}
```

### 9.3 Penerapan di Route

-   Terapkan pada rute **login POST** (rute autentikasi panel) dengan middleware:

```php
// contoh, jika rute login panel perlu override:
Route::post('/admin/login')->middleware('throttle:web-login');
```

> Untuk panel Filament, middleware `throttle:web-login` dapat diterapkan melalui konfigurasi panel/route service provider sesuai kebutuhan proyek.

---

## 10) Antarmuka Teknis

### 10.1 Guard & Auth

-   **Guard**: `web` (driver `session`).
-   Auth bawaan Laravel untuk login, logout, middleware `auth`.

### 10.2 Middleware

-   **Global**: `RequestId` (set `X-Request-Id` + `Log::withContext`), `SecurityHeaders` (CSP ringan, HSTS di HTTPS, no-sniff, frame-deny).
-   **Web**: `auth`, `verified` (opsional), `throttle:web-login` khusus rute login.

### 10.3 Health

-   `GET /health` → JSON: status app, waktu, hasil ping DB.

---

## 11) Validasi & Otorisasi

### 11.1 Form Request

-   **StoreUserRequest**:

    -   `name`: required|string|max:100
    -   `email`: required|email|unique:users,email
    -   `password_hash`: required|min:8 _(input plain → mutator hash)_
    -   `role`: required|in:superadmin,admin,operator,validator
    -   `is_active`: boolean

-   **UpdateUserRequest**:

    -   `name`: required|string|max:100
    -   `email`: required|email|unique:users,email,{user_id},user_id
    -   `password_hash`: nullable|min:8
    -   `role`: required|in:…
    -   `is_active`: boolean

### 11.2 Policy `UserPolicy`

-   `viewAny/create/update` → superadmin|admin
-   `delete` → superadmin

---

## 12) Implementasi (Urutan)

1. **Bootstrap**: Laravel 12, Tailwind v4 (Vite), Filament v4, Livewire 3.
2. **DB & Model**: migration `users`, Model `User` (mutator, scope, canAccessPanel).
3. **Seeder**: `superadmin@example.com / Password123`.
4. **Filament**: generate `UserResource` (form & table sesuai §7.3–7.6).
5. **Dashboard**: widget `UsersByRoleOverview`.
6. **Policy**: `UserPolicy` + daftarkan di `AuthServiceProvider`.
7. **FormRequest**: Store/Update User.
8. **RateLimiter**: definisi `web-login` di `RouteServiceProvider`; terapkan `throttle:web-login` ke login POST.
9. **Dark Mode**: `darkMode: 'class'`, script anti-FOUC, toggle (Light/Dark/System).
10. **Monitoring**: middleware `RequestId`, `SecurityHeaders`; route `/health`.
11. **README & Tests**: Pest (login aktif/nonaktif, CRUD dasar, policy delete), screenshot.

---

## 13) Acceptance Criteria (Checklist)

-   [ ] Login panel hanya untuk **user aktif**; pesan gagal ramah; backoff 100–300 ms.
-   [ ] **Rate limit** login aktif via **Laravel RateLimiter + `throttle:web-login`** (5/min per email+session).
-   [ ] Dashboard menampilkan total user per role (admin/operator/validator).
-   [ ] Users List: search, filter role/status, sort, pagination, global search; responsif 390/768/1280.
-   [ ] Create/Edit/Delete/Bulk Delete berfungsi; password di-hash via mutator; toast sukses.
-   [ ] Policy: viewAny/create/update (admin, superadmin); delete (superadmin).
-   [ ] Dark Mode: follow system; toggle Light/Dark/System; persist; tanpa FOUC; kontras OK.
-   [ ] Non-fungsional: `.env` aman; logging JSON + `X-Request-Id`; `/health` OK.

---

## 14) Deliverables

-   **Repository** lengkap.
-   **README**: setup (composer, npm, migrate, seed), run (artisan serve), kredensial seed, screenshot (Login, Dashboard, Users List, Create/Edit, modal Delete, Dark/Light).
-   **PRD** (.md) ini.

---

## 15) Lampiran Teknis (Snippet Ringkas)

### 15.1 Mutator Password

```php
// app/Models/User.php
public function setPasswordHashAttribute(?string $value): void
{
    if (filled($value)) {
        $this->attributes['password_hash'] =
            str_starts_with((string) $value, '$2y$')
                ? $value
                : \Illuminate\Support\Facades\Hash::make($value);
    }
}
```

### 15.2 Rate Limiter (Laravel)

```php
// app/Providers/RouteServiceProvider.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function configureRateLimiting(): void
{
    RateLimiter::for('web-login', function (Request $request) {
        $email  = mb_strtolower(trim((string) $request->input('email', 'unknown')));
        $sessId = $request->session()->getId() ?: 'no-session';
        return Limit::perMinute(5)->by("web-login:{$email}|{$sessId}");
    });
}
```

### 15.3 Terapkan `throttle` pada Login Route

```php
// contoh: routes/web.php (jika override rute login)
Route::post('/admin/login')->middleware('throttle:web-login');
```

### 15.4 Dark Mode (Tailwind + Anti-FOUC + Toggle)

```js
// tailwind.config.js
export default {
    darkMode: "class",
    content: ["./resources/views/**/*.blade.php", "./app/Filament/**/*.php"],
};
```

```blade
{{-- resources/views/partials/theme-loader.blade.php --}}
<script>
(()=>{try{
  const s=localStorage.getItem('theme');
  const sys=window.matchMedia('(prefers-color-scheme: dark)').matches;
  const dark = s==='dark' || (!s || s==='system') && sys;
  document.documentElement.classList.toggle('dark', dark);
}catch(e){}})();
</script>
```

```php
// app/Providers/Filament/AdminPanelProvider.php
return $panel->renderHook('panels::body.start', fn()=> view('partials.theme-loader')->render());
```

```blade
{{-- contoh toggle sederhana di header --}}
<div x-data="{
  set(t){ localStorage.setItem('theme', t);
    const sys=window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.classList.toggle('dark', t==='dark'||(t==='system'&&sys));
  }
}">
  <button @click="set('light')"  aria-label="Light">🌞</button>
  <button @click="set('dark')"   aria-label="Dark">🌙</button>
  <button @click="set('system')" aria-label="System">💻</button>
</div>
```

### 15.5 Health Endpoint (DB Ping)

```php
// routes/web.php
Route::get('/health', function () {
    $checks = ['app'=>'ok', 'time'=>now()->toIso8601String()];
    try { \DB::connection()->getPdo(); $checks['database'] = 'ok'; }
    catch (\Throwable $e) { $checks['database'] = 'down'; }
    return response()->json([
        'status' => in_array('down', $checks, true) ? 'degraded' : 'ok',
        'checks' => $checks,
    ]);
});
```

---
