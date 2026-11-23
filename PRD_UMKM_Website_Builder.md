PRODUCT REQUIREMENTS DOCUMENT (PRD) UMKM WEBSITE BUILDER Platform
Pembuatan Landing Page Katalog Digital untuk UMKM

Versi Dokumen : 1.0 Status : Initial Product Specification Platform :
Web Application Target User : Pelaku UMKM dan Administrator Bahasa :
Indonesia Arsitektur : Laravel Full Stack, Monolithic Multi-Tenant
Database : MySQL

============================================================ 1. PRODUCT
OVERVIEW ============================================================

1.1 Latar Belakang

Perkembangan teknologi digital mendorong pelaku Usaha Mikro, Kecil, dan
Menengah (UMKM) untuk memasarkan produknya secara online. Namun, masih
banyak UMKM yang belum memiliki website sendiri karena keterbatasan
pengetahuan teknis, biaya pembuatan website, dan kesulitan mengelola
konten digital.

Sebagian UMKM hanya mengandalkan media sosial seperti Instagram,
Facebook, dan WhatsApp untuk memperkenalkan produknya.

Oleh karena itu, diperlukan sebuah platform yang dapat membantu UMKM
membuat website katalog produk secara mandiri, mudah, dan efisien.

Platform ini memungkinkan pengguna memilih template, mengisi formulir
informasi usaha, menambahkan produk, serta menyesuaikan tampilan website
sesuai identitas bisnis masing-masing.

1.2 Product Vision

Menjadi platform digital yang memudahkan setiap pelaku UMKM memiliki
website katalog profesional tanpa membutuhkan kemampuan pemrograman
maupun desain web.

1.3 Product Goals

1.  Mempermudah UMKM memiliki website katalog digital.
2.  Menyediakan minimal tiga template landing page.
3.  Memberikan kebebasan kustomisasi tampilan website.
4.  Mempermudah pengelolaan informasi bisnis dan produk.
5.  Menyediakan publikasi website dengan URL unik.
6.  Memfasilitasi komunikasi pelanggan dan UMKM melalui WhatsApp.
7.  Menyediakan dashboard untuk mengelola website secara mandiri.

============================================================ 2. TARGET
PENGGUNA DAN USER ROLES
============================================================

Sistem memiliki dua peran utama.

2.1 UMKM (Business Owner) - Primary User

Pelaku usaha yang menggunakan platform untuk membuat dan mengelola
website katalog.

Hak akses: - Registrasi dan login. - Mengelola profil usaha. - Memilih
template. - Mengelola banner dan identitas visual. - Mengelola produk. -
Mengatur warna dan font. - Melihat preview. - Publish dan unpublish
website. - Melihat statistik website.

2.2 Administrator (System Admin)

Pengelola seluruh platform dan aktivitas pengguna.

Hak akses: - Mengelola akun UMKM. - Melihat seluruh website UMKM. -
Mengelola template. - Mengelola kategori bisnis. - Mengelola kategori
produk. - Memantau statistik platform. - Mengaktifkan atau menonaktifkan
akun. - Mengelola konfigurasi sistem.

============================================================ 3. SCOPE OF
PRODUCT ============================================================

3.1 MVP - Must Have

Fitur wajib pada versi pertama:

-   Authentication: register, login, logout.
-   Dashboard: ringkasan aktivitas UMKM.
-   Template: memilih tiga template.
-   Business Profile: informasi dan identitas bisnis.
-   Product Management: CRUD produk.
-   Banner Management: upload dan pengelolaan banner.
-   Customization: warna, font, dan logo.
-   Preview: preview landing page.
-   Publishing: publish dan unpublish.
-   URL Management: slug website unik.
-   WhatsApp: tombol kontak dan pemesanan.
-   Responsive: tampilan mobile, tablet, dan desktop.

3.2 Future Development - Nice to Have

-   Custom domain.
-   QR Code generator.
-   AI-generated product description.
-   SEO management lanjutan.
-   Visitor analytics lanjutan.
-   Paket berlangganan.
-   Testimoni pelanggan.
-   Integrasi payment gateway.
-   Multi-language.

Fitur lanjutan tidak menjadi penghambat peluncuran MVP.

============================================================ 4.
FUNCTIONAL REQUIREMENTS
============================================================

4.1 Authentication & Account Management

AUTH-01: Pengguna dapat melakukan registrasi akun UMKM. AUTH-02:
Pengguna dapat login menggunakan email dan password. AUTH-03: Pengguna
dapat logout. AUTH-04: Pengguna dapat mengubah profil akun. AUTH-05:
Pengguna dapat mengganti password. AUTH-06: Sistem menyediakan validasi
email unik. AUTH-07: Sistem membatasi akses dashboard berdasarkan role.

Ketentuan: - Email harus unik. - Password disimpan menggunakan
hashing. - Pengguna yang belum login tidak dapat mengakses dashboard
UMKM. - Admin memiliki akses sesuai role administrator.

4.2 Dashboard UMKM

Dashboard menampilkan: - Welcome Message: sapaan kepada pemilik UMKM. -
Website Status: Draft, Published, atau Unpublished. - Total Products:
jumlah produk. - Total Visitors: jumlah pengunjung website. - WhatsApp
Clicks: jumlah klik tombol WhatsApp. - Selected Template: template yang
sedang digunakan. - Website URL: link website UMKM. - Quick Actions:
edit website, preview, dan publish.

Requirement: DASH-01: Dashboard menampilkan informasi bisnis pengguna.
DASH-02: Dashboard menampilkan status website. DASH-03: Dashboard
menampilkan jumlah produk. DASH-04: Dashboard menampilkan statistik
dasar. DASH-05: Dashboard menyediakan navigasi ke setiap modul.

4.3 Template Management

Sistem menyediakan tiga template landing page.

TEMPLATE 01 - MINIMALIST STORE

Target: - Fashion dan pakaian. - Kerajinan tangan. - Aksesoris. - Produk
lifestyle.

Karakteristik: - Desain minimalis dan elegan. - Banner besar. - Katalog
produk berbentuk grid. - Dominasi warna netral. - Fokus pada foto
produk.

TEMPLATE 02 - FOOD & BEVERAGE

Target: - UMKM kuliner. - Cafe dan restoran. - Bakery. - Minuman dan
makanan ringan.

Karakteristik: - Banner dengan foto makanan. - Kategori menu. - Harga
produk terlihat jelas. - Informasi lokasi dan jam operasional. - Tombol
pemesanan WhatsApp.

TEMPLATE 03 - MODERN BUSINESS

Target: - Skincare dan kosmetik. - Produk kecantikan. - Produk rumah
tangga. - Produk non-medis lainnya.

Karakteristik: - Tampilan modern dan profesional. - Hero section dengan
CTA. - Deskripsi keunggulan produk. - Katalog produk. - Testimoni
pelanggan.

Ketiga template tetap dapat digunakan lintas kategori bisnis. Perbedaan
template terletak pada karakter visual dan susunan layout, bukan pada
sumber data.

Requirement: TEMP-01: Sistem menyediakan minimal tiga template. TEMP-02:
UMKM dapat melihat preview setiap template. TEMP-03: UMKM dapat memilih
salah satu template. TEMP-04: UMKM dapat mengganti template. TEMP-05:
Data bisnis dan produk tetap tersimpan ketika template diganti. TEMP-06:
Setiap template menggunakan data bisnis dan produk yang sama. TEMP-07:
Template harus responsif.

Aturan penting: Template merupakan komponen desain, bukan tempat
penyimpanan data. Pergantian template tidak boleh menghapus data produk,
profil bisnis, banner, dan informasi lainnya.

4.4 Business Profile Management

Field profil bisnis:

-   Business Name (Text, wajib).
-   Business Logo (Image, opsional).
-   Business Category (Select, wajib).
-   Tagline (Text, opsional).
-   Description (Textarea, wajib).
-   Phone Number (Text, opsional).
-   WhatsApp Number (Text, wajib).
-   Email (Email, opsional).
-   Address (Textarea, opsional).
-   Operating Hours (JSON/Text, opsional).
-   Instagram URL (URL, opsional).
-   TikTok URL (URL, opsional).
-   Facebook URL (URL, opsional).
-   Google Maps URL (URL, opsional).

Requirement: PROF-01: UMKM dapat menambahkan informasi bisnis. PROF-02:
UMKM dapat memperbarui informasi bisnis. PROF-03: UMKM dapat mengunggah
logo. PROF-04: UMKM dapat mengatur informasi kontak. PROF-05: UMKM dapat
menambahkan media sosial. PROF-06: Data hanya dapat diakses dan diubah
oleh pemiliknya atau admin berwenang.

4.5 Product Management

Field produk:

-   Product Name (Text, wajib).
-   Product Category (Select, opsional).
-   Product Description (Textarea, wajib).
-   Price (Decimal, wajib).
-   Discount Price (Decimal, opsional).
-   Product Images (Multiple Images, minimal satu).
-   Stock (Integer, opsional).
-   Product Variant (opsional).
-   Product Label (Best Seller, New, Promo, atau lainnya).
-   Availability (Boolean, wajib).
-   Display Order (Integer, opsional).

Requirement: PROD-01: UMKM dapat menambahkan produk. PROD-02: UMKM dapat
melihat daftar produk. PROD-03: UMKM dapat mengubah informasi produk.
PROD-04: UMKM dapat menghapus produk. PROD-05: UMKM dapat mengunggah
beberapa foto produk. PROD-06: UMKM dapat menentukan harga dan harga
diskon. PROD-07: UMKM dapat mengatur status ketersediaan. PROD-08: UMKM
dapat mengatur kategori produk. PROD-09: UMKM dapat menentukan urutan
produk. PROD-10: Produk yang tidak tersedia tidak ditampilkan sebagai
produk aktif di katalog.

4.6 Banner & Gallery Management

Banner: - Upload banner utama. - Mengubah gambar banner. - Mengatur
judul banner. - Mengatur subtitle. - Mengatur teks tombol CTA. -
Mengatur link CTA jika diperlukan.

Gallery: - Upload beberapa foto usaha. - Menghapus foto. - Mengatur
urutan foto. - Menampilkan galeri di landing page.

4.7 Website Customization

Pengaturan yang dapat dikustomisasi: - Primary Color: warna utama
website. - Secondary Color: warna pendukung. - Background Color: warna
latar belakang. - Text Color: warna teks. - Font Family: jenis font. -
Button Style: bentuk tombol. - Border Radius: bentuk sudut elemen. -
Product Layout: grid atau daftar jika didukung template.

Requirement: CUST-01: UMKM dapat memilih warna utama. CUST-02: UMKM
dapat memilih warna sekunder. CUST-03: UMKM dapat memilih font. CUST-04:
UMKM dapat melihat perubahan secara langsung. CUST-05: Sistem
menyediakan pilihan warna default. CUST-06: Sistem menyediakan tombol
reset customization. CUST-07: Setiap template harus tetap menjaga
konsistensi desain.

Pada MVP, jangan memberikan kebebasan mengubah seluruh struktur layout.
Sediakan opsi kustomisasi yang sudah ditentukan agar desain tetap rapi
dan konsisten.

4.8 Live Preview

Fitur preview digunakan untuk melihat tampilan landing page sebelum
dipublikasikan.

Requirement: PREV-01: Pengguna dapat melihat preview website. PREV-02:
Preview menampilkan data bisnis terbaru. PREV-03: Preview menampilkan
produk terbaru. PREV-04: Preview menampilkan hasil kustomisasi warna dan
font. PREV-05: Preview dapat menampilkan tampilan desktop dan mobile.
PREV-06: Preview tidak mengubah status website menjadi Published.

4.9 Website Publishing

Setiap UMKM dapat mempublikasikan website secara mandiri.

Contoh URL: https://platformkamu.id/adhit-fashion

Requirement: PUB-01: UMKM dapat melakukan publish. PUB-02: UMKM dapat
melakukan unpublish. PUB-03: Sistem menghasilkan slug unik. PUB-04:
Website publik hanya menampilkan data yang berstatus aktif. PUB-05:
Website dapat diakses tanpa login. PUB-06: Sistem memvalidasi
kelengkapan informasi sebelum publish. PUB-07: Pengguna dapat mengubah
slug selama belum digunakan pengguna lain. PUB-08: Website yang
di-unpublish tidak dapat diakses oleh publik.

4.10 WhatsApp Integration

Ketika pelanggan menekan tombol pemesanan, sistem mengarahkan pelanggan
ke WhatsApp UMKM.

Contoh format:
https://wa.me/6281234567890?text=Halo%2C%20saya%20tertarik%20dengan%20produk%20Kaos%20Oversize.

Requirement: WA-01: Sistem menyediakan tombol WhatsApp. WA-02: Nomor
WhatsApp menggunakan format internasional. WA-03: Pesan otomatis dapat
berisi nama produk. WA-04: Sistem mencatat klik tombol WhatsApp. WA-05:
Tombol WhatsApp tersedia di halaman detail produk dan landing page
sesuai template.

============================================================ 5. USER
FLOW ============================================================

5.1 Flow Registrasi dan Pembuatan Website

1.  Pengguna membuka platform.
2.  Pengguna melakukan registrasi akun.
3.  Sistem memvalidasi data registrasi.
4.  Pengguna login.
5.  Pengguna masuk ke dashboard UMKM.
6.  Pengguna memilih template.
7.  Pengguna mengisi profil bisnis.
8.  Pengguna mengunggah logo dan banner.
9.  Pengguna menambahkan produk.
10. Pengguna melakukan kustomisasi website.
11. Pengguna melihat live preview.
12. Pengguna memeriksa hasil website.
13. Pengguna melakukan publish.
14. Sistem menghasilkan URL unik.
15. Website dapat diakses publik.

5.2 Flow Pelanggan Mengakses Website

1.  Pelanggan membuka URL UMKM.
2.  Sistem memvalidasi slug.
3.  Sistem memeriksa status website.
4.  Jika website tidak dipublikasikan, tampilkan halaman 404 atau
    halaman tidak tersedia.
5.  Jika website dipublikasikan, tampilkan landing page.
6.  Pelanggan memilih produk.
7.  Pelanggan melihat detail produk.
8.  Pelanggan menekan tombol WhatsApp.
9.  Sistem mencatat klik.
10. Pelanggan diarahkan ke WhatsApp UMKM.

5.3 Flow Administrator

1.  Admin login.
2.  Admin masuk ke dashboard administrator.
3.  Admin dapat mengelola pengguna UMKM.
4.  Admin dapat mengelola template.
5.  Admin dapat mengelola kategori.
6.  Admin dapat memantau website.
7.  Admin dapat melihat statistik platform.

============================================================ 6. UI/UX
REQUIREMENTS
============================================================

6.1 Dashboard UMKM

Konsep desain: - Modern SaaS dashboard. - Minimalis dan profesional. -
Sidebar navigation. - Warna netral. - Informasi berbentuk card. - Form
mudah digunakan. - Responsive untuk desktop dan mobile.

Sitemap dashboard:

/dashboard /dashboard/profile /dashboard/templates /dashboard/products
/dashboard/products/create /dashboard/products/edit/{id}
/dashboard/banners /dashboard/gallery /dashboard/customization
/dashboard/preview /dashboard/website /dashboard/analytics
/dashboard/settings

6.2 Public Landing Page

Struktur halaman:

Public Landing Page - Navbar - Logo UMKM - Beranda - Produk - Tentang
Kami - Kontak - Hero Section - Banner - Headline - Deskripsi - CTA -
About Business - Product Categories - Product Catalog - Product Image -
Product Name - Price - Detail Product - Gallery - Testimonials
(opsional) - Contact Information - Footer - Floating WhatsApp Button

Ketentuan desain: Ketiga template harus memiliki struktur dan identitas
visual yang berbeda, tetapi mengambil data dari sumber database yang
sama.

============================================================ 7.
TECHNICAL REQUIREMENTS
============================================================

7.1 Technology Stack

Backend : Laravel Frontend : Laravel Blade Interactivity : Livewire CSS
Framework : Tailwind CSS JavaScript : Alpine.js Database : MySQL ORM :
Eloquent Authentication: Laravel Authentication Image Storage : Laravel
Storage Build Tool : Vite Version Control: Git dan GitHub

7.2 Arsitektur Aplikasi

Browser / User Interface - Blade - Tailwind CSS - Alpine.js

Laravel Application - Routes - Controllers - Livewire Components -
Middleware - Services - Policies

Eloquent ORM - Models - Relationships - Query Builder

MySQL Database - Users - Businesses - Products - Websites - Templates -
Themes

Laravel Storage - Logo - Banner - Foto produk - Galeri usaha

Keuntungan pendekatan: 1. Tidak membutuhkan REST API terpisah untuk
frontend. 2. Pengembangan lebih sederhana. 3. Autentikasi dapat
menggunakan session Laravel. 4. Data dikelola menggunakan Eloquent ORM.
5. Livewire mempermudah pembuatan interaksi tanpa banyak JavaScript. 6.
Lebih mudah dipelihara untuk tim kecil.

7.3 Architecture Pattern

Gunakan struktur modular dengan pemisahan tanggung jawab.

Struktur awal:

app/ Models/ User.php Business.php Website.php Template.php Product.php
ProductImage.php Banner.php Theme.php

Http/ Controllers/ Admin/ Business/ Public/ Middleware/ EnsureAdmin.php
EnsureBusinessOwner.php Requests/

Livewire/ Dashboard/ Business/ Products/ Templates/ Customization/

Services/ WebsitePublisher.php SlugGenerator.php ImageUploadService.php

Policies/ BusinessPolicy.php ProductPolicy.php WebsitePolicy.php

Struktur ini merupakan rancangan awal dan dapat disesuaikan dengan versi
Laravel yang digunakan.

============================================================ 8. DATABASE
DESIGN (MYSQL)
============================================================

Database menggunakan pendekatan relational database dengan konsep
multi-tenant. Semua UMKM berada dalam satu database, tetapi data setiap
UMKM dipisahkan berdasarkan business_id.

8.1 Daftar Tabel

1.  users Menyimpan akun pengguna dan role admin/UMKM.

2.  businesses Menyimpan informasi UMKM.

3.  business_categories Menyimpan kategori bisnis.

4.  templates Menyimpan data template landing page.

5.  websites Menyimpan data website setiap UMKM.

6.  themes Menyimpan konfigurasi tampilan website.

7.  banners Menyimpan banner website.

8.  products Menyimpan katalog produk.

9.  product_categories Menyimpan kategori produk.

10. product_images Menyimpan gambar produk.

11. business_galleries Menyimpan galeri usaha.

12. testimonials Menyimpan testimoni pelanggan.

13. website_visits Menyimpan statistik pengunjung.

14. whatsapp_clicks Menyimpan statistik klik WhatsApp.

8.2 Struktur Tabel

TABEL: users

-   id: BIGINT, Primary Key.
-   name: VARCHAR(255), nama pengguna.
-   email: VARCHAR(255), email unik.
-   password: VARCHAR(255), password hash.
-   role: ENUM atau VARCHAR, admin/umkm.
-   email_verified_at: TIMESTAMP, verifikasi email.
-   created_at: TIMESTAMP.
-   updated_at: TIMESTAMP.

TABEL: businesses

-   id: BIGINT, Primary Key.
-   user_id: BIGINT, FK users.
-   category_id: BIGINT, FK business_categories.
-   name: VARCHAR(255), nama UMKM.
-   slug: VARCHAR(255), identitas bisnis.
-   logo: VARCHAR(255), path logo.
-   tagline: VARCHAR(255), tagline.
-   description: TEXT, deskripsi.
-   phone: VARCHAR(30), nomor telepon.
-   whatsapp: VARCHAR(30), nomor WhatsApp.
-   email: VARCHAR(255), email bisnis.
-   address: TEXT, alamat.
-   operating_hours: JSON, jam operasional.
-   created_at: TIMESTAMP.
-   updated_at: TIMESTAMP.

TABEL: business_categories

-   id: BIGINT, Primary Key.
-   name: VARCHAR(100), nama kategori.
-   slug: VARCHAR(100), slug kategori.
-   description: TEXT, deskripsi.

TABEL: templates

-   id: BIGINT, Primary Key.
-   name: VARCHAR(100), nama template.
-   slug: VARCHAR(100), identitas template.
-   thumbnail: VARCHAR(255), gambar preview.
-   view_path: VARCHAR(255), Blade view.
-   description: TEXT, deskripsi.
-   is_active: BOOLEAN, status template.

Contoh data template: 1. Minimalist Store - minimalist -
templates.minimalist 2. Food & Beverage - food-beverage - templates.food
3. Modern Business - modern - templates.modern

TABEL: websites

-   id: BIGINT, Primary Key.
-   business_id: BIGINT, FK businesses.
-   template_id: BIGINT, FK templates.
-   slug: VARCHAR(255), URL unik.
-   status: ENUM, draft/published/unpublished.
-   published_at: TIMESTAMP.
-   created_at: TIMESTAMP.
-   updated_at: TIMESTAMP.

TABEL: themes

-   id: BIGINT, Primary Key.
-   website_id: BIGINT, FK websites.
-   primary_color: VARCHAR(20), warna utama.
-   secondary_color: VARCHAR(20), warna sekunder.
-   background_color: VARCHAR(20), warna background.
-   text_color: VARCHAR(20), warna teks.
-   font_family: VARCHAR(100), jenis font.
-   button_style: VARCHAR(50), style tombol.
-   border_radius: VARCHAR(20), radius elemen.

TABEL: banners

-   id: BIGINT, Primary Key.
-   business_id: BIGINT, FK businesses.
-   image: VARCHAR(255), path gambar.
-   title: VARCHAR(255), judul banner.
-   subtitle: TEXT, deskripsi banner.
-   button_text: VARCHAR(100), teks CTA.
-   button_url: VARCHAR(255), link CTA.
-   is_active: BOOLEAN, status banner.

TABEL: products

-   id: BIGINT, Primary Key.
-   business_id: BIGINT, FK businesses.
-   category_id: BIGINT, FK product_categories.
-   name: VARCHAR(255), nama produk.
-   slug: VARCHAR(255), slug produk.
-   description: TEXT, deskripsi.
-   price: DECIMAL(15,2), harga.
-   discount_price: DECIMAL(15,2), harga diskon.
-   stock: INT, stok.
-   label: VARCHAR(50), Best Seller/New/Promo.
-   is_available: BOOLEAN, ketersediaan.
-   sort_order: INT, urutan produk.

TABEL: product_categories

-   id: BIGINT, Primary Key.
-   business_id: BIGINT, FK businesses.
-   name: VARCHAR(100), nama kategori.
-   slug: VARCHAR(100), slug.

TABEL: product_images

-   id: BIGINT, Primary Key.
-   product_id: BIGINT, FK products.
-   image: VARCHAR(255), path gambar.
-   is_primary: BOOLEAN, foto utama.
-   sort_order: INT, urutan gambar.

TABEL: business_galleries

-   id: BIGINT, Primary Key.
-   business_id: BIGINT, FK businesses.
-   image: VARCHAR(255), path gambar.
-   caption: VARCHAR(255), keterangan.
-   sort_order: INT, urutan.

TABEL: testimonials

-   id: BIGINT, Primary Key.
-   business_id: BIGINT, FK businesses.
-   customer_name: VARCHAR(255), nama pelanggan.
-   content: TEXT, isi testimoni.
-   rating: TINYINT, rating 1-5.
-   is_active: BOOLEAN, status tampil.

TABEL: website_visits

-   id: BIGINT, Primary Key.
-   website_id: BIGINT, FK websites.
-   visited_at: TIMESTAMP, waktu kunjungan.
-   visitor_hash: VARCHAR(64), identifier anonim.
-   referrer: TEXT, sumber kunjungan.

TABEL: whatsapp_clicks

-   id: BIGINT, Primary Key.
-   website_id: BIGINT, FK websites.
-   product_id: BIGINT, FK products, nullable.
-   clicked_at: TIMESTAMP, waktu klik.
-   source: VARCHAR(50), sumber tombol.

8.3 Relasi Database

-   Satu user dapat memiliki satu atau beberapa bisnis, sesuai aturan
    bisnis yang ditetapkan.
-   Satu bisnis memiliki satu website aktif pada MVP.
-   Satu website menggunakan satu template.
-   Satu website memiliki satu konfigurasi tema aktif.
-   Satu bisnis memiliki banyak banner.
-   Satu bisnis memiliki banyak produk.
-   Satu bisnis memiliki banyak kategori produk.
-   Satu produk dapat memiliki banyak gambar.
-   Satu bisnis memiliki banyak galeri.
-   Satu bisnis memiliki banyak testimoni.
-   Satu website memiliki banyak catatan kunjungan.
-   Satu website memiliki banyak catatan klik WhatsApp.
-   Satu produk dapat direferensikan oleh banyak catatan klik WhatsApp.

Catatan desain: - Gunakan foreign key untuk menjaga integritas data. -
Tambahkan index pada business_id, website_id, dan slug. - Slug website
harus unik secara global. - Slug produk sebaiknya unik dalam satu
UMKM. - Gunakan DECIMAL untuk harga, bukan FLOAT. - Tambahkan created_at
dan updated_at pada tabel operasional. - Terapkan soft delete untuk data
yang perlu dipulihkan.

============================================================ 9. ROUTING
DESIGN ============================================================

9.1 Authentication

/register /login /logout /forgot-password /reset-password

9.2 UMKM Dashboard

/dashboard /dashboard/profile /dashboard/templates /dashboard/products
/dashboard/products/create /dashboard/products/{id}/edit
/dashboard/banners /dashboard/gallery /dashboard/customization
/dashboard/preview /dashboard/website /dashboard/analytics
/dashboard/settings

9.3 Administrator

/admin/dashboard /admin/users /admin/businesses /admin/templates
/admin/categories /admin/websites /admin/analytics /admin/settings

9.4 Public Website

/{business_slug}

/{business_slug}/produk/{product_slug}

Contoh: - /lapak-kopi-senja - /adhit-fashion - /dapur-ibu -
/adhit-fashion/produk/kaos-oversize

Aturan routing: - Route publik tidak boleh bentrok dengan route sistem
seperti /login, /register, /admin, dan /dashboard. - Slug harus
divalidasi. - Website hanya ditampilkan jika statusnya published.

============================================================ 10.
NON-FUNCTIONAL REQUIREMENTS
============================================================

NFR-01 - Performance: Halaman utama ditargetkan memiliki waktu respons
server di bawah 2 detik pada kondisi normal.

NFR-02 - Security: Password menggunakan hashing.

NFR-03 - Security: Validasi dan sanitasi input.

NFR-04 - Security: Proteksi CSRF.

NFR-05 - Security: Proteksi akses antar-UMKM.

NFR-06 - Security: Validasi file upload.

NFR-07 - Responsive: Mendukung desktop, tablet, dan mobile.

NFR-08 - Availability: Website tersedia selama server beroperasi.

NFR-09 - Maintainability: Struktur kode modular dan mudah dipelihara.

NFR-10 - Scalability: Mendukung pertambahan jumlah UMKM.

NFR-11 - SEO: Mendukung meta title dan meta description.

NFR-12 - Backup: Database memiliki mekanisme backup.

NFR-13 - Usability: Form dapat digunakan tanpa pengetahuan coding.

10.1 Security Requirements Khusus Multi-Tenant

Contoh: - UMKM A memiliki business_id = 1. - UMKM B memiliki business_id
= 2.

UMKM A tidak boleh dapat mengakses atau mengubah produk UMKM B hanya
dengan mengganti ID pada URL.

Contoh request yang harus ditolak: /dashboard/products/25/edit

Jika produk dengan ID 25 bukan milik pengguna yang sedang login, sistem
harus mengembalikan HTTP 403 atau 404.

Implementasi keamanan: 1. Gunakan Laravel Policies. 2. Gunakan
middleware untuk role. 3. Pastikan query data dibatasi berdasarkan
business ownership. 4. Jangan mempercayai business_id yang dikirim dari
form pengguna. 5. Validasi file upload berdasarkan MIME type, ukuran,
dan ekstensi. 6. Simpan file menggunakan nama acak. 7. Gunakan CSRF
protection dan output escaping. 8. Jangan menampilkan data website
berstatus draft kepada publik.

============================================================ 11.
BUSINESS RULES
============================================================

BR-01: Setiap akun UMKM harus memiliki profil bisnis sebelum membuat
website. BR-02: Satu UMKM hanya memiliki satu website aktif pada MVP.
BR-03: Setiap website wajib menggunakan satu template. BR-04: Setiap
website harus memiliki slug unik. BR-05: Website hanya bisa
dipublikasikan jika informasi wajib sudah lengkap. BR-06: Setiap UMKM
hanya boleh mengelola data miliknya. BR-07: Produk hanya dapat dimiliki
oleh satu UMKM. BR-08: Produk yang tidak tersedia tidak dapat dipesan
melalui katalog. BR-09: Pergantian template tidak boleh menghapus data
UMKM. BR-10: Unpublish tidak menghapus data website. BR-11: Admin
memiliki akses pengelolaan platform. BR-12: Website publik hanya
menampilkan konten yang aktif.

Validasi sebelum publish: - Nama UMKM harus tersedia. - Deskripsi bisnis
harus tersedia. - Nomor WhatsApp harus valid. - Template harus
dipilih. - Banner utama harus tersedia. - Minimal satu produk harus
tersedia. - Slug harus valid dan unik.

Kriteria validasi tersebut dapat dibuat sebagai service tersendiri,
misalnya WebsitePublishValidator.

============================================================ 12.
ACCEPTANCE CRITERIA (DEFINITION OF DONE)
============================================================

Fitur dianggap selesai apabila memenuhi kriteria berikut:

Authentication: Pengguna berhasil register, login, dan logout.

Dashboard: Informasi UMKM tampil sesuai akun.

Template: Pengguna dapat memilih tiga template.

Business Profile: Data bisnis dapat disimpan dan diperbarui.

Product: CRUD produk berjalan dengan baik.

Banner: Gambar dapat diunggah dan ditampilkan.

Customization: Warna dan font berubah sesuai pengaturan.

Preview: Preview menampilkan hasil kustomisasi.

Publishing: Website dapat diakses melalui URL unik.

WhatsApp: Tombol mengarah ke nomor WhatsApp yang benar.

Security: UMKM tidak dapat mengakses data UMKM lain.

Responsive: Website berfungsi di berbagai ukuran layar.

12.1 Skenario Pengujian Utama

1.  Registrasi menggunakan email yang belum terdaftar.
2.  Registrasi menggunakan email yang sudah terdaftar.
3.  Login dengan password benar dan salah.
4.  UMKM membuat profil bisnis.
5.  UMKM memilih template pertama.
6.  UMKM mengganti template menjadi template kedua.
7.  UMKM menambahkan produk dengan foto.
8.  UMKM mengubah warna utama website.
9.  UMKM melakukan preview.
10. UMKM melakukan publish.
11. Pengunjung membuka URL website.
12. Pengunjung menekan tombol WhatsApp.
13. UMKM melakukan unpublish.
14. UMKM A mencoba mengakses produk UMKM B.
15. Pengguna mencoba mengakses dashboard tanpa login.

============================================================ 13.
DEVELOPMENT ROADMAP
============================================================

Estimasi pengerjaan: 8 minggu untuk satu developer, dengan asumsi
kebutuhan tidak berubah secara besar.

WEEK 1 - PROJECT SETUP & DATABASE - Inisialisasi Laravel. - Setup
MySQL. - Setup Tailwind dan Livewire. - Membuat migration dan model. -
Membuat ERD. - Setup autentikasi dan role.

WEEK 2 - AUTHENTICATION & DASHBOARD - Register dan login. - Role admin
dan UMKM. - Dashboard UMKM. - Dashboard admin. - Middleware dan
authorization.

WEEK 3 - BUSINESS & PRODUCT MANAGEMENT - CRUD profil bisnis. - CRUD
kategori. - CRUD produk. - Upload foto produk. - Banner dan galeri.

WEEK 4 - TEMPLATE DEVELOPMENT - Template Minimalist Store. - Template
Food & Beverage. - Template Modern Business. - Dynamic data rendering.

WEEK 5 - CUSTOMIZATION - Pengaturan warna. - Pengaturan font. -
Pengaturan logo dan banner. - Live preview.

WEEK 6 - PUBLISHING - Generate slug. - Public landing page. - Publish
dan unpublish. - WhatsApp integration. - SEO dasar.

WEEK 7 - ADMIN & ANALYTICS - Manajemen pengguna. - Manajemen template. -
Statistik pengunjung. - Statistik klik WhatsApp.

WEEK 8 - TESTING & DEPLOYMENT - Functional testing. - Security
testing. - Responsive testing. - Bug fixing. - Deployment ke server.

Timeline merupakan estimasi perencanaan, bukan jaminan durasi
pengerjaan.

============================================================ 14. PROJECT
STRUCTURE ============================================================

Struktur folder yang direkomendasikan:

umkm-builder/ | |– app/ | |– Http/ | | |– Controllers/ | | | |– Admin/ |
| | |– Business/ | | | |– Public/ | | |– Middleware/ | | |– Requests/ |
| | |– Livewire/ | | |– Admin/ | | |– Dashboard/ | | |– Products/ | | |–
Templates/ | | |– Customization/ | | | |– Models/ | |– Policies/ | |–
Services/ | |– Enums/ | |– database/ | |– migrations/ | |– seeders/ | |–
factories/ | |– resources/ | |– views/ | | |– layouts/ | | |– auth/ | |
|– admin/ | | |– dashboard/ | | |– components/ | | |– templates/ | | |–
minimalist/ | | |– food/ | | |– modern/ | | | |– css/ | |– js/ | |–
routes/ | |– web.php | |– console.php | |– storage/ | |– tests/ |–
Feature/ |– Unit/

============================================================ 15. RISIKO
DAN MITIGASI
============================================================

Risiko: Data antar-UMKM tercampur. Mitigasi: Implementasi Policy dan
business ownership.

Risiko: Upload gambar terlalu besar. Mitigasi: Validasi ukuran dan
optimasi gambar.

Risiko: Slug bertabrakan. Mitigasi: Unique index dan slug generator.

Risiko: Template rusak saat pergantian. Mitigasi: Pisahkan data dan
presentation layer.

Risiko: Website lambat. Mitigasi: Cache, optimasi gambar, dan database
indexing.

Risiko: Form terlalu rumit. Mitigasi: Multi-step form dan preview.

Risiko: Desain tidak konsisten. Mitigasi: Gunakan design system dan
batas kustomisasi.

Risiko: Pengguna kehilangan data. Mitigasi: Backup database dan soft
delete.

============================================================ 16.
KEPUTUSAN PRODUK
============================================================

Aspek Keputusan MVP

Framework Laravel Full Stack Database MySQL Frontend Blade + Livewire +
Tailwind JavaScript Alpine.js User Role Admin dan UMKM Template 3
template Website per UMKM 1 website URL Subdirectory berbasis slug
Produk Banyak produk per UMKM Pemesanan WhatsApp Pembayaran Belum
tersedia Marketplace Tidak termasuk MVP Custom Domain Pengembangan
berikutnya Subscription Pengembangan berikutnya

Rekomendasi URL: platformkamu.id/nama-umkm

Subdirectory berbasis slug lebih mudah dikelola daripada membuat
subdomain atau domain berbeda untuk setiap UMKM pada tahap MVP.

============================================================ 17.
DEVELOPMENT CHECKLIST
============================================================

[ ] Setup project Laravel dan MySQL. [ ] Setup Blade, Livewire, Tailwind
CSS, dan Alpine.js. [ ] Membuat database migration dan ERD. [ ] Membuat
authentication dan role management. [ ] Membuat dashboard UMKM. [ ]
Membuat business profile management. [ ] Membuat product management. [ ]
Membuat tiga template landing page. [ ] Membuat fitur customization. [ ]
Membuat live preview. [ ] Membuat publish dan generate URL. [ ]
Integrasi WhatsApp. [ ] Membuat dashboard admin. [ ] Testing dan
deployment.

============================================================ 18. PRODUCT
DIRECTION DAN KESIMPULAN
============================================================

Positioning produk:

“Platform Website Builder untuk UMKM yang memungkinkan siapa saja
membuat website katalog bisnis profesional hanya dengan beberapa langkah
sederhana.”

Tiga prinsip utama produk:

1.  SIMPLE UMKM cukup mengisi form tanpa harus memahami coding.

2.  CUSTOMIZABLE Setiap UMKM bisa memiliki identitas visual yang berbeda
    meskipun menggunakan template yang sama.

3.  INDEPENDENT Setiap UMKM memiliki URL website sendiri yang dapat
    dibagikan kepada pelanggan.

Rekomendasi tahap berikutnya:

1.  Membuat sitemap dan wireframe UI/UX.
2.  Merancang halaman Dashboard UMKM.
3.  Merancang halaman Template Selection.
4.  Merancang Form Builder.
5.  Merancang Live Preview.
6.  Memfinalisasi ERD dan migration MySQL.
7.  Mengimplementasikan fitur Laravel secara bertahap.

Dengan arsitektur Laravel Full Stack dan MySQL, sistem ini memiliki
fondasi yang baik untuk dikembangkan menjadi produk SaaS.

============================================================ AKHIR
DOKUMEN ============================================================
