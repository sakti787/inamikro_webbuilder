# Simplified 1-Click Template Selector Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the complex split-screen Visual Studio Builder with a friendly, 1-click curated template selector at `/dashboard/template` designed specifically for non-technical MSME (UMKM) owners.

**Architecture:** A lightweight full-page Livewire 3 component (`TemplateSelector`) displays 3 visual cards with industry recommendations and 1-click activation that automatically assigns curated color palettes and fonts to the tenant's `Theme`. Publishing controls and readiness validation (`WebsitePublishValidator`) are integrated directly on this screen. Obsolete studio builder routes and views are cleanly removed.

**Tech Stack:** Laravel 12, Livewire 3, Alpine.js, Tailwind CSS, MySQL/SQLite.

**Spec:** `docs/superpowers/specs/2026-11-24-simplified-template-selector-design.md`

## Global Constraints
- Strictly preserve existing multi-tenant data isolation: all template choices and publish toggles must strictly modify the authenticated user's own business website (`Auth::user()->business->website`).
- Preset values must be applied verbatim:
  - `minimalist`: primary `#0F172A`, secondary `#334155`, font `Inter`
  - `food`: primary `#DC2626`, secondary `#B91C1C`, font `Poppins`
  - `modern`: primary `#2563EB`, secondary `#1D4ED8`, font `Plus Jakarta Sans`
- Publishing must strictly pass `WebsitePublishValidator` before status can become `published`.
- Follow Conventional Commits: `<type>(<scope>): <desc>`.
- PHP 8.5 strictly typed code and pass Pint styling (`vendor/bin/pint --format agent`).

## Review Focus
1. User switches template: verifies that `websites.template_id` and all 3 theme fields (`primary_color`, `secondary_color`, `font_family`) are updated in a single operation.
2. User clicks publish with missing phone or 0 products: verifies that `publish_error` is flashed, status remains `draft`, and friendly guidance is displayed.
3. User navigates dashboard: verifies no remaining links or references point to deleted `/dashboard/builder`.
4. Tenant isolation: verifies that User A cannot alter User B's template or theme.
5. Seeded and existing stores continue to render public templates (`/{slug}`) cleanly without breaking changes.

---

### Task 1: Create `TemplateSelector` Livewire Component & Curated Presets

**Files:**
- Create: `app/Livewire/Dashboard/TemplateSelector.php`
- Create: `resources/views/livewire/dashboard/template-selector.blade.php`
- Create: `tests/Feature/TemplateSelectorTest.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `Website`, `Theme`, `Template`, `WebsitePublishValidator`.
- Produces: Route `GET /dashboard/template` (name: `dashboard.template`), methods `selectTemplate(int $templateId)` and `togglePublish()`.

- [ ] **Step 1: Write failing feature test in `tests/Feature/TemplateSelectorTest.php`**
```php
public function test_umkm_can_view_template_selector_and_select_curated_template(): void
{
    $user = User::factory()->create(['role' => 'umkm']);
    $business = Business::factory()->create(['user_id' => $user->id]);
    $templateMinimalist = Template::where('slug', 'minimalist')->first();
    $templateFood = Template::where('slug', 'food')->first();
    $website = Website::factory()->create([
        'business_id' => $business->id,
        'template_id' => $templateMinimalist->id,
        'status' => 'draft',
    ]);
    $theme = Theme::factory()->create(['website_id' => $website->id]);

    Livewire::actingAs($user)
        ->test(TemplateSelector::class)
        ->assertSee('Pilihan Tampilan Toko')
        ->assertSee('Toko Minimalis')
        ->assertSee('Kuliner & Minuman')
        ->call('selectTemplate', $templateFood->id)
        ->assertHasNoErrors();

    $this->assertEquals($templateFood->id, $website->fresh()->template_id);
    $this->assertEquals('#DC2626', $theme->fresh()->primary_color);
    $this->assertEquals('Poppins', $theme->fresh()->font_family);
}
```

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --filter=TemplateSelectorTest`
Expected: FAIL (Component does not exist).

- [ ] **Step 3: Implement `TemplateSelector` component and Blade view**
- In `app/Livewire/Dashboard/TemplateSelector.php`:
  - `mount()`: loads `$this->website = Auth::user()->business->website` and eagerly loads `theme` and `template`.
  - `selectTemplate(int $templateId)`:
    - Finds active `Template`.
    - Updates `$website->update(['template_id' => $template->id])`.
    - Automatically updates `$website->theme` with curated presets based on `$template->slug`:
      - `minimalist`: `primary_color => '#0F172A'`, `secondary_color => '#334155'`, `font_family => 'Inter'`
      - `food`: `primary_color => '#DC2626'`, `secondary_color => '#B91C1C'`, `font_family => 'Poppins'`
      - `modern`: `primary_color => '#2563EB'`, `secondary_color => '#1D4ED8'`, `font_family => 'Plus Jakarta Sans'`
    - Flashes success message: "Tampilan toko berhasil diubah ke {$template->name}!"
  - `togglePublish()`:
    - If status is `published`, toggles back to `draft` and flashes message.
    - If status is `draft`, runs `app(WebsitePublishValidator::class)->validate($this->website)`. If invalid, sets `session()->flash('publish_error', implode(' ', $result->getErrors()))`. If valid, updates to `published` and sets `published_at = now()`.
- In `resources/views/livewire/dashboard/template-selector.blade.php`:
  - Uses `layouts.dashboard`.
  - Responsive header with status badge (Draft/Published), link to "Lihat Website Toko", and button "Terbitkan Website".
  - Alert banners for `publish_error` and `success`.
  - 3 visual cards with badge "Sedang Digunakan" vs button "Pilih Desain Ini", thumbnail preview, target business description (Fashion vs Kuliner vs Jasa/Umum), and active color accent bar.
- In `routes/web.php`:
  - Add `Route::get('/dashboard/template', \App\Livewire\Dashboard\TemplateSelector::class)->name('dashboard.template');` inside the `['auth', 'umkm.owner']` group.

- [ ] **Step 4: Run test to verify it passes**
Run: `php artisan test --filter=TemplateSelectorTest`
Expected: PASS.

- [ ] **Step 5: Commit**
```bash
git add app/Livewire/Dashboard/TemplateSelector.php resources/views/livewire/dashboard/template-selector.blade.php routes/web.php tests/Feature/TemplateSelectorTest.php
git commit -m "feat(template): add simplified 1-click template selector with curated design presets"
```

---

### Task 2: Update Navigation, Layouts & Remove Deprecated Studio Builder

**Files:**
- Modify: `resources/views/layouts/dashboard.blade.php`
- Modify: `resources/views/livewire/dashboard/index.blade.php`
- Modify: `routes/web.php`
- Delete: `app/Livewire/Builder/Studio.php`
- Delete: `resources/views/livewire/builder/studio.blade.php`
- Delete: `resources/views/layouts/builder.blade.php`
- Delete: `tests/Feature/StudioBuilderTest.php`

**Interfaces:**
- Consumes: `dashboard.template` route.
- Produces: Cleaned dashboard navigation and elimination of obsolete builder code.

- [ ] **Step 1: Update dashboard sidebar and index quick actions**
- In `resources/views/layouts/dashboard.blade.php`:
  - Replace "Desain Website" nav link with:
    - Route: `route('dashboard.template')`
    - Label: `Pilihan Tampilan`
    - Active condition: `request()->routeIs('dashboard.template')`
    - Icon: Palette / Layout icon
- In `resources/views/livewire/dashboard/index.blade.php`:
  - Replace any CTA pointing to `dashboard.builder` with `dashboard.template`.

- [ ] **Step 2: Remove obsolete studio builder routes and files**
- In `routes/web.php`:
  - Remove `Route::get('/builder', Studio::class)->name('dashboard.builder');`
  - Remove or simplify `Route::get('/builder/preview/{website}', ...)` if not needed.
- Delete obsolete files:
  - `app/Livewire/Builder/Studio.php`
  - `resources/views/livewire/builder/studio.blade.php`
  - `resources/views/layouts/builder.blade.php`
  - `tests/Feature/StudioBuilderTest.php`

- [ ] **Step 3: Run full test suite to verify no broken routes or references**
Run: `php artisan test`
Expected: All tests pass.

- [ ] **Step 4: Commit**
```bash
git add resources/views/ routes/web.php tests/
git rm app/Livewire/Builder/Studio.php resources/views/livewire/builder/studio.blade.php resources/views/layouts/builder.blade.php tests/Feature/StudioBuilderTest.php
git commit -m "refactor(builder): replace split-screen studio builder with simplified template selector"
```

---

### Task 3: Full End-to-End Workflow Verification & Regression Test

**Files:**
- Modify: `tests/Feature/FullUmkmJourneyTest.php`
- Test: `tests/Feature/FullUmkmJourneyTest.php`

**Interfaces:**
- Consumes: All updated components across the application.
- Produces: 100% passing test suite covering the simplified UMKM journey.

- [ ] **Step 1: Update `FullUmkmJourneyTest` to use `TemplateSelector`**
- In `tests/Feature/FullUmkmJourneyTest.php`:
  - Replace testing of obsolete `Studio` component with testing `TemplateSelector`:
    - UMKM selects template via `Livewire::actingAs($user)->test(TemplateSelector::class)->call('selectTemplate', $templateFood->id)`.
    - Verifies curated presets are automatically populated in `Theme`.
    - Verifies publishing via `TemplateSelector::class->call('togglePublish')`.
    - Verifies public website (`/{slug}`) renders with selected template.

- [ ] **Step 2: Run full test suite**
Run: `php artisan test`
Expected: 100% tests pass (all unit, feature, and E2E tests).

- [ ] **Step 3: Run code formatting & frontend build**
Run: `vendor/bin/pint --format agent`
Run: `npm run build`

- [ ] **Step 4: Commit**
```bash
git add tests/Feature/FullUmkmJourneyTest.php
git commit -m "test(e2e): update end-to-end journey to verify simplified template selector workflow"
```
