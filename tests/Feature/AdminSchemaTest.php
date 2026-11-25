<?php

namespace Tests\Feature;

use App\Http\Requests\Api\SaveSiteRequest;
use App\Models\AdminLog;
use App\Models\AppSetting;
use App\Models\Site;
use App\Models\SiteHistory;
use App\Models\TemplateSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_sites_table_stores_admin_and_revision_notes_and_relates_to_site_histories(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $site = Site::factory()->create([
            'user_id' => $owner->id,
            'admin_note' => 'Catatan internal admin untuk verifikasi usaha.',
            'revision_note' => 'Mohon perjelas foto produk utama.',
        ]);

        $history = SiteHistory::create([
            'site_id' => $site->id,
            'actor_id' => $admin->id,
            'title' => 'Diminta Revisi',
            'description' => 'Foto produk masih buram.',
        ]);

        $freshSite = $site->fresh();

        $this->assertNotNull($freshSite);
        $this->assertSame('Catatan internal admin untuk verifikasi usaha.', $freshSite->admin_note);
        $this->assertSame('Mohon perjelas foto produk utama.', $freshSite->revision_note);
        $this->assertCount(1, $freshSite->histories);
        $this->assertTrue($freshSite->histories->first()->is($history));
        $this->assertTrue($history->site->is($freshSite));
        $this->assertTrue($history->actor->is($admin));

        $admin->delete();
        $this->assertNull($history->fresh()?->actor_id);

        $freshSite->delete();
        $this->assertDatabaseMissing('site_histories', ['id' => $history->id]);
    }

    public function test_admin_logs_app_settings_and_template_settings_persist_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $log = AdminLog::create([
            'actor_id' => $admin->id,
            'title' => 'Ubah Pengaturan Sistem',
            'description' => 'Mengaktifkan persetujuan otomatis.',
            'who' => $admin->name,
            'kind' => 'settings',
        ]);

        $this->assertDatabaseHas('admin_logs', [
            'id' => $log->id,
            'actor_id' => $admin->id,
            'title' => 'Ubah Pengaturan Sistem',
            'who' => $admin->name,
            'kind' => 'settings',
        ]);
        $this->assertTrue($log->actor->is($admin));

        $this->assertFalse(AppSetting::autoApproveEnabled());
        $this->assertSame('fallback', AppSetting::getValue('missing_key', 'fallback'));

        AppSetting::setValue('auto_approve', '1');
        $this->assertTrue(AppSetting::autoApproveEnabled());
        $this->assertSame('1', AppSetting::getValue('auto_approve'));

        AppSetting::setValue('auto_approve', '0');
        $this->assertFalse(AppSetting::autoApproveEnabled());
        $this->assertDatabaseCount('app_settings', 1);

        TemplateSetting::create([
            'template_id' => 'warung-2',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('template_settings', [
            'template_id' => 'warung-2',
            'is_active' => false,
        ]);

        $all = TemplateSetting::allWithDefaults();
        $this->assertCount(count(SaveSiteRequest::ALLOWED_TEMPLATES), $all);

        $byId = collect($all)->keyBy('template_id');
        $this->assertFalse($byId['warung-2']['is_active']);
        $this->assertTrue($byId['warung']['is_active']);

        $activeIds = TemplateSetting::activeTemplateIds();
        $this->assertNotContains('warung-2', $activeIds);
        $this->assertContains('warung', $activeIds);
        $this->assertCount(7, $activeIds);
    }

    public function test_template_setting_defaults_all_eight_templates_active_when_table_is_empty(): void
    {
        $this->assertDatabaseCount('template_settings', 0);

        $all = TemplateSetting::allWithDefaults();
        $activeIds = TemplateSetting::activeTemplateIds();

        $this->assertCount(8, $all);
        $this->assertSame(SaveSiteRequest::ALLOWED_TEMPLATES, $activeIds);

        foreach ($all as $item) {
            $this->assertTrue($item['is_active']);
            $this->assertContains($item['template_id'], SaveSiteRequest::ALLOWED_TEMPLATES);
        }
    }

    public function test_app_setting_admin_contact_builds_whatsapp_url_and_handles_empty_whatsapp(): void
    {
        $defaultContact = AppSetting::adminContact();

        $this->assertSame([
            'name' => 'Tim INAmikro',
            'whatsapp' => '',
            'waUrl' => null,
        ], $defaultContact);

        AppSetting::setValue('admin_name', '');
        AppSetting::setValue('admin_whatsapp', '');

        $this->assertSame([
            'name' => 'Tim INAmikro',
            'whatsapp' => '',
            'waUrl' => null,
        ], AppSetting::adminContact());

        AppSetting::setValue('admin_name', 'Admin INAmikro');
        AppSetting::setValue('admin_whatsapp', '6281234567890');

        $this->assertSame([
            'name' => 'Admin INAmikro',
            'whatsapp' => '6281234567890',
            'waUrl' => 'https://wa.me/6281234567890?text='.rawurlencode('Halo kak, butuh bantuan INAmikro.'),
        ], AppSetting::adminContact());
    }
}
