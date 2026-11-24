# Technical Design: Simplified 1-Click Template Selector for UMKM

## 1. Context & Motivation
Platform Inamikro UMKM Website Builder dirancang khusus untuk pelaku UMKM di Indonesia. Evaluasi kegunaan (*usability*) menunjukkan bahwa fitur Visual Studio Builder split-screen (`/dashboard/builder`) dengan pemilihan kode warna HEX, pemilihan jenis font (typography), dan preview iframe realtime terlalu rumit dan membingungkan bagi pelaku UMKM yang awam teknologi (*gaptek*).

Pemilik UMKM membutuhkan solusi **instan dan anti-ribet**: cukup 1 klik memilih jenis usaha/tampilan toko, dan sistem otomatis menerapkan desain, palet warna, dan tipografi terbaik yang sudah dikurasi profesional.

---

## 2. Goals & Non-Goals

### Goals
1. **Penyederhanaan Ekstrem (Zero Complexity):** Menghilangkan kontrol warna HEX, font picker, dan split-screen iframe builder.
2. **Pilihan Template 1-Klik (Preset Curation):** Menyediakan 3 kartu template visual dengan rekomendasi jenis usaha:
   - **Toko Minimalis:** Bersih dan elegan. Direkomendasikan untuk fashion, pakaian, kriya, aksesoris, & produk lifestyle. (Preset warna netral/monochrome, font Inter).
   - **Kuliner & Minuman (F&B):** Hangat dan menggugah selera. Direkomendasikan untuk kafe, warung makan, aneka snack, katering, frozen food. (Preset warna warm amber/ruby, font Poppins).
   - **Modern Bisnis & Jasa:** Tegas dan terpercaya. Direkomendasikan untuk toko kelontong modern, bengkel, laundry, jasa, & perdagangan umum. (Preset warna royal blue, font Plus Jakarta Sans).
3. **Penerapan Otomatis Seketika:** Memilih kartu template langsung menyimpan `template_id` dan preset tema (`primary_color`, `secondary_color`, `font_family`) ke database tanpa modal/langkah tambahan.
4. **Kendali Publikasi yang Jelas:** Memindahkan tombol publikasi/draft dan validasi kesiapan publikasi (`WebsitePublishValidator`) langsung ke halaman template dan ringkasan dashboard dengan panduan ramah UMKM.
5. **Pembersihan Kode:** Menghapus atau mendeprekasi komponen builder studio split-screen yang berlebih (`Studio.php`, `builder.blade.php`, `PreviewController.php` jika tidak lagi diperlukan).

### Non-Goals
- Tidak mengubah skema database inti (`templates`, `websites`, `themes` tetap digunakan seperti sekarang).
- Tidak mengubah struktur 3 template publik yang sudah bekerja dengan baik (`minimalist`, `food`, `modern`).

---

## 3. User Experience & Wireframe Concept

### Navigasi Sidebar Dashboard
- Ubah menu sidebar dari **"Desain Website"** (ikon kuas) menjadi **"Pilihan Tampilan"** atau **"Tema Toko"** mengarah ke `/dashboard/template`.

### Tampilan Halaman `/dashboard/template`
```
+--------------------------------------------------------------------------+
|  Pilihan Tampilan Toko Online                                            |
|  Pilih gaya desain yang paling cocok untuk usaha Anda. Desain, warna,    |
|  dan huruf akan otomatis disesuaikan secara profesional.                 |
+--------------------------------------------------------------------------+
|  [Status Website: DRAFT / PUBLISHED]   [Tombol: Terbitkan Website Toko]  |
|  [Tautan: Lihat Website Toko Saya (Buka di Tab Baru)                   ]  |
+--------------------------------------------------------------------------+
|                                                                          |
|  +--------------------+  +--------------------+  +--------------------+  |
|  | [ Gambar Mockup ]  |  | [ Gambar Mockup ]  |  | [ Gambar Mockup ]  |  |
|  |                    |  |                    |  |                    |  |
|  | Toko Minimalis     |  | Kuliner & Minuman  |  | Modern Bisnis      |  |
|  | Cocok untuk        |  | Cocok untuk        |  | Cocok untuk        |  |
|  | Fashion, Kriya,    |  | Makanan, Minuman,  |  | Ritel, Jasa,       |  |
|  | Aksesoris          |  | Snack, Katering    |  | Usaha Umum         |  |
|  |                    |  |                    |  |                    |  |
|  | [✓ Sedang Dipakai] |  | [ Gunakan Desain ] |  | [ Gunakan Desain ] |  |
|  +--------------------+  +--------------------+  +--------------------+  |
|                                                                          |
+--------------------------------------------------------------------------+
```

---

## 4. Detailed Component Changes

### 1. Komponen Livewire Baru: `App\Livewire\Dashboard\TemplateSelector`
- **File:** `app/Livewire/Dashboard/TemplateSelector.php` & `resources/views/livewire/dashboard/template-selector.blade.php`
- **Rute:** `GET /dashboard/template` (name: `dashboard.template`)
- **Fungsi:**
  - Menampilkan 3 kartu template dengan visual preview badge, status template yang sedang aktif.
  - Method `selectTemplate(int $templateId)`:
    - Memperbarui `$website->template_id`.
    - Menerapkan preset tema otomatis ke tabel `themes`:
      - `minimalist`: primary `#0F172A`, secondary `#334155`, font `Inter`
      - `food`: primary `#DC2626`, secondary `#B91C1C`, font `Poppins`
      - `modern`: primary `#2563EB`, secondary `#1D4ED8`, font `Plus Jakarta Sans`
    - Menampilkan notifikasi ramah: *"Tampilan toko berhasil diubah ke [Nama Template]!"*
  - Method `togglePublish()`:
    - Menjalankan `WebsitePublishValidator`.
    - Jika valid: ubah status ke `published`, catat `published_at`.
    - Jika belum memenuhi syarat: tampilkan daftar hal yang perlu dilengkapi (misal: "Nomor WhatsApp belum diisi", "Belum ada produk aktif").

### 2. Update Layout & Dashboard Index
- **`resources/views/layouts/dashboard.blade.php`:**
  - Ganti tautan menu `route('dashboard.builder')` menjadi `route('dashboard.template')` dengan label **"Pilihan Tampilan"**.
- **`resources/views/livewire/dashboard/index.blade.php`:**
  - Quick action card "Desain Website" diarahkan ke `route('dashboard.template')`.

### 3. Pembersihan Komponen Lama
- Hapus rute `/dashboard/builder` dan layout `resources/views/layouts/builder.blade.php`.
- Hapus `app/Livewire/Builder/Studio.php` dan `resources/views/livewire/builder/studio.blade.php`.
- Hapus atau sederhanakan `PreviewController.php` (preview internal iframe sudah tidak diperlukan, pengunjung dan pemilik UMKM langsung melihat website via rute publik `/{slug}` atau tombol "Lihat Toko Saya").

### 4. Testing
- Ganti `tests/Feature/StudioBuilderTest.php` dengan `tests/Feature/TemplateSelectorTest.php`:
  - Test UMKM dapat melihat 3 template dengan rekomendasi kategori.
  - Test UMKM memilih template dan preset tema otomatis tersimpan di database.
  - Test validasi publikasi website bekerja di halaman pemilih template.
  - Test isolasi multi-tenant (UMKM lain tidak terpengaruh).
  - Test full suite (pastikan seluruh tes aplikasi tetap lulus hijau).

---

## 5. Migration & Backward Compatibility
- Tidak ada breaking change pada database.
- UMKM yang sudah terdaftar tetap memiliki website dan tema yang valid.
- Default pendaftaran akun baru otomatis disetel ke template Minimalist dengan preset elegan.
