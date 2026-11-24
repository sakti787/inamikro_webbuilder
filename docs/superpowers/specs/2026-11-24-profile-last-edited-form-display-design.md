# Desain Teknis: Tampilan Data Terakhir Diedit Langsung di Dalam Form Profil Usaha

## 1. Latar Belakang & Tujuan
Pelaku UMKM sering kali perlu memperbarui profil usahanya (seperti nomor WhatsApp, jam buka, atau alamat toko). Namun, pengguna sering lupa data apa yang saat ini sedang aktif di website tokonya. Fitur ini bertujuan menampilkan data yang tersimpan/terakhir diedit langsung di dalam form profil usaha beserta penanda waktu terakhir diperbarui, sehingga pengguna tidak perlu lagi membuka website publik hanya untuk memeriksa data yang aktif sebelum melakukan perubahan.

## 2. Pendekatan Desain (Approaches)
- **Pendekatan A (Panel Samping / Side Preview Card):** Menampilkan kartu terpisah di sisi kanan form berisi data aktif.
  - *Trade-off:* Memakan ruang layar, membuat form input terasa sempit di perangkat layar kecil, dan memecah fokus pengguna.
- **Pendekatan B (Langsung di Dalam Form / Inline Display - Dipilih):**
  - Mengintegrasikan informasi terakhir diedit langsung di dalam kontainer form utama.
  - Menampilkan bar info waktu terakhir diperbarui (`updated_at`) berformat Indonesia yang ramah (misal: *Terakhir diperbarui: 24 November 2026, 15:00 WIB*).
  - Memastikan seluruh field input menampilkan nilai terakhir yang tersimpan secara terstruktur dengan placeholder petunjuk ramah jika data masih kosong (*Belum diisi*).
  - Menampilkan label penegas pada pratinjau logo (*Logo Aktif Saat Ini*).
  - *Alasan Pemilihan:* Paling simpel, intuitif, hemat ruang, dan secara eksplisit diminta oleh pengguna.

## 3. Spesifikasi Teknis & Komponen yang Terpengaruh

### A. Komponen Livewire: `app/Livewire/Dashboard/ProfileForm.php`
1. Properti Tambahan:
   - `public ?string $lastUpdatedHuman = null;`
   - `public ?string $lastUpdatedFull = null;`
2. Logika `mount()`:
   - Ambil `$business = $user->business;`
   - Format `updated_at` ke bahasa Indonesia:
     - Full format: e.g. `24 November 2026, 15:00 WIB`
     - Diff format: e.g. `2 jam yang lalu`
3. Logika `save()`:
   - Setelah `$business->update($updateData);`, mutakhirkan `$lastUpdatedHuman` dan `$lastUpdatedFull`.
   - Mengirim session flash sukses.

### B. Tampilan Blade: `resources/views/livewire/dashboard/profile-form.blade.php`
1. **Banner Info Terakhir Diedit (Di dalam Form):**
   - Menampilkan kartu header di dalam form dengan icon jam/kalender:
     - Jika sudah pernah diedit: `"Data profil terakhir diperbarui pada 24 November 2026, 15:00 WIB"`
     - Jika baru / belum pernah diubah: `"Profil belum pernah diperbarui"`
2. **Field Inputs & Helpers:**
   - Nama Usaha: Input terisi nilai saat ini.
   - Logo: Menampilkan badge "Logo Aktif Saat Ini" pada thumbnail logo yang tersimpan.
   - WhatsApp, Telepon, Kategori, Deskripsi, Alamat, Jam Operasional:
     - Placeholder informatif jika kosong: `"Belum diisi (Contoh: ...)"`
3. **Responsivitas:**
   - Bersih di mobile dan desktop tanpa scroll horizontal atau layout bergeser.

## 4. Rencana Pengujian (Test Cases)
- `test_profile_form_displays_last_updated_timestamp_when_business_exists`: Memastikan timestamp `updated_at` muncul di tampilan form dalam format yang ramah.
- `test_profile_form_displays_existing_data_in_inputs`: Memastikan field form terisi dengan data yang terakhir disimpan.
- `test_profile_form_updates_last_updated_timestamp_after_saving`: Memastikan setelah form disimpan, timestamp yang ditampilkan otomatis terbarui.
- `test_profile_form_displays_unfilled_status_for_empty_optional_fields`: Memastikan indikator/placeholder informatif muncul jika field opsional belum diisi.
