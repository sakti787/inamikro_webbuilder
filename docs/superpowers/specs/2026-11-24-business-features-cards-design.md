# Desain Teknis: Fitur Kartu Keunggulan Layanan (Mengapa Memilih Kami)

**Tanggal:** 2026-11-24  
**Status:** Menunggu Persetujuan Pengguna  
**Ukuran:** Small Change  

---

## 1. Latar Belakang & Tujuan (Why & Intent)
Pengguna UMKM memerlukan cara mudah untuk membangun kepercayaan (*trust*) calon pembeli yang mengunjungi landing page toko mereka. Bagian "Keunggulan Layanan Kami" / "Mengapa Memilih Kami" memberikan alasan rasional bagi pelanggan untuk memilih toko UMKM tersebut dibandingkan kompetitor.

Fitur ini harus sangat ramah bagi UMKM awam:
- Cukup 1 tombol toggle ON/OFF di tab Profil Usaha.
- Ketika diaktifkan pertama kali, otomatis terisi template teks bawaan yang profesional sehingga UMKM tidak harus memikirkan kata-kata dari nol.
- Teks utama (badge, judul, sub-judul) dan 3 kartu dapat diedit sepenuhnya oleh pemilik usaha.
- Tampil di template **Minimalist Store** dengan desain bersih tanpa ikon (*iconless minimalist card layout*).

---

## 2. Goals, Non-Goals, & Anti-Goals

### Goals:
1. Menyediakan toggle on/off di `ProfileForm` Livewire component.
2. Ketika toggle diubah ke **ON** dan belum ada teks, otomatis memuat template teks standar.
3. Form input kartu (badge, judul utama, subjudul, dan 3 kartu berisikan judul + deskripsi) tampil secara dinamis jika toggle ON, dan sembunyi jika OFF.
4. Di landing page template Minimalist Store (`resources/views/templates/minimalist/index.blade.php`), bagian keunggulan ini muncul di atas/sebelum katalog produk ketika statusnya ON, dan tidak muncul jika statusnya OFF.
5. Tampilan kartu di Minimalist Store berdesain bersih tanpa ikon.

### Non-Goals:
- Menambah atau menghapus jumlah kartu (tetap 3 kartu untuk menjaga keseimbangan layout 3 kolom).
- Upload custom icon/gambar untuk setiap kartu.
- Mengubah template lain selain Minimalist Store pada tahap ini.

### Anti-Goals:
- Jangan membuat sistem builder atau drag-and-drop yang rumit untuk mengatur posisi kartu.
- Jangan mewajibkan user mengisi teks jika fitur ini berstatus OFF.

---

## 3. Pendekatan yang Dipertimbangkan (Approaches)

| Pendekatan | Deskripsi | Kelebihan | Kekurangan | Status |
|---|---|---|---|---|
| **A: Kolom JSON `features` pada tabel `businesses`** | Menambahkan kolom `features` bertipe `json` di tabel `businesses`. | Ringan, selaras dengan arsitektur `ProfileForm`, data keunggulan melekat pada profil usaha bukan template spesifik, minim join. | Schema tidak kaku di tingkat database, namun divalidasi via Livewire rules. | **Dipilih** |
| **B: Tabel Relasional Terpisah (`business_features` & `business_feature_items`)** | Membuat 2 tabel terpisah dengan foreign key ke `businesses`. | Relasi SQL murni. | *Overkill* untuk 3 kartu UMKM sederhana, migrasi dan query Livewire lebih berat. | Ditolak |
| **C: Pengaturan di tabel `themes` atau `websites`** | Disimpan di level website/tema. | Spesifik per website. | Konten keunggulan bisnis adalah bagian dari profil usaha; jika user ganti tema, konten akan hilang atau terputus. | Ditolak |

---

## 4. Struktur Data (JSON Schema pada `businesses.features`)

```json
{
  "enabled": true,
  "badge": "Mengapa Memilih Kami",
  "title": "Keunggulan Layanan Kami",
  "subtitle": "Komitmen kami dalam memberikan standar mutu dan kepuasan terbaik",
  "items": [
    {
      "title": "Kualitas Terjamin & Terpercaya",
      "description": "Setiap produk dan layanan melewati kontrol kualitas yang ketat demi kepuasan menyeluruh."
    },
    {
      "title": "Respon Cepat & Profesional",
      "description": "Tim kami selalu siap mendengarkan kebutuhan Anda dengan respon tanggap dan solusi tepat."
    },
    {
      "title": "Harga Transparan & Bersaing",
      "description": "Menawarkan nilai terbaik tanpa biaya tersembunyi untuk kemitraan jangka panjang yang kokoh."
    }
  ]
}
```

---

## 5. Rincian Implementasi

### A. Database
- Migration baru: `add_features_to_businesses_table` menambahkan `$table->json('features')->nullable()->after('operating_hours');`.
- Model `Business`:
  - Tambahkan `'features'` ke `$fillable`.
  - Tambahkan `'features' => 'array'` ke `$casts`.

### B. Livewire Component: `ProfileForm`
- Properti baru:
  - `public bool $features_enabled = false;`
  - `public string $features_badge = 'Mengapa Memilih Kami';`
  - `public string $features_title = 'Keunggulan Layanan Kami';`
  - `public string $features_subtitle = 'Komitmen kami dalam memberikan standar mutu dan kepuasan terbaik';`
  - `public array $features_items = [];` (3 item, masing-masing dengan kunci `title` dan `description`)
- Method `updatedFeaturesEnabled($value)`:
  - Jika `$value === true` dan `$features_items` masih kosong/default, inisialisasi dengan template bawaan.
- Validasi saat `save()`:
  - Jika `$features_enabled === true`, validasi:
    - `features_title` => `['required', 'string', 'max:255']`
    - `features_items.*.title` => `['required', 'string', 'max:255']`
    - `features_items.*.description` => `['required', 'string', 'max:500']`
- Penyimpanan ke database di `save()`:
  - Disimpan ke kolom `features` model `Business`.

### C. Livewire View: `profile-form.blade.php`
- Menambahkan kartu pengaturan "Keunggulan Layanan (Mengapa Memilih Kami)" sebelum tombol simpan:
  - Toggle switch untuk mengaktifkan/menonaktifkan.
  - Section form yang muncul dengan animasi/transisi Alpine.js atau conditional Blade jika toggle aktif.
  - Input field untuk Label Badge, Judul Utama, Subjudul, dan 3 grup input Kartu Keunggulan.

### D. Template View: `resources/views/templates/minimalist/index.blade.php`
- Di bawah Hero Section dan di atas Katalog Produk:
  - Cek `@if(!empty($business?->features['enabled']))`.
  - Tampilkan section dengan layout 3 grid responsif.
  - Kartu minimalis tanpa ikon dengan tipografi tegas, padding yang nyaman, dan subtle border/background.

### E. Testing
- Feature test untuk memastikan:
  - Toggle off tidak menampilkan section di landing page Minimalist Store.
  - Toggle on otomatis terisi default jika belum ada isi.
  - Form validasi berjalan saat toggle on.
  - Form berhasil menyimpan perubahan data ke database.
  - Landing page Minimalist Store menampilkan data kartu yang tersimpan saat statusnya ON.
