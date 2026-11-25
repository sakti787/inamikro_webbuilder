<?php

namespace Tests\Feature;

use App\Http\Requests\Api\SaveSiteRequest;
use App\Models\AdminLog;
use App\Models\AppSetting;
use App\Models\Site;
use App\Models\SiteHistory;
use App\Models\TemplateSetting;
use App\Models\User;
use App\Services\SiteHtmlSanitizer;
use Database\Seeders\DemoUmkmSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_site_is_accessible_at_root_slug_with_csp_header(): void
    {
        $user = User::factory()->create([
            'business_name' => 'Warung Kopi Nusantara',
            'slug' => 'warung-kopi-nusantara',
        ]);

        $sanitizedHtml = SiteHtmlSanitizer::sanitize('<!doctype html><html><head><title>Warung Kopi Nusantara</title></head><body><h1>Warung Kopi Nusantara</h1><p>Kopi seduh harian.</p></body></html>');

        $site = Site::factory()->published()->create([
            'user_id' => $user->id,
            'slug' => $user->slug,
            'rendered_html' => $sanitizedHtml,
        ]);

        $response = $this->get('/'.$site->slug);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee('Warung Kopi Nusantara');
        $response->assertSee('Kopi seduh harian.');

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString(SiteHtmlSanitizer::cspScriptHash(), $csp);
        $this->assertSame(SiteHtmlSanitizer::cspHeader(), $csp);
    }

    public function test_draft_site_returns_404_at_root_slug(): void
    {
        $user = User::factory()->create([
            'slug' => 'kedai-draft',
        ]);

        $draftSite = Site::factory()->create([
            'user_id' => $user->id,
            'slug' => $user->slug,
            'status' => 'draft',
            'rendered_html' => '<html><body>Draft Content</body></html>',
        ]);

        $this->get('/'.$draftSite->slug)
            ->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/tidak-ada')
            ->assertNotFound();
    }

    public function test_system_routes_take_precedence_over_public_slug_fallback(): void
    {
        Site::factory()->published()->create([
            'slug' => 'login',
            'rendered_html' => '<html><body>Fake Login Site</body></html>',
        ]);

        Site::factory()->published()->create([
            'slug' => 'studio',
            'rendered_html' => '<html><body>Fake Studio Site</body></html>',
        ]);

        $loginResponse = $this->get('/login');
        $loginResponse->assertOk();
        $loginResponse->assertSee('Masuk ke INAweb Studio');
        $loginResponse->assertDontSee('Fake Login Site');

        $this->get('/register')
            ->assertOk()
            ->assertSee('Daftar Akun UMKM');

        $this->get('/studio')
            ->assertRedirect(route('login'));

        $this->get('/up')
            ->assertOk();
    }

    public function test_demo_umkm_seeder_creates_ready_to_use_umkm_and_admin_accounts_idempotently(): void
    {
        $this->seed(DemoUmkmSeeder::class);
        $this->seed(DemoUmkmSeeder::class);

        $admin = User::where('email', 'admin@inamikro.id')->first();

        $this->assertNotNull($admin);
        $this->assertSame('Admin Pendamping', $admin->name);
        $this->assertSame('Tim INAmikro', $admin->business_name);
        $this->assertSame('tim-inamikro', $admin->slug);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('password', $admin->password));

        $this->assertSame(8, TemplateSetting::count());

        foreach (SaveSiteRequest::ALLOWED_TEMPLATES as $templateId) {
            $templateSetting = TemplateSetting::where('template_id', $templateId)->first();
            $this->assertNotNull($templateSetting);
            $this->assertTrue($templateSetting->is_active);
        }

        $this->assertSame('0', AppSetting::getValue('auto_approve'));
        $this->assertSame('Tim INAmikro', AppSetting::getValue('admin_name'));
        $this->assertSame('6281234567890', AppSetting::getValue('admin_whatsapp'));

        $sari = User::where('email', 'sari@inamikro.id')->first();

        $this->assertNotNull($sari);
        $this->assertSame('Sari Rahmawati', $sari->name);
        $this->assertSame('Dapur Bu Sari', $sari->business_name);
        $this->assertSame('dapur-bu-sari', $sari->slug);
        $this->assertSame('umkm', $sari->role);
        $this->assertTrue(Hash::check('password', $sari->password));

        $sariSite = $sari->site()->first();
        $this->assertNotNull($sariSite);
        $this->assertSame('published', $sariSite->status);
        $this->assertTrue(SaveSiteRequest::hasCompletePublishContent($sariSite->content));
        $this->assertSame('Profil usaha dan menu lengkap, siap tayang.', $sariSite->admin_note);
        $this->assertNull($sariSite->revision_note);
        $this->assertStringContainsString(SiteHtmlSanitizer::CLIENT_JS, (string) $sariSite->rendered_html);

        $this->get('/dapur-bu-sari')
            ->assertOk()
            ->assertSee('Dapur Bu Sari')
            ->assertSee('Paket Nasi Ayam Bakar');

        $budi = User::where('email', 'budi@inamikro.id')->first();

        $this->assertNotNull($budi);
        $this->assertSame('Budi Santoso', $budi->name);
        $this->assertSame('Sigap Teknik', $budi->business_name);
        $this->assertSame('sigap-teknik', $budi->slug);
        $this->assertSame('umkm', $budi->role);
        $this->assertTrue(Hash::check('password', $budi->password));

        $budiSite = $budi->site()->first();
        $this->assertNotNull($budiSite);
        $this->assertSame('pending', $budiSite->status);
        $this->assertTrue(SaveSiteRequest::hasCompletePublishContent($budiSite->content));

        $this->get('/sigap-teknik')
            ->assertNotFound();

        $this->assertSame(3, SiteHistory::count());
        $this->assertSame(2, AdminLog::count());
    }
}
