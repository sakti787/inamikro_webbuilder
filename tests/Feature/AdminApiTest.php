<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\AppSetting;
use App\Models\Site;
use App\Models\SiteHistory;
use App\Models\TemplateSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build complete 4/4 UMKM site content payload.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function completeContent(array $overrides = []): array
    {
        return array_replace_recursive([
            'template' => 'warung',
            'variant' => false,
            'brand' => '#dc2626',
            'nama' => 'Dapur Bu Sari',
            'tagline' => 'Masakan rumahan hangat setiap hari',
            'kategori' => 'Kuliner rumahan',
            'deskripsi' => 'Menyediakan menu harian dan paket katering.',
            'heroImg' => '',
            'wa' => '081234567890',
            'ig' => 'dapurbusari',
            'alamat' => 'Jl. Melati No. 12, Bekasi',
            'jam' => 'Setiap hari, 08.00 - 21.00',
            'produk' => [
                [
                    'nama' => 'Nasi Ayam Bakar',
                    'harga' => '25000',
                    'ket' => 'Ayam kampung dengan sambal matah',
                ],
            ],
        ], $overrides);
    }

    public function test_umkm_and_guest_cannot_access_admin_api_endpoints(): void
    {
        $umkmTarget = User::factory()->create(['role' => 'umkm']);
        $umkmActor = User::factory()->create(['role' => 'umkm']);

        $this->getJson('/api/admin/bootstrap')->assertUnauthorized();
        $this->getJson('/api/admin/logs')->assertUnauthorized();
        $this->postJson("/api/admin/umkm/{$umkmTarget->id}/approve")->assertUnauthorized();
        $this->postJson("/api/admin/umkm/{$umkmTarget->id}/revise", ['reason' => 'Perbaiki'])->assertUnauthorized();
        $this->patchJson("/api/admin/umkm/{$umkmTarget->id}/note", ['note' => 'Catatan'])->assertUnauthorized();
        $this->patchJson('/api/admin/templates/warung', ['is_active' => false])->assertUnauthorized();
        $this->patchJson('/api/admin/settings/auto-approve', ['auto_approve' => true])->assertUnauthorized();
        $this->patchJson('/api/admin/settings/contact', ['admin_name' => 'Admin', 'admin_whatsapp' => '081234567890'])->assertUnauthorized();

        $this->actingAs($umkmActor)->getJson('/api/admin/bootstrap')->assertForbidden();
        $this->actingAs($umkmActor)->getJson('/api/admin/logs')->assertForbidden();
        $this->actingAs($umkmActor)->postJson("/api/admin/umkm/{$umkmTarget->id}/approve")->assertForbidden();
        $this->actingAs($umkmActor)->postJson("/api/admin/umkm/{$umkmTarget->id}/revise", ['reason' => 'Perbaiki'])->assertForbidden();
        $this->actingAs($umkmActor)->patchJson("/api/admin/umkm/{$umkmTarget->id}/note", ['note' => 'Catatan'])->assertForbidden();
        $this->actingAs($umkmActor)->patchJson('/api/admin/templates/warung', ['is_active' => false])->assertForbidden();
        $this->actingAs($umkmActor)->patchJson('/api/admin/settings/auto-approve', ['auto_approve' => true])->assertForbidden();
        $this->actingAs($umkmActor)->patchJson('/api/admin/settings/contact', ['admin_name' => 'Admin', 'admin_whatsapp' => '081234567890'])->assertForbidden();
    }

    public function test_bootstrap_returns_all_umkm_including_users_without_sites_plus_templates_settings_and_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Pendamping Utama']);

        $umkmWithSite = User::factory()->create([
            'role' => 'umkm',
            'name' => 'Bu Sari',
            'business_name' => 'Dapur Bu Sari',
            'slug' => 'dapur-bu-sari',
            'email' => 'sari@example.com',
        ]);

        $site = Site::factory()->for($umkmWithSite)->create([
            'template_id' => 'warung',
            'slug' => $umkmWithSite->slug,
            'status' => 'pending',
            'brand_color' => '#dc2626',
            'content' => $this->completeContent(),
            'rendered_html' => '<!doctype html><html><body><h1>Dapur Bu Sari</h1></body></html>',
            'admin_note' => 'Sudah dicek awal.',
            'revision_note' => 'Periksa ulang jam buka.',
        ]);

        SiteHistory::create([
            'site_id' => $site->id,
            'actor_id' => $umkmWithSite->id,
            'title' => 'Diminta publikasi',
            'description' => 'UMKM menekan tombol Publikasikan.',
        ]);

        $umkmWithoutSite = User::factory()->create([
            'role' => 'umkm',
            'name' => 'Pak Budi',
            'business_name' => 'Bengkel Budi',
            'slug' => 'bengkel-budi',
            'email' => 'budi@example.com',
        ]);

        TemplateSetting::create([
            'template_id' => 'toko-2',
            'is_active' => false,
        ]);

        AppSetting::setValue('auto_approve', '1');
        AppSetting::setValue('admin_name', 'Tim Pendamping');
        AppSetting::setValue('admin_whatsapp', '6281234567890');

        AdminLog::create([
            'actor_id' => $admin->id,
            'title' => 'Template dinonaktifkan',
            'description' => 'Sembako Jaya tidak lagi dipakai UMKM.',
            'who' => 'Sembako Jaya',
            'kind' => 'template',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/bootstrap');

        $response->assertOk();
        $response->assertJsonCount(2, 'umkm');
        $response->assertJsonCount(8, 'templates');
        $response->assertJsonPath('settings.auto_approve', true);
        $response->assertJsonPath('settings.admin_name', 'Tim Pendamping');
        $response->assertJsonPath('settings.admin_whatsapp', '6281234567890');
        $response->assertJsonCount(1, 'logs');
        $response->assertJsonPath('logs.0.title', 'Template dinonaktifkan');
        $response->assertJsonPath('logs.0.kind', 'template');

        $umkmById = collect($response->json('umkm'))->keyBy('id');

        $this->assertArrayNotHasKey($admin->id, $umkmById->all());

        $first = $umkmById[$umkmWithSite->id];
        $this->assertTrue($first['has_site']);
        $this->assertSame('sari@example.com', $first['email']);
        $this->assertSame('Menunggu', $first['status']);
        $this->assertSame('pending', $first['status_key']);
        $this->assertSame('Sudah dicek awal.', $first['note']);
        $this->assertSame('Periksa ulang jam buka.', $first['revision_note']);
        $this->assertSame(4, $first['completeness']);
        $this->assertCount(1, $first['history']);
        $this->assertSame('Diminta publikasi', $first['history'][0]['title']);
        $this->assertArrayHasKey('t', $first['history'][0]);
        $this->assertArrayHasKey('at', $first['history'][0]);
        $this->assertStringEndsWith('/dapur-bu-sari', $first['public_url']);

        $second = $umkmById[$umkmWithoutSite->id];
        $this->assertFalse($second['has_site']);
        $this->assertSame('Draf', $second['status']);
        $this->assertSame('draft', $second['status_key']);
        $this->assertSame('Belum ada draf', $second['changed']);
        $this->assertSame(0, $second['completeness']);
        $this->assertSame([], $second['history']);
        $this->assertSame('Bengkel Budi', $second['state']['nama']);
        $this->assertNull($second['rendered_html']);

        $templatesById = collect($response->json('templates'))->keyBy('template_id');
        $this->assertFalse($templatesById['toko-2']['is_active']);
        $this->assertTrue($templatesById['warung']['is_active']);
    }

    public function test_admin_can_approve_complete_pending_site_and_make_it_live(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $umkm = User::factory()->create([
            'role' => 'umkm',
            'business_name' => 'Dapur Bu Sari',
            'slug' => 'dapur-bu-sari',
        ]);

        $site = Site::factory()->for($umkm)->create([
            'template_id' => 'warung',
            'slug' => $umkm->slug,
            'status' => 'pending',
            'content' => $this->completeContent(),
            'rendered_html' => '<!doctype html><html><body><h1>Dapur Bu Sari</h1></body></html>',
            'admin_note' => 'Catatan internal tetap aman.',
            'revision_note' => 'Revisi sebelumnya sudah beres.',
        ]);

        $this->get('/dapur-bu-sari')->assertNotFound();

        $response = $this->actingAs($admin)->postJson("/api/admin/umkm/{$umkm->id}/approve");

        $response->assertOk();
        $response->assertJsonPath('umkm.status', 'Tayang');
        $response->assertJsonPath('umkm.status_key', 'published');
        $response->assertJsonPath('umkm.revision_note', null);
        $response->assertJsonPath('umkm.note', 'Catatan internal tetap aman.');
        $response->assertJsonPath('logs.0.kind', 'approve');
        $response->assertJsonPath('logs.0.title', 'Disetujui tayang');

        $freshSite = $site->fresh();
        $this->assertNotNull($freshSite);
        $this->assertSame('published', $freshSite->status);
        $this->assertNotNull($freshSite->published_at);
        $this->assertNull($freshSite->revision_note);
        $this->assertSame('Catatan internal tetap aman.', $freshSite->admin_note);

        $this->assertDatabaseHas('site_histories', [
            'site_id' => $site->id,
            'actor_id' => $admin->id,
            'title' => 'Disetujui tayang',
        ]);
        $this->assertDatabaseHas('admin_logs', [
            'actor_id' => $admin->id,
            'title' => 'Disetujui tayang',
            'kind' => 'approve',
            'who' => 'Dapur Bu Sari',
        ]);

        $this->get('/dapur-bu-sari')
            ->assertOk()
            ->assertSee('Dapur Bu Sari');
    }

    public function test_admin_approve_rejects_incomplete_or_missing_html_site_with_indonesian_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $umkmNoSite = User::factory()->create(['role' => 'umkm']);
        $umkmIncomplete = User::factory()->create(['role' => 'umkm', 'slug' => 'usaha-kurang']);
        $umkmNoHtml = User::factory()->create(['role' => 'umkm', 'slug' => 'usaha-tanpa-html']);

        $incompleteSite = Site::factory()->for($umkmIncomplete)->create([
            'template_id' => 'warung',
            'slug' => $umkmIncomplete->slug,
            'status' => 'pending',
            'content' => $this->completeContent(['jam' => '']),
            'rendered_html' => '<!doctype html><html><body><h1>Usaha Kurang</h1></body></html>',
        ]);

        $noHtmlSite = Site::factory()->for($umkmNoHtml)->create([
            'template_id' => 'warung',
            'slug' => $umkmNoHtml->slug,
            'status' => 'pending',
            'content' => $this->completeContent(),
            'rendered_html' => '   ',
        ]);

        $this->actingAs($admin)
            ->postJson("/api/admin/umkm/{$otherAdmin->id}/approve")
            ->assertNotFound();

        $noSiteRes = $this->actingAs($admin)
            ->postJson("/api/admin/umkm/{$umkmNoSite->id}/approve");
        $noSiteRes->assertUnprocessable();
        $this->assertStringContainsString('belum membuat', strtolower((string) $noSiteRes->json('message')));

        $incompleteRes = $this->actingAs($admin)
            ->postJson("/api/admin/umkm/{$umkmIncomplete->id}/approve");
        $incompleteRes->assertUnprocessable();
        $this->assertStringContainsString('Minta revisi', (string) $incompleteRes->json('message'));
        $this->assertSame('pending', $incompleteSite->fresh()?->status);
        $this->get('/usaha-kurang')->assertNotFound();

        $noHtmlRes = $this->actingAs($admin)
            ->postJson("/api/admin/umkm/{$umkmNoHtml->id}/approve");
        $noHtmlRes->assertUnprocessable();
        $this->assertStringContainsString('Minta revisi', (string) $noHtmlRes->json('message'));
        $this->assertSame('pending', $noHtmlSite->fresh()?->status);

        $this->assertDatabaseCount('admin_logs', 0);
        $this->assertDatabaseCount('site_histories', 0);
    }

    public function test_admin_revise_returns_site_to_draft_sets_revision_note_and_preserves_admin_note(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $umkmNoSite = User::factory()->create(['role' => 'umkm']);
        $umkm = User::factory()->create([
            'role' => 'umkm',
            'business_name' => 'Sigap Teknik',
            'slug' => 'sigap-teknik',
        ]);

        $site = Site::factory()->for($umkm)->published()->create([
            'template_id' => 'jasa',
            'slug' => $umkm->slug,
            'content' => $this->completeContent(['nama' => 'Sigap Teknik']),
            'admin_note' => 'Catatan internal penting admin.',
            'revision_note' => null,
        ]);

        $this->actingAs($admin)
            ->postJson("/api/admin/umkm/{$otherAdmin->id}/revise", ['reason' => 'Perbaiki jam buka'])
            ->assertNotFound();

        $this->actingAs($admin)
            ->postJson("/api/admin/umkm/{$umkmNoSite->id}/revise", ['reason' => 'Perbaiki jam buka'])
            ->assertUnprocessable();

        $emptyReason = $this->actingAs($admin)
            ->postJson("/api/admin/umkm/{$umkm->id}/revise", ['reason' => '   ']);
        $emptyReason->assertUnprocessable();
        $emptyReason->assertJsonValidationErrors(['reason']);
        $this->assertSame('Alasan revisi wajib diisi.', $emptyReason->json('errors.reason.0'));

        $response = $this->actingAs($admin)->postJson("/api/admin/umkm/{$umkm->id}/revise", [
            'reason' => 'Jam buka belum lengkap, mohon perjelas.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('umkm.status', 'Draf');
        $response->assertJsonPath('umkm.status_key', 'draft');
        $response->assertJsonPath('umkm.revision_note', 'Jam buka belum lengkap, mohon perjelas.');
        $response->assertJsonPath('umkm.note', 'Catatan internal penting admin.');
        $response->assertJsonPath('logs.0.kind', 'revise');
        $response->assertJsonPath('logs.0.title', 'Diminta revisi');

        $freshSite = $site->fresh();
        $this->assertNotNull($freshSite);
        $this->assertSame('draft', $freshSite->status);
        $this->assertSame('Jam buka belum lengkap, mohon perjelas.', $freshSite->revision_note);
        $this->assertSame('Catatan internal penting admin.', $freshSite->admin_note);

        $this->assertDatabaseHas('site_histories', [
            'site_id' => $site->id,
            'actor_id' => $admin->id,
            'title' => 'Diminta revisi',
            'description' => 'Jam buka belum lengkap, mohon perjelas.',
        ]);
        $this->assertDatabaseHas('admin_logs', [
            'actor_id' => $admin->id,
            'title' => 'Diminta revisi',
            'description' => 'Jam buka belum lengkap, mohon perjelas.',
            'who' => 'Sigap Teknik',
            'kind' => 'revise',
        ]);

        $this->get('/sigap-teknik')->assertNotFound();
    }

    public function test_admin_save_note_updates_internal_admin_note_without_touching_revision_note(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $umkmNoSite = User::factory()->create(['role' => 'umkm']);
        $umkm = User::factory()->create([
            'role' => 'umkm',
            'business_name' => 'Tenun Loka',
            'slug' => 'tenun-loka',
        ]);

        $site = Site::factory()->for($umkm)->create([
            'template_id' => 'butik',
            'slug' => $umkm->slug,
            'status' => 'pending',
            'content' => $this->completeContent(['nama' => 'Tenun Loka']),
            'admin_note' => 'Catatan awal.',
            'revision_note' => 'Mohon lengkapi deskripsi.',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/umkm/{$otherAdmin->id}/note", ['note' => 'Tes'])
            ->assertNotFound();

        $this->actingAs($admin)
            ->patchJson("/api/admin/umkm/{$umkmNoSite->id}/note", ['note' => 'Tes'])
            ->assertUnprocessable();

        $response = $this->actingAs($admin)->patchJson("/api/admin/umkm/{$umkm->id}/note", [
            'note' => 'Sudah dikonfirmasi lewat telepon.',
        ]);

        $response->assertOk();
        $response->assertJsonPath('umkm.note', 'Sudah dikonfirmasi lewat telepon.');
        $response->assertJsonPath('umkm.revision_note', 'Mohon lengkapi deskripsi.');
        $response->assertJsonPath('umkm.status_key', 'pending');
        $response->assertJsonPath('logs.0.kind', 'note');
        $response->assertJsonPath('logs.0.title', 'Catatan diperbarui');

        $freshSite = $site->fresh();
        $this->assertNotNull($freshSite);
        $this->assertSame('Sudah dikonfirmasi lewat telepon.', $freshSite->admin_note);
        $this->assertSame('Mohon lengkapi deskripsi.', $freshSite->revision_note);
        $this->assertSame('pending', $freshSite->status);

        $this->assertDatabaseHas('site_histories', [
            'site_id' => $site->id,
            'actor_id' => $admin->id,
            'title' => 'Catatan diperbarui',
            'description' => 'Sudah dikonfirmasi lewat telepon.',
        ]);
    }

    public function test_admin_can_toggle_template_status_and_logs_activity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $invalidTpl = $this->actingAs($admin)
            ->patchJson('/api/admin/templates/tidak-ada', ['is_active' => false]);
        $invalidTpl->assertUnprocessable();
        $invalidTpl->assertJsonValidationErrors(['template_id']);
        $this->assertSame('Template yang dipilih tidak valid.', $invalidTpl->json('errors.template_id.0'));

        $disableRes = $this->actingAs($admin)
            ->patchJson('/api/admin/templates/toko-2', ['is_active' => false]);

        $disableRes->assertOk();
        $disableRes->assertJsonPath('template.template_id', 'toko-2');
        $disableRes->assertJsonPath('template.is_active', false);
        $disableRes->assertJsonPath('logs.0.title', 'Template dinonaktifkan');
        $disableRes->assertJsonPath('logs.0.kind', 'template');

        $this->assertDatabaseHas('template_settings', [
            'template_id' => 'toko-2',
            'is_active' => false,
        ]);
        $this->assertNotContains('toko-2', TemplateSetting::activeTemplateIds());

        $enableRes = $this->actingAs($admin)
            ->patchJson('/api/admin/templates/toko-2', ['is_active' => true]);

        $enableRes->assertOk();
        $enableRes->assertJsonPath('template.is_active', true);
        $enableRes->assertJsonPath('logs.0.title', 'Template diaktifkan');

        $this->assertContains('toko-2', TemplateSetting::activeTemplateIds());
    }

    public function test_enabling_auto_approve_automatically_publishes_complete_pending_sites_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $readyPendingUser = User::factory()->create([
            'role' => 'umkm',
            'business_name' => 'Warung Lengkap',
            'slug' => 'warung-lengkap',
        ]);
        $readyPendingSite = Site::factory()->for($readyPendingUser)->create([
            'template_id' => 'warung',
            'slug' => $readyPendingUser->slug,
            'status' => 'pending',
            'content' => $this->completeContent(['nama' => 'Warung Lengkap']),
            'rendered_html' => '<!doctype html><html><body><h1>Warung Lengkap</h1></body></html>',
            'revision_note' => 'Catatan revisi lama.',
        ]);

        $incompletePendingUser = User::factory()->create([
            'role' => 'umkm',
            'business_name' => 'Warung Kurang',
            'slug' => 'warung-kurang',
        ]);
        $incompletePendingSite = Site::factory()->for($incompletePendingUser)->create([
            'template_id' => 'warung',
            'slug' => $incompletePendingUser->slug,
            'status' => 'pending',
            'content' => $this->completeContent(['nama' => 'Warung Kurang', 'wa' => '']),
            'rendered_html' => '<!doctype html><html><body><h1>Warung Kurang</h1></body></html>',
        ]);

        $noHtmlPendingUser = User::factory()->create([
            'role' => 'umkm',
            'business_name' => 'Warung Kosong HTML',
            'slug' => 'warung-kosong-html',
        ]);
        $noHtmlPendingSite = Site::factory()->for($noHtmlPendingUser)->create([
            'template_id' => 'warung',
            'slug' => $noHtmlPendingUser->slug,
            'status' => 'pending',
            'content' => $this->completeContent(['nama' => 'Warung Kosong HTML']),
            'rendered_html' => '',
        ]);

        $readyDraftUser = User::factory()->create([
            'role' => 'umkm',
            'business_name' => 'Draf Lengkap',
            'slug' => 'draf-lengkap',
        ]);
        $readyDraftSite = Site::factory()->for($readyDraftUser)->create([
            'template_id' => 'warung',
            'slug' => $readyDraftUser->slug,
            'status' => 'draft',
            'content' => $this->completeContent(['nama' => 'Draf Lengkap']),
            'rendered_html' => '<!doctype html><html><body><h1>Draf Lengkap</h1></body></html>',
        ]);

        $response = $this->actingAs($admin)->patchJson('/api/admin/settings/auto-approve', [
            'auto_approve' => true,
        ]);

        $response->assertOk();
        $response->assertJsonPath('auto_approve', true);
        $response->assertJsonPath('approved_count', 1);
        $response->assertJsonPath('settings.auto_approve', true);

        $this->assertTrue(AppSetting::autoApproveEnabled());

        $freshReadyPending = $readyPendingSite->fresh();
        $this->assertSame('published', $freshReadyPending?->status);
        $this->assertNotNull($freshReadyPending?->published_at);
        $this->assertNull($freshReadyPending?->revision_note);

        $this->assertSame('pending', $incompletePendingSite->fresh()?->status);
        $this->assertSame('pending', $noHtmlPendingSite->fresh()?->status);
        $this->assertSame('draft', $readyDraftSite->fresh()?->status);

        $this->assertDatabaseHas('site_histories', [
            'site_id' => $readyPendingSite->id,
            'title' => 'Disetujui otomatis',
        ]);
        $this->assertDatabaseHas('admin_logs', [
            'title' => 'Auto setujui diaktifkan',
            'kind' => 'settings',
        ]);
        $this->assertDatabaseHas('admin_logs', [
            'title' => 'Disetujui otomatis',
            'who' => 'Warung Lengkap',
            'kind' => 'auto',
        ]);

        $this->get('/warung-lengkap')->assertOk();
        $this->get('/warung-kurang')->assertNotFound();

        $disableRes = $this->actingAs($admin)->patchJson('/api/admin/settings/auto-approve', [
            'auto_approve' => false,
        ]);

        $disableRes->assertOk();
        $disableRes->assertJsonPath('auto_approve', false);
        $disableRes->assertJsonPath('approved_count', 0);
        $this->assertFalse(AppSetting::autoApproveEnabled());
        $this->assertDatabaseHas('admin_logs', [
            'title' => 'Auto setujui dimatikan',
            'kind' => 'settings',
        ]);
    }

    public function test_admin_contact_normalizes_indonesian_whatsapp_and_rejects_invalid_numbers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $validRes = $this->actingAs($admin)->patchJson('/api/admin/settings/contact', [
            'admin_name' => 'Kak Rina',
            'admin_whatsapp' => '0812-3456-7890',
        ]);

        $validRes->assertOk();
        $validRes->assertJsonPath('settings.admin_name', 'Kak Rina');
        $validRes->assertJsonPath('settings.admin_whatsapp', '6281234567890');
        $this->assertSame('6281234567890', AppSetting::getValue('admin_whatsapp'));
        $this->assertSame('Kak Rina', AppSetting::getValue('admin_name'));
        $this->assertDatabaseHas('admin_logs', [
            'title' => 'Kontak pendamping diperbarui',
            'kind' => 'settings',
        ]);

        $invalidShort = $this->actingAs($admin)->patchJson('/api/admin/settings/contact', [
            'admin_name' => 'Kak Rina',
            'admin_whatsapp' => '123',
        ]);
        $invalidShort->assertUnprocessable();
        $invalidShort->assertJsonValidationErrors(['admin_whatsapp']);
        $this->assertSame('Nomor WhatsApp tidak valid. Gunakan format Indonesia (08... atau 62...) dengan 10-15 digit.', $invalidShort->json('errors.admin_whatsapp.0'));
        $this->assertSame('6281234567890', AppSetting::getValue('admin_whatsapp'));

        $invalidLetters = $this->actingAs($admin)->patchJson('/api/admin/settings/contact', [
            'admin_name' => 'Kak Rina',
            'admin_whatsapp' => 'abc',
        ]);
        $invalidLetters->assertUnprocessable();
        $invalidLetters->assertJsonValidationErrors(['admin_whatsapp']);

        $emptyRes = $this->actingAs($admin)->patchJson('/api/admin/settings/contact', [
            'admin_name' => '',
            'admin_whatsapp' => '',
        ]);

        $emptyRes->assertOk();
        $emptyRes->assertJsonPath('settings.admin_name', 'Tim INAmikro');
        $emptyRes->assertJsonPath('settings.admin_whatsapp', '');
        $this->assertNull(AppSetting::adminContact()['waUrl']);
    }

    public function test_admin_logs_endpoint_paginates_eight_items_per_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 1; $i <= 19; $i++) {
            AdminLog::create([
                'actor_id' => $admin->id,
                'title' => "Log #{$i}",
                'description' => "Deskripsi #{$i}",
                'who' => 'Sistem',
                'kind' => 'general',
            ]);
        }

        $page1 = $this->actingAs($admin)->getJson('/api/admin/logs?page=1');
        $page1->assertOk();
        $page1->assertJsonCount(8, 'items');
        $page1->assertJsonPath('page', 1);
        $page1->assertJsonPath('pages', 3);
        $page1->assertJsonPath('per_page', 8);
        $page1->assertJsonPath('total', 19);
        $page1->assertJsonPath('items.0.title', 'Log #19');

        $page2 = $this->actingAs($admin)->getJson('/api/admin/logs?page=2');
        $page2->assertOk();
        $page2->assertJsonCount(8, 'items');
        $page2->assertJsonPath('page', 2);
        $page2->assertJsonPath('items.0.title', 'Log #11');

        $page3 = $this->actingAs($admin)->getJson('/api/admin/logs?page=3');
        $page3->assertOk();
        $page3->assertJsonCount(3, 'items');
        $page3->assertJsonPath('page', 3);
        $page3->assertJsonPath('items.0.title', 'Log #3');
        $page3->assertJsonPath('items.2.title', 'Log #1');
    }
}
