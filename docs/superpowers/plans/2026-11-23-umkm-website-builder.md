# UMKM Website Builder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun platform SaaS web builder katalog digital untuk UMKM berbasis Laravel, dengan fitur live preview instan (side-by-side split view via postMessage), 3 pilihan template responsif, kustomisasi visual, publikasi URL unik, dan integrasi pemesanan via WhatsApp.

**Architecture:** Monolitik Laravel full-stack dengan skema multi-tenant berbasis `business_id`. Dashboard hybrid memisahkan pengelolaan katalog data (tabel lapang) dan studio kustomisasi visual (side-by-side iframe split view). Komunikasi real-time preview menggunakan `window.postMessage` ke iframe CSS variables untuk respon instan (0ms) tanpa reload.

**Tech Stack:** PHP 8.5+, Laravel 11/12, Livewire 3, Alpine.js, Tailwind CSS, MySQL (dengan SQLite support untuk automated testing), PHPUnit/Pest.

**Spec:** [docs/superpowers/specs/2026-11-23-umkm-website-builder-design.md](file:///e:/Projek/ag/inamikro_webbuilder/docs/superpowers/specs/2026-11-23-umkm-website-builder-design.md)

## Global Constraints
- **Conventional Git Commits:** Setiap subagent wajib melakukan commit Git secara otomatis dengan format terstruktur: `<type>(<scope>): <deskripsi singkat>` (misal: `feat(catalog): ...`, `chore(scaffold): ...`, `test(auth): ...`).
- Laravel multi-tenant isolation: Setiap akses data UMKM wajib diverifikasi kepemilikannya melalui `business_id` dan Laravel Policies (anti cross-tenant data leakage).
- Route slug reservation: Slug website publik tidak boleh bentrok dengan rute sistem reserved (`admin`, `dashboard`, `login`, `register`, `builder`, `api`, `logout`).
- Single source of truth untuk URL publik adalah `websites.slug`.
- Website berstatus selain `published` (`draft` atau `unpublished`) wajib mengembalikan HTTP 404 pada rute publik `/{slug}`.
- Kompatibilitas template: Pergantian template tidak boleh mengubah atau menghapus data produk, profil bisnis, atau banner (`TEMP-05`).

## Review Focus
1. **Cross-Tenant ID Tampering:** Pengguna UMKM A mencoba mengakses URL `/dashboard/products/{id}/edit` milik UMKM B -> Harus mengembalikan HTTP 403 Forbidden.
2. **Unpublished Website Privacy:** Pengunjung publik mengakses URL `/{slug}` milik website yang berstatus `draft` atau `unpublished` -> Harus mengembalikan HTTP 404 Not Found.
3. **Publish Incompleteness Validation:** Pengguna mencoba mempublikasikan website saat belum ada produk atau belum mengisi nomor WhatsApp -> Harus ditolak dengan pesan validasi yang jelas.
4. **Reserved Route Collision:** Pengguna mencoba mendaftarkan slug website seperti `admin`, `login`, atau `dashboard` -> Validasi harus menolak dan meminta pengguna memilih slug lain.
5. **WhatsApp Message Formatting:** Nomor WhatsApp dengan awalan lokal (`0812...`) atau tanda plus (`+62...`) harus dinormalisasi menjadi format internasional tanpa karakter non-digit (`62812...`) agar tautan `wa.me` berfungsi valid di desktop dan mobile.

---

### Task 1: Scaffolding Project, Git, & Core Dependencies

**Files:**
- Create: `composer.json`, `package.json`, `.env.example`, `.env`, `vite.config.js`, `tailwind.config.js`
- Test: `tests/Feature/HealthCheckTest.php`

**Interfaces:**
- Produces: Proyek Laravel terkonfigurasi dengan Livewire 3, Tailwind CSS, Alpine.js, dan database testing environment.

- [ ] **Step 1: Inisialisasi Git repository dan periksa workspace**
```bash
git init
```

- [ ] **Step 2: Buat proyek Laravel di workspace saat ini**
Jalankan composer create-project atau inisialisasi file Laravel standar di root direktori jika belum ada, lalu install Livewire 3 dan Tailwind CSS.
```bash
composer create-project laravel/laravel . --prefer-dist
composer require livewire/livewire
npm install -D tailwindcss postcss autoprefixer @tailwindcss/forms
npx tailwindcss init -p
npm install alpinejs
```

- [ ] **Step 3: Konfigurasi Tailwind CSS dan build assets**
Tambahkan template path ke `tailwind.config.js` dan import Tailwind di `resources/css/app.css`. Jalankan `npm run build` untuk memverifikasi kompilasi.

- [ ] **Step 4: Tulis test dasar health check `tests/Feature/HealthCheckTest.php`**
```php
public function test_application_returns_successful_response(): void
{
    $response = $this->get('/');
    $response->assertStatus(200);
}
```

- [ ] **Step 5: Jalankan test dan pastikan PASS**
Run: `php artisan test --filter=HealthCheckTest`
Expected: PASS

- [ ] **Step 6: Commit awal**
```bash
git add .
git commit -m "chore: scaffold laravel application with livewire, alpine, and tailwind"
```

---

### Task 2: Multi-Tenant Database Schema, Migrations, & Seeders

**Files:**
- Create: `database/migrations/*_create_umkm_builder_tables.php`
- Create: `app/Models/User.php`, `app/Models/Business.php`, `app/Models/BusinessCategory.php`, `app/Models/Template.php`, `app/Models/Website.php`, `app/Models/Theme.php`, `app/Models/Product.php`, `app/Models/ProductCategory.php`, `app/Models/ProductImage.php`, `app/Models/Banner.php`, `app/Models/BusinessGallery.php`, `app/Models/Testimonial.php`, `app/Models/WebsiteVisit.php`, `app/Models/WhatsappClick.php`
- Create: `database/seeders/DatabaseSeeder.php`, `database/seeders/TemplateSeeder.php`, `database/seeders/CategorySeeder.php`, `database/seeders/DemoUmkmSeeder.php`
- Test: `tests/Feature/DatabaseSchemaTest.php`

**Interfaces:**
- Consumes: Koneksi database MySQL / SQLite in-memory test.
- Produces: 14 tabel database lengkap sesuai PRD dengan foreign keys, indexing, dan relasi Eloquent (`User->hasOne(Business)`, `Business->hasOne(Website)`, `Business->hasMany(Product)`, `Website->hasOne(Theme)`, dll).

- [ ] **Step 1: Tulis failing test relasi database di `tests/Feature/DatabaseSchemaTest.php`**
```php
public function test_user_business_website_theme_relationships_work(): void
{
    $user = User::factory()->create(['role' => 'umkm']);
    $category = BusinessCategory::create(['name' => 'Kuliner', 'slug' => 'kuliner']);
    $business = Business::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Kopi Mantap',
        'slug' => 'kopi-mantap',
        'whatsapp' => '08123456789'
    ]);
    $template = Template::create([
        'name' => 'Minimalist Store',
        'slug' => 'minimalist',
        'view_path' => 'templates.minimalist.index',
        'is_active' => true
    ]);
    $website = Website::create([
        'business_id' => $business->id,
        'template_id' => $template->id,
        'slug' => 'kopi-mantap',
        'status' => 'draft'
    ]);
    $theme = Theme::create([
        'website_id' => $website->id,
        'primary_color' => '#3B82F6',
        'secondary_color' => '#1E40AF',
        'font_family' => 'Inter'
    ]);

    $this->assertEquals('Kopi Mantap', $user->business->name);
    $this->assertEquals('kopi-mantap', $business->website->slug);
    $this->assertEquals('#3B82F6', $website->theme->primary_color);
}
```

- [ ] **Step 2: Jalankan test untuk memastikan FAIL**
Run: `php artisan test --filter=DatabaseSchemaTest`
Expected: FAIL (tabel/model belum ada).

- [ ] **Step 3: Buat migrasi untuk 14 tabel database**
Terapkan struktur tabel sesuai Section 8.2 PRD dan Design Doc Section 4. Tambahkan indeks pada `business_id`, `website_id`, dan unique index pada `websites.slug`.

- [ ] **Step 4: Buat Model Eloquent dengan relasi lengkap**
Definisikan relasi `belongsTo`, `hasMany`, `hasOne`, serta `$casts` (termasuk `operating_hours` sebagai json array, `price` sebagai decimal, dll).

- [ ] **Step 5: Buat Seeder (`TemplateSeeder` untuk 3 template bawaan: minimalist, food, modern; `CategorySeeder`, dan `DemoUmkmSeeder`)**

- [ ] **Step 6: Jalankan migrasi dan test**
Run: `php artisan migrate --seed; php artisan test --filter=DatabaseSchemaTest`
Expected: PASS

- [ ] **Step 7: Commit**
```bash
git add database/ app/Models/ tests/
git commit -m "feat: add 14-table database migrations, models, relationships, and seeders"
```

---

### Task 3: Authentication, Role Middleware & Multi-Tenant Authorization

**Files:**
- Create: `app/Http/Middleware/EnsureAdmin.php`, `app/Http/Middleware/EnsureBusinessOwner.php`
- Create: `app/Policies/BusinessPolicy.php`, `app/Policies/ProductPolicy.php`, `app/Policies/WebsitePolicy.php`
- Create: `app/Http/Controllers/Auth/AuthController.php` (atau Laravel Breeze / Fortify routes)
- Create: `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`
- Test: `tests/Feature/AuthAndTenantIsolationTest.php`

**Interfaces:**
- Consumes: Model `User`, `Business`, `Product`, `Website`.
- Produces: Middleware `admin`, `umkm.owner`, otentikasi login/register, dan policy untuk mencegah UMKM lain mengakses data yang bukan miliknya.

- [ ] **Step 1: Tulis failing test di `tests/Feature/AuthAndTenantIsolationTest.php`**
```php
public function test_umkm_cannot_access_or_edit_another_umkm_product(): void
{
    $userA = User::factory()->create(['role' => 'umkm']);
    $businessA = Business::factory()->create(['user_id' => $userA->id]);

    $userB = User::factory()->create(['role' => 'umkm']);
    $businessB = Business::factory()->create(['user_id' => $userB->id]);
    $productB = Product::factory()->create(['business_id' => $businessB->id]);

    $response = $this->actingAs($userA)->get("/dashboard/products/{$productB->id}/edit");
    $response->assertStatus(403);
}

public function test_non_admin_cannot_access_admin_dashboard(): void
{
    $umkm = User::factory()->create(['role' => 'umkm']);
    $response = $this->actingAs($umkm)->get('/admin/dashboard');
    $response->assertStatus(403);
}
```

- [ ] **Step 2: Jalankan test untuk memverifikasi FAIL**
Run: `php artisan test --filter=AuthAndTenantIsolationTest`
Expected: FAIL

- [ ] **Step 3: Implementasikan Auth Controller, Register, Login, Logout**
Pastikan saat register, jika role adalah `umkm`, otomatis dibuatkan entri default `Business`, `Website` (status `draft`), dan `Theme` default.

- [ ] **Step 4: Implementasikan Middleware & Policies**
- Daftarkan `EnsureAdmin` dan `EnsureBusinessOwner`.
- Buat `ProductPolicy`: method `update`, `delete`, `view` memeriksa `$user->business->id === $product->business_id`.

- [ ] **Step 5: Jalankan test dan pastikan PASS**
Run: `php artisan test --filter=AuthAndTenantIsolationTest`
Expected: PASS

- [ ] **Step 6: Commit**
```bash
git add app/Http/Middleware/ app/Policies/ app/Http/Controllers/Auth/ resources/views/auth/ tests/
git commit -m "feat: implement authentication, role middleware, and multi-tenant authorization policies"
```

---

### Task 4: UMKM Dashboard Layout & Business Profile Management

**Files:**
- Create: `resources/views/layouts/dashboard.blade.php`
- Create: `app/Livewire/Dashboard/Index.php`, `resources/views/livewire/dashboard/index.blade.php`
- Create: `app/Livewire/Dashboard/ProfileForm.php`, `resources/views/livewire/dashboard/profile-form.blade.php`
- Test: `tests/Feature/DashboardProfileTest.php`

**Interfaces:**
- Consumes: Auth session user dan model `Business`.
- Produces: Halaman `/dashboard` (ringkasan statistik: total produk, total visitor, total klik WA, status website) dan `/dashboard/profile` (update nama usaha, kategori, logo, deskripsi, alamat, kontak WhatsApp, jam operasional).

- [ ] **Step 1: Tulis failing test di `tests/Feature/DashboardProfileTest.php`**
```php
public function test_umkm_can_update_business_profile_and_contact(): void
{
    $user = User::factory()->create(['role' => 'umkm']);
    $business = Business::factory()->create(['user_id' => $user->id, 'name' => 'Nama Lama']);

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set('name', 'Kedai Kopi Inamikro')
        ->set('whatsapp', '081298765432')
        ->set('description', 'Kopi asli pilihan nusantara.')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals('Kedai Kopi Inamikro', $business->fresh()->name);
    $this->assertEquals('081298765432', $business->fresh()->whatsapp);
}
```

- [ ] **Step 2: Jalankan test untuk memverifikasi FAIL**
Run: `php artisan test --filter=DashboardProfileTest`
Expected: FAIL

- [ ] **Step 3: Buat master layout `resources/views/layouts/dashboard.blade.php`**
Layout responsif dengan sidebar navigasi: Dashboard, Profil Bisnis, Katalog Produk, Banner & Galeri, Tampilan Website (Builder), dan Pengaturan Akun.

- [ ] **Step 4: Implementasikan `Dashboard/Index` dan `ProfileForm`**
Sertakan upload file logo ke storage publik (`public/logos`), validasi ekstensi gambar (JPG, PNG, WebP max 2MB), dan pembaruan kolom `businesses`.

- [ ] **Step 5: Jalankan test dan pastikan PASS**
Run: `php artisan test --filter=DashboardProfileTest`
Expected: PASS

- [ ] **Step 6: Commit**
```bash
git add app/Livewire/Dashboard/ resources/views/ tests/
git commit -m "feat: add dashboard layout, metrics summary, and business profile management"
```

---

### Task 5: Product & Banner Management (Full-Page CRUD)

**Files:**
- Create: `app/Livewire/Products/ProductList.php`, `resources/views/livewire/products/product-list.blade.php`
- Create: `app/Livewire/Products/ProductForm.php`, `resources/views/livewire/products/product-form.blade.php`
- Create: `app/Livewire/Banners/BannerManager.php`, `resources/views/livewire/banners/banner-manager.blade.php`
- Test: `tests/Feature/ProductManagementTest.php`

**Interfaces:**
- Consumes: `Business` milik user yang sedang aktif.
- Produces: Rute `/dashboard/products`, `/dashboard/products/create`, `/dashboard/products/{id}/edit`, dan `/dashboard/banners`. Fitur CRUD produk, upload gambar produk multiple, diskon, ketersediaan produk, dan pengelolaan hero banner.

- [ ] **Step 1: Tulis failing test di `tests/Feature/ProductManagementTest.php`**
```php
public function test_umkm_can_create_and_delete_product_with_images(): void
{
    $user = User::factory()->create(['role' => 'umkm']);
    $business = Business::factory()->create(['user_id' => $user->id]);

    Storage::fake('public');
    $image = UploadedFile::fake()->image('produk1.jpg');

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('name', 'Kaos Polos Cotton')
        ->set('price', 85000)
        ->set('description', 'Bahan katun combed 30s dingin.')
        ->set('is_available', true)
        ->set('images', [$image])
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('business_id', $business->id)->first();
    $this->assertNotNull($product);
    $this->assertEquals(85000, (float)$product->price);
    $this->assertCount(1, $product->images);
}
```

- [ ] **Step 2: Jalankan test untuk memverifikasi FAIL**
Run: `php artisan test --filter=ProductManagementTest`
Expected: FAIL

- [ ] **Step 3: Implementasikan `ProductList` dan `ProductForm`**
- Tabel produk dengan aksi Edit, Toggle Ketersediaan, Hapus, dan Filter kategori.
- Form create/edit produk dengan input nama, slug otomatis, kategori, harga, harga diskon, label (Best Seller / Promo), varian teks sederhana, dan upload multiple gambar (`ProductImage`).

- [ ] **Step 4: Implementasikan `BannerManager`**
Upload gambar banner utama hero section, input judul banner, subtitle, teks tombol CTA, dan URL tombol CTA.

- [ ] **Step 5: Jalankan test dan pastikan PASS**
Run: `php artisan test --filter=ProductManagementTest`
Expected: PASS

- [ ] **Step 6: Commit**
```bash
git add app/Livewire/Products/ app/Livewire/Banners/ resources/views/livewire/ tests/
git commit -m "feat: implement product catalog and banner management with image uploading"
```

---

### Task 6: Visual Studio Builder (Side-by-side Split View & PostMessage Bridge)

**Files:**
- Create: `resources/views/layouts/builder.blade.php`
- Create: `app/Livewire/Builder/Studio.php`, `resources/views/livewire/builder/studio.blade.php`
- Create: `app/Http/Controllers/Builder/PreviewController.php`
- Test: `tests/Feature/StudioBuilderTest.php`

**Interfaces:**
- Consumes: Data `Website`, `Theme`, `Template`, `Business` milik user.
- Produces: Halaman `/dashboard/builder` (layar penuh split-view: kontrol di sisi kiri, iframe preview di sisi kanan, selector template, color picker, font picker, viewport switch desktop/tablet/mobile, dan tombol Publish).

- [ ] **Step 1: Tulis failing test di `tests/Feature/StudioBuilderTest.php`**
```php
public function test_umkm_can_access_builder_and_update_theme_settings(): void
{
    $user = User::factory()->create(['role' => 'umkm']);
    $business = Business::factory()->create(['user_id' => $user->id]);
    $template = Template::factory()->create(['slug' => 'minimalist']);
    $website = Website::factory()->create(['business_id' => $business->id, 'template_id' => $template->id]);
    $theme = Theme::factory()->create(['website_id' => $website->id, 'primary_color' => '#000000']);

    Livewire::actingAs($user)
        ->test(Studio::class)
        ->set('primaryColor', '#EF4444')
        ->set('fontFamily', 'Poppins')
        ->call('saveTheme')
        ->assertHasNoErrors();

    $this->assertEquals('#EF4444', $theme->fresh()->primary_color);
    $this->assertEquals('Poppins', $theme->fresh()->font_family);
}
```

- [ ] **Step 2: Jalankan test untuk memverifikasi FAIL**
Run: `php artisan test --filter=StudioBuilderTest`
Expected: FAIL

- [ ] **Step 3: Buat layout khusus `resources/views/layouts/builder.blade.php`**
Header ringkas (tombol kembali ke Dashboard, status website, viewport switcher desktop/tablet/mobile, tombol Publish/Unpublish), panel kiri (lebar ~380px) untuk kontrol kustomisasi, panel kanan area iframe preview.

- [ ] **Step 4: Integrasikan Alpine.js bridge `postMessage` pada `Studio.php` & blade view**
- Input color picker dan font family memicu event Alpine:
```javascript
$watch('primaryColor', (val) => {
    document.getElementById('preview-iframe').contentWindow.postMessage({
        type: 'UPDATE_THEME',
        payload: { primaryColor: val, secondaryColor: secondaryColor, font: fontFamily }
    }, '*');
});
```
- Tombol simpan perubahan / auto-save menyimpan nilai ke tabel `themes`.

- [ ] **Step 5: Buat rute internal preview `/builder/preview/{website_id}`**
Dikendalikan oleh `PreviewController@show`, memverifikasi bahwa user yang sedang login adalah pemilik website tersebut.

- [ ] **Step 6: Jalankan test dan pastikan PASS**
Run: `php artisan test --filter=StudioBuilderTest`
Expected: PASS

- [ ] **Step 7: Commit**
```bash
git add resources/views/layouts/builder.blade.php app/Livewire/Builder/ app/Http/Controllers/Builder/ tests/
git commit -m "feat: build side-by-side visual studio builder with postMessage iframe bridge"
```

---

### Task 7: Three Landing Page Templates & PostMessage Theme Listener

**Files:**
- Create: `resources/views/templates/layouts/base.blade.php`
- Create: `resources/views/templates/minimalist/index.blade.php`, `resources/views/templates/minimalist/detail.blade.php`
- Create: `resources/views/templates/food/index.blade.php`, `resources/views/templates/food/detail.blade.php`
- Create: `resources/views/templates/modern/index.blade.php`, `resources/views/templates/modern/detail.blade.php`
- Test: `tests/Feature/TemplateRenderingTest.php`

**Interfaces:**
- Consumes: Object `$website` lengkap dengan relasi `business`, `theme`, `banners`, `products`, `galleries`.
- Produces: 3 tampilan desain landing page yang berbeda secara visual namun mengambil data dari skema yang sama, mendukung injeksi CSS variables dinamis (`--color-primary`, dll) dan event listener `postMessage`.

- [ ] **Step 1: Tulis test rendering untuk ketiga template di `tests/Feature/TemplateRenderingTest.php`**
```php
public function test_all_three_templates_render_successfully_with_same_business_data(): void
{
    $business = Business::factory()->create(['name' => 'Usaha Nusantara']);
    Product::factory()->count(3)->create(['business_id' => $business->id]);
    Banner::factory()->create(['business_id' => $business->id, 'title' => 'Selamat Datang']);

    foreach (['minimalist', 'food', 'modern'] as $slug) {
        $template = Template::where('slug', $slug)->first();
        $website = Website::factory()->create(['business_id' => $business->id, 'template_id' => $template->id]);
        Theme::factory()->create(['website_id' => $website->id]);

        $view = view($template->view_path, ['website' => $website->load(['business', 'theme', 'products', 'banners'])]);
        $view->assertSee('Usaha Nusantara');
        $view->assertSee('Selamat Datang');
    }
}
```

- [ ] **Step 2: Jalankan test untuk memverifikasi FAIL**
Run: `php artisan test --filter=TemplateRenderingTest`
Expected: FAIL

- [ ] **Step 3: Buat master layout template `resources/views/templates/layouts/base.blade.php`**
Layout dasar memuat Tailwind CSS, Google Fonts, meta tags, style CSS Variables:
```html
<style>
  :root {
    --color-primary: {{ $website->theme->primary_color ?? '#3B82F6' }};
    --color-secondary: {{ $website->theme->secondary_color ?? '#1E40AF' }};
    --font-family: '{{ $website->theme->font_family ?? 'Inter' }}', sans-serif;
  }
</style>
```
Sertakan listener JavaScript untuk pesan `postMessage`:
```javascript
window.addEventListener('message', (event) => {
    if (event.data?.type === 'UPDATE_THEME') {
        const { primaryColor, secondaryColor, font } = event.data.payload;
        if (primaryColor) document.documentElement.style.setProperty('--color-primary', primaryColor);
        if (secondaryColor) document.documentElement.style.setProperty('--color-secondary', secondaryColor);
        if (font) document.body.style.fontFamily = font;
    }
});
```

- [ ] **Step 4: Buat Template 1 - Minimalist Store**
Karakter: Desain bersih, foto produk besar berbentuk grid, dominasi warna netral, fokus pada fashion & lifestyle.

- [ ] **Step 5: Buat Template 2 - Food & Beverage**
Karakter: Banner foto makanan hero besar, jam operasional & lokasi mencolok, badge kategori menu, dan tombol order langsung.

- [ ] **Step 6: Buat Template 3 - Modern Business**
Karakter: Hero section dengan CTA tajam, section value proposition / keunggulan usaha, galeri usaha, dan testimoni.

- [ ] **Step 7: Jalankan test dan pastikan PASS**
Run: `php artisan test --filter=TemplateRenderingTest`
Expected: PASS

- [ ] **Step 8: Commit**
```bash
git add resources/views/templates/ tests/
git commit -m "feat: implement 3 responsive landing page templates with dynamic css variables and postmessage listener"
```

---

### Task 8: Website Publishing Service & Public Routing

**Files:**
- Create: `app/Services/WebsitePublishValidator.php`
- Create: `app/Services/SlugService.php`
- Create: `app/Http/Controllers/Public/PublicWebsiteController.php`
- Test: `tests/Feature/PublishAndPublicRoutingTest.php`

**Interfaces:**
- Consumes: Model `Website`, `Business`, `Product`.
- Produces: Mekanisme publish/unpublish, validasi kelayakan publish, rute publik `/{slug}` dan `/{slug}/produk/{product_slug}`.

- [ ] **Step 1: Tulis failing test di `tests/Feature/PublishAndPublicRoutingTest.php`**
```php
public function test_website_cannot_be_published_if_requirements_not_met(): void
{
    $business = Business::factory()->create(['whatsapp' => null]);
    $website = Website::factory()->create(['business_id' => $business->id, 'status' => 'draft']);

    $validator = app(WebsitePublishValidator::class);
    $result = $validator->validate($website);

    $this->assertFalse($result->isValid());
    $this->assertContains('Nomor WhatsApp wajib diisi sebelum publikasi.', $result->getErrors());
}

public function test_public_cannot_access_unpublished_website(): void
{
    $business = Business::factory()->create();
    $website = Website::factory()->create(['business_id' => $business->id, 'slug' => 'toko-draft', 'status' => 'draft']);

    $response = $this->get('/toko-draft');
    $response->assertStatus(404);
}

public function test_public_can_access_published_website_and_product_detail(): void
{
    $business = Business::factory()->create(['name' => 'Toko Laris']);
    $template = Template::where('slug', 'minimalist')->first();
    $website = Website::factory()->create([
        'business_id' => $business->id,
        'template_id' => $template->id,
        'slug' => 'toko-laris',
        'status' => 'published'
    ]);
    Theme::factory()->create(['website_id' => $website->id]);
    $product = Product::factory()->create(['business_id' => $business->id, 'slug' => 'baju-batik', 'name' => 'Baju Batik']);

    $response = $this->get('/toko-laris');
    $response->assertStatus(200);
    $response->assertSee('Toko Laris');

    $detailResponse = $this->get('/toko-laris/produk/baju-batik');
    $detailResponse->assertStatus(200);
    $detailResponse->assertSee('Baju Batik');
}
```

- [ ] **Step 2: Jalankan test untuk memverifikasi FAIL**
Run: `php artisan test --filter=PublishAndPublicRoutingTest`
Expected: FAIL

- [ ] **Step 3: Implementasikan `SlugService`**
Menyediakan method validasi slug unik dan pemeriksaan terhadap kata-kata terlarang (`['admin', 'login', 'register', 'dashboard', 'builder', 'api', 'logout', 'css', 'js', 'storage']`).

- [ ] **Step 4: Implementasikan `WebsitePublishValidator`**
Memeriksa: nama bisnis terisi, nomor WhatsApp valid, minimal 1 banner terunggah, minimal 1 produk aktif, dan slug valid.

- [ ] **Step 5: Implementasikan `PublicWebsiteController`**
- Resolve model `Website` via slug.
- Pastikan status website adalah `published`. Jika `draft` atau `unpublished`, return `abort(404)`.
- Rekam entri ke tabel `website_visits` dengan anonymized visitor hash.
- Render template Blade yang sesuai (`$website->template->view_path`).

- [ ] **Step 6: Jalankan test dan pastikan PASS**
Run: `php artisan test --filter=PublishAndPublicRoutingTest`
Expected: PASS

- [ ] **Step 7: Commit**
```bash
git add app/Services/ app/Http/Controllers/Public/ tests/
git commit -m "feat: implement publish validation, slug reservation, and public website routing"
```

---

### Task 9: WhatsApp Ordering CTA & Conversion Tracking

**Files:**
- Create: `app/Services/WhatsAppUrlGenerator.php`
- Modify: `app/Http/Controllers/Public/PublicWebsiteController.php`
- Modify: `resources/views/templates/layouts/base.blade.php` (floating WA button)
- Test: `tests/Feature/WhatsAppOrderingTest.php`

**Interfaces:**
- Consumes: Nomor WhatsApp dari `Business`, data produk yang dipilih.
- Produces: Link `https://wa.me/62...` dengan teks dinamis terformat, dan endpoint pencatat analytics `POST /{slug}/wa-click`.

- [ ] **Step 1: Tulis failing test di `tests/Feature/WhatsAppOrderingTest.php`**
```php
public function test_whatsapp_url_generator_cleans_number_and_formats_product_message(): void
{
    $generator = new WhatsAppUrlGenerator();
    $url = $generator->generate(
        rawPhone: '0812-3456-7890',
        businessName: 'Batik Cantik',
        productName: 'Kemeja Sutra',
        price: 150000
    );

    $this->assertStringStartsWith('https://wa.me/6281234567890', $url);
    $this->assertStringContainsString('Kemeja%20Sutra', $url);
}

public function test_clicking_whatsapp_button_records_analytics_event(): void
{
    $business = Business::factory()->create();
    $website = Website::factory()->create(['business_id' => $business->id, 'slug' => 'toko-wa', 'status' => 'published']);
    $product = Product::factory()->create(['business_id' => $business->id]);

    $response = $this->post("/toko-wa/wa-click", [
        'product_id' => $product->id,
        'source' => 'product_detail'
    ]);
    $response->assertStatus(200);

    $this->assertDatabaseHas('whatsapp_clicks', [
        'website_id' => $website->id,
        'product_id' => $product->id,
        'source' => 'product_detail'
    ]);
}
```

- [ ] **Step 2: Jalankan test untuk memverifikasi FAIL**
Run: `php artisan test --filter=WhatsAppOrderingTest`
Expected: FAIL

- [ ] **Step 3: Implementasikan `WhatsAppUrlGenerator`**
Normalisasi nomor HP (mengubah `08...` atau `+62...` menjadi `62...`), menyusun template pesan terstruktur sesuai kebutuhan katalog dan floating button.

- [ ] **Step 4: Tambahkan endpoint `POST /{slug}/wa-click` di `PublicWebsiteController`**
Merekam klik ke tabel `whatsapp_clicks` dan merespons dengan JSON success.

- [ ] **Step 5: Integrasikan floating button dan product order CTA di ketiga template**

- [ ] **Step 6: Jalankan test dan pastikan PASS**
Run: `php artisan test --filter=WhatsAppOrderingTest`
Expected: PASS

- [ ] **Step 7: Commit**
```bash
git add app/Services/WhatsAppUrlGenerator.php app/Http/Controllers/Public/ tests/ resources/views/templates/
git commit -m "feat: implement whatsapp url generator and conversion tracking analytics"
```

---

### Task 10: Superadmin Dashboard & Platform Management

**Files:**
- Create: `app/Livewire/Admin/Dashboard.php`, `resources/views/livewire/admin/dashboard.blade.php`
- Create: `app/Livewire/Admin/UserManagement.php`, `resources/views/livewire/admin/user-management.blade.php`
- Create: `app/Livewire/Admin/TemplateManagement.php`, `resources/views/livewire/admin/template-management.blade.php`
- Test: `tests/Feature/AdminManagementTest.php`

**Interfaces:**
- Consumes: Middleware `auth`, `admin`.
- Produces: Halaman `/admin/dashboard`, `/admin/users` (suspend/aktifkan UMKM), dan `/admin/templates` (toggle aktif/nonaktif template).

- [ ] **Step 1: Tulis failing test di `tests/Feature/AdminManagementTest.php`**
```php
public function test_admin_can_view_platform_stats_and_toggle_template_status(): void
{
    $admin = User::factory()->create(['role' => 'admin']);
    $template = Template::factory()->create(['is_active' => true]);

    Livewire::actingAs($admin)
        ->test(TemplateManagement::class)
        ->call('toggleStatus', $template->id)
        ->assertHasNoErrors();

    $this->assertFalse($template->fresh()->is_active);
}
```

- [ ] **Step 2: Jalankan test untuk memverifikasi FAIL**
Run: `php artisan test --filter=AdminManagementTest`
Expected: FAIL

- [ ] **Step 3: Implementasikan Admin Livewire components**
- `Admin/Dashboard`: Menampilkan total UMKM terdaftar, total website berstatus published, total traffic platform, total klik WA.
- `Admin/UserManagement`: Daftar akun UMKM, status website mereka, dan opsi aktivasi/penonaktifan akun.
- `Admin/TemplateManagement`: Daftar template landing page dengan toggle aktif/non-aktif.

- [ ] **Step 4: Jalankan test dan pastikan PASS**
Run: `php artisan test --filter=AdminManagementTest`
Expected: PASS

- [ ] **Step 5: Commit**
```bash
git add app/Livewire/Admin/ resources/views/livewire/admin/ tests/
git commit -m "feat: implement superadmin dashboard, tenant user management, and template toggles"
```

---

### Task 11: End-to-End Workflow Verification & Seeded Demo Testing

**Files:**
- Create: `tests/Feature/FullUmkmJourneyTest.php`
- Test: `tests/Feature/FullUmkmJourneyTest.php`

**Interfaces:**
- Consumes: Seluruh modul yang telah dibangun dari Task 1 hingga Task 10.
- Produces: Verifikasi menyeluruh alur lengkap pengguna: Registrasi -> Isi Profil -> Tambah Produk -> Atur Tema di Studio -> Publish -> Buka Publik -> Klik WhatsApp -> Verifikasi Analitik.

- [ ] **Step 1: Tulis integration test alur penuh UMKM di `tests/Feature/FullUmkmJourneyTest.php`**
```php
public function test_complete_umkm_lifecycle_from_registration_to_public_whatsapp_order(): void
{
    // 1. Registrasi
    $response = $this->post('/register', [
        'name' => 'Budi Santoso',
        'email' => 'budi@inamikro.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);
    $response->assertRedirect('/dashboard');

    $user = User::where('email', 'budi@inamikro.com')->first();
    $business = $user->business;
    $website = $business->website;

    // 2. Isi Profil Usaha & WA
    $business->update([
        'name' => 'Kue Tradisional Budi',
        'whatsapp' => '081234567890',
        'description' => 'Menyediakan aneka kue basah dan jajanan pasar gurih manis.'
    ]);

    // 3. Tambah Produk
    $product = Product::create([
        'business_id' => $business->id,
        'name' => 'Lapis Legit Premium',
        'slug' => 'lapis-legit-premium',
        'price' => 125000,
        'description' => 'Lapis legit dengan butter wisman asli.',
        'is_available' => true
    ]);

    // 4. Tambah Banner
    Banner::create([
        'business_id' => $business->id,
        'title' => 'Kelezatan Asli Nusantara',
        'image' => 'banners/demo.jpg',
        'is_active' => true
    ]);

    // 5. Atur Tema di Studio
    $website->theme->update([
        'primary_color' => '#10B981',
        'font_family' => 'Poppins'
    ]);

    // 6. Validasi & Publish Website
    $website->update([
        'slug' => 'kue-budi',
        'status' => 'published',
        'published_at' => now()
    ]);

    // 7. Pengunjung Publik Mengakses Halaman
    $publicPage = $this->get('/kue-budi');
    $publicPage->assertStatus(200);
    $publicPage->assertSee('Kue Tradisional Budi');
    $publicPage->assertSee('Lapis Legit Premium');

    // 8. Pengunjung Klik WhatsApp
    $waResponse = $this->post('/kue-budi/wa-click', [
        'product_id' => $product->id,
        'source' => 'product_card'
    ]);
    $waResponse->assertStatus(200);

    // 9. Cek Dashboard Metrik Bertambah
    $this->assertDatabaseHas('whatsapp_clicks', ['website_id' => $website->id]);
    $this->assertDatabaseHas('website_visits', ['website_id' => $website->id]);
}
```

- [ ] **Step 2: Jalankan full test suite aplikasi**
Run: `php artisan test`
Expected: Seluruh test (Feature & Unit) berstatus PASS.

- [ ] **Step 3: Commit akhir**
```bash
git add tests/Feature/FullUmkmJourneyTest.php
git commit -m "test: add comprehensive end-to-end integration test for umkm lifecycle"
```
