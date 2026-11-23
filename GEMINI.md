# Project Guidelines & Rules: Inamikro UMKM Website Builder

## Git Commit Message Conventions
Setiap perubahan kode dan commit Git yang dibuat oleh agen atau subagent harus otomatis mengikuti standar **Conventional Commits**:

### Format:
`<type>(<scope>): <deskripsi singkat perubahan>`

### Jenis Type:
- **`feat`**: Penambahan fitur atau fungsionalitas baru.
  - *Contoh:* `feat(catalog): implement product CRUD and image uploading`
  - *Contoh:* `feat(builder): add real-time theme customizer with postMessage bridge`
- **`fix`**: Perbaikan bug, error validasi, atau kebocoran akses.
  - *Contoh:* `fix(security): enforce policy check to prevent cross-tenant product editing`
- **`test`**: Penambahan atau pembaruan unit/feature/integration test.
  - *Contoh:* `test(auth): add tenant isolation and role middleware tests`
- **`chore`**: Setup awal, konfigurasi build tool, instalasi package/dependensi, atau migrasi awal.
  - *Contoh:* `chore(scaffold): setup laravel with livewire, tailwind, and alpine`
- **`docs`**: Pembaruan dokumentasi, PRD, atau dokumen desain.
  - *Contoh:* `docs(spec): add hybrid builder technical specifications`

### Ketentuan Tambahan:
1. Deskripsi harus jelas, spesifik, dan mencerminkan apa yang diubah.
2. Setiap kali subagent menyelesaikan satu task TDD, subagent wajib melakukan commit otomatis dengan format di atas sebelum melaporkan tugas selesai.
