<?php

namespace Tests\Feature;

use App\Models\Site;
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

    public function test_demo_umkm_seeder_creates_ready_to_use_umkm_account(): void
    {
        $this->seed(DemoUmkmSeeder::class);
        $this->seed(DemoUmkmSeeder::class);

        $sari = User::where('email', 'sari@inamikro.id')->first();

        $this->assertNotNull($sari);
        $this->assertSame('Sari Rahmawati', $sari->name);
        $this->assertSame('Dapur Bu Sari', $sari->business_name);
        $this->assertSame('dapur-bu-sari', $sari->slug);
        $this->assertSame('umkm', $sari->role);
        $this->assertTrue(Hash::check('password', $sari->password));

        $budi = User::where('email', 'budi@inamikro.id')->first();

        $this->assertNotNull($budi);
        $this->assertSame('Budi Santoso', $budi->name);
        $this->assertSame('Sigap Teknik', $budi->business_name);
        $this->assertSame('sigap-teknik', $budi->slug);
        $this->assertSame('umkm', $budi->role);
        $this->assertTrue(Hash::check('password', $budi->password));
    }
}
