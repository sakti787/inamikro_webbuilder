# Design Document: UMKM Website Builder (Inamikro)

- **Date:** 2026-11-23
- **Author/Coordinator:** Antigravity & User
- **Status:** Approved Architecture (Awaiting Final User Sign-off)
- **Target Platform:** Web Application (Laravel Full Stack Monolith)
- **Repository Location:** `e:\Projek\ag\inamikro_webbuilder`

---

## 1. Intent & The "Why"
Banyak pelaku Usaha Mikro, Kecil, dan Menengah (UMKM) belum memiliki website katalog digital mandiri karena kendala biaya, kompleksitas teknis pemrograman, serta kesulitan desain. Mereka umumnya hanya mengandalkan media sosial atau chat WhatsApp secara manual.

**Inamikro Web Builder** hadir untuk memfasilitasi UMKM agar dapat membangun landing page katalog digital profesional dalam hitungan menit cukup dengan memilih template, mengisi informasi usaha, menambah produk, dan mengatur warna/font, serta langsung terhubung ke pemesanan via WhatsApp.

---

## 2. Goals, Non-Goals, and Anti-Goals

### Goals (MVP)
1. **Self-Service Onboarding:** UMKM dapat registrasi, login, dan mengelola informasi usaha secara mandiri.
2. **Hybrid Workspace UX:**
   - **Dashboard Admin UMKM (Full-Page CRUD):** Halaman lapang dan leluasa untuk manajemen profil bisnis, galeri, dan katalog produk (upload multiple image, harga, stok, kategori).
   - **Visual Studio Builder (Side-by-side Split View):** Halaman khusus (`/dashboard/builder`) dengan kontrol visual di panel kiri dan instant live preview pada `<iframe>` di panel kanan.
3. **Hybrid Real-Time Preview:**
   - Kustomisasi warna (`--color-primary`, `--color-secondary`), jenis font, dan teks judul langsung diinjeksikan secara instan (0ms latency) melalui `window.postMessage` ke iframe preview.
   - Pergantian template atau perubahan data diproses oleh Livewire dan me-refresh iframe secara sinkron.
4. **Direct-Save & Publishing Lifecycle:**
   - Perubahan data otomatis tersimpan ke database.
   - Status website (`draft`, `published`, `unpublished`) menentukan apakah website publik dapat diakses publik di `/{slug}` atau mengembalikan halaman 404/maintenance.
5. **Multi-Tenant Single Database:**
   - 1 Akun User = 1 Bisnis = 1 Website pada MVP.
   - Isolasi data menggunakan relasi Eloquent, Global Scopes, dan Laravel Policies berbasis kepemilikan bisnis.
6. **3 Template Landing Page Responsif:**
   - *Minimalist Store* (Fashion, aksesoris, kerajinan).
   - *Food & Beverage* (Kuliner, cafe, menu resto).
   - *Modern Business* (Skincare, herbal, ritel & jasa).
   - Pergantian template bersifat non-destruktif (data produk & profil bisnis tetap utuh).
7. **WhatsApp Ordering CTA:**
   - Pemesanan produk otomatis mengarahkan ke `wa.me` dengan nomor berformat internasional dan template pesan dinamis (memuat nama produk dan harga).
8. **Public Access via Clean Slug:**
   - Website publik dapat diakses di `/{business_slug}` dan detail produk di `/{business_slug}/produk/{product_slug}`.
   - Sistem memvalidasi reservasi slug terlarang (`admin`, `dashboard`, `login`, `register`, dll).
9. **Superadmin Dashboard:**
   - Manajemen pengguna, kontrol website/template, dan monitoring metrik platform.

### Non-Goals (Post-MVP)
- Payment gateway & automated checkout flow.
- Custom domain mapping (DNS/CNAME).
- Sistem multi-gudang dan manajemen stok kompleks (SKU inventory matrix).
- Otomatisasi AI copywriting.

### Anti-Goals
- Tidak memaksa form CRUD produk yang padat ke dalam sidebar kecil builder (<400px).
- Tidak mengizinkan kustomisasi CSS bebas tanpa batas yang dapat merusak responsivitas tampilan di mobile.
- Tidak memperbolehkan akses silang data antar-tenant (*zero cross-tenant leakage*).

---

## 3. Core Approaches & Architecture

### 3.1 Comparison of Approaches
1. **Server-Driven Iframe Reload:** Form Livewire me-reload iframe pada setiap perubahan input (Ditolak: terasa patah-patah/flicker saat color picker digeser).
2. **Full Client-Side DOM Binding:** Seluruh builder dan template dalam satu DOM (Ditolak: risiko tabrakan CSS styling dashboard vs template).
3. **Hybrid Split-View (Selected & Approved):**
   - Panel kiri memuat opsi kustomisasi visual & tata letak.
   - Panel kanan memuat `<iframe>` terisolasi dengan bridge `postMessage`.
   - CSS variables diubah seketika untuk interaktivitas warna/font, sementara data inti disimpan oleh Livewire ke MySQL.

### 3.2 Workspace & Route Layout
- **Auth Routes:** `/login`, `/register`, `/logout`
- **Dashboard CRUD Routes:**
  - `/dashboard` (Ringkasan statistik: total pengunjung, total klik WA, status website)
  - `/dashboard/profile` (Form informasi profil bisnis, kontak, alamat, jam operasional)
  - `/dashboard/products` & `/dashboard/products/create` & `/dashboard/products/{id}/edit` (Katalog produk, upload multiple foto, badge diskon/stok)
  - `/dashboard/banners` & `/dashboard/gallery` (Upload banner hero dan foto galeri)
- **Visual Studio Builder Route:**
  - `/dashboard/builder` (Antarmuka Side-by-side Split View: pilih template, atur warna/font, atur hero CTA, switch viewport Desktop/Tablet/Mobile, live preview, tombol Publish/Unpublish)
- **Internal Preview Route:**
  - `/builder/preview/{website_id}` (Rute yang dimuat di dalam `<iframe>` studio, diamankan oleh policy pemilik bisnis)
- **Public Routes:**
  - `/{slug}` (Landing page publik jika status `published`)
  - `/{slug}/produk/{product_slug}` (Halaman detail produk)
  - `POST /{slug}/wa-click` (Mencatat analitik konversi klik WhatsApp)
- **Superadmin Routes:**
  - `/admin/dashboard`, `/admin/users`, `/admin/businesses`, `/admin/templates`, `/admin/analytics`

---

## 4. Database Schema (14 Tables Overview)

1. `users`: ID, name, email, password, role (`admin` | `umkm`), timestamps.
2. `businesses`: ID, user_id (FK), category_id (FK), name, slug, logo, tagline, description, phone, whatsapp, email, address, operating_hours (JSON), timestamps.
3. `business_categories`: ID, name, slug, description, timestamps.
4. `templates`: ID, name, slug (`minimalist`, `food`, `modern`), thumbnail, view_path, description, is_active.
5. `websites`: ID, business_id (FK), template_id (FK), slug (Unique public URL), status (`draft`, `published`, `unpublished`), published_at, timestamps.
6. `themes`: ID, website_id (FK), primary_color, secondary_color, background_color, text_color, font_family, button_style, border_radius, timestamps.
7. `banners`: ID, business_id (FK), image, title, subtitle, button_text, button_url, is_active, timestamps.
8. `products`: ID, business_id (FK), category_id (FK, nullable), name, slug, description, price, discount_price, stock, label, variant_options (string/json sederhana), is_available, sort_order, timestamps.
9. `product_categories`: ID, business_id (FK), name, slug, timestamps.
10. `product_images`: ID, product_id (FK), image, is_primary, sort_order, timestamps.
11. `business_galleries`: ID, business_id (FK), image, caption, sort_order, timestamps.
12. `testimonials`: ID, business_id (FK), customer_name, content, rating, is_active, timestamps.
13. `website_visits`: ID, website_id (FK), visited_at, visitor_hash, referrer.
14. `whatsapp_clicks`: ID, website_id (FK), product_id (FK, nullable), clicked_at, source (`landing_page` | `product_detail`).

---

## 5. Technical Decisions Settled

1. **Multi-Tenancy Cardinality:** 1 User = 1 Business = 1 Website. Relasi `User` memiliki satu `Business`, `Business` memiliki satu `Website`. Auth middleware memastikan isolasi data tanpa perlu selector bisnis yang rumit pada tahap MVP.
2. **Slug Authority:** `websites.slug` adalah *single source of truth* untuk URL publik `/{slug}`. Kolom slug pada `businesses` akan disinkronkan secara otomatis.
3. **Product Variants:** Disimpan sebagai atribut string/tag sederhana (misalnya: "Ukuran: S, M, L, XL" atau "Pilihan: Pedas, Sedang") tanpa sistem stok per-SKU terpisah.
4. **Direct-Save Lifecycle:** Data yang disimpan di dashboard atau builder langsung masuk ke database aktif. Aksesibilitas publik semata-mata dikendalikan oleh kolom `websites.status` (`published` = bisa diakses publik; `draft` / `unpublished` = 404 bagi publik).
5. **Publish Validation (WebsitePublishValidator):** Validasi kelayakan sebelum website dapat diubah statusnya menjadi `published`:
   - Nama bisnis dan deskripsi telah diisi.
   - Nomor WhatsApp valid.
   - Template sudah dipilih.
   - Minimal 1 banner aktif terunggah.
   - Minimal 1 produk aktif tersedia.
   - Slug unik dan tidak bertabrakan dengan rute sistem.

---

## 6. What's Left to the Builder (Implementation Execution)
- Inisialisasi scaffolding proyek Laravel 11/12 dengan Livewire 3, Alpine.js, Tailwind CSS.
- Pembuatan migrasi dan model Eloquent dengan relasi lengkap.
- Pembuatan layout dashboard responsif dan modul CRUD (Profile, Products, Banners).
- Pembangunan UI Visual Builder (`/dashboard/builder`) dengan panel kontrol warna/font dan iframe bridge `postMessage`.
- Pembangunan 3 template Blade landing page (`minimalist`, `food`, `modern`).
- Implementasi routing publik `/{slug}` dan integrasi WhatsApp ordering CTA.
- Seeding data awal: akun demo UMKM, 3 template bawaan, dan katalog contoh untuk pengujian lokal.
