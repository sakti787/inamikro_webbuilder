# Fitur Kartu Keunggulan Layanan (Mengapa Memilih Kami) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan fitur kartu keunggulan layanan ("Mengapa Memilih Kami") dengan kontrol switch toggle On/Off di form profil usaha dan menampilkannya di landing page template Minimalist Store tanpa ikon.

**Architecture:** Data disimpan sebagai atribut JSON `features` pada tabel `businesses`. Komponen Livewire `ProfileForm` menyediakan toggle On/Off dan form input reaktif yang secara otomatis mengisi template default saat pertama kali diaktifkan. Template `Minimalist Store` merender section 3 kartu keunggulan secara kondisional jika status `features.enabled` bernilai true.

**Tech Stack:** Laravel 12 / PHP 8.5, Livewire 3, Alpine.js, Tailwind CSS, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-24-business-features-cards-design.md`

## Global Constraints

- Conventional Commits: `<type>(<scope>): <deskripsi singkat>` (misal: `feat(profile): ...`, `test(template): ...`).
- Laravel Pint: Wajib jalankan `vendor/bin/pint --dirty --format agent` sebelum setiap commit.
- Bahasa UI: Seluruh label, placeholder, pesan validasi, dan teks default wajib menggunakan Bahasa Indonesia.
- Multi-tenancy: Mutasi data hanya boleh dilakukan terhadap `$user->business` milik akun yang terautentikasi.
- Desain Kartu: Tanpa ikon (*iconless*), tata letak bersih dan elegan sesuai gaya Minimalist Store.

## Review Focus

1. **Atribut `features` bernilai `null` pada data lama:** Website landing page dan form profil tidak boleh melempar exception saat membaca bisnis yang belum memiliki data `features`.
2. **Validasi Kondisional saat Aktif:** Ketika toggle `features_enabled` bernilai true, judul dan 3 kartu wajib terisi dan tidak boleh kosong.
3. **Preservasi Nilai saat Toggle Off:** Jika pengguna mematikan toggle setelah melakukan edit, data tidak boleh corrupt atau menyebabkan crash saat disimpan.
4. **Proteksi Overwrite Template:** Mengubah toggle ON tidak boleh menimpa teks yang telah dikustomisasi pengguna jika data sudah ada sebelumnya.
5. **Isolasi Tenant:** Pengguna tidak dapat memodifikasi fitur keunggulan milik bisnis lain.

---

### Task 1: Database Migration & Model Layer for Business Features

**Files:**
- Create: `database/migrations/2026_11_24_000001_add_features_to_businesses_table.php`
- Modify: `app/Models/Business.php:18-44`
- Modify: `tests/Feature/DatabaseSchemaTest.php`

**Interfaces:**
- Consumes: `businesses` table schema
- Produces: `Business::$features` (array casted JSON column with shape: `{ enabled: bool, badge: string, title: string, subtitle: string, items: list<array{title: string, description: string}> }`)

- [ ] **Step 1: Write the failing test**

Tambahkan method pengujian pada `tests/Feature/DatabaseSchemaTest.php`:
```php
public function test_businesses_table_has_features_json_column_and_casts_to_array(): void
{
    $user = User::factory()->create(['role' => 'umkm']);
    $business = Business::create([
        'user_id' => $user->id,
        'name' => 'Toko Fitur',
        'slug' => 'toko-fitur',
        'whatsapp' => '081234567890',
        'features' => [
            'enabled' => true,
            'badge' => 'Mengapa Memilih Kami',
            'title' => 'Keunggulan Layanan Kami',
            'subtitle' => 'Komitmen mutu terbaik',
            'items' => [
                ['title' => 'Kualitas Terjamin', 'description' => 'Kontrol ketat.'],
            ],
        ],
    ]);

    $this->assertDatabaseHas('businesses', [
        'id' => $business->id,
    ]);

    $fresh = $business->fresh();
    $this->assertIsArray($fresh->features);
    $this->assertTrue($fresh->features['enabled']);
    $this->assertSame('Mengapa Memilih Kami', $fresh->features['badge']);
    $this->assertSame('Kualitas Terjamin', $fresh->features['items'][0]['title']);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter=test_businesses_table_has_features_json_column_and_casts_to_array`
Expected: FAIL (unknown column 'features' in table 'businesses')

- [ ] **Step 3: Create migration and update Business model**

1. Jalankan `php artisan make:migration add_features_to_businesses_table --table=businesses --no-interaction`
2. Isi migration dengan menambahkan:
```php
$table->json('features')->nullable()->after('operating_hours');
```
Dan pada method `down()`:
```php
$table->dropColumn('features');
```
3. Update `app/Models/Business.php`:
- Tambahkan `'features'` ke dalam properti `$fillable`.
- Tambahkan `'features' => 'array'` ke dalam method `casts()`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan migrate --no-interaction; vendor/bin/phpunit --filter=test_businesses_table_has_features_json_column_and_casts_to_array`
Expected: PASS

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/*add_features_to_businesses_table.php app/Models/Business.php tests/Feature/DatabaseSchemaTest.php
git commit -m "feat(database): add features json column to businesses table"
```

---

### Task 2: Livewire ProfileForm Component State, Toggling & Validation (TDD)

**Files:**
- Modify: `app/Livewire/Dashboard/ProfileForm.php`
- Modify: `tests/Feature/DashboardProfileTest.php`

**Interfaces:**
- Consumes: `Business::$features`
- Produces: Livewire properties `$features_enabled`, `$features_badge`, `$features_title`, `$features_subtitle`, `$features_items` dan persistence pada method `save()`.

- [ ] **Step 1: Write the failing tests**

Tambahkan pengujian di `tests/Feature/DashboardProfileTest.php`:
1. `test_profile_form_hydrates_existing_features_from_business()`: memastikan properti terisi jika bisnis sudah punya features.
2. `test_profile_form_auto_populates_default_template_when_features_enabled_is_toggled_on()`: jika diubah ke `true` saat kosong, otomatis mengisi template kata-kata default.
3. `test_profile_form_validates_required_feature_fields_when_features_enabled()`: jika `features_enabled === true`, validasi judul dan kartu harus ada.
4. `test_profile_form_saves_features_data_successfully()`: berhasil menyimpan ke database.

```php
public function test_profile_form_auto_populates_default_template_when_features_enabled_is_toggled_on(): void
{
    $user = User::factory()->create(['role' => 'umkm']);
    Business::create([
        'user_id' => $user->id,
        'name' => 'Toko Uji',
        'slug' => 'toko-uji',
        'whatsapp' => '081234567890',
        'features' => null,
    ]);

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->assertSet('features_enabled', false)
        ->set('features_enabled', true)
        ->assertSet('features_badge', 'Mengapa Memilih Kami')
        ->assertSet('features_title', 'Keunggulan Layanan Kami')
        ->assertSet('features_subtitle', 'Komitmen kami dalam memberikan standar mutu dan kepuasan terbaik')
        ->assertCount('features_items', 3);
}

public function test_profile_form_validates_required_feature_fields_when_features_enabled(): void
{
    $user = User::factory()->create(['role' => 'umkm']);
    Business::create([
        'user_id' => $user->id,
        'name' => 'Toko Uji',
        'slug' => 'toko-uji',
        'whatsapp' => '081234567890',
    ]);

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set('features_enabled', true)
        ->set('features_title', '')
        ->set('features_items.0.title', '')
        ->call('save')
        ->assertHasErrors(['features_title', 'features_items.0.title']);
}

public function test_profile_form_saves_features_data_successfully(): void
{
    $user = User::factory()->create(['role' => 'umkm']);
    $business = Business::create([
        'user_id' => $user->id,
        'name' => 'Toko Uji',
        'slug' => 'toko-uji',
        'whatsapp' => '081234567890',
    ]);

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->set('features_enabled', true)
        ->set('features_badge', 'Alasan Memilih')
        ->set('features_title', 'Keunggulan Toko Kami')
        ->set('features_subtitle', 'Pelayanan nomor satu')
        ->set('features_items.0.title', 'Bahan Terbaik')
        ->set('features_items.0.description', 'Dari sumber pilihan.')
        ->set('features_items.1.title', 'Pengiriman Cepat')
        ->set('features_items.1.description', 'Sampai di hari yang sama.')
        ->set('features_items.2.title', 'Garansi Uang Kembali')
        ->set('features_items.2.description', '100% aman.')
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $business->fresh();
    $this->assertTrue($fresh->features['enabled']);
    $this->assertSame('Alasan Memilih', $fresh->features['badge']);
    $this->assertSame('Keunggulan Toko Kami', $fresh->features['title']);
    $this->assertSame('Bahan Terbaik', $fresh->features['items'][0]['title']);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/phpunit --filter=ProfileForm`
Expected: FAIL (properties `features_enabled`, etc. not found on component)

- [ ] **Step 3: Implement component state, hook, and save logic in `ProfileForm.php`**

1. Tambahkan konstanta template default di `ProfileForm`:
```php
public const DEFAULT_FEATURES_BADGE = 'Mengapa Memilih Kami';
public const DEFAULT_FEATURES_TITLE = 'Keunggulan Layanan Kami';
public const DEFAULT_FEATURES_SUBTITLE = 'Komitmen kami dalam memberikan standar mutu dan kepuasan terbaik';
public const DEFAULT_FEATURES_ITEMS = [
    [
        'title' => 'Kualitas Terjamin & Terpercaya',
        'description' => 'Setiap produk dan layanan melewati kontrol kualitas yang ketat demi kepuasan menyeluruh.',
    ],
    [
        'title' => 'Respon Cepat & Profesional',
        'description' => 'Tim kami selalu siap mendengarkan kebutuhan Anda dengan respon tanggap dan solusi tepat.',
    ],
    [
        'title' => 'Harga Transparan & Bersaing',
        'description' => 'Menawarkan nilai terbaik tanpa biaya tersembunyi untuk kemitraan jangka panjang yang kokoh.',
    ],
];
```
2. Deklarasikan properti publik:
```php
public bool $features_enabled = false;
public string $features_badge = self::DEFAULT_FEATURES_BADGE;
public string $features_title = self::DEFAULT_FEATURES_TITLE;
public string $features_subtitle = self::DEFAULT_FEATURES_SUBTITLE;
public array $features_items = [];
```
3. Di method `mount()`:
Muat data dari `$business->features` jika ada. Jika belum ada atau kosong, jangan set `$features_enabled = true`.
4. Tambahkan lifecycle hook Livewire `updatedFeaturesEnabled($value)`:
Jika `$value === true` dan `empty($this->features_items)`, isi dengan `self::DEFAULT_FEATURES_ITEMS`, `$this->features_badge`, `$this->features_title`, dan `$this->features_subtitle`.
5. Di method `rules()`:
Tambahkan validasi kondisional saat `$this->features_enabled` aktif:
```php
if ($this->features_enabled) {
    $rules['features_badge'] = ['nullable', 'string', 'max:100'];
    $rules['features_title'] = ['required', 'string', 'max:255'];
    $rules['features_subtitle'] = ['nullable', 'string', 'max:255'];
    $rules['features_items'] = ['required', 'array', 'size:3'];
    $rules['features_items.*.title'] = ['required', 'string', 'max:255'];
    $rules['features_items.*.description'] = ['required', 'string', 'max:500'];
}
```
6. Di method `save()`:
Simpan ke dalam array `$updateData['features'] = [ ... ]` dan simpan ke model `$business->update($updateData);`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit --filter=ProfileForm`
Expected: PASS

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Livewire/Dashboard/ProfileForm.php tests/Feature/DashboardProfileTest.php
git commit -m "feat(profile): implement features toggling, default auto-fill, and validation logic"
```

---

### Task 3: Livewire ProfileForm Blade View (Card & Switch UI)

**Files:**
- Modify: `resources/views/livewire/dashboard/profile-form.blade.php`
- Modify: `tests/Feature/DashboardProfileTest.php`

**Interfaces:**
- Consumes: `ProfileForm` component properties (`$features_enabled`, `$features_badge`, `$features_title`, `$features_subtitle`, `$features_items`)
- Produces: Interactive Blade UI dengan kartu toggle, form inputs, dan pesan validasi error.

- [ ] **Step 1: Write view rendering test**

Tambahkan method di `tests/Feature/DashboardProfileTest.php`:
```php
public function test_profile_form_view_renders_features_section_and_conditional_inputs(): void
{
    $user = User::factory()->create(['role' => 'umkm']);
    Business::create([
        'user_id' => $user->id,
        'name' => 'Toko UI',
        'slug' => 'toko-ui',
        'whatsapp' => '081234567890',
        'features' => [
            'enabled' => true,
            'badge' => 'Mengapa Memilih Kami',
            'title' => 'Keunggulan Layanan Kami',
            'subtitle' => 'Komitmen kami',
            'items' => [
                ['title' => 'Kualitas 1', 'description' => 'Desc 1'],
                ['title' => 'Kualitas 2', 'description' => 'Desc 2'],
                ['title' => 'Kualitas 3', 'description' => 'Desc 3'],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ProfileForm::class)
        ->assertSee('Keunggulan Layanan (Mengapa Memilih Kami)')
        ->assertSee('Aktifkan Bagian Keunggulan')
        ->assertSee('Kualitas 1')
        ->assertSee('Kualitas 2')
        ->assertSee('Kualitas 3');
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter=test_profile_form_view_renders_features_section_and_conditional_inputs`
Expected: FAIL (string 'Keunggulan Layanan' not found in view)

- [ ] **Step 3: Update `profile-form.blade.php`**

Tambahkan kartu pengaturan Keunggulan Layanan tepat sebelum `<!-- Submit Button -->`:
1. Header Card: Ikon bintang/centang, judul "Keunggulan Layanan (Mengapa Memilih Kami)", deskripsi ringkas.
2. Toggle switch button:
```html
<label class="relative inline-flex items-center cursor-pointer">
    <input type="checkbox" wire:model.live="features_enabled" class="sr-only peer">
    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
    <span class="ml-3 text-sm font-semibold text-slate-900">Aktifkan Bagian Keunggulan di Landing Page</span>
</label>
```
3. Conditional block `@if ($features_enabled)`:
- Input Badge (Label Kecil, misal: "Mengapa Memilih Kami").
- Input Judul Utama (misal: "Keunggulan Layanan Kami").
- Input Subjudul (misal: "Komitmen kami dalam memberikan standar mutu...").
- Grid 3 Kartu Keunggulan:
  - Kartu 1: Input `wire:model="features_items.0.title"` & `wire:model="features_items.0.description"`.
  - Kartu 2: Input `wire:model="features_items.1.title"` & `wire:model="features_items.1.description"`.
  - Kartu 3: Input `wire:model="features_items.2.title"` & `wire:model="features_items.2.description"`.
  - Sertakan `@error` directive untuk masing-masing field.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit --filter=test_profile_form_view_renders_features_section_and_conditional_inputs`
Expected: PASS

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/livewire/dashboard/profile-form.blade.php tests/Feature/DashboardProfileTest.php
git commit -m "feat(profile): add features toggle switch and card form inputs to profile view"
```

---

### Task 4: Minimalist Store Template Integration (Clean Iconless Cards)

**Files:**
- Modify: `resources/views/templates/minimalist/index.blade.php`
- Modify: `tests/Feature/TemplateRenderingTest.php`

**Interfaces:**
- Consumes: `$business->features`
- Produces: HTML section "Mengapa Memilih Kami" berdesain minimalis tanpa ikon, dirender secara kondisional di landing page toko.

- [ ] **Step 1: Write failing template tests**

Tambahkan method di `tests/Feature/TemplateRenderingTest.php`:
```php
public function test_minimalist_store_renders_features_section_when_enabled(): void
{
    $this->business->update([
        'features' => [
            'enabled' => true,
            'badge' => 'Mengapa Memilih Kami',
            'title' => 'Keunggulan Layanan Kami',
            'subtitle' => 'Standar kepuasan pelanggan',
            'items' => [
                ['title' => 'Kualitas Teruji', 'description' => 'Produk melewati uji ketat.'],
                ['title' => 'Respon Cepat', 'description' => 'Admin ramah dan cepat tanggap.'],
                ['title' => 'Harga Terbaik', 'description' => 'Bersaing tanpa biaya tersembunyi.'],
            ],
        ],
    ]);

    $template = Template::where('slug', 'minimalist')->first();
    $website = Website::where('business_id', $this->business->id)->first();
    $rendered = view($template->view_path, ['website' => $website, 'business' => $this->business])->render();

    $this->assertStringContainsString('Mengapa Memilih Kami', $rendered);
    $this->assertStringContainsString('Keunggulan Layanan Kami', $rendered);
    $this->assertStringContainsString('Kualitas Teruji', $rendered);
    $this->assertStringContainsString('Respon Cepat', $rendered);
    $this->assertStringContainsString('Harga Terbaik', $rendered);
}

public function test_minimalist_store_does_not_render_features_section_when_disabled(): void
{
    $this->business->update([
        'features' => [
            'enabled' => false,
            'badge' => 'Mengapa Memilih Kami',
            'title' => 'Keunggulan Layanan Kami',
            'subtitle' => 'Standar kepuasan pelanggan',
            'items' => [
                ['title' => 'Kualitas Teruji', 'description' => 'Produk melewati uji ketat.'],
                ['title' => 'Respon Cepat', 'description' => 'Admin ramah dan cepat tanggap.'],
                ['title' => 'Harga Terbaik', 'description' => 'Bersaing tanpa biaya tersembunyi.'],
            ],
        ],
    ]);

    $template = Template::where('slug', 'minimalist')->first();
    $website = Website::where('business_id', $this->business->id)->first();
    $rendered = view($template->view_path, ['website' => $website, 'business' => $this->business])->render();

    $this->assertStringNotContainsString('Mengapa Memilih Kami', $rendered);
    $this->assertStringNotContainsString('Kualitas Teruji', $rendered);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit --filter=test_minimalist_store_renders_features_section_when_enabled`
Expected: FAIL (assertStringContainsString failed)

- [ ] **Step 3: Update `resources/views/templates/minimalist/index.blade.php`**

Tempatkan section keunggulan tepat di bawah Hero Section (`</section> <!-- Hero Section -->`) dan tepat di atas Catalog Grid (`<section id="catalog"`):
```blade
@if (!empty($business?->features['enabled']) && !empty($business?->features['items']))
    <!-- Value Propositions / Mengapa Memilih Kami (Minimalist Iconless) -->
    <section class="py-12 sm:py-16 px-4 bg-slate-50/50 border-t border-slate-100">
        <div class="max-w-6xl mx-auto">
            <div class="text-center max-w-xl mx-auto mb-10 space-y-2">
                @if (!empty($business->features['badge']))
                    <span class="text-[11px] font-bold uppercase tracking-widest theme-text-primary block">
                        {{ $business->features['badge'] }}
                    </span>
                @endif
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $business->features['title'] ?? 'Keunggulan Layanan Kami' }}
                </h2>
                @if (!empty($business->features['subtitle']))
                    <p class="text-xs sm:text-sm text-slate-500 font-light leading-relaxed">
                        {{ $business->features['subtitle'] }}
                    </p>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($business->features['items'] as $item)
                    <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-xs hover:border-slate-300 hover:shadow-sm transition flex flex-col justify-between">
                        <div class="space-y-2.5">
                            <h3 class="text-base font-bold text-slate-900 tracking-tight">
                                {{ $item['title'] ?? '' }}
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-light">
                                {{ $item['description'] ?? '' }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit --filter=TemplateRenderingTest`
Expected: PASS

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/templates/minimalist/index.blade.php tests/Feature/TemplateRenderingTest.php
git commit -m "feat(template): add clean iconless features section to minimalist store template"
```

---

### Task 5: Full Regression Testing & E2E Verification

**Files:**
- Run: Full test suite `vendor/bin/phpunit`

- [ ] **Step 1: Run full test suite**

Run: `vendor/bin/phpunit`
Expected: All tests pass (100+ tests, 0 failures, 0 errors).

- [ ] **Step 2: Run Laravel Pint code formatter**

Run: `vendor/bin/pint --dirty --format agent`
Expected: No formatting issues.

- [ ] **Step 3: Push changes to main branch**

Run: `git push origin main`
Expected: Pushed cleanly to origin/main.
