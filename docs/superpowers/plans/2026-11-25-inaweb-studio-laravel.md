# INAweb Studio pada Laravel 13 (Role UMKM) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengimplementasikan aplikasi INAweb Studio (`inamikro-webbuilder-html`) secara utuh ke dalam Laravel 13 dengan autentikasi UMKM, penyimpanan MySQL (`1 akun = 1 landing page`), upload gambar ke server, slug permanen, dan halaman publik langsung di `/{slug}`.

**Architecture:** Menggunakan **Pendekatan A**: antarmuka Studio, `style.css`, `templates.js`, dan `app.js` dipertahankan 100% di sisi klien untuk menjaga *live preview* instan, checklist, undo/riwayat 10 versi, serta *Unduh HTML*, lalu dihubungkan ke Laravel JSON API (`/api/site`, `/api/media`) dan MySQL. Halaman publik `GET /{slug}` menyajikan `rendered_html` yang disanitasi di server dan diproteksi header `Content-Security-Policy` SHA-256 hash yang terikat langsung pada blok `CLIENT_JS`.

**Tech Stack:** Laravel 13 / PHP 8.5, MySQL (XAMPP), PHPUnit 12, Laravel Pint, Laravel Boost, Vanilla JS (`templates.js`, `app.js`), Plain CSS (`style.css`).

**Spec:** `docs/superpowers/specs/2026-11-25-inaweb-studio-laravel-design.md`

## Global Constraints

- **Conventional Commits:** Wajib format `<type>(<scope>): <deskripsi singkat>` di setiap commit (`GEMINI.md`).
- **Laravel Pint:** Wajib jalankan `vendor/bin/pint --dirty --format agent` sebelum setiap commit PHP.
- **Laravel Boost:** Wajib install `laravel/boost --dev` dan jalankan `php artisan boost:install` pada Task 1 sebelum perubahan kode aplikasi (`AGENTS.md`).
- **Bahasa UI:** Seluruh label, pesan error validasi, dan copywriting antarmuka wajib Bahasa Indonesia.
- **Integritas Prototipe:** Jangan mengubah tata letak, gaya visual, checklist 4 item, 8 template, atau fitur editor di `inamikro-webbuilder-html`; tombol *"Tanya admin"* dan *"Hubungkan situs"* menampilkan toast *"Fitur segera hadir."*
- **Aturan Bisnis:** `1 user = 1 landing page`; `slug` diturunkan permanen dari `users.business_name` dan tidak pernah berubah oleh edit editor.
- **Keamanan & Tenant:** Mutasi API selalu terikat ke `Auth::user()`; tidak menyimpan base64 gambar di MySQL; halaman `GET /{slug}` dilindungi sanitasi script + CSP SHA-256.

## Review Focus

1. **Stored XSS same-origin pada `GET /{slug}`:** Request palsu ke `PUT /api/site` atau `POST /api/site/publish` yang menyisipkan `<script>` liar atau `onload=` harus dibersihkan oleh `SiteHtmlSanitizer` dan diblokir oleh header CSP SHA-256 pada `GET /{slug}`.
2. **Slug permanen & perlindungan route sistem:** Mengubah `content.nama` di editor atau mencoba mendaftar dengan nama seperti `"login"` / `"studio"` / `"api"` tidak boleh merusak route aplikasi maupun mengubah `sites.slug`.
3. **Isolasi penghapusan media & path traversal:** `DELETE /api/media` dengan payload `/storage/sites/999/foto.jpg` atau `/storage/sites/1/../2/foto.jpg` wajib ditolak dengan status `403`.
4. **Edit situs `published` dengan syarat wajib sementara kosong:** Auto-save `PUT /api/site` tetap menyimpan `content` draf agar kerjaan tidak hilang, sementara `rendered_html` publik lama yang valid tidak boleh ditimpa sampai 4 syarat terpenuhi.
5. **Sinkronisasi hash `CLIENT_JS` antara `public/studio/templates.js` dan backend:** Test otomatis wajib memverifikasi hash SHA-256 `CLIENT_JS` di `public/studio/templates.js` identik dengan `SiteHtmlSanitizer::cspScriptHash()` agar fitur pembeli di `GET /{slug}` tidak terblokir CSP.

---

### Task 1: Environment Setup, MySQL Config & Laravel Boost

**Files:**
- Modify: `composer.json`, `composer.lock`, `AGENTS.md`, `boost.json` (via `boost:install`)
- Modify: `.env` (untracked local config)

**Interfaces:**
- Consumes: Fresh Laravel 13 skeleton
- Produces: Laravel Boost installed, `.env` pointed to XAMPP MySQL (`inamikro_webbuilder`), `public/storage` symlink active, `phpunit.xml` verified using `:memory:` SQLite.

- [ ] **Step 1: Install Laravel Boost**

Jalankan dari root repository:
```powershell
composer require laravel/boost --dev
php artisan boost:install --no-interaction
```
Jika `boost:install` meminta pilihan interaktif tanpa `--no-interaction`, jalankan sesuai opsi default untuk agent/environment yang ada.

- [ ] **Step 2: Configure `.env` for XAMPP MySQL & create storage link**

Update `.env`:
```env
APP_NAME="INAweb Studio"
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inamikro_webbuilder
DB_USERNAME=root
DB_PASSWORD=
```
Jalankan:
```powershell
php artisan storage:link
php artisan config:clear
```

- [ ] **Step 3: Verify `phpunit.xml` and run baseline test suite**

Pastikan `phpunit.xml` tetap menggunakan `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:`.
Run: `vendor\bin\phpunit`
Expected: PASS (2 tests, 2 assertions) — tidak menyentuh MySQL live.

- [ ] **Step 4: Commit Laravel Boost setup**

```powershell
git add -A
git commit -m "chore(setup): install Laravel Boost and verify baseline tests"
```

---

### Task 2: Database Schema, Models & Permanent `SlugService` (TDD)

**Files:**
- Create: `app/Services/SlugService.php`
- Create: `database/migrations/2026_11_25_000001_add_umkm_fields_to_users_table.php`
- Create: `database/migrations/2026_11_25_000002_create_sites_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Create: `app/Models/Site.php`
- Create: `database/factories/SiteFactory.php`
- Create: `tests/Unit/SlugServiceTest.php`
- Create: `tests/Feature/DatabaseSchemaTest.php`

**Interfaces:**
- Consumes: `users` table
- Produces:
  - `App\Services\SlugService::generateUniqueSlug(string $businessName, ?int $ignoreUserId = null): string`
  - `App\Services\SlugService::RESERVED_SLUGS` (constant array)
  - `User` attributes: `name`, `business_name`, `slug`, `role`, `email`, `password` + `site(): HasOne`
  - `Site` attributes: `user_id`, `template_id`, `slug`, `status`, `brand_color`, `content` (array cast), `rendered_html`, `published_at` (datetime cast) + `user(): BelongsTo`

- [ ] **Step 1: Write failing tests for `SlugService` and Database Schema**

Buat `tests/Unit/SlugServiceTest.php` menguji:
- `test_generates_clean_slug_from_business_name`: `"Dapur Bu Sari!"` → `"dapur-bu-sari"`, string kosong/simbol saja → fallback `"usaha"`.
- `test_avoids_reserved_slugs`: input `"Login"`, `"Studio"`, `"API"`, `"Admin"`, `"Storage"` otomatis diberi suffix (misal `"login-2"`).
- `test_appends_numeric_suffix_when_slug_already_exists_in_users_or_sites`: jika `"kopi-senja"` sudah dipakai user lain, menghasilkan `"kopi-senja-2"`, dst.
- `test_allows_same_slug_when_ignoring_current_user_id`: validasi ignore `$ignoreUserId`.

Buat `tests/Feature/DatabaseSchemaTest.php` menguji:
- `test_users_table_stores_business_name_slug_and_role`: default `role` = `'umkm'`.
- `test_sites_table_enforces_one_site_per_user_and_casts_content_to_array`: 1 user tidak boleh punya 2 record `Site` (unique constraint exception), `content` ter-cast sebagai `array`, `published_at` ter-cast sebagai `Carbon`.

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Unit/SlugServiceTest.php tests/Feature/DatabaseSchemaTest.php`
Expected: FAIL (`SlugService` & `Site` not found).

- [ ] **Step 3: Implement `SlugService`, migrations, models, and factories**

1. Buat `app/Services/SlugService.php` dengan `RESERVED_SLUGS`:
   `['login', 'register', 'logout', 'studio', 'api', 'admin', 'dashboard', 'storage', 'up', 'build', 'assets', 'vendor', 'auth', 'password', 's', 'favicon.ico', 'robots.txt']`
   Method `generateUniqueSlug(string $businessName, ?int $ignoreUserId = null): string` mengecek `RESERVED_SLUGS` dan tabel `users` serta `sites`.
2. Buat 2 migration dengan `php artisan make:migration`:
   - `add_umkm_fields_to_users_table`: tambah `business_name` (`string`), `slug` (`string(120)->unique()`), `role` (`string(30)->default('umkm')`) setelah `name`/`password`.
   - `create_sites_table`: `id`, `user_id` (`foreignId()->unique()->constrained()->cascadeOnDelete()`), `template_id` (`string(50)`), `slug` (`string(120)->unique()`), `status` (`string(20)->default('draft')`), `brand_color` (`string(20)->default('#dc2626')`), `content` (`json`), `rendered_html` (`longText()->nullable()`), `published_at` (`timestamp()->nullable()`), `timestamps`.
3. Update `app/Models/User.php`: tambahkan `'business_name'`, `'slug'`, `'role'` ke `#[Fillable]`, dan relasi `site(): HasOne`.
4. Buat `app/Models/Site.php` dengan `#[Fillable]`, `casts()` (`content => 'array'`, `published_at => 'datetime'`), dan `user(): BelongsTo`.
5. Update `database/factories/UserFactory.php` dan buat `database/factories/SiteFactory.php`.

- [ ] **Step 4: Run tests to verify GREEN**

Run: `vendor\bin\phpunit tests/Unit/SlugServiceTest.php tests/Feature/DatabaseSchemaTest.php`
Expected: PASS

- [ ] **Step 5: Format and commit**

```powershell
vendor\bin\pint --dirty --format agent
git add app/Services/SlugService.php app/Models/User.php app/Models/Site.php database/migrations/ database/factories/ tests/Unit/SlugServiceTest.php tests/Feature/DatabaseSchemaTest.php
git commit -m "feat(database): add user business fields, sites table, and permanent SlugService"
```

---

### Task 3: UMKM Authentication (`/login`, `/register`, `/logout`) & Root Redirect (TDD)

**Files:**
- Create: `app/Http/Controllers/Auth/AuthController.php`
- Create: `app/Http/Requests/Auth/LoginRequest.php`
- Create: `app/Http/Requests/Auth/RegisterRequest.php`
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/auth/register.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/AuthTest.php`

**Interfaces:**
- Consumes: `User`, `SlugService::generateUniqueSlug()`
- Produces: Routes `login` (`GET/POST /login`), `register` (`GET/POST /register`), `logout` (`POST /logout`), `/` redirect (`guest -> /login`, `auth -> /studio`).

- [ ] **Step 1: Write failing tests in `tests/Feature/AuthTest.php`**

Uji skenario berikut:
- `test_root_redirects_guest_to_login_and_authenticated_user_to_studio`
- `test_login_page_renders_in_indonesian`
- `test_register_page_renders_with_business_name_field`
- `test_umkm_can_register_and_gets_permanent_slug_and_umkm_role`: POST `/register` dengan `name`, `business_name`, `email`, `password`, `password_confirmation` → user tersimpan dengan `role = 'umkm'`, `slug` unik (uji juga reserved slug `"Login"` menghasilkan `"login-2"`), terautentikasi, redirect `/studio`.
- `test_register_validates_required_fields_and_unique_email_in_indonesian`
- `test_umkm_can_login_and_logout`: login sukses redirect `/studio`, password salah gagal dengan pesan error Indonesia, logout mengosongkan sesi dan redirect `/login`.

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Feature/AuthTest.php`
Expected: FAIL (404 / routes not defined).

- [ ] **Step 3: Implement `AuthController`, FormRequests, Auth Blade views, and routes**

1. Buat `LoginRequest` dan `RegisterRequest` dengan pesan error Bahasa Indonesia (`email.required`, `business_name.required`, `password.confirmed`, dll).
2. Buat `AuthController`:
   - `register()` memakai `SlugService::generateUniqueSlug($validated['business_name'])`, membuat `User` (`role => 'umkm'`), `Auth::login($user)`, `regenerate()`, redirect `route('studio')`.
   - `login()` memvalidasi kredensial dengan `remember`, `regenerate()`, redirect `intended(route('studio'))`, atau `back()->withErrors(['email' => 'Email atau kata sandi salah.'])`.
   - `logout()` meng-invalidate session, regenerate token, redirect `route('login')`.
3. Buat `resources/views/auth/login.blade.php` dan `register.blade.php` memakai gaya visual INAweb Studio (`Plus Jakarta Sans`, `--brand: #dc2626`, `--page-bg: #fff8f7`, brand mark `i` *INAweb Studio*, kartu bersih, Bahasa Indonesia).
4. Update `routes/web.php` untuk `/`, `/login`, `/register`, `/logout`, dan stub sementara `/studio` (placeholder auth route agar redirect test lulus sebelum Task 6).

- [ ] **Step 4: Run tests to verify GREEN**

Run: `vendor\bin\phpunit tests/Feature/AuthTest.php`
Expected: PASS

- [ ] **Step 5: Format and commit**

```powershell
vendor\bin\pint --dirty --format agent
git add app/Http/Controllers/Auth/ app/Http/Requests/Auth/ resources/views/auth/ routes/web.php tests/Feature/AuthTest.php
git commit -m "feat(auth): implement UMKM login, registration with permanent slug, and logout"
```

---

### Task 4: Studio Site JSON API & `SiteHtmlSanitizer` (TDD)

**Files:**
- Create: `app/Services/SiteHtmlSanitizer.php`
- Create: `app/Http/Controllers/Api/SiteApiController.php`
- Create: `app/Http/Requests/Api/SaveSiteRequest.php`
- Create: `app/Http/Requests/Api/PublishSiteRequest.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/SiteApiTest.php`

**Interfaces:**
- Consumes: `Site`, `User`, `templates.js` canonical `CLIENT_JS`
- Produces:
  - `SiteHtmlSanitizer::CLIENT_JS` (const string persis sama dengan `CLIENT_JS` di `templates.js`)
  - `SiteHtmlSanitizer::sanitize(string $html): string`
  - `SiteHtmlSanitizer::cspScriptHash(): string`
  - `SiteHtmlSanitizer::cspHeader(): string`
  - API routes (`auth` middleware): `GET /api/site`, `PUT /api/site`, `POST /api/site/publish`, `DELETE /api/site`.

- [ ] **Step 1: Write failing tests in `tests/Feature/SiteApiTest.php`**

Uji skenario berikut:
- `test_guest_cannot_access_site_api_endpoints`: semua endpoint mengembalikan `401` untuk JSON request tanpa login.
- `test_umkm_can_get_empty_or_existing_site_with_account_slug`
- `test_umkm_can_save_draft_and_slug_remains_locked_to_user_slug`: ubah `content.nama` ke `"Nama Baru Berubah"` via `PUT /api/site`, pastikan `sites.slug` tetap sama dengan `$user->slug` dan tidak berubah.
- `test_save_site_rejects_base64_images_in_content`: pastikan `heroImg` atau `produk.0.img` berawalan `data:image/` ditolak dengan `422`.
- `test_publish_requires_all_four_checklist_items`: kosongkan `nama`, `wa`, `jam`, atau produk → `POST /api/site/publish` mengembalikan `422` dengan error Indonesia.
- `test_umkm_can_publish_site_and_sanitizer_strips_xss_scripts_and_inline_handlers`: kirim HTML mengandung `<script>alert(1)</script>` dan `<div onclick="evil()">` ke `POST /api/site/publish` → pastikan skrip jahat & `onclick=` hilang dari `rendered_html` tersimpan, sedangkan skrip kanonis `CLIENT_JS` terpasang rapi.
- `test_editing_published_site_with_incomplete_fields_saves_draft_without_overwriting_valid_rendered_html`: ubah `content.wa` jadi `""` lewat `PUT /api/site` pada situs `published` → `content` tersimpan, namun `rendered_html` lama yang valid tetap utuh.
- `test_delete_site_removes_record_and_user_media_directory`: `DELETE /api/site` menghapus record `sites` dan folder `Storage::disk('public')->exists("sites/{$user->id}")` menjadi `false`.

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Feature/SiteApiTest.php`
Expected: FAIL (classes/routes not found).

- [ ] **Step 3: Implement `SiteHtmlSanitizer`, FormRequests, `SiteApiController`, and routes**

1. Buat `app/Services/SiteHtmlSanitizer.php`:
   - Simpan string kanonis `CLIENT_JS` persis sama dengan isi `CLIENT_JS` di `inamikro-web-builder-html/templates.js` (tanpa spasi/baris tambahan yang berbeda).
   - `sanitize(string $html): string`: gunakan `DOMDocument` (`libxml_use_internal_errors(true)`) atau DOM parsing yang bersih untuk menghapus semua `<script>`, `<iframe>`, `<object>`, `<embed>`, serta seluruh atribut `on*` (`onclick`, `onerror`, `onload`, dll) dan `href`/`src` berawalan `javascript:` / `vbscript:`, lalu sisipkan `<script>` tunggal berisi `self::CLIENT_JS` tepat sebelum `</body>`.
   - `cspScriptHash(): string`: `'sha256-' . base64_encode(hash('sha256', self::CLIENT_JS, true))`.
   - `cspHeader(): string`: `"default-src 'self'; script-src '" . self::cspScriptHash() . "'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self';"`.
2. Buat `SaveSiteRequest` & `PublishSiteRequest`:
   - Validasi `template_id` (`in:warung,warung-2,butik,butik-2,jasa,jasa-2,toko,toko-2`), `brand_color` (`regex:/^#[0-9a-fA-F]{6}$/`), `content` array, tolak base64 pada `content.heroImg` dan `content.produk.*.img` (hanya izinkan `nullable`, `string`, dan tidak diawali `data:`).
   - Pada `PublishSiteRequest`: wajibkan `content.nama`, `content.wa`, `content.jam`, dan minimal 1 item di `content.produk` dengan `nama` terisi, serta `rendered_html` wajib `string`.
3. Buat `SiteApiController` (`show`, `upsert`, `publish`, `destroy`) dengan scoping ketat `$request->user()` dan sanitasi HTML via `SiteHtmlSanitizer`.
4. Daftarkan route API di `routes/web.php` di bawah `Route::prefix('api')->middleware('auth')->group(...)`.

- [ ] **Step 4: Run tests to verify GREEN**

Run: `vendor\bin\phpunit tests/Feature/SiteApiTest.php`
Expected: PASS

- [ ] **Step 5: Format and commit**

```powershell
vendor\bin\pint --dirty --format agent
git add app/Services/SiteHtmlSanitizer.php app/Http/Controllers/Api/SiteApiController.php app/Http/Requests/Api/ routes/web.php tests/Feature/SiteApiTest.php
git commit -m "feat(api): add site CRUD, publish validation, and HTML XSS sanitizer"
```

---

### Task 5: Media Upload & Tenant-Isolated Deletion API (`/api/media`) (TDD)

**Files:**
- Create: `app/Http/Controllers/Api/MediaApiController.php`
- Create: `app/Http/Requests/Api/UploadMediaRequest.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/MediaUploadTest.php`

**Interfaces:**
- Consumes: `Storage::disk('public')`, `Auth::user()`
- Produces: `POST /api/media` (`201` `{ url: '/storage/sites/{user_id}/...' }`), `DELETE /api/media` (`200` `{ deleted: true }`).

- [ ] **Step 1: Write failing tests in `tests/Feature/MediaUploadTest.php`**

Uji skenario berikut:
- `test_guest_cannot_upload_or_delete_media`
- `test_umkm_can_upload_valid_image_to_own_directory`: `UploadedFile::fake()->image('hero.jpg')` → returns 201 dengan `url` diawali `/storage/sites/{$user->id}/`, file ada di `Storage::disk('public')`.
- `test_upload_rejects_non_image_or_oversized_file`
- `test_umkm_can_delete_own_uploaded_image`: file terhapus dari `Storage::disk('public')`.
- `test_umkm_cannot_delete_another_users_media_or_use_path_traversal`: coba hapus `/storage/sites/{$otherUser->id}/secret.jpg` atau `/storage/sites/{$user->id}/../{$otherUser->id}/secret.jpg` → returns `403`, file milik user lain tetap ada.

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Feature/MediaUploadTest.php`
Expected: FAIL

- [ ] **Step 3: Implement `UploadMediaRequest`, `MediaApiController`, and routes**

1. `UploadMediaRequest`: `'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']` dengan pesan Indonesia.
2. `MediaApiController::store`: `$path = $request->file('image')->store('sites/' . $request->user()->id, 'public');` mengembalikan `response()->json(['url' => '/storage/' . $path], 201)`.
3. `MediaApiController::destroy`: validasi `url` string, cek tidak mengandung `..` atau `\`, pastikan diawali `'/storage/sites/' . $request->user()->id . '/'`; jika tidak cocok, `abort(403, 'Akses ditolak.')`. Hapus `substr($url, strlen('/storage/'))` dari `Storage::disk('public')`.
4. Tambahkan route `POST /api/media` & `DELETE /api/media` di `routes/web.php`.

- [ ] **Step 4: Run tests to verify GREEN**

Run: `vendor\bin\phpunit tests/Feature/MediaUploadTest.php`
Expected: PASS

- [ ] **Step 5: Format and commit**

```powershell
vendor\bin\pint --dirty --format agent
git add app/Http/Controllers/Api/MediaApiController.php app/Http/Requests/Api/UploadMediaRequest.php routes/web.php tests/Feature/MediaUploadTest.php
git commit -m "feat(media): add image upload and tenant-isolated deletion API"
```

---

### Task 6: INAweb Studio Integration (`/studio`, `public/studio/*`) & Frontend API Wiring

**Files:**
- Create: `app/Http/Controllers/StudioController.php`
- Create: `resources/views/studio/index.blade.php`
- Create: `public/studio/style.css`
- Create: `public/studio/templates.js`
- Create: `public/studio/app.js`
- Modify: `routes/web.php`
- Create: `tests/Feature/StudioViewTest.php`

**Interfaces:**
- Consumes: `Site`, `User`, `/api/site`, `/api/site/publish`, `/api/media`, `SiteHtmlSanitizer::cspScriptHash()`
- Produces: Complete authenticated Studio view at `GET /studio` wired to Laravel APIs and server media storage.

- [ ] **Step 1: Write failing tests in `tests/Feature/StudioViewTest.php`**

Uji skenario berikut:
- `test_guest_is_redirected_from_studio_to_login`
- `test_authenticated_umkm_can_view_studio_with_injected_config_and_logout_button`: cek `window.INAWEB_CONFIG`, `businessName`, `siteSlug`, `csrfToken`, dan form/tombol `POST /logout`.
- `test_studio_static_assets_exist_in_public_studio`: `style.css`, `templates.js`, `app.js` ada di `public/studio/`.
- `test_templates_js_client_js_hash_matches_server_sanitizer_hash`: ekstrak blok `const CLIENT_JS = \`...\`;` dari `public/studio/templates.js` (atau jalankan hash check) dan pastikan `'sha256-' . base64_encode(hash('sha256', ...))` sama persis dengan `SiteHtmlSanitizer::cspScriptHash()`.

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Feature/StudioViewTest.php`
Expected: FAIL

- [ ] **Step 3: Copy & adapt `style.css`, `templates.js`, `app.js`, and build `StudioController` + `studio/index.blade.php`**

1. Salin `inamikro-web-builder-html/style.css` ke `public/studio/style.css` (+ tambahkan styling ringan untuk tombol/menu **Keluar** di topbar & mobile nav agar selaras dengan gaya tombol `.btn-ghost` / `.mobile-nav-item`).
2. Salin `inamikro-web-builder-html/templates.js` ke `public/studio/templates.js` dan sesuaikan:
   - `imgOr` dan `prodThumb` mendukung `/^(https?:\/\/|\/storage\/|data:image\/)/`.
   - `siteUrl(state)` memakai `window.INAWEB_CONFIG?.publicSiteUrl` jika tersedia (fallback ke `"https://inamikro.co.id/" + slugTpl(state.nama)` untuk keamanan).
   - `doc()` menyertakan `<meta property="og:image" content="...">` apabila `state.heroImg` ada (dikonversi ke URL absolut memakai `window.INAWEB_CONFIG?.publicBaseUrl`).
   - Pastikan string `CLIENT_JS` **identik byte-per-byte** dengan `SiteHtmlSanitizer::CLIENT_JS`!
3. Salin `inamikro-web-builder-html/app.js` ke `public/studio/app.js` dan integrasikan dengan Laravel API:
   - Baca `window.INAWEB_CONFIG` (`initialSite`, `businessName`, `siteSlug`, `publicSiteUrl`, `publicBaseUrl`, `csrfToken`, `routes`).
   - Di `defaultsFor(t)`, jika `window.INAWEB_CONFIG?.businessName` ada, pakai itu sebagai nilai awal `nama` saat membuat situs baru.
   - `slugOf()` selalu mengembalikan `window.INAWEB_CONFIG?.siteSlug` jika tersedia agar domain preview & *Situs saya* menampilkan slug permanen akun.
   - Ganti `localStorage` (`allDrafts`, `loadDraft`, `save`, `delete`) dengan request `fetch` JSON (`PUT /api/site`, `POST /api/site/publish`, `DELETE /api/site`) ber-header `X-CSRF-TOKEN`. Debounce auto-save `~600ms`.
   - Pada upload foto (`#heroUpload` dan `[data-prod-upload]`), setelah `compressImage()`, kirim `Blob` ke `POST /api/media`, simpan `url` (`/storage/...`) ke `state` (sehingga tidak ada base64 di database), dan hapus via `DELETE /api/media` saat user klik hapus foto.
   - Pada `exportHtml()`, konversi `/storage/...` di HTML hasil `activeTpl.render(state)` menjadi URL absolut sebelum diunduh.
   - Pada tombol **Tanya admin** dan **Hubungkan situs**, tampilkan `toast("Fitur segera hadir.")`.
   - Di `renderMySites()`, tampilkan status `Draf` atau `Terbit`, URL permanen `publicSiteUrl`, serta tombol `Lihat situs` (`target="_blank"`) jika status `'published'`.
4. Buat `StudioController::__invoke()` dan `resources/views/studio/index.blade.php` (memindahkan seluruh struktur HTML dari `inamikro-web-builder-html/index.html`, mengganti URL preview chrome menjadi URL base asli + slug akun, menambahkan tombol Keluar, dan menyuntikkan `@json($studioConfig)` ke `window.INAWEB_CONFIG`).
5. Update route `GET /studio` di `routes/web.php` menunjuk ke `StudioController`.

- [ ] **Step 4: Run tests to verify GREEN**

Run: `vendor\bin\phpunit tests/Feature/StudioViewTest.php`
Expected: PASS

- [ ] **Step 5: Format and commit**

```powershell
vendor\bin\pint --dirty --format agent
git add app/Http/Controllers/StudioController.php resources/views/studio/ public/studio/ routes/web.php tests/Feature/StudioViewTest.php
git commit -m "feat(studio): integrate INAweb Studio UI with Laravel API and media uploads"
```

---

### Task 7: Public Route (`/{slug}`), Demo Seeder, MySQL Migration & Cleanup (TDD)

**Files:**
- Create: `app/Http/Controllers/PublicSiteController.php`
- Create: `database/seeders/DemoUmkmSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `routes/web.php`
- Delete: `inamikro-web-builder-html/` (after verifying everything migrated)
- Create: `tests/Feature/PublicSiteTest.php`

**Interfaces:**
- Consumes: `Site`, `SiteHtmlSanitizer`
- Produces: `GET /{slug}` route (last in `routes/web.php`) returning sanitized HTML + CSP headers, seeded MySQL database `inamikro_webbuilder`.

- [ ] **Step 1: Write failing tests in `tests/Feature/PublicSiteTest.php`**

Uji skenario berikut:
- `test_published_site_is_accessible_at_root_slug_with_csp_header`: `GET /{$site->slug}` → `200`, mengandung nama/konten HTML situs, header `Content-Security-Policy` mengandung `SiteHtmlSanitizer::cspScriptHash()`.
- `test_draft_site_returns_404_at_root_slug`: `GET /{$draftSite->slug}` → `404`.
- `test_unknown_slug_returns_404`: `GET /tidak-ada` → `404`.
- `test_system_routes_take_precedence_over_public_slug_fallback`: `/login`, `/register`, `/studio`, `/up` tetap merespons route sistem, bukan tertangkap oleh `/{slug}`.
- `test_demo_umkm_seeder_creates_ready_to_use_umkm_account`: jalankan `$this->seed(DemoUmkmSeeder::class)` dan verifikasi akun demo (`sari@inamikro.id`) memiliki `business_name`, `slug`, dan `role === 'umkm'`.

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Feature/PublicSiteTest.php`
Expected: FAIL

- [ ] **Step 3: Implement `PublicSiteController`, `DemoUmkmSeeder`, register fallback route, run cleanup & `migrate:fresh --seed`**

1. Buat `app/Http/Controllers/PublicSiteController.php` (`__invoke(string $slug)`): query `Site::where('slug', $slug)->where('status', 'published')->firstOrFail()`, kembalikan `response($site->rendered_html, 200)->header('Content-Type', 'text/html; charset=UTF-8')->header('Content-Security-Policy', $sanitizer->cspHeader())`.
2. Daftarkan paling akhir di `routes/web.php`:
   `Route::get('/{slug}', PublicSiteController::class)->where('slug', '[a-z0-9-]+')->name('public.site');`
3. Buat `DemoUmkmSeeder.php` (akun demo: `Sari Rahmawati`, `business_name`: `"Dapur Bu Sari"`, `slug`: `"dapur-bu-sari"`, `email`: `sari@inamikro.id`, `password`: `password`, `role`: `'umkm'`, plus 1 akun lagi misalnya `Budi Santoso` / `budisantoso@inamikro.id` untuk kemudahan uji multi-tenant). Panggil di `DatabaseSeeder.php`.
4. Hapus folder sementara `inamikro-web-builder-html/` setelah semua aset di `public/studio/` terverifikasi.
5. Jalankan `php artisan migrate:fresh --seed` pada MySQL `inamikro_webbuilder` dan `vendor\bin\phpunit` untuk seluruh test suite!

- [ ] **Step 4: Run full test suite and Pint**

Run:
```powershell
vendor\bin\pint --dirty --format agent
vendor\bin\phpunit
```
Expected: Seluruh test suite PASS (0 failures, 0 errors), Pint clean.

- [ ] **Step 5: Commit and push to `origin/main`**

```powershell
git add -A
git commit -m "feat(public): add public /{slug} landing page route, CSP headers, and demo seeder"
git push origin main
```
