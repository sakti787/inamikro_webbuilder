<?php

namespace Tests\Feature;

use App\Models\AdminLog;
use App\Models\AppSetting;
use App\Models\Site;
use App\Models\TemplateSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_page_renders_panel_without_tanya_admin_inbox_or_dummy_reset(): void
    {
        $admin = User::factory()->create([
            'name' => 'Pendamping Utama',
            'email' => 'admin@inamikro.id',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin'));

        $response->assertOk();
        $response->assertViewIs('admin.index');
        $response->assertSee('Panel Pendamping');
        $response->assertSee('Nomor WhatsApp di atas langsung dipakai oleh tombol Tanya admin di Studio UMKM.');
        $response->assertDontSee('view-pesan', false);
        $response->assertDontSee('navPesan', false);
        $response->assertDontSee('Data contoh');
        $response->assertDontSee('Kembalikan data contoh');
        $response->assertDontSee('btnReset', false);
        $response->assertDontSee('replyBtn', false);
    }

    public function test_admin_page_injects_bootstrap_config_and_avatar_logout_popover(): void
    {
        $admin = User::factory()->create([
            'name' => 'Rina Kartika',
            'email' => 'rina.admin@inamikro.id',
            'role' => 'admin',
        ]);

        $umkmWithSite = User::factory()->create([
            'name' => 'Sari Dewi',
            'email' => 'sari@inamikro.id',
            'business_name' => 'Dapur Bu Sari',
            'slug' => 'dapur-bu-sari',
            'role' => 'umkm',
        ]);

        Site::factory()->for($umkmWithSite)->create([
            'template_id' => 'warung',
            'slug' => $umkmWithSite->slug,
            'status' => 'pending',
            'brand_color' => '#dc2626',
            'content' => [
                'nama' => 'Dapur Bu Sari',
                'wa' => '081234567890',
                'jam' => '08.00 - 21.00',
                'produk' => [
                    ['nama' => 'Nasi Ayam Bakar', 'harga' => '25000', 'ket' => 'Enak'],
                ],
            ],
            'rendered_html' => '<!doctype html><html><body><h1>Dapur Bu Sari</h1></body></html>',
        ]);

        $umkmWithoutSite = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@inamikro.id',
            'business_name' => 'Bengkel Budi',
            'slug' => 'bengkel-budi',
            'role' => 'umkm',
        ]);

        TemplateSetting::create([
            'template_id' => 'toko-2',
            'is_active' => false,
        ]);

        AppSetting::setValue('auto_approve', '1');
        AppSetting::setValue('admin_name', 'Kak Rina');
        AppSetting::setValue('admin_whatsapp', '6281234567890');

        AdminLog::create([
            'actor_id' => $admin->id,
            'title' => 'Kontak pendamping diperbarui',
            'description' => 'Nama: Kak Rina',
            'who' => 'Pengaturan',
            'kind' => 'settings',
        ]);

        $response = $this->actingAs($admin)->get(route('admin'));

        $response->assertOk();
        $response->assertSee('id="adminAvatarBtn"', false);
        $response->assertSee('id="adminProfilePop"', false);
        $response->assertSee('Rina Kartika');
        $response->assertSee('rina.admin@inamikro.id');
        $response->assertSee('ADMIN');
        $response->assertSee('class="profile-logout"', false);
        $response->assertSee('<svg', false);
        $response->assertSee('method="POST" action="'.route('logout').'"', false);
        $response->assertSee('name="_token"', false);
        $response->assertSee('Keluar');
        $response->assertSee('window.INAWEB_ADMIN_CONFIG', false);

        $html = (string) $response->getContent();
        $this->assertMatchesRegularExpression('/window\.INAWEB_ADMIN_CONFIG\s*=\s*(\{.*?\});/s', $html);

        preg_match('/window\.INAWEB_ADMIN_CONFIG\s*=\s*(\{.*?\});/s', $html, $matches);
        $config = json_decode($matches[1], true);

        $this->assertIsArray($config);
        $this->assertSame('Rina Kartika', $config['userName']);
        $this->assertSame('rina.admin@inamikro.id', $config['userEmail']);
        $this->assertNotEmpty($config['csrfToken']);

        $this->assertSame(route('api.admin.bootstrap'), $config['routes']['bootstrap']);
        $this->assertSame(route('api.admin.logs'), $config['routes']['logs']);
        $this->assertSame(url('/api/admin/umkm'), $config['routes']['umkmBase']);
        $this->assertSame(url('/api/admin/templates'), $config['routes']['templatesBase']);
        $this->assertSame(route('api.admin.settings.auto-approve'), $config['routes']['autoApprove']);
        $this->assertSame(route('api.admin.settings.contact'), $config['routes']['contact']);
        $this->assertSame(route('logout'), $config['routes']['logout']);

        $this->assertIsArray($config['initialData']);
        $this->assertCount(2, $config['initialData']['umkm']);
        $this->assertCount(8, $config['initialData']['templates']);
        $this->assertTrue($config['initialData']['settings']['auto_approve']);
        $this->assertSame('Kak Rina', $config['initialData']['settings']['admin_name']);
        $this->assertSame('6281234567890', $config['initialData']['settings']['admin_whatsapp']);
        $this->assertCount(1, $config['initialData']['logs']);

        $umkmById = collect($config['initialData']['umkm'])->keyBy('id');
        $this->assertTrue($umkmById[$umkmWithSite->id]['has_site']);
        $this->assertFalse($umkmById[$umkmWithoutSite->id]['has_site']);
    }

    public function test_admin_detail_preview_iframe_uses_strict_sandbox_attribute(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin'));

        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertMatchesRegularExpression(
            '/<iframe\b[^>]*\bid="detailPreview"[^>]*\bsandbox="allow-scripts"[^>]*>/i',
            $html
        );
        $this->assertStringNotContainsString('allow-same-origin', $html);
    }

    public function test_admin_static_assets_exist_and_reuse_studio_templates_js(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('admin'));
        $this->assertFileDoesNotExist(public_path('admin-assets/templates.js'));

        $stylePath = public_path('admin-assets/style.css');
        $logPath = public_path('admin-assets/log.js');
        $settingsPath = public_path('admin-assets/settings.js');
        $appPath = public_path('admin-assets/app.js');

        $this->assertFileExists($stylePath);
        $this->assertFileExists($logPath);
        $this->assertFileExists($settingsPath);
        $this->assertFileExists($appPath);

        $this->assertGreaterThan(0, filesize($stylePath));
        $this->assertGreaterThan(0, filesize($logPath));
        $this->assertGreaterThan(0, filesize($settingsPath));
        $this->assertGreaterThan(0, filesize($appPath));

        $appJs = (string) file_get_contents($appPath);
        $this->assertStringContainsString('INAWEB_ADMIN_CONFIG', $appJs);
        $this->assertStringContainsString('rendered_html', $appJs);
        $this->assertStringContainsString('has_site', $appJs);
        $this->assertStringNotContainsString('localStorage', $appJs);
        $this->assertStringNotContainsString('function seed(', $appJs);
        $this->assertStringNotContainsString('btnReset', $appJs);
        $this->assertStringNotContainsString('replyBtn', $appJs);

        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get(route('admin'));

        $response->assertOk();
        $response->assertSee('studio-assets/templates.js', false);
        $response->assertSee('admin-assets/style.css', false);
        $response->assertSee('admin-assets/log.js', false);
        $response->assertSee('admin-assets/settings.js', false);
        $response->assertSee('admin-assets/app.js', false);
    }

    public function test_admin_page_escapes_xss_payloads_in_config_json_and_html_body(): void
    {
        $payload = '</script><script>alert(1)</script>';

        $admin = User::factory()->create([
            'name' => $payload,
            'email' => 'xss-admin@inamikro.id',
            'role' => 'admin',
        ]);

        $umkm = User::factory()->create([
            'name' => 'Pemilik UMKM',
            'business_name' => $payload,
            'slug' => 'umkm-xss-test',
            'role' => 'umkm',
        ]);

        Site::factory()->for($umkm)->create([
            'template_id' => 'warung',
            'slug' => $umkm->slug,
            'status' => 'pending',
            'content' => [
                'nama' => $payload,
                'wa' => '081234567890',
                'jam' => '08.00 - 17.00',
                'produk' => [['nama' => 'Produk', 'harga' => '10000', 'ket' => 'Ket']],
            ],
            'rendered_html' => '<!doctype html><html><body>'.$payload.'</body></html>',
        ]);

        $response = $this->actingAs($admin)->get(route('admin'));

        $response->assertOk();
        $response->assertDontSee($payload, false);

        $html = (string) $response->getContent();
        $this->assertSame(
            1,
            preg_match('/<script>\s*window\.INAWEB_ADMIN_CONFIG\s*=\s*(\{.*?\});\s*<\/script>/s', $html, $matches)
        );
        $this->assertStringNotContainsString('</script>', $matches[1]);

        $config = json_decode($matches[1], true);
        $this->assertIsArray($config);
        $this->assertSame($payload, $config['userName']);
        $this->assertSame($payload, $config['initialData']['umkm'][0]['business_name']);
        $this->assertSame('<!doctype html><html><body>'.$payload.'</body></html>', $config['initialData']['umkm'][0]['rendered_html']);
    }
}
