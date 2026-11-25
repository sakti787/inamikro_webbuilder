# Desain Arsitektur: Panel Pendamping Admin (`inamikro-admin`) pada Laravel 13

**Tanggal:** 2026-11-25  
**Status:** Siap Disetujui  
**Ukuran:** Project (Multi-Role Integration)  

---

## 1. Latar Belakang & Tujuan (Intent & Why)

Setelah fase Studio UMKM (`INAweb Studio`) selesai, tim pendamping INAmikro membutuhkan **Panel Pendamping (`/admin`)** untuk memantau akun UMKM, meninjau situs sebelum tayang (*Antrean tayang*), mengaktifkan/menonaktifkan auto-persetujuan, menulis catatan & meminta revisi, mengatur ketersediaan 8 template, memantau statistik & log aktivitas, serta mengatur nomor WhatsApp pendamping.

Prototipe HTML/CSS/JS di `inamikro-admin/` sudah menetapkan desain visual dan interaksi panel admin yang diinginkan. Tujuan project ini adalah mengintegrasikan `inamikro-admin` ke Laravel 13 beserta penyelarasan alur publikasi dan tombol *"Tanya admin"* pada Studio UMKM yang sudah ada.

---

## 2. Goals, Non-Goals, & Anti-Goals

### Goals
1. **Autentikasi Terpadu & Pemisahan Role (`umkm` vs `admin`):**
   - Menggunakan halaman `/login` yang sama. Akun `role === 'admin'` diarahkan ke `/admin`; akun `role === 'umkm'` diarahkan ke `/studio` (begitu pula redirect dari `/`).
   - Route `/admin` dan `/api/admin/*` dilindungi middleware `role:admin` (akun UMKM mendapat `403`). Route `/studio`, `/api/site*`, dan `/api/media*` dilindungi middleware `role:umkm` (`GET /studio` me-redirect admin ke `/admin`, API mengembalikan `403`).
   - Topbar Panel Admin memiliki tombol avatar interaktif dengan dropdown detail profil admin dan tombol **Keluar** (`POST /logout` + ikon SVG), konsisten dengan Studio UMKM.
2. **Penghapusan Fitur "Tanya admin" di Panel Admin & Integrasi WhatsApp:**
   - Menghapus menu, tampilan, dan dummy data *"Tanya admin"* (`view-pesan`, `#navPesan`) dari Panel Admin (desktop rail maupun mobile nav).
   - Menu **Pengaturan** admin mengatur `admin_name` dan `admin_whatsapp`.
   - Tombol **"Tanya admin"** di Studio UMKM (`/studio`) membaca nomor WhatsApp pendamping tersebut untuk membuka link `https://wa.me/{admin_whatsapp}?text=...` di tab baru (atau menampilkan toast info jika belum diisi admin).
3. **Alur Moderasi Publikasi (*Antrean Tayang* & *Auto Setujui*):**
   - Mendukung 3 status situs: `draft` (**Draf**), `pending` (**Menunggu**), dan `published` (**Tayang**).
   - Hanya situs berstatus `published` yang dapat diakses di `GET /{slug}`.
   - **Moderasi untuk situs belum tayang (`draft` / `pending`):** Jika pengaturan `auto_approve` **OFF** (`false`), klik *Publikasikan* oleh UMKM (setelah 4/4 syarat terpenuhi) mengubah status ke `pending` (*Antrean tayang*), menunggu admin mengklik **Setujui tayang** (`published`) atau **Minta revisi** (kembali `draft` disertai `revision_note` untuk UMKM). Jika `auto_approve` **ON** (`true`), klik *Publikasikan* langsung menyetujui status ke `published` serta mencatat jejaknya di riwayat situs dan log aktivitas admin. Mengubah toggle `auto_approve` menjadi ON di Admin otomatis menyetujui semua situs `pending` yang sudah 4/4 lengkap.
   - **Edit situs `published` (*Opsi 1A*):** Setelah situs disetujui menjadi `published`, moderasi awal dianggap selesai. Baik auto-save (`PUT /api/site` jika 4/4 lengkap) maupun klik *Publikasikan* ulang (`POST /api/site/publish`) mempertahankan `status = 'published'` dan langsung memperbarui `rendered_html` tanpa membuat web toko offline. Admin tetap dapat menurunkannya kembali ke `draft` kapan pun melalui **Minta revisi**.
   - **Edit situs `pending` (*Opsi 2A*):** Selama status masih `pending`, UMKM tetap dapat mengedit konten secara *in-place* di Studio (`PUT /api/site` menyimpan `content` & `rendered_html` tanpa mengeluarkan situs dari antrean). Jika syarat wajibnya kurang dari 4/4 saat diperiksa, di Antrean Admin tampil penanda *"X bagian kosong"* agar admin dapat meminta revisi.
4. **Pengelolaan Template Aktif/Nonaktif:**
   - Admin dapat mengaktifkan/menonaktifkan masing-masing dari 8 template Studio melalui menu **Template**.
   - Galeri Studio UMKM (`/studio`) hanya menampilkan template yang aktif (`is_active = true`), sementara UMKM yang terlanjur memakai template yang dinonaktifkan tetap dapat mengedit/menampilkan situsnya tanpa error.
5. **7 Menu Admin Utuh dengan Data MySQL Asli:**
   - **Beranda** (stat cards, butuh tindakan top 3, aktivitas terakhir top 5), **UMKM** (daftar seluruh akun `umkm`, cari & saring status, progres 0/4–4/4, serta halaman **Detail UMKM** dengan iframe pratinjau + info + catatan internal + tombol setujui/revisi + timeline riwayat), **Antrean tayang**, **Aktivitas** (log dengan paginasi 8/halaman), **Template**, **Statistik** (4 metrik + donut chart per kategori), dan **Pengaturan**.
   - Hilangkan chip *"Data contoh"*, catatan dummy, dan tombol *"Kembalikan data contoh"*, serta hapus folder mentah `inamikro-admin/` setelah migrasi aset selesai.

### Non-Goals
- Pembuatan/penghapusan akun UMKM langsung oleh Admin dari UI Panel Admin (import akun tetap melalui database/seeder).
- Kotak pesan/chat internal di aplikasi antara UMKM dan Admin (diganti sepenuhnya dengan WhatsApp pendamping).
- Upload/pembuatan kode template baru secara dinamis (hanya toggle aktif/nonaktif untuk 8 template yang tersedia).

### Anti-Goals
- Jangan mempertahankan duplikat `inamikro-admin/templates.js` terpisah — pratinjau situs UMKM di Detail Admin harus menggunakan data `rendered_html` tersimpan atau `/studio-assets/templates.js` sehingga foto `/storage/...` ikut tampil dan tidak ada duplikasi template.
- Jangan mengeksekusi script HTML milik UMKM di origin yang sama di dalam DOM admin tanpa atribut `sandbox` pada `<iframe>` pratinjau.

---

## 3. Pendekatan Arsitektur yang Dipertimbangkan (Approaches)

| Pendekatan | Deskripsi | Kelebihan | Keputusan |
|---|---|---|---|
| **A: UI/JS Asli `inamikro-admin` (`public/admin-assets/`) + Laravel `/admin` & API MySQL** | Memindahkan `style.css`, `log.js`, `settings.js`, dan `app.js` ke `public/admin-assets/`, merender kerangka di `resources/views/admin/index.blade.php` dengan bootstrap state JSON awal + `fetch` ke `/api/admin/*`, serta memakai `/studio-assets/templates.js` sebagai fallback preview. | 100% setia pada UI/UX `inamikro-admin`, menjaga helper murni `log.js`/`settings.js`, reuse `templates.js`, implementasi cepat dan minim risiko melenceng. | **Dipilih** |
| **B: Tulis Ulang ke Blade Server-Side / Livewire** | Membangun ulang seluruh 7 menu admin, chart, filter, dan paginasi menjadi komponen Livewire/Blade terpisah. | State murni di PHP. | Ditolak — memakan waktu lebih lama dan berisiko mengubah estetika CSS/JS prototipe. |

---

## 4. Rancangan Database & Skema Baru

### A. Penambahan Kolom pada `sites`
| Kolom Baru | Tipe | Keterangan |
|---|---|---|
| `admin_note` | `text` (Nullable) | Catatan internal pendamping (`Catatan pendamping` di Detail Admin; tidak tampil sebagai alert revisi di UMKM) |
| `revision_note` | `text` (Nullable) | Alasan revisi/penolakan dari Admin yang ditampilkan ke UMKM di Studio; dikosongkan otomatis saat UMKM kembali mengajukan publikasi atau disetujui tayang |

*(Status valid pada `sites.status`: `'draft'`, `'pending'`, `'published'`)*

### B. Tabel Baru `site_histories`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint unsigned` (PK) | Auto increment |
| `site_id` | `foreignId` (Cascade Delete) | Relasi ke `sites.id` |
| `actor_id` | `foreignId` (Nullable, Null on Delete) | Relasi ke `users.id` (UMKM atau Admin pelaku aksi) |
| `title` | `string(120)` | Judul kejadian, misal: `Diminta publikasi`, `Disetujui tayang`, `Diminta revisi`, `Catatan diperbarui` |
| `description` | `text` (Nullable) | Detail atau alasan aksi |
| `created_at` / `updated_at` | `timestamps` | Dipakai untuk tampilan relatif di timeline Detail UMKM |

### C. Tabel Baru `admin_logs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint unsigned` (PK) | Auto increment |
| `actor_id` | `foreignId` (Nullable, Null on Delete) | User pemicu log |
| `title` | `string(150)` | Ringkasan aksi (misal: `Disetujui tayang`, `Template dinonaktifkan`) |
| `description` | `text` (Nullable) | Penjelasan aksi |
| `who` | `string(150)` | Target/subjek (misal nama usaha UMKM, nama template, `Pengaturan antrean`) |
| `kind` | `string(30)` | Jenis ikon/warna: `approve`, `auto`, `revise`, `note`, `template`, `settings`, `general` |
| `created_at` / `updated_at` | `timestamps` | Diurutkan terbaru di atas untuk paginasi 8 entri per halaman |

### D. Tabel Baru `app_settings`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `key` | `string(80)` (Unique/PK) | `auto_approve`, `admin_name`, `admin_whatsapp` |
| `value` | `text` (Nullable) | Nilai string pengaturan |
| `created_at` / `updated_at` | `timestamps` | Timestamp standar |

### E. Tabel Baru `template_settings`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint unsigned` (PK) | Auto increment |
| `template_id` | `string(50)` (Unique) | Salah satu dari 8 ID: `warung`, `butik`, `jasa`, `toko`, `warung-2`, `butik-2`, `jasa-2`, `toko-2` |
| `is_active` | `boolean` (Default `true`) | Menentukan apakah template tampil di galeri UMKM baru |
| `created_at` / `updated_at` | `timestamps` | Timestamp standar |

---

## 5. Rancangan Endpoint, Middleware & Kontrak API

### A. Role Middleware & Auth Redirect
- Middleware `EnsureRole` didaftarkan dengan alias `'role'` di `bootstrap/app.php`.
- `GET /` & `AuthController::login()`:
  - Jika `$user->role === 'admin'`, arahkan ke `route('admin')` (`/admin`).
  - Jika `$user->role === 'umkm'`, arahkan ke `route('studio')` (`/studio`).
- `GET /studio`: middleware `['auth', 'role:umkm']` (admin yang mengakses `/studio` diarahkan ke `route('admin')` atau API mengembalikan `403`).
- `GET /admin`: middleware `['auth', 'role:admin']` (UMKM mendapat `403`).

### B. Endpoint Admin API (`prefix('/api/admin')`, middleware `['auth', 'role:admin']`)
1. `GET /api/admin/bootstrap` → Mengembalikan payload utuh Panel Admin:
   - `umkm`: Daftar seluruh `User::where('role', 'umkm')->with(['site.histories.actor'])` termasuk UMKM yang belum membuat `Site` (`status: 'draft'`, `completeness: 0/4`), lengkap dengan status (`Draf` / `Menunggu` / `Tayang`), `rendered_html`, `admin_note`, `revision_note`, `content`, `history`.
   - `templates`: 8 template beserta status `is_active` dari `template_settings` (default `true` jika row belum ada).
   - `settings`: `{ auto_approve: bool, admin_name: string, admin_whatsapp: string }`.
   - `logs`: Entri terbaru dari `admin_logs` (diurutkan `latest('id')`, batas 200 atau paginasi via `?page=` + endpoint `GET /api/admin/logs?page=` agar sinkron dengan `INAWebLog`).
2. `POST /api/admin/umkm/{user}/approve` → Menyetujui situs milik UMKM tersebut. Memastikan `Site` ada, memiliki `rendered_html` non-kosong, dan memenuhi 4/4 syarat kelengkapan (`SaveSiteRequest::hasCompletePublishContent($site->content)` — jika belum 4/4 lengkap, tolak dengan `422` berbahasa Indonesia dan sarankan memakai *Minta revisi*). Mengubah `status = 'published'`, `published_at = now()`, `revision_note = null`, menambah `SiteHistory` + `AdminLog`, mengembalikan item UMKM terbaru + logs.
3. `POST /api/admin/umkm/{user}/revise` → Validasi `reason` (`required|string|max:1000` dengan pesan Indonesia). Mengubah `status = 'draft'`, mengisi `revision_note = $reason` tanpa menimpa catatan internal `admin_note` *(keputusan desain)*, menambah `SiteHistory` (`Diminta revisi`) + `AdminLog` (`kind = 'revise'`).
4. `PATCH /api/admin/umkm/{user}/note` → Validasi `note` (`nullable|string|max:2000`). Menyimpan `admin_note` pada `Site` (jika user belum punya `Site`, tolak `422` dengan pesan bahwa UMKM belum membuat draf), menambah `SiteHistory` (`Catatan diperbarui`) + `AdminLog` (`kind = 'note'`).
5. `PATCH /api/admin/templates/{templateId}` → Validasi `templateId` salah satu dari 8 template & `is_active` boolean. Update `template_settings`, tambah `AdminLog` (`kind = 'template'`).
6. `PATCH /api/admin/settings/auto-approve` → Validasi `auto_approve` boolean. Update `app_settings`, catat `AdminLog` (`kind = 'settings'`). Jika `true`, jalankan batch approval otomatis untuk semua `Site` berstatus `'pending'` yang memenuhi `SaveSiteRequest::hasCompletePublishContent($site->content)` dan memiliki `rendered_html` non-kosong; kembalikan `approved_count` dan data terbaru.
7. `PATCH /api/admin/settings/contact` → Validasi `admin_name` (`nullable|string|max:100`) & `admin_whatsapp` (boleh kosong, jika diisi wajib nomor Indonesia valid berawalan `08`/`62`, panjang 10–15 digit setelah normalisasi `62...`, konsisten dengan `INAWebSettings.validWa`). Simpan ke `app_settings`, catat `AdminLog` (`kind = 'settings'`).

### C. Pembaruan Studio UMKM (`/studio` & `/api/site*`)
- **`StudioController`:**
  - Kirim `activeTemplates` (daftar ID template yang `is_active === true` di `template_settings`), `adminContact` (`name`, `whatsapp`, `waUrl`), `autoApprove` (`bool`), dan `revision_note` pada `initialSite`.
- **`SaveSiteRequest`:**
  - Saat user memilih `template_id` yang **berbeda** dari template situs yang sudah ada (atau saat membuat situs baru), tolak dengan `422` jika template tersebut dinonaktifkan Admin (`is_active === false`). Jika user mengedit situsnya yang *sudah* terlanjur memakai template tersebut, tetap izinkan agar tidak memutus pengeditan.
- **`SiteApiController::publish`:**
  - Jika `$existingSite?->status === 'published'` (*Opsi 1A*), pertahankan `status = 'published'`, perbarui `rendered_html`, dan bersihkan `revision_note = null`.
  - Jika masih `'draft'` atau `'pending'`, cek `AppSetting::autoApproveEnabled()`:
    - `true` → `status = 'published'`, `published_at = now()`, `revision_note = null`, catat `SiteHistory` (`Disetujui otomatis`) & `AdminLog` (`kind = 'auto'`).
    - `false` → `status = 'pending'`, `revision_note = null`, catat `SiteHistory` (`Diminta publikasi`) & `AdminLog` (`kind = 'general'`).
- **Frontend `public/studio-assets/app.js` & `resources/views/studio/index.blade.php`:**
  - Filter `renderGallery()` berdasarkan `CFG.activeTemplates`.
  - Di **Situs saya** & **Editor**: tampilkan badge status `Terbit` (`published`), `Menunggu` (`pending` — *"Menunggu tinjauan admin"*), atau `Draf` (`draft`); serta tampilkan banner catatan revisi bila `revision_note` terisi.
  - Tombol **"Tanya admin"** (sidebar & Panduan): jika `CFG.adminContact?.waUrl` terisi, buka di tab baru; jika kosong, tampilkan toast *"Nomor WhatsApp pendamping belum diatur."*

---

## 6. Keamanan & Pembersihan
- **Preview Iframe Admin:** `<iframe id="detailPreview" sandbox="allow-scripts">` pada `admin/index.blade.php` memastikan HTML pratinjau UMKM tetap bisa menjalankan `CLIENT_JS` / styling tetapi terisolasi (tanpa `allow-same-origin`) dari sesi admin!
- **Sanitasi Frontend Admin:** Seluruh rendering string dari database di `public/admin-assets/app.js` wajib melewati helper escaping `h(...)`.
- **Pembersihan:** Hapus folder `inamikro-admin/` setelah seluruh aset berhasil dipindahkan ke `public/admin-assets/` dan `resources/views/admin/index.blade.php` terverifikasi.
- **Seeder:** Perbarui `DemoUmkmSeeder` untuk menyertakan akun Admin (`admin@inamikro.id` / `password`, `role: 'admin'`), `TemplateSetting` 8 template, `AppSetting` awal, serta contoh situs/log agar Panel Admin langsung siap diuji setelah `php artisan migrate:fresh --seed`.
