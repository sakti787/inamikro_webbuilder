# Desain Arsitektur: INAweb Studio pada Laravel 13 (Role UMKM)

**Tanggal:** 2026-11-25  
**Status:** Siap Disetujui  
**Ukuran:** Project (Full-Stack Rebuild)  

---

## 1. Latar Belakang & Tujuan (Intent & Why)

Pengguna UMKM di ekosistem INAmikro membutuhkan cara termudah untuk memiliki landing page toko/usaha online tanpa perlu memahami coding atau pengaturan desain yang rumit. Prototipe HTML/CSS/JS di `inamikro-web-builder-html` (**INAweb Studio**) sudah menetapkan pengalaman pengguna yang sangat ringkas dan sesuai kebutuhan:

- Alur 4 langkah (*Pilih template → Isi konten → Warna & gaya → Publikasikan*)
- 8 template siap pakai di 4 kategori (*Kuliner, Fashion, Jasa, Retail*, masing-masing 2 varian)
- Pratinjau langsung Desktop/Ponsel, checklist 4 syarat wajib, Urungkan & Riwayat 10 versi, serta aturan **1 akun = 1 landing page**.

Tujuan project ini adalah **mengimplementasikan INAweb Studio ke dalam aplikasi Laravel 13 secara penuh** tanpa mengubah fitur maupun tampilan antarmuka yang sudah ada di prototipe, sekaligus mengganti penyimpanan sementara (`localStorage`) menjadi autentikasi nyata, database MySQL (`inamikro_webbuilder`), penyimpanan gambar di server, dan halaman publik langsung di `/{slug}` dengan **slug permanen berbasis data usaha akun UMKM**.

---

## 2. Goals, Non-Goals, & Anti-Goals

### Goals
1. **Autentikasi & Identitas Usaha UMKM (`/login`, `/register`, `/logout`):**
   - Pengunjung yang belum login saat mengakses `/` langsung diarahkan ke `/login`.
   - Halaman `/register` mengumpulkan Nama Pemilik (`name`), **Nama Usaha (`business_name`)**, Email, dan Password. Saat akun dibuat (maupun nanti saat bulk-import dari database INAmikro), sistem menyimpan `business_name` dan men-generate `slug` permanen di tabel `users`.
   - Setelah login atau mendaftar akun UMKM baru, pengguna langsung masuk ke Studio (`/studio`).
   - Halaman Login dan Register memakai sistem visual yang seragam dengan `style.css` (`Plus Jakarta Sans`, aksen merah `#dc2626`, latar `#fff8f7`, bahasa Indonesia).
   - Topbar studio menampilkan identitas akun yang sedang login dan tombol **Keluar**.
2. **Studio Editor & Fitur Utuh (100% Sesuai Prototipe):**
   - Mempertahankan penuh 3 menu utama (`Template`, `Situs saya`, `Panduan`), 8 template, filter kategori, checklist 4 syarat, Urungkan & Riwayat versi, color swatches & picker, upload foto utama (opsional) + foto per item produk (tanpa batas jumlah), dan tombol **Unduh HTML**.
   - Ketika membuka editor pertama kali tanpa draf tersimpan, kolom Nama Usaha otomatis terisi dari `business_name` akun (sementara teks contoh tagline, deskripsi, dan produk template tetap disediakan agar UMKM mudah memulai).
   - Tombol **"Tanya admin"** dan **"Hubungkan situs"** menampilkan toast informasi *"Fitur segera hadir."* untuk tahap ini.
3. **Penyimpanan MySQL & Aturan 1 Akun = 1 Halaman:**
   - Seluruh draf dan situs terbit disimpan ke tabel `sites` di database MySQL `inamikro_webbuilder` dengan relasi `1 user : 1 site` (`unique(user_id)`).
   - Auto-save ke backend Laravel secara *debounced* (~600ms) saat pengguna mengedit konten.
4. **Penyimpanan Gambar di Server (`storage/app/public`):**
   - Kompresi foto tetap berjalan di browser sebelum upload, lalu dikirim via API ke Laravel dan disimpan di `storage/app/public/sites/{user_id}/...`.
   - URL foto yang tersimpan dalam JSON `content` adalah path `/storage/sites/{user_id}/...` (bukan string base64 raksasa di database).
5. **Slug Permanen & Routing Publik Langsung (`/{slug}`):**
   - Alamat situs publik selalu menggunakan `slug` permanen milik akun (`http://<host>/{slug}`). Slug **tidak pernah berubah** meskipun pengguna mengedit teks `content.nama` di editor, dan draf yang belum terbit tidak akan pernah merebut slug contoh template.
   - Proteksi daftar *reserved slugs* di `SlugService` agar nama usaha seperti `login`, `register`, `studio`, `api`, `storage`, `up` otomatis diberi suffix agar tidak menabrak route sistem.
   - Validasi 4 syarat wajib di backend saat publikasi (`content.nama`, `content.wa`, `content.jam`, minimal 1 produk bernama).
   - Halaman `GET /{slug}` hanya dapat diakses jika `status === 'published'` (draf atau tidak ditemukan mengembalikan `404`).

### Non-Goals
- **Panel Admin / Pendamping INAmikro:** Ditunda untuk fase berikutnya setelah desain HTML admin tersedia.
- **Analitik Pengunjung / Klik WA:** Tidak ada di prototipe baru, sehingga tidak ditambahkan di fase ini.
- **QR Code Situs & Custom Domain:** Belum diimplementasikan pada fase ini.
- **Verifikasi Email / Reset Password via SMTP:** Menggunakan autentikasi standar Laravel berbasis email & password lokal untuk UMKM.

### Anti-Goals
- **Jangan mengubah UI/UX atau mengurangi/menambah fitur Studio prototipe** di luar integrasi backend yang dibutuhkan (autentikasi, tombol keluar, penyimpanan server, URL slug permanen).
- **Jangan menyimpan string base64 gambar ke dalam kolom database MySQL.**
- **Jangan membocorkan situs berstatus `draft` pada route publik `/{slug}`.**

---

## 3. Pendekatan Arsitektur yang Dipertimbangkan (Approaches)

| Pendekatan | Deskripsi | Kelebihan | Trade-off / Kekurangan | Keputusan |
|---|---|---|---|---|
| **A: Pertahankan Engine Studio Asli (`style.css`, `templates.js`, `app.js`) + Laravel API & MySQL** | Studio dirender via Blade menggunakan aset CSS/JS asli prototipe yang dihubungkan ke JSON API Laravel (`/api/site`, `/api/media`), serta menyajikan `rendered_html` tersimpan di `GET /{slug}`. | 100% setia pada tampilan & rasa prototipe, *live preview* tetap instan 0ms di browser, *Unduh HTML* dan situs live memakai satu sumber renderer yang identik (`templates.js`), minim risiko regresi UX. | `rendered_html` berasal dari klien; dimitigasi melalui **keputusan keamanan teknis** berupa header CSP `script-src 'sha256-...'` + verifikasi/injeksi script `CLIENT_JS` kanonis di server. | **Dipilih** |
| **B: Tulis Ulang ke Livewire + 8 Template Blade PHP** | Mengonversi seluruh editor JS dan ke-8 fungsi template ke komponen Livewire dan file Blade terpisah. | Seluruh HTML dirender di sisi server PHP. | Sangat berisiko mengubah perilaku *live preview*, Undo/Riwayat lokal, dan kompresi foto; butuh duplikasi renderer untuk fitur *Unduh HTML*. | Ditolak |

---

## 4. Rancangan Teknis & Komponen

### A. Konfigurasi Lingkungan & Laravel Boost
- Mengikuti `AGENTS.md`: install `laravel/boost --dev` dan jalankan `php artisan boost:install` sebelum melakukan perubahan aplikasi.
- Konfigurasi `.env` menggunakan MySQL XAMPP:
  - `APP_URL=http://127.0.0.1:8000`
  - `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=inamikro_webbuilder`, `DB_USERNAME=root`, `DB_PASSWORD=`
- Jalankan `php artisan storage:link` agar folder `public/storage` terhubung ke `storage/app/public`.
- Menjalankan `php artisan migrate:fresh --seed` untuk mereset tabel MySQL lama menjadi skema baru yang bersih beserta akun demo UMKM.

### B. Skema Database

#### 1. Tabel `users` (Modifikasi Migration Bawaan / Tambahan)
| Kolom Tambahan | Tipe | Keterangan |
|---|---|---|
| `business_name` | `string(255)` | Nama usaha resmi UMKM (dari form daftar atau import database atasan) |
| `slug` | `string(120)` (Unique) | Slug permanen yang diturunkan dari `business_name` dan tidak berubah |
| `role` | `string(30)` (Default `'umkm'`) | Role akun (`'umkm'`, siap untuk `'admin'` di fase mendatang) |

#### 2. Tabel `sites` (Migration Baru)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigint unsigned` (PK) | Auto increment |
| `user_id` | `foreignId` (Unique, Cascade Delete) | Relasi ke `users.id` — menjamin **1 akun = 1 landing page** |
| `template_id` | `string(50)` | ID template: `warung`, `warung-2`, `butik`, `butik-2`, `jasa`, `jasa-2`, `toko`, `toko-2` |
| `slug` | `string(120)` (Unique) | Disalin permanen dari `users.slug` saat pembuatan record `Site` |
| `status` | `string(20)` (Default `'draft'`) | `'draft'` atau `'published'` |
| `brand_color` | `string(20)` (Default `'#dc2626'`) | Kode hex warna utama brand |
| `content` | `json` | Struktur payload: `nama`, `tagline`, `kategori`, `deskripsi`, `heroImg`, `heroName`, `heroSize`, `wa`, `ig`, `alamat`, `jam`, `variant`, dan array `produk[]` |
| `rendered_html` | `longText` (Nullable) | Output HTML mandiri yang dihasilkan `templates.js` |
| `published_at` | `timestamp` (Nullable) | Waktu terakhir dipublikasikan |
| `created_at` / `updated_at` | `timestamps` | Timestamp standar Laravel |

### C. Aturan Slug Permanen & Reserved Slugs (`SlugService`)
- Slug dibuat **satu kali saat registrasi / pembuatan user** dari `business_name` (lowercase, alfanumerik + `-`, fallback `'usaha'`).
- **Daftar Reserved Slugs** (tidak boleh dipakai sebagai slug usaha):
  `login`, `register`, `logout`, `studio`, `api`, `admin`, `dashboard`, `storage`, `up`, `build`, `assets`, `vendor`, `auth`, `password`, `s`, `favicon.ico`, `robots.txt`.
- Jika slug hasil konversi masuk daftar *reserved* atau sudah dimiliki `user_id` lain di tabel `users` / `sites`, sistem otomatis menambahkan akhiran angka (`-2`, `-3`, dst.) hingga unik.
- `sites.slug` selalu diisi dari `$user->slug` dan **bersifat permanen (immutable)**: perubahan `content.nama` di editor atau saat republish tidak mengubah `sites.slug` maupun `users.slug`.

### D. Routing & Endpoint

#### 1. Web Routes (`routes/web.php`)
- `GET /` → Redirect ke `/studio` jika sudah login, atau `/login` jika guest.
- `GET /login`, `POST /login` (middleware `guest`) → Halaman & proses masuk UMKM.
- `GET /register`, `POST /register` (middleware `guest`) → Halaman & proses pendaftaran akun UMKM (`name`, `business_name`, `email`, `password`; otomatis menghasilkan `users.slug` dan `role = 'umkm'`, lalu login ke `/studio`).
- `POST /logout` (middleware `auth`) → Keluar sesi dan redirect ke `/login`.
- `GET /studio` (middleware `auth`) → Menampilkan aplikasi INAweb Studio (Blade view dengan aset `style.css`, `templates.js`, `app.js` serta konfigurasi awal dari PHP).
- `GET /{slug}` (**harus didaftarkan paling akhir** setelah seluruh route sistem) → Mengambil `Site` dengan `slug` dan `status === 'published'`, jika tidak ditemukan mengembalikan `404`. Mengirimkan `rendered_html` dengan header `Content-Type: text/html; charset=UTF-8` dan header keamanan CSP.

#### 2. Studio JSON API Routes (middleware `auth`, prefix `/api`)
Menggunakan sesi web + CSRF token (`X-CSRF-TOKEN`):
- `GET /api/site` → Mengembalikan data situs milik user saat ini (atau `null` jika belum punya) beserta `slug` permanen akun dan `public_url`.
- `PUT /api/site` → Menyimpan perubahan draf/konten (`template_id`, `brand_color`, `content`, opsional `rendered_html`) untuk `auth()->user()`. Jika user sudah memiliki situs berstatus `published`:
  - *(Keputusan builder/call):* Jika keempat syarat wajib masih terpenuhi, `rendered_html` ikut diperbarui dan status tetap `published`. Jika salah satu syarat wajib sedang kosong saat mengetik (auto-save mid-edit), `content` draf tetap tersimpan agar pekerjaan tidak hilang sementara `rendered_html` lama yang valid tetap dipertahankan hingga user menekan *Publikasikan* lagi (atau status kembali ke `draft`).
- `POST /api/site/publish` → Memvalidasi keras 4 syarat wajib (`content.nama`, `content.wa`, `content.jam`, minimal 1 item di `content.produk` dengan `nama` terisi) + `rendered_html` wajib terisi. Mengubah `status` menjadi `'published'`, mengisi `published_at`, menyimpan `rendered_html`, dan mengembalikan `{ site, public_url }`.
- `DELETE /api/site` → Menghapus record `Site` milik user saat ini beserta folder gambar `storage/app/public/sites/{user_id}/` (akun user & `users.slug` tetap ada sehingga user bisa memilih template ulang kapan saja).
- `POST /api/media` → Validasi file gambar (`image`, `mimes:jpg,jpeg,png,webp`, `max:5120`), simpan ke `sites/{user_id}/`, kembalikan `{ url: '/storage/sites/{user_id}/...' }`.
- `DELETE /api/media` → Menerima `{ url }`, memverifikasi ketat bahwa path berada di dalam `/storage/sites/{user_id}/` (mencegah path traversal `..` dan penghapusan aset milik tenant lain), lalu menghapus file dari disk `public`.

### E. Penyesuaian Prototipe Frontend (`public/studio/` & `templates.js`)
Aset dari `inamikro-web-builder-html` dipindahkan ke direktori `public/studio/` (`style.css`, `templates.js`, `app.js`), dan folder mentah `inamikro-web-builder-html/` dihapus setelah integrasi & verifikasi selesai:
1. **`templates.js`:**
   - Perbarui `imgOr` dan `prodThumb` agar menerima URL lokal `/storage/...` dan `https?://...` selain `data:image/...`.
   - Perbarui `siteUrl(state)` agar menggunakan `window.INAWEB_CONFIG?.publicSiteUrl` (yakni URL permanen `http://<host>/{siteSlug}`), sehingga tombol **Bagikan** dan meta tag `og:url` selalu memakai slug permanen akun.
   - Tambahkan `<meta property="og:image">` di `doc()` apabila `state.heroImg` tersedia (dikonversi ke URL absolut).
   - Pertahankan 100% ke-8 template (`TEMPLATES`) dan blok `CLIENT_JS`.
2. **`app.js`:**
   - Terima konfigurasi dari `window.INAWEB_CONFIG` (`userName`, `businessName`, `siteSlug`, `publicBaseUrl`, `publicSiteUrl`, `initialSite`, `csrfToken`).
   - Saat membuat state baru dari template (`defaultsFor(t)`), prefill `nama` dengan `window.INAWEB_CONFIG.businessName` jika tersedia, namun pertahankan sample tagline, deskripsi, dan produk template.
   - Tampilkan `#previewDomain` dan URL di *Situs saya* menggunakan `siteSlug` permanen dari akun (bukan hasil slugify ketikan `state.nama`).
   - Ganti operasi `localStorage` dengan `fetch` ke `/api/site`, `/api/site/publish`, `/api/site` (`DELETE`).
   - Implementasikan *debounced auto-save* (~600ms) yang memperbarui teks status `#edStatus` (*"Menyimpan..."* → *"Tersimpan · HH:mm"*).
   - Pada upload foto (`#heroUpload` dan `[data-prod-upload]`), pertahankan `compressImage()`, ubah hasil ke Blob lalu kirim ke `POST /api/media`, simpan URL `/storage/...` ke `state`, lalu simpan ke backend.
   - Pada tombol **Publikasikan**, panggil `POST /api/site/publish`, tampilkan toast sukses beserta tautan live.
   - Pada tampilan **Situs saya**, tampilkan status sesungguhnya (`Draf` atau `Terbit`), link URL permanen, dan tombol **Lihat situs** (`target="_blank"`) jika statusnya sudah `Terbit`.
   - Pada tombol **Tanya admin** dan **Hubungkan situs**, tampilkan toast *"Fitur segera hadir."*
3. **Halaman Auth (`/login` & `/register`):**
   - Dibuat dengan Blade view (`resources/views/auth/login.blade.php` & `register.blade.php`) mewarisi CSS tokens yang sama (`Plus Jakarta Sans`, `--brand: #dc2626`, `--page-bg: #fff8f7`, kartu bersih), form sederhana Bahasa Indonesia.

### F. Keamanan, Mitigasi XSS, & Isolasi Multi-Tenant
- **Mitigasi Same-Origin Stored XSS pada `GET /{slug}` *(Keputusan Arsitektur)*:**
  - Karena `rendered_html` disubmit oleh klien saat simpan/publikasi dan disajikan pada origin yang sama dengan `/studio` dan `/api/*`, server wajib memproteksi `GET /{slug}` agar script arbitrer tidak pernah dapat dieksekusi:
    1. Saat menyimpan `rendered_html` di backend (`SiteHtmlSanitizer`), server mengekstrak/memvalidasi HTML atau mengganti blok `<script>` dengan string kanonis `CLIENT_JS` yang identik dari file konfigurasi/konstanta server, serta membersihkan atribut inline event handler (`on*`) atau tag `<script>` tambahan selain `CLIENT_JS`.
    2. `GET /{slug}` mengirimkan header **`Content-Security-Policy`** ketat yang hanya mengizinkan eksekusi script dengan hash SHA-256 tepat milik `CLIENT_JS` (`script-src 'sha256-<hash>'; object-src 'none'; base-uri 'self'; style-src 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data: https:;`), sehingga browser memblokir total script injeksi apa pun walau ada bypass di lapisan lain.
- **Isolasi Tenant:**
  - Semua endpoint `/api/*` dilindungi middleware `auth` dan CSRF Laravel; seluruh mutasi `Site` hanya memakai `$request->user()->site`.
  - Penghapusan media melalui `DELETE /api/media` memvalidasi prefix path `/storage/sites/{auth()->id()}/` serta menolak `..`.

---

## 5. Yang Diserahkan ke Implementer / Builder
- Penamaan controller (`AuthController`, `StudioController`, `SiteApiController`, `MediaApiController`, `PublicSiteController`), FormRequest classes, service (`SlugService`, `SiteHtmlSanitizer`), serta `DemoUmkmSeeder`.
- Implementasi UI detail pada `login.blade.php` dan `register.blade.php` selama tetap menggunakan palet warna, tipografi, dan gaya komponen `style.css`.
- Penanganan konversi URL gambar relatif (`/storage/...`) menjadi URL absolut pada saat tombol **Unduh HTML** dijalankan.
- Cakupan unit & feature test lengkap untuk autentikasi, slug permanen & reserved slugs, API site/publish/media, proteksi sanitasi & CSP pada `GET /{slug}`, dan isolasi tenant.
