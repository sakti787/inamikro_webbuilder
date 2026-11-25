# Panel Pendamping Admin (`inamikro-admin`) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengintegrasikan Panel Pendamping Admin (`inamikro-admin`) ke dalam Laravel 13 lengkap dengan kontrol role (`admin` vs `umkm`), antrean persetujuan tayang (*auto-approve* / manual / revisi), pengelolaan 8 template, log aktivitas, statistik kategori, pengaturan WhatsApp pendamping yang terhubung ke Studio UMKM, dan pembersihan fitur pesan *Tanya admin* di Admin.

**Architecture:** Menggunakan **Pendekatan A**: aset UI/JS dari `inamikro-admin/` dipindahkan ke `public/admin-assets/` (tanpa menu *Tanya admin*, chip/tombol *Data contoh*, dan tanpa duplikasi `templates.js` karena memakai `/studio-assets/templates.js`), dirender lewat `resources/views/admin/index.blade.php`, dan dihubungkan ke `/api/admin/*` + tabel MySQL (`sites` notes, `site_histories`, `admin_logs`, `app_settings`, `template_settings`). Studio UMKM (`/studio`) menerima status `pending` / `revision_note`, daftar template aktif, serta URL WhatsApp admin melalui `window.INAWEB_CONFIG`.

**Tech Stack:** Laravel 13 / PHP 8.5, MySQL (XAMPP), PHPUnit 12, Laravel Pint, Vanilla JS (`public/admin-assets/*.js`, `public/studio-assets/*.js`), Plain CSS.

**Spec:** `docs/superpowers/specs/2026-11-25-inamikro-admin-panel-design.md`

## Global Constraints

- **Conventional Commits:** Wajib format `<type>(<scope>): <deskripsi singkat>` di setiap commit (`GEMINI.md`).
- **Laravel Pint:** Wajib jalankan `vendor/bin/pint --dirty --format agent` sebelum setiap commit PHP.
- **Bahasa UI:** Seluruh label, pesan error validasi, status badge, toast, dan riwayat wajib Bahasa Indonesia.
- **Role Security:** `/admin` & `/api/admin/*` wajib middleware `auth` + `role:admin` (`403` untuk `umkm`); `/studio`, `/api/site*`, `/api/media*` wajib `auth` + `role:umkm` (`GET /studio` me-redirect admin ke `/admin`, API mengembalikan `403`).
- **Integritas UX & Pembersihan:** Menu/view *Tanya admin* (`view-pesan`, `#navPesan`) dan banner/tombol *Data contoh* dihapus total dari Panel Admin; folder mentah `inamikro-admin/` dihapus pada Task 6 setelah verifikasi selesai.
- **Keamanan Preview:** `<iframe id="detailPreview">` di Panel Admin wajib memakai `sandbox="allow-scripts"` (tanpa `allow-same-origin`).
- **Aturan Bisnis Publikasi:** Opsi 1A (`published` site tetap live saat edit/re-publish; moderasi berlaku untuk `draft`/`pending`) dan Opsi 2A (`pending` site dapat diedit *in-place*; `approve` manual mensyaratkan `rendered_html` non-kosong + 4/4 kelengkapan); `revise` mengisi `revision_note` tanpa menimpa `admin_note`.

## Review Focus

1. **Privilege escalation & role isolation:** Pengguna `umkm` memanggil endpoint `/api/admin/*` atau `/admin` harus menerima `403`, dan pengguna `admin` memanggil `/api/site*` / `/api/media*` harus menerima `403` (diuji di `RoleAccessControlTest` & `AdminApiTest`).
2. **Situs `pending` & `draft` tidak bocor ke publik:** `GET /{slug}` harus tetap mengembalikan `404` untuk situs berstatus `'pending'` sampai Admin menekan **Setujui tayang** atau mengaktifkan `auto_approve` (diuji di `AdminApiTest` & `PublicSiteTest`).
3. **Moderasi 1A & 2A + isolasi `admin_note` vs `revision_note`:** Re-publish situs `published` tidak membuatnya offline; `revise` tidak menimpa `admin_note`; `approve` manual menolak situs `< 4/4` dengan `422` (diuji di `AdminApiTest` & `SiteApiTest`).
4. **Template nonaktif tidak merusak situs UMKM lama:** UMKM yang sudah memakai template `toko-2` tetap dapat mengedit & menyimpan draf meskipun `toko-2` dinonaktifkan Admin, sedangkan UMKM yang mencoba memilih template nonaktif baru ditolak `422` (diuji di `SiteApiTest` & `StudioViewTest`).
5. **Iframe XSS sandbox & escaping di Panel Admin:** `admin/index.blade.php` wajib menggunakan `sandbox="allow-scripts"` tanpa `allow-same-origin` pada `#detailPreview`, dan `public/admin-assets/app.js` me-escape seluruh input UMKM dengan `h(...)` (diuji di `AdminViewTest`).

---

### Task 1: Admin Database Schema & Eloquent Models

**Files:**
- Create: `database/migrations/2026_11_25_000003_add_review_notes_to_sites_table.php`
- Create: `database/migrations/2026_11_25_000004_create_admin_panel_tables.php`
- Modify: `app/Models/Site.php`
- Create: `app/Models/SiteHistory.php`
- Create: `app/Models/AdminLog.php`
- Create: `app/Models/AppSetting.php`
- Create: `app/Models/TemplateSetting.php`
- Create: `tests/Feature/AdminSchemaTest.php`

**Interfaces:**
- Consumes: `sites` & `users` tables
- Produces:
  - `Site` attributes `admin_note`, `revision_note`, relationship `histories(): HasMany`
  - `SiteHistory` (`site_id`, `actor_id`, `title`, `description`)
  - `AdminLog` (`actor_id`, `title`, `description`, `who`, `kind`)
  - `AppSetting::getValue(string $key, ?string $default = null): ?string`, `setValue(string $key, ?string $value): void`, `autoApproveEnabled(): bool`, `adminContact(): array{name: string, whatsapp: string, waUrl: ?string}`
  - `TemplateSetting::activeTemplateIds(): array<int, string>`, `allWithDefaults(): array<int, array{template_id: string, is_active: bool}>`

- [ ] **Step 1: Write failing tests in `tests/Feature/AdminSchemaTest.php`**

Uji:
- `test_sites_table_stores_admin_and_revision_notes_and_relates_to_site_histories`
- `test_admin_logs_app_settings_and_template_settings_persist_correctly`
- `test_template_setting_defaults_all_eight_templates_active_when_table_is_empty`
- `test_app_setting_admin_contact_builds_whatsapp_url_and_handles_empty_whatsapp`

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Feature/AdminSchemaTest.php`
Expected: FAIL

- [ ] **Step 3: Create migrations and models**

1. Buat migration `add_review_notes_to_sites_table` menambahkan `admin_note` (`text->nullable()->after('rendered_html')`) dan `revision_note` (`text->nullable()->after('admin_note')`).
2. Buat migration `create_admin_panel_tables` membuat:
   - `site_histories` (`id`, `site_id` cascade delete, `actor_id` nullable nullOnDelete, `title(120)`, `description` text nullable, `timestamps`)
   - `admin_logs` (`id`, `actor_id` nullable nullOnDelete, `title(150)`, `description` text nullable, `who(150)`, `kind(30)`, `timestamps`)
   - `app_settings` (`id`, `key(80)->unique()`, `value` text nullable, `timestamps`)
   - `template_settings` (`id`, `template_id(50)->unique()`, `is_active` boolean default `true`, `timestamps`)
3. Update `Site.php` (`admin_note`, `revision_note` di `#[Fillable]`, relasi `histories(): HasMany`).
4. Buat model `SiteHistory`, `AdminLog`, `AppSetting`, dan `TemplateSetting` (memakai `SaveSiteRequest::ALLOWED_TEMPLATES` sebagai daftar 8 template resmi agar konsisten).
   Catatan pada `AppSetting::adminContact()`: jika `admin_whatsapp` tidak kosong (sudah berawalan `62`), kembalikan `waUrl` = `'https://wa.me/'.$wa.'?text='.rawurlencode('Halo kak, butuh bantuan INAmikro.')`, jika kosong `waUrl` = `null`.

- [ ] **Step 4: Run tests to verify GREEN**

Run: `vendor\bin\phpunit tests/Feature/AdminSchemaTest.php`
Expected: PASS

- [ ] **Step 5: Format and commit**

```powershell
vendor\bin\pint --dirty --format agent
git add database/migrations/ app/Models/ tests/Feature/AdminSchemaTest.php
git commit -m "feat(database): add admin panel tables, review notes, and settings models"
```

---

### Task 2: Role Middleware (`EnsureRole`), Auth Role Routing & Access Control (TDD)

**Files:**
- Create: `app/Http/Middleware/EnsureRole.php`
- Modify: `bootstrap/app.php`
- Modify: `app/Http/Controllers/Auth/AuthController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/RoleAccessControlTest.php`

**Interfaces:**
- Consumes: `User::$role` (`'umkm'` | `'admin'`)
- Produces: Middleware alias `'role'`, role-based redirect on `/` and `/login`, strict `403` isolation on `/admin`, `/api/admin/*`, `/api/site*`, `/api/media*`.

- [ ] **Step 1: Write failing tests in `tests/Feature/RoleAccessControlTest.php`**

Uji:
- `test_admin_login_redirects_to_admin_panel_and_umkm_login_redirects_to_studio`: pastikan admin tidak pernah nyasar ke `/studio` meski previously intended `/studio`!
- `test_root_redirects_based_on_user_role`: guest → `/login`, `umkm` → `/studio`, `admin` → `/admin`.
- `test_umkm_cannot_access_admin_panel_or_admin_apis`: returns `403`.
- `test_admin_cannot_access_umkm_apis_and_studio_redirects_admin_to_admin_panel`: `/api/site*` & `/api/media*` returns `403` untuk admin; `GET /studio` me-redirect admin ke `/admin`.

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Feature/RoleAccessControlTest.php`
Expected: FAIL

- [ ] **Step 3: Implement `EnsureRole`, register middleware, update `AuthController` & `routes/web.php`**

1. Buat `EnsureRole::handle(Request $request, Closure $next, string $role): Response`:
   - Jika `! $user` → biarkan `auth` middleware menangani, atau redirect `/login` / `401`.
   - Jika `$user->role !== $role`:
     - Jika `$request->expectsJson() || $request->is('api/*')` → `abort(403, 'Akses ditolak.')`.
     - Jika `$role === 'umkm' && $user->role === 'admin'` → `return redirect()->route('admin')`.
     - Selain itu → `abort(403, 'Akses ditolak.')`.
2. Daftarkan alias `'role' => EnsureRole::class` di `bootstrap/app.php`.
3. Update `AuthController::login()` & `routes/web.php`:
   - `AuthController::login()`: jika `$user->role === 'admin'`, arahkan ke `redirect()->route('admin')` (atau validasi `intended` hanya bila bukan `/studio` untuk admin); jika `'umkm'`, `redirect()->intended(route('studio'))`.
   - Route `/` mengarahkan `admin` → `route('admin')`, `umkm` → `route('studio')`, guest → `route('login')`.
   - Bungkus `/studio` dan `/api/site*`, `/api/media*` dengan `'role:umkm'`.
   - Tambahkan route sementara `GET /admin` dengan middleware `['auth', 'role:admin']` (`->name('admin')`) sebelum fallback `/{slug}` agar test lulus sebelum Task 5.

- [ ] **Step 4: Run tests to verify GREEN**

Run: `vendor\bin\phpunit tests/Feature/RoleAccessControlTest.php tests/Feature/AuthTest.php`
Expected: PASS

- [ ] **Step 5: Format and commit**

```powershell
vendor\bin\pint --dirty --format agent
git add app/Http/Middleware/EnsureRole.php bootstrap/app.php app/Http/Controllers/Auth/AuthController.php routes/web.php tests/Feature/RoleAccessControlTest.php
git commit -m "feat(auth): add role middleware and admin/umkm login routing"
```

---

### Task 3: Admin Backend JSON APIs (`/api/admin/*`) (TDD)

**Files:**
- Create: `app/Http/Controllers/Api/AdminApiController.php`
- Create: `app/Http/Requests/Api/ReviseSiteRequest.php`
- Create: `app/Http/Requests/Api/SaveAdminNoteRequest.php`
- Create: `app/Http/Requests/Api/ToggleTemplateRequest.php`
- Create: `app/Http/Requests/Api/ToggleAutoApproveRequest.php`
- Create: `app/Http/Requests/Api/SaveAdminContactRequest.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/AdminApiTest.php`

**Interfaces:**
- Consumes: `User`, `Site`, `SiteHistory`, `AdminLog`, `AppSetting`, `TemplateSetting`, `SaveSiteRequest::hasCompletePublishContent()`
- Produces: JSON API endpoints di bawah `Route::prefix('api/admin')->middleware(['auth', 'role:admin'])`:
  - `GET /api/admin/bootstrap` (`api.admin.bootstrap`)
  - `GET /api/admin/logs` (`api.admin.logs`)
  - `POST /api/admin/umkm/{user}/approve` (`api.admin.umkm.approve`)
  - `POST /api/admin/umkm/{user}/revise` (`api.admin.umkm.revise`)
  - `PATCH /api/admin/umkm/{user}/note` (`api.admin.umkm.note`)
  - `PATCH /api/admin/templates/{templateId}` (`api.admin.templates.toggle`)
  - `PATCH /api/admin/settings/auto-approve` (`api.admin.settings.auto-approve`)
  - `PATCH /api/admin/settings/contact` (`api.admin.settings.contact`)

- [ ] **Step 1: Write failing tests in `tests/Feature/AdminApiTest.php`**

Uji seluruh perilaku utama:
- `test_bootstrap_returns_all_umkm_including_users_without_sites_plus_templates_settings_and_logs`
- `test_admin_can_approve_complete_pending_site_and_make_it_live`: mengubah status ke `'published'`, mengisi `published_at`, mengosongkan `revision_note`, menambah `SiteHistory` + `AdminLog`, lalu `GET /{slug}` mengembalikan `200`.
- `test_admin_approve_rejects_incomplete_or_missing_html_site_with_indonesian_error`: `< 4/4` atau `rendered_html` kosong → `422`.
- `test_admin_revise_returns_site_to_draft_sets_revision_note_and_preserves_admin_note`: pastikan `admin_note` lama TIDAK tertimpa oleh `$reason`!
- `test_admin_save_note_updates_internal_admin_note_without_touching_revision_note`
- `test_admin_can_toggle_template_status_and_logs_activity`
- `test_enabling_auto_approve_automatically_publishes_complete_pending_sites_only`: situs `'pending'` 4/4 langsung `'published'`, situs `'pending'` 3/4 tetap `'pending'`, situs `'draft'` 4/4 tetap `'draft'`.
- `test_admin_contact_normalizes_indonesian_whatsapp_and_rejects_invalid_numbers`: `"0812-3456-7890"` → `"6281234567890"`, kosong `""` diizinkan, `"123"` gagal `422` Bahasa Indonesia.
- `test_admin_logs_endpoint_paginates_eight_items_per_page`

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Feature/AdminApiTest.php`
Expected: FAIL

- [ ] **Step 3: Implement FormRequests, `AdminApiController`, and register `/api/admin/*` routes**

1. Buat 5 FormRequest di `app/Http/Requests/Api/` dengan pesan validasi Bahasa Indonesia. Pada `SaveAdminContactRequest`, normalisasi nomor WA dengan menghapus non-digit dan mengubah awalan `0` menjadi `62` sebelum mengecek panjang `10..15` digit berawalan `62` (jika non-kosong).
2. Buat `AdminApiController`:
   - Gunakan helper bersih untuk format payload item UMKM (menampilkan user dengan `role === 'umkm'`, meskipun `$user->site === null` tampil sebagai `Draf`, `0/4`, `history: []`), helper logging `AdminLog::create(...)`, dan helper `DB::transaction(...)` saat mutasi `Site` + `SiteHistory` + `AdminLog` / batch auto-approve.
   - Pastikan target `{user}` pada `approve`/`revise`/`saveNote` harus ber-role `'umkm'` (`abort(404)` bila bukan `'umkm'`).
3. Daftarkan 8 route di `routes/web.php` di bawah `Route::prefix('api/admin')->middleware(['auth', 'role:admin'])`.

- [ ] **Step 4: Run tests to verify GREEN**

Run: `vendor\bin\phpunit tests/Feature/AdminApiTest.php`
Expected: PASS

- [ ] **Step 5: Format and commit**

```powershell
vendor\bin\pint --dirty --format agent
git add app/Http/Controllers/Api/AdminApiController.php app/Http/Requests/Api/ routes/web.php tests/Feature/AdminApiTest.php
git commit -m "feat(admin-api): implement UMKM moderation, template toggles, logs, and settings APIs"
```

---

### Task 4: Studio UMKM Moderation Workflow, Template Filter & WhatsApp Integration (TDD)

**Files:**
- Modify: `app/Http/Requests/Api/SaveSiteRequest.php`
- Modify: `app/Http/Controllers/Api/SiteApiController.php`
- Modify: `app/Http/Controllers/StudioController.php`
- Modify: `resources/views/studio/index.blade.php`
- Modify: `public/studio-assets/app.js`
- Modify: `public/studio-assets/style.css`
- Modify: `tests/Feature/SiteApiTest.php`, `tests/Feature/StudioViewTest.php`

**Interfaces:**
- Consumes: `AppSetting`, `TemplateSetting`, `SiteHistory`, `AdminLog`
- Produces: Moderated publish (`pending` vs `published` via `auto_approve` dengan Opsi 1A & 2A), `revision_note` notice, filtered `activeTemplates` di Studio, dan tombol *Tanya admin* membuka WhatsApp pendamping.

- [ ] **Step 1: Write/update failing tests in `SiteApiTest.php` and `StudioViewTest.php`**

Tambahkan pengujian di `SiteApiTest.php`:
- `test_publish_creates_pending_site_when_auto_approve_is_disabled_and_stays_404_publicly`
- `test_publish_immediately_publishes_when_auto_approve_is_enabled`
- `test_republishing_already_published_site_keeps_it_published_even_when_auto_approve_is_disabled` (Opsi 1A)
- `test_editing_pending_site_updates_content_in_place_and_keeps_status_pending` (Opsi 2A)
- `test_disabled_template_blocks_new_selection_but_allows_existing_site_edits`

Tambahkan/perbarui pengujian di `StudioViewTest.php`:
- `test_studio_injects_active_templates_admin_contact_and_revision_note`

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Feature/SiteApiTest.php tests/Feature/StudioViewTest.php`
Expected: FAIL

- [ ] **Step 3: Implement backend moderation & Studio frontend updates**

1. **`SaveSiteRequest.php`:** Pada `baseRules()`, validasi `template_id` harus aktif di `TemplateSetting` apabila user belum punya `Site` atau mengubah `template_id` dari `$existingSite->template_id` (`'Template yang dipilih sedang dinonaktifkan oleh admin.'`).
2. **`SiteApiController.php`:**
   - `publish()`: ikuti Opsi 1A & `AppSetting::autoApproveEnabled()` + catat `SiteHistory` dan `AdminLog` di dalam `DB::transaction()`.
   - `upsert()`: pastikan `$existingSite->status === 'pending'` tetap menyimpan `content` & `rendered_html` (sanitized) dengan `status` tetap `'pending'` (Opsi 2A).
3. **`StudioController.php`:** Tambahkan `activeTemplates`, `adminContact`, `autoApprove`, dan `revision_note` pada `initialSite`.
4. **`public/studio-assets/app.js` & `style.css`:**
   - Filter `renderGallery()` memakai `CFG.activeTemplates` (jika array tersedia).
   - Di `renderMySites()` dan banner editor: tampilkan status `Terbit` (`published`), `Menunggu` (`pending` — *"Menunggu tinjauan admin"*), atau `Draf` (`draft`), serta tampilkan banner catatan revisi jika `site.revision_note` terisi.
   - Di `#btnPublish` / `Situs saya` publish: tampilkan toast berbeda untuk `pending` (*"Pengajuan publikasi terkirim. Menunggu persetujuan admin."*) vs `published`.
   - Di tombol **"Tanya admin"** (sidebar & `#guideAsk`): jika `CFG.adminContact?.waUrl` terisi, `window.open(CFG.adminContact.waUrl, "_blank", "noopener")`; jika kosong, `toast("Nomor WhatsApp pendamping belum diatur.")`.
   - *Penting:* Pastikan pesan toast `"Fitur segera hadir."` tetap berlaku untuk tombol **"Hubungkan situs"** (karena custom domain bukan bagian dari fase ini).

- [ ] **Step 4: Run tests to verify GREEN**

Run: `node --check public/studio-assets/app.js; vendor\bin\pint --dirty --format agent; vendor\bin\phpunit`
Expected: ALL PASS

- [ ] **Step 5: Commit**

```powershell
git add app/Http/Requests/Api/SaveSiteRequest.php app/Http/Controllers/Api/SiteApiController.php app/Http/Controllers/StudioController.php resources/views/studio/index.blade.php public/studio-assets/ tests/Feature/SiteApiTest.php tests/Feature/StudioViewTest.php
git commit -m "feat(studio): integrate publish queue moderation, active template filter, and WhatsApp support link"
```

---

### Task 5: Panel Pendamping Admin Frontend Integration (`/admin` & `public/admin-assets/*`) (TDD)

**Files:**
- Create: `app/Http/Controllers/AdminController.php`
- Create: `resources/views/admin/index.blade.php`
- Create: `public/admin-assets/style.css`
- Create: `public/admin-assets/log.js`
- Create: `public/admin-assets/settings.js`
- Create: `public/admin-assets/app.js`
- Modify: `routes/web.php`
- Create: `tests/Feature/AdminViewTest.php`

**Interfaces:**
- Consumes: `/api/admin/*`, `/studio-assets/templates.js`, `AdminApiController` payload structure
- Produces: Full Admin Panel UI at `GET /admin` matching `inamikro-admin` (without `Tanya admin` inbox or dummy reset) with avatar profile popover + logout, sandboxed preview iframe, and live API mutations.

- [ ] **Step 1: Write failing tests in `tests/Feature/AdminViewTest.php`**

Uji:
- `test_admin_page_renders_panel_without_tanya_admin_inbox_or_dummy_reset` (pastikan tidak ada `view-pesan`, `navPesan`, `Data contoh`, `Kembalikan data contoh`)
- `test_admin_page_injects_bootstrap_config_and_avatar_logout_popover` (cek `#adminAvatarBtn`, `#adminProfilePop`, `POST /logout`, `@csrf`, `Keluar`, `window.INAWEB_ADMIN_CONFIG`)
- `test_admin_detail_preview_iframe_uses_strict_sandbox_attribute` (pastikan `<iframe id="detailPreview"` memiliki `sandbox="allow-scripts"` dan tidak memiliki `allow-same-origin`)
- `test_admin_static_assets_exist_and_reuse_studio_templates_js` (cek `public/admin-assets/{style.css,log.js,settings.js,app.js}`, tidak ada duplikat `public/admin-assets/templates.js`, dan `resources/views/admin/index.blade.php` memuat `studio-assets/templates.js`)

- [ ] **Step 2: Run tests to verify RED**

Run: `vendor\bin\phpunit tests/Feature/AdminViewTest.php`
Expected: FAIL

- [ ] **Step 3: Implement `AdminController`, `admin/index.blade.php`, and `public/admin-assets/*`**

1. Salin `inamikro-admin/log.js` dan `inamikro-admin/settings.js` ke `public/admin-assets/` (pertahankan helper murni `INAWebLog` & `INAWebSettings`).
2. Salin `inamikro-admin/style.css` ke `public/admin-assets/style.css` + hapus styling khusus `mail-grid`/`msg-row`/`demo-chip` yang tak terpakai & tambahkan styling `.profile-wrap` / `.profile-pop` / `.profile-logout` untuk avatar dropdown Admin serta status disabled tombol saat request berjalan.
3. Buat `AdminController::__invoke()` yang menyiapkan `$initialBootstrap` (sama seperti `AdminApiController::bootstrap` payload) dan `$adminConfig` (`userName`, `userEmail`, `csrfToken`, `routes`, `initialData`), lalu merender `resources/views/admin/index.blade.php`.
4. Buat `resources/views/admin/index.blade.php` berdasarkan `inamikro-admin/index.html` dengan:
   - Menghapus menu `Tanya admin` (`view-pesan`, `#navPesan`) dari sidebar & mobile nav.
   - Menghapus badge `Data contoh`, banner catatan prototipe, dan tombol `#btnReset`.
   - Mengganti `.avatar` menjadi tombol `#adminAvatarBtn` + dropdown `#adminProfilePop` berisi detail admin (`name`, `email`, role badge `ADMIN`) & tombol **Keluar** + ikon logout (`POST /logout` + `@csrf`).
   - Memasang `sandbox="allow-scripts"` pada `<iframe id="detailPreview">`.
   - Mengubah catatan pengaturan kontak menjadi *"Nomor WhatsApp di atas langsung dipakai oleh tombol Tanya admin di Studio UMKM."*
   - Memuat `studio-assets/templates.js`, `admin-assets/log.js`, `admin-assets/settings.js`, `admin-assets/app.js`.
5. Salin & adaptasi `inamikro-admin/app.js` ke `public/admin-assets/app.js`:
   - Hilangkan `localStorage`, `seed()`, `messages`, `replyBtn`, `btnReset`.
   - Gunakan `window.INAWEB_ADMIN_CONFIG.initialData` dan `apiFetch` ke `/api/admin/*` untuk `approve`, `revise`, `saveNote`, `toggleTemplate`, `toggleAutoApprove`, `saveContact`, dan log pagination (`/api/admin/logs?page=...`).
   - Pada `renderDetail()`, gunakan `u.rendered_html || TEMPLATE_BY_ID(u.state.template).render(u.state)` untuk `#detailPreview.srcdoc`.
   - Tangani UMKM yang belum membuat `Site` (`!u.has_site`) secara elegan di Detail UMKM (nonaktifkan tombol Setujui/Catatan dengan info *"UMKM ini belum membuat draf situs di Studio."*).
   - Tambahkan handler `#adminAvatarBtn` / `#adminProfilePop` click & `Escape`.
6. Update `routes/web.php` `GET /admin` ke `AdminController::class`.

- [ ] **Step 4: Run tests and JS checks to verify GREEN**

Run: `node --check public/admin-assets/log.js; node --check public/admin-assets/settings.js; node --check public/admin-assets/app.js; vendor\bin\pint --dirty --format agent; vendor\bin\phpunit tests/Feature/AdminViewTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```powershell
git add app/Http/Controllers/AdminController.php resources/views/admin/ public/admin-assets/ routes/web.php tests/Feature/AdminViewTest.php
git commit -m "feat(admin): integrate Panel Pendamping UI and admin API client"
```

---

### Task 6: Demo Seeder Update, MySQL `migrate:fresh --seed`, Cleanup & Full Regression

**Files:**
- Modify: `database/seeders/DemoUmkmSeeder.php`
- Delete: `inamikro-admin/` (hapus setelah verifikasi aset `public/admin-assets/` lengkap)
- Modify: `tests/Feature/PublicSiteTest.php` (tambahkan assertion untuk akun admin demo, `TemplateSetting`, `AppSetting`)

**Interfaces:**
- Consumes: All migrations & seeders
- Produces: Clean repository tanpa `inamikro-admin/`, database MySQL `inamikro_webbuilder` siap dipakai untuk kedua role (`admin@inamikro.id` & `sari@inamikro.id` / `budi@inamikro.id`), seluruh test suite hijau.

- [ ] **Step 1: Update `DemoUmkmSeeder` and `PublicSiteTest` assertions**

Perbarui `DemoUmkmSeeder.php` (idempoten via `updateOrCreate`):
- Seed akun Admin: `Admin Pendamping` (`email: admin@inamikro.id`, `password: password`, `role: 'admin'`, `business_name: 'Tim INAmikro'`, `slug: 'tim-inamikro'`).
- Seed `TemplateSetting`: 8 template aktif (`is_active: true`).
- Seed `AppSetting`: `auto_approve = '0'`, `admin_name = 'Tim INAmikro'`, `admin_whatsapp = '6281234567890'`.
- Seed akun UMKM (`sari@inamikro.id` dengan contoh situs `'published'` *Dapur Bu Sari*, `budi@inamikro.id` dengan contoh situs `'pending'` *Sigap Teknik* agar antrean Admin langsung terisi contoh realistis) + `SiteHistory` & `AdminLog` awal (gunakan `SiteHtmlSanitizer::sanitize(...)` saat membuat `rendered_html` contoh!).

- [ ] **Step 2: Remove `inamikro-admin/` and run `php artisan migrate:fresh --seed` on MySQL**

Verifikasi `public/admin-assets/{style.css,log.js,settings.js,app.js}` sudah ter-commit, lalu hapus `inamikro-admin/`.
Jalankan `php artisan migrate:fresh --seed --no-interaction`.

- [ ] **Step 3: Run Pint and full test suite**

Run:
```powershell
vendor\bin\pint --dirty --format agent
vendor\bin\phpunit
```
Expected: ALL PASS (0 failures, 0 errors).

- [ ] **Step 4: Commit and push (`main` after final review)**

```powershell
git add -A
git commit -m "feat(seeder): add admin demo account, default settings, and clean up raw inamikro-admin"
```
*(Push ke `origin/main` dilakukan setelah final whole-branch review selesai bersih).*
