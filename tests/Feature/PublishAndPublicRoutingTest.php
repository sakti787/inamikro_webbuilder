<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteVisit;
use App\Services\SlugService;
use App\Services\WebsitePublishValidator;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishAndPublicRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BusinessCategory $category;

    private Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->template = Template::where('slug', 'minimalist')->firstOrFail();

        $this->user = User::factory()->create(['role' => 'umkm']);

        $this->category = BusinessCategory::create([
            'name' => 'Fashion & Craft',
            'slug' => 'fashion-craft',
        ]);
    }

    private function createBusiness(array $overrides = []): Business
    {
        return Business::create(array_merge([
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'name' => 'Batik Nusantara Laris',
            'slug' => 'batik-nusantara-laris',
            'tagline' => 'Warisan Budaya Terbaik',
            'description' => 'Menyediakan aneka batik tulis dan cap premium berkualitas tinggi.',
            'phone' => '02198765432',
            'whatsapp' => '081234567890',
            'email' => 'halo@batiknusantara.com',
            'address' => 'Jl. Malioboro No. 12, Yogyakarta',
        ], $overrides));
    }

    private function createWebsite(Business $business, array $overrides = []): Website
    {
        $website = Website::create(array_merge([
            'business_id' => $business->id,
            'template_id' => $this->template->id,
            'slug' => $business->slug,
            'status' => 'draft',
        ], $overrides));

        Theme::create([
            'website_id' => $website->id,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'background_color' => '#FFFFFF',
            'text_color' => '#1F2937',
            'font_family' => 'Inter',
            'button_style' => 'rounded',
            'border_radius' => '8px',
        ]);

        return $website;
    }

    private function createProduct(Business $business, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'business_id' => $business->id,
            'name' => 'Kemeja Batik Parang',
            'slug' => 'kemeja-batik-parang',
            'description' => 'Kemeja batik bahan katun primisima halus dan adem.',
            'price' => 150000,
            'discount_price' => 125000,
            'stock' => 20,
            'is_available' => true,
        ], $overrides));
    }

    public function test_slug_service_identifies_reserved_slugs(): void
    {
        $slugService = app(SlugService::class);

        $reservedSlugs = [
            'admin', 'login', 'register', 'dashboard', 'builder',
            'api', 'logout', 'css', 'js', 'storage', 'livewire',
            'up', 'health',
        ];

        foreach ($reservedSlugs as $reserved) {
            $this->assertTrue($slugService->isReserved($reserved), "Slug {$reserved} should be reserved.");
            $this->assertTrue($slugService->isReserved(strtoupper($reserved)), "Uppercase slug {$reserved} should be reserved.");
            $this->assertFalse($slugService->isAvailable($reserved), "Reserved slug {$reserved} should not be available.");
        }

        $this->assertFalse($slugService->isReserved('toko-batik-jogja'));
        $this->assertTrue($slugService->isAvailable('toko-batik-jogja'));
    }

    public function test_slug_service_generates_unique_slug_and_handles_collisions(): void
    {
        $slugService = app(SlugService::class);

        $slug1 = $slugService->generateUniqueSlug('Batik Nusantara Laris');
        $this->assertEquals('batik-nusantara-laris', $slug1);

        $business = $this->createBusiness();
        $this->createWebsite($business, ['slug' => 'batik-nusantara-laris']);

        $slug2 = $slugService->generateUniqueSlug('Batik Nusantara Laris');
        $this->assertEquals('batik-nusantara-laris-1', $slug2);

        // Reserved word collision
        $slugReserved = $slugService->generateUniqueSlug('Dashboard');
        $this->assertNotEquals('dashboard', $slugReserved);
        $this->assertEquals('dashboard-1', $slugReserved);
    }

    public function test_website_publish_validator_fails_if_business_name_missing(): void
    {
        $business = $this->createBusiness(['name' => '']);
        $website = $this->createWebsite($business);
        $this->createProduct($business);

        $validator = app(WebsitePublishValidator::class);
        $result = $validator->validate($website);

        $this->assertFalse($result->isValid());
        $this->assertContains('Nama bisnis wajib diisi sebelum publikasi.', $result->getErrors());
    }

    public function test_website_publish_validator_fails_if_whatsapp_missing_or_invalid(): void
    {
        $business = $this->createBusiness(['whatsapp' => '']);
        $website = $this->createWebsite($business);
        $this->createProduct($business);

        $validator = app(WebsitePublishValidator::class);
        $result = $validator->validate($website);

        $this->assertFalse($result->isValid());
        $this->assertContains('Nomor WhatsApp wajib diisi sebelum publikasi.', $result->getErrors());

        $business->update(['whatsapp' => '12345']);
        $resultInvalid = $validator->validate($website->fresh());
        $this->assertFalse($resultInvalid->isValid());
        $this->assertContains('Format nomor WhatsApp tidak valid.', $resultInvalid->getErrors());
    }

    public function test_website_publish_validator_fails_if_no_active_products(): void
    {
        $business = $this->createBusiness();
        $website = $this->createWebsite($business);

        $validator = app(WebsitePublishValidator::class);
        $result = $validator->validate($website);

        $this->assertFalse($result->isValid());
        $this->assertContains('Minimal harus memiliki 1 produk aktif sebelum publikasi.', $result->getErrors());

        // Inactive product should still fail
        $product = $this->createProduct($business, ['is_available' => false]);
        $resultWithInactive = $validator->validate($website->fresh());
        $this->assertFalse($resultWithInactive->isValid());
        $this->assertContains('Minimal harus memiliki 1 produk aktif sebelum publikasi.', $resultWithInactive->getErrors());
    }

    public function test_website_publish_validator_fails_if_slug_is_reserved_or_taken(): void
    {
        $business = $this->createBusiness();
        $website = $this->createWebsite($business, ['slug' => 'dashboard']);
        $this->createProduct($business);

        $validator = app(WebsitePublishValidator::class);
        $result = $validator->validate($website);

        $this->assertFalse($result->isValid());
        $this->assertContains('Slug website menggunakan kata yang dicadangkan sistem.', $result->getErrors());

        // Slug taken by another website (tested on in-memory attribute)
        $otherBusiness = Business::create([
            'user_id' => User::factory()->create(['role' => 'umkm'])->id,
            'category_id' => $this->category->id,
            'name' => 'Toko Sebelah',
            'slug' => 'toko-sebelah',
            'whatsapp' => '08987654321',
        ]);
        $this->createWebsite($otherBusiness, ['slug' => 'toko-sebelah']);

        $website->slug = 'toko-sebelah';
        $resultTaken = $validator->validate($website);
        $this->assertFalse($resultTaken->isValid());
        $this->assertContains('Slug website sudah digunakan oleh website lain.', $resultTaken->getErrors());
    }

    public function test_website_publish_validator_passes_when_all_requirements_met(): void
    {
        $business = $this->createBusiness();
        $website = $this->createWebsite($business, ['slug' => 'batik-nusantara-laris']);
        $this->createProduct($business);

        $validator = app(WebsitePublishValidator::class);
        $result = $validator->validate($website);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function test_public_cannot_access_draft_or_unpublished_website(): void
    {
        $business = $this->createBusiness();
        $website = $this->createWebsite($business, [
            'slug' => 'toko-draft',
            'status' => 'draft',
        ]);
        $this->createProduct($business);

        $this->get('/toko-draft')->assertStatus(404);

        $website->update(['status' => 'unpublished']);
        $this->get('/toko-draft')->assertStatus(404);

        $this->get('/non-existent-random-slug')->assertStatus(404);
    }

    public function test_public_can_access_published_website_and_renders_template(): void
    {
        $business = $this->createBusiness(['name' => 'Toko Laris Manis']);
        $website = $this->createWebsite($business, [
            'slug' => 'toko-laris-manis',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $this->createProduct($business, [
            'name' => 'Baju Batik Primisima',
        ]);

        $response = $this->get('/toko-laris-manis');

        $response->assertStatus(200);
        $response->assertSee('Toko Laris Manis');
        $response->assertSee('Baju Batik Primisima');
    }

    public function test_public_website_visit_is_recorded(): void
    {
        $business = $this->createBusiness();
        $website = $this->createWebsite($business, [
            'slug' => 'toko-kunjungan',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $this->createProduct($business);

        $this->assertDatabaseCount('website_visits', 0);

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.1.100',
            'HTTP_USER_AGENT' => 'TestBrowser/1.0',
            'HTTP_REFERER' => 'https://google.com',
        ])->get('/toko-kunjungan');

        $response->assertStatus(200);

        $this->assertDatabaseCount('website_visits', 1);
        $visit = WebsiteVisit::first();

        $this->assertEquals($website->id, $visit->website_id);
        $this->assertNotNull($visit->visitor_hash);
        $this->assertEquals('https://google.com', $visit->referrer);
    }

    public function test_public_can_access_product_detail_page(): void
    {
        $business = $this->createBusiness(['name' => 'Toko Busana Cantik']);
        $website = $this->createWebsite($business, [
            'slug' => 'toko-busana',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $product = $this->createProduct($business, [
            'slug' => 'dress-batik-modern',
            'name' => 'Dress Batik Modern',
            'price' => 250000,
        ]);

        $response = $this->get('/toko-busana/produk/dress-batik-modern');

        $response->assertStatus(200);
        $response->assertSee('Dress Batik Modern');
        $response->assertSee('Toko Busana Cantik');
    }

    public function test_public_cannot_access_product_detail_of_draft_website_or_invalid_product(): void
    {
        $business = $this->createBusiness();
        $website = $this->createWebsite($business, [
            'slug' => 'toko-draft-produk',
            'status' => 'draft',
        ]);
        $product = $this->createProduct($business, [
            'slug' => 'produk-draft',
        ]);

        // Draft website should return 404
        $this->get('/toko-draft-produk/produk/produk-draft')->assertStatus(404);

        // Published website with nonexistent product should return 404
        $website->update(['status' => 'published', 'published_at' => now()]);
        $this->get('/toko-draft-produk/produk/produk-tidak-ada')->assertStatus(404);

        // Product belonging to another business under this website's slug should return 404
        $otherBusiness = Business::create([
            'user_id' => User::factory()->create(['role' => 'umkm'])->id,
            'category_id' => $this->category->id,
            'name' => 'Bisnis Lain',
            'slug' => 'bisnis-lain',
            'whatsapp' => '0822222222',
        ]);
        $otherProduct = $this->createProduct($otherBusiness, ['slug' => 'produk-milik-lain']);

        $this->get('/toko-draft-produk/produk/produk-milik-lain')->assertStatus(404);
    }

    public function test_system_routes_are_not_intercepted_by_public_slug_routes(): void
    {
        $this->get('/health')->assertStatus(200)->assertJson(['status' => 'ok']);
        $this->get('/login')->assertStatus(200)->assertSee('Masuk');
        $this->get('/register')->assertStatus(200)->assertSee('Daftar Akun UMKM');
    }
}
