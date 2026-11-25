<?php

namespace Tests\Feature;

use App\Http\Requests\Api\SaveSiteRequest;
use App\Models\AppSetting;
use App\Models\Site;
use App\Models\TemplateSetting;
use App\Models\User;
use App\Services\SiteHtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudioViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_studio_to_login(): void
    {
        $response = $this->get(route('studio'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_umkm_can_view_studio_with_injected_config_and_logout_button(): void
    {
        $user = User::factory()->create([
            'name' => 'Sari Dewi',
            'email' => 'sari.dewi@inamikro.id',
            'business_name' => 'Warung Mbak Sari',
            'slug' => 'warung-mbak-sari',
            'role' => 'umkm',
        ]);

        $response = $this->actingAs($user)->get(route('studio'));

        $response->assertOk();
        $response->assertViewIs('studio.index');
        $response->assertSee('window.INAWEB_CONFIG', false);
        $response->assertSee('businessName', false);
        $response->assertSee('siteSlug', false);
        $response->assertSee('csrfToken', false);
        $response->assertSee('studio-assets/style.css', false);
        $response->assertSee('studio-assets/templates.js', false);
        $response->assertSee('studio-assets/app.js', false);
        $response->assertSee('id="avatarBtn"', false);
        $response->assertSee('id="profilePop"', false);
        $response->assertSee('Sari Dewi');
        $response->assertSee('sari.dewi@inamikro.id');
        $response->assertSee('Warung Mbak Sari');
        $response->assertSee('/warung-mbak-sari');
        $response->assertSee('UMKM');
        $response->assertSee('class="profile-logout"', false);
        $response->assertSee('<svg', false);
        $response->assertSee('action="'.route('logout').'"', false);
        $response->assertSee('name="_token"', false);
        $response->assertSee('Keluar');

        $html = $response->getContent();
        $this->assertIsString($html);
        $this->assertMatchesRegularExpression('/window\.INAWEB_CONFIG\s*=\s*(\{.*?\});/s', $html);

        preg_match('/window\.INAWEB_CONFIG\s*=\s*(\{.*?\});/s', $html, $matches);
        $config = json_decode($matches[1], true);

        $this->assertIsArray($config);
        $this->assertSame('Sari Dewi', $config['userName']);
        $this->assertSame('Warung Mbak Sari', $config['businessName']);
        $this->assertSame('warung-mbak-sari', $config['siteSlug']);
        $this->assertSame(url('/warung-mbak-sari'), $config['publicSiteUrl']);
        $this->assertSame(rtrim(url('/'), '/'), $config['publicBaseUrl']);
        $this->assertSame(SaveSiteRequest::ALLOWED_TEMPLATES, $config['activeTemplates']);
        $this->assertSame([
            'name' => 'Tim INAmikro',
            'whatsapp' => '',
            'waUrl' => null,
        ], $config['adminContact']);
        $this->assertFalse($config['autoApprove']);
        $this->assertNull($config['initialSite']);
        $this->assertNotEmpty($config['csrfToken']);
        $this->assertSame(route('api.site.upsert'), $config['routes']['siteSave']);
        $this->assertSame(route('api.site.publish'), $config['routes']['sitePublish']);
        $this->assertSame(route('api.site.destroy'), $config['routes']['siteDelete']);
        $this->assertSame(route('api.media.store'), $config['routes']['mediaStore']);
        $this->assertSame(route('api.media.destroy'), $config['routes']['mediaDelete']);
    }

    public function test_authenticated_umkm_receives_only_own_initial_site_and_escapes_user_fields(): void
    {
        $user = User::factory()->create([
            'name' => "O'Reilly <script>alert('xss')</script>",
            'business_name' => 'Warung <script>evil()</script>',
            'slug' => 'warung-aman',
        ]);
        $otherUser = User::factory()->create([
            'business_name' => 'Toko Rahasia Tetangga',
            'slug' => 'toko-rahasia',
        ]);

        $site = Site::factory()->for($user)->published()->create([
            'template_id' => 'warung-2',
            'slug' => $user->slug,
            'brand_color' => '#d97706',
            'content' => [
                'nama' => 'Warung Aman',
                'wa' => '081234567890',
                'jam' => '08.00 - 21.00',
                'produk' => [
                    ['nama' => 'Soto Ayam', 'harga' => '20000', 'ket' => 'Hangat'],
                ],
            ],
        ]);
        Site::factory()->for($otherUser)->published()->create([
            'template_id' => 'toko',
            'slug' => $otherUser->slug,
            'content' => [
                'nama' => 'Toko Rahasia Tetangga',
                'produk' => [],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('studio'));

        $response->assertOk();
        $html = (string) $response->getContent();

        $this->assertStringNotContainsString("<script>alert('xss')</script>", $html);
        $this->assertStringNotContainsString('<script>evil()</script>', $html);
        $this->assertStringNotContainsString('Toko Rahasia Tetangga', $html);
        $this->assertMatchesRegularExpression('/window\.INAWEB_CONFIG\s*=\s*(\{.*?\});/s', $html);

        preg_match('/window\.INAWEB_CONFIG\s*=\s*(\{.*?\});/s', $html, $matches);
        $config = json_decode($matches[1], true);

        $this->assertIsArray($config['initialSite']);
        $this->assertSame($site->id, $config['initialSite']['id']);
        $this->assertSame('warung-2', $config['initialSite']['template_id']);
        $this->assertSame('published', $config['initialSite']['status']);
        $this->assertSame('warung-aman', $config['initialSite']['slug']);
        $this->assertSame('Warung Aman', $config['initialSite']['content']['nama']);
    }

    public function test_studio_static_assets_exist_in_public_studio(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('studio'));

        $stylePath = public_path('studio-assets/style.css');
        $templatesPath = public_path('studio-assets/templates.js');
        $appPath = public_path('studio-assets/app.js');

        $this->assertFileExists($stylePath);
        $this->assertFileExists($templatesPath);
        $this->assertFileExists($appPath);

        $this->assertGreaterThan(0, filesize($stylePath));
        $this->assertGreaterThan(0, filesize($templatesPath));
        $this->assertGreaterThan(0, filesize($appPath));

        $templatesJs = (string) file_get_contents($templatesPath);
        $appJs = (string) file_get_contents($appPath);

        $this->assertStringContainsString('\/storage\/', $templatesJs);
        $this->assertStringContainsString('og:image', $templatesJs);
        $this->assertStringContainsString('publicSiteUrl', $templatesJs);
        $this->assertStringContainsString('INAWEB_CONFIG', $appJs);
        $this->assertStringContainsString('activeTemplates', $appJs);
        $this->assertStringContainsString('Pengajuan publikasi terkirim. Menunggu persetujuan admin.', $appJs);
        $this->assertStringContainsString('Nomor WhatsApp pendamping belum diatur.', $appJs);
        $this->assertStringContainsString('Fitur segera hadir.', $appJs);
        $this->assertStringNotContainsString('localStorage', $appJs);
    }

    public function test_templates_js_client_js_hash_matches_server_sanitizer_hash(): void
    {
        $templatesPath = public_path('studio-assets/templates.js');
        $this->assertFileExists($templatesPath);

        $contents = (string) file_get_contents($templatesPath);
        $marker = 'const CLIENT_JS = `';
        $start = strpos($contents, $marker);
        $this->assertNotFalse($start, 'Marker const CLIENT_JS = ` tidak ditemukan di public/studio-assets/templates.js.');

        $scriptStart = $start + strlen($marker);
        $end = strpos($contents, '`;', $scriptStart);
        $this->assertNotFalse($end, 'Penutup template literal CLIENT_JS tidak ditemukan.');

        $clientJs = substr($contents, $scriptStart, $end - $scriptStart);
        $clientHash = 'sha256-'.base64_encode(hash('sha256', $clientJs, true));

        $this->assertSame(SiteHtmlSanitizer::cspScriptHash(), $clientHash);
        $this->assertSame(SiteHtmlSanitizer::CLIENT_JS, $clientJs);
    }

    public function test_studio_injects_active_templates_admin_contact_and_revision_note(): void
    {
        TemplateSetting::create([
            'template_id' => 'toko-2',
            'is_active' => false,
        ]);
        TemplateSetting::create([
            'template_id' => 'jasa-2',
            'is_active' => false,
        ]);
        AppSetting::setValue('auto_approve', '1');
        AppSetting::setValue('admin_name', 'Kak Rina');
        AppSetting::setValue('admin_whatsapp', '6281234567890');

        $user = User::factory()->create([
            'business_name' => 'Dapur Bu Sari',
            'slug' => 'dapur-bu-sari',
            'role' => 'umkm',
        ]);
        Site::factory()->for($user)->create([
            'template_id' => 'warung',
            'slug' => $user->slug,
            'status' => 'draft',
            'revision_note' => 'Mohon lengkapi jam buka setiap hari.',
            'admin_note' => 'Catatan internal khusus admin.',
        ]);

        $response = $this->actingAs($user)->get(route('studio'));

        $response->assertOk();
        $response->assertSee('id="revisionNotice"', false);
        $response->assertDontSee('Catatan internal khusus admin.');

        $html = (string) $response->getContent();
        $this->assertMatchesRegularExpression('/window\.INAWEB_CONFIG\s*=\s*(\{.*?\});/s', $html);

        preg_match('/window\.INAWEB_CONFIG\s*=\s*(\{.*?\});/s', $html, $matches);
        $config = json_decode($matches[1], true);

        $this->assertIsArray($config);
        $this->assertSame(
            ['warung', 'warung-2', 'butik', 'butik-2', 'jasa', 'toko'],
            $config['activeTemplates'],
        );
        $this->assertSame('Kak Rina', $config['adminContact']['name']);
        $this->assertSame('6281234567890', $config['adminContact']['whatsapp']);
        $this->assertSame(
            'https://wa.me/6281234567890?text=Halo%20kak%2C%20butuh%20bantuan%20INAmikro.',
            $config['adminContact']['waUrl'],
        );
        $this->assertTrue($config['autoApprove']);
        $this->assertSame('Mohon lengkapi jam buka setiap hari.', $config['initialSite']['revision_note']);
        $this->assertArrayNotHasKey('admin_note', $config['initialSite']);
    }
}
