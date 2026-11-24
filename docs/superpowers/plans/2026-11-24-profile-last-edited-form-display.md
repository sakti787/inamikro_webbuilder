# Profile Form Last-Edited Inline Display Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Display the last saved profile data and timestamp directly inside the UMKM profile form so owners do not need to check the public website before editing.

**Architecture:** Extend `ProfileForm` Livewire component to format and expose `updated_at` timestamps in Indonesian locale, and update `profile-form.blade.php` with an inline header card and clear field indicators for active saved values.

**Tech Stack:** Laravel 11/12, Livewire 3, Tailwind CSS, Blade, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-11-24-profile-last-edited-form-display-design.md`

## Global Constraints
- Commit convention: Standard Conventional Commits (`<type>(<scope>): <desc>`).
- Formatting: Must run `vendor/bin/pint --dirty --format agent` before committing.
- Language: UI copy, timestamps, and messages in Indonesian.
- Zero data loss: Do not alter existing business attributes or database schema.

## Review Focus
- New business with `updated_at == created_at` shows clean initial timestamp without errors.
- Unfilled optional fields (phone, address, operating hours, description) show helpful `(Belum diisi)` placeholders.
- Saving updates immediately refreshes the `updated_at` display in the UI without a full page refresh.
- Existing logo displays a clear "Logo Aktif Saat Ini" indicator so the owner knows what is currently published.
- Mobile responsiveness: the inline timestamp banner adapts cleanly to small screens.

---

### Task 1: Expose Formatted Last-Edited Timestamp in ProfileForm Component (TDD)

**Files:**
- Modify: `app/Livewire/Dashboard/ProfileForm.php`
- Test: `tests/Feature/DashboardProfileTest.php`

**Interfaces:**
- Produces:
  - `public ?string $lastUpdatedHuman = null;` (e.g. "baru saja", "2 jam yang lalu")
  - `public ?string $lastUpdatedFormatted = null;` (e.g. "24 November 2026, 15:30 WIB")

- [ ] **Step 1: Write the failing tests in `tests/Feature/DashboardProfileTest.php`**

Add tests verifying:
1. `test_profile_form_exposes_formatted_last_updated_timestamp`: Asserts that when opening `ProfileForm`, `$lastUpdatedFormatted` contains the Indonesian formatted date and `$lastUpdatedHuman` is set.
2. `test_profile_form_refreshes_last_updated_timestamp_after_saving`: Asserts that after saving changes, `$lastUpdatedFormatted` and `$lastUpdatedHuman` are updated to reflect the new `updated_at`.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/DashboardProfileTest.php --filter=test_profile_form_exposes_formatted_last_updated_timestamp`
Expected: FAIL (property `lastUpdatedFormatted` does not exist or is null)

- [ ] **Step 3: Implement timestamp formatting in `app/Livewire/Dashboard/ProfileForm.php`**

- Add properties `public ?string $lastUpdatedHuman = null;` and `public ?string $lastUpdatedFormatted = null;`.
- In `mount()`, format `$business->updated_at` using Carbon with Indonesian locale:
  ```php
  if ($business && $business->updated_at) {
      $this->lastUpdatedFormatted = $business->updated_at->locale('id')->isoFormat('D MMMM Y, HH:mm') . ' WIB';
      $this->lastUpdatedHuman = $business->updated_at->locale('id')->diffForHumans();
  }
  ```
- In `save()`, after `$business->update($updateData)`, refresh the business model and recalculate `$this->lastUpdatedFormatted` and `$this->lastUpdatedHuman`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/phpunit tests/Feature/DashboardProfileTest.php --filter=test_profile_form_exposes_formatted_last_updated_timestamp`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Dashboard/ProfileForm.php tests/Feature/DashboardProfileTest.php
git commit -m "feat(profile): expose formatted last-edited timestamps in ProfileForm component"
```

---

### Task 2: Update Profile Blade View with Inline Last-Edited Card & Field Badges

**Files:**
- Modify: `resources/views/livewire/dashboard/profile-form.blade.php`
- Test: `tests/Feature/DashboardProfileTest.php`

**Interfaces:**
- Consumes: `$lastUpdatedFormatted`, `$lastUpdatedHuman`, `$existingLogo`, `$name`, `$whatsapp`, `$phone`, `$address`, `$operating_hours`, `$description` from `ProfileForm`.

- [ ] **Step 1: Write the failing view assertion tests**

Add tests in `tests/Feature/DashboardProfileTest.php`:
1. `test_profile_form_renders_last_edited_banner_in_view`: Asserts that the rendered HTML contains "Data Profil Tersimpan", the formatted date, and "Logo Aktif Saat Ini".
2. `test_profile_form_renders_helpful_placeholders_for_empty_optional_fields`: Asserts that when optional fields are empty, placeholder hints like "Belum diisi" are present.

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Feature/DashboardProfileTest.php --filter=test_profile_form_renders_last_edited_banner_in_view`
Expected: FAIL (text not found in rendered view)

- [ ] **Step 3: Update `resources/views/livewire/dashboard/profile-form.blade.php`**

- Inside `<form wire:submit="save" ...>` at the top:
  Add an inline status banner card:
  - Display clock icon, label "Data Tersimpan Saat Ini", formatted timestamp (`$lastUpdatedFormatted`), and relative time (`$lastUpdatedHuman`).
- In Logo section:
  - Add a small badge `Logo Aktif Saat Ini` above or below the existing logo thumbnail.
- In optional fields (phone, address, operating hours, description):
  - Provide helpful placeholders showing `Belum diisi (Contoh: ...)`.
- Ensure clean responsive styling with Tailwind CSS.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Feature/DashboardProfileTest.php --filter=test_profile_form_renders_last_edited_banner_in_view`
Expected: PASS

- [ ] **Step 5: Run full test suite & format code**

Run: `vendor/bin/pint --format agent`
Run: `vendor/bin/phpunit`
Expected: PASS (All tests pass)

- [ ] **Step 6: Commit**

```bash
git add resources/views/livewire/dashboard/profile-form.blade.php tests/Feature/DashboardProfileTest.php
git commit -m "feat(profile): add inline last-edited status banner and active field indicators"
```
