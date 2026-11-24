<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Banners\BannerManager;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Dashboard\ProfileForm;
use App\Livewire\Dashboard\TemplateSelector;
use App\Livewire\Products\ProductForm;
use App\Models\Banner;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteVisit;
use App\Models\WhatsappClick;
use App\Services\WebsitePublishValidator;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoUmkmSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FullUmkmJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->seed(CategorySeeder::class);
    }

    /**
     * Test the complete UMKM lifecycle journey from end-to-end:
     * 1. Registration -> auto provisions User, Business, Website (draft), and Theme
     * 2. Business profile update (description, address, tagline, WhatsApp)
     * 3. Product creation with pricing, discounts, stock, and variant options
     * 4. Hero banner upload
     * 5. Simplified Template Selection with curated design presets
     * 6. Publishing validation & toggling website status to published
     * 7. Public storefront visit and analytics recording
     * 8. Public product detail page access
     * 9. WhatsApp ordering click recording
     * 10. UMKM Dashboard metric reflection (products, page views, WA clicks)
     * 11. Superadmin Dashboard platform metric reflection
     * 12. Unpublish and 404 verification
     */
    public function test_complete_umkm_lifecycle_journey(): void
    {
        Storage::fake('public');

        $kulinerCategory = BusinessCategory::where('slug', 'kuliner')->firstOrFail();
        $foodTemplate = Template::where('slug', 'food')->firstOrFail();

        // -------------------------------------------------------------
        // Step 1: UMKM User Registration
        // -------------------------------------------------------------
        $registerPayload = [
            'name' => 'Dewi Lestari',
            'email' => 'dewi@sambalnusantara.com',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
            'phone' => '081234567890',
            'whatsapp' => '081234567890',
            'business_name' => 'Sambal Bu Dewi',
            'category_id' => $kulinerCategory->id,
        ];

        $registerResponse = $this->post('/register', $registerPayload);
        $registerResponse->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'dewi@sambalnusantara.com')->firstOrFail();
        $this->assertSame('umkm', $user->role);
        $this->assertTrue(Hash::check('Rahasia123!', $user->password));

        $business = Business::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Sambal Bu Dewi', $business->name);
        $this->assertSame('081234567890', $business->whatsapp);
        $this->assertSame($kulinerCategory->id, $business->category_id);

        $website = Website::where('business_id', $business->id)->firstOrFail();
        $this->assertSame('draft', $website->status);
        $this->assertNotEmpty($website->slug);

        $theme = Theme::where('website_id', $website->id)->firstOrFail();
        $this->assertNotNull($theme->primary_color);
        $this->assertNotNull($theme->font_family);

        // Verify draft store is not yet accessible to public visitors
        $this->get("/{$website->slug}")->assertStatus(404);

        // -------------------------------------------------------------
        // Step 2: Update Business Profile & Contact
        // -------------------------------------------------------------
        Livewire::actingAs($user)
            ->test(ProfileForm::class)
            ->assertSet('name', 'Sambal Bu Dewi')
            ->set('description', 'Sambal rumahan khas Nusantara dibuat dengan cabai segar pilihan tanpa bahan pengawet.')
            ->set('address', 'Jl. Malioboro No. 88, Danurejan, Kota Yogyakarta')
            ->set('operating_hours', 'Senin - Sabtu: 08.00 - 17.00 WIB')
            ->set('whatsapp', '081298765432')
            ->set('phone', '081298765432')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Profil usaha berhasil diperbarui.');

        // Optionally set tagline on business for branding
        $business->update(['tagline' => 'Sensasi Pedas Nagih Asli Nusantara']);

        $business->refresh();
        $this->assertSame('Sensasi Pedas Nagih Asli Nusantara', $business->tagline);
        $this->assertSame('Sambal rumahan khas Nusantara dibuat dengan cabai segar pilihan tanpa bahan pengawet.', $business->description);
        $this->assertSame('081298765432', $business->whatsapp);

        // -------------------------------------------------------------
        // Step 3: Product CRUD (Create Products with Pricing, Discount & Variants)
        // -------------------------------------------------------------
        $productImageFile1 = UploadedFile::fake()->create('sambal-bawang.jpg', 100, 'image/jpeg');

        Livewire::actingAs($user)
            ->test(ProductForm::class)
            ->set('name', 'Sambal Bawang Pedas Nampol')
            ->set('price', 35000)
            ->set('discount_price', 30000)
            ->set('stock', 100)
            ->set('label', 'Best Seller')
            ->set('description', 'Sambal bawang homemade dengan aroma gurih bawang dan sensasi pedas cabai rawit merah segar.')
            ->set('variant_string', 'Pedas Sedang, Super Pedas')
            ->set('is_available', true)
            ->set('images', [$productImageFile1])
            ->call('save')
            ->assertHasNoErrors();

        $product1 = Product::where('business_id', $business->id)
            ->where('slug', 'sambal-bawang-pedas-nampol')
            ->firstOrFail();

        $this->assertSame('Sambal Bawang Pedas Nampol', $product1->name);
        $this->assertEquals(35000, (float) $product1->price);
        $this->assertEquals(30000, (float) $product1->discount_price);
        $this->assertSame(100, $product1->stock);
        $this->assertSame(['Pedas Sedang', 'Super Pedas'], $product1->variant_options);
        $this->assertTrue($product1->is_available);
        $this->assertCount(1, $product1->images);

        // Add second product
        $productImageFile2 = UploadedFile::fake()->create('sambal-terasi.jpg', 100, 'image/jpeg');

        Livewire::actingAs($user)
            ->test(ProductForm::class)
            ->set('name', 'Sambal Terasi Juara')
            ->set('price', 38000)
            ->set('discount_price', null)
            ->set('stock', 50)
            ->set('label', 'Favorit')
            ->set('description', 'Sambal terasi matang dengan aroma terasi khas pesisir yang harum menggugah selera.')
            ->set('variant_string', 'Level 1, Level 3, Level 5')
            ->set('is_available', true)
            ->set('images', [$productImageFile2])
            ->call('save')
            ->assertHasNoErrors();

        $product2 = Product::where('business_id', $business->id)
            ->where('slug', 'sambal-terasi-juara')
            ->firstOrFail();
        $this->assertSame('Sambal Terasi Juara', $product2->name);
        $this->assertEquals(38000, (float) $product2->price);
        $this->assertNull($product2->discount_price);

        // -------------------------------------------------------------
        // Step 4: Hero Banner Upload
        // -------------------------------------------------------------
        $bannerImage = UploadedFile::fake()->create('hero-promo.jpg', 150, 'image/jpeg');

        Livewire::actingAs($user)
            ->test(BannerManager::class)
            ->set('image', $bannerImage)
            ->set('title', 'Sensasi Pedas Nagih Asli Sambal Bu Dewi')
            ->set('subtitle', 'Pesan langsung sekarang via WhatsApp dan dapatkan diskon promo spesial!')
            ->set('button_text', 'Pesan Sambal Sekarang')
            ->set('button_url', '#katalog')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Banner promosi berhasil ditambahkan.');

        $banner = Banner::where('business_id', $business->id)->firstOrFail();
        $this->assertSame('Sensasi Pedas Nagih Asli Sambal Bu Dewi', $banner->title);
        $this->assertSame('Pesan Sambal Sekarang', $banner->button_text);
        $this->assertTrue($banner->is_active);

        // -------------------------------------------------------------
        // Step 5: Simplified Template Selection & Curated Presets
        // -------------------------------------------------------------
        Livewire::actingAs($user)
            ->test(TemplateSelector::class)
            ->call('selectTemplate', $foodTemplate->id)
            ->assertSee('Tampilan toko berhasil diubah ke Food & Beverage!');

        $website->refresh();
        $this->assertSame($foodTemplate->id, $website->template_id);

        $theme->refresh();
        $this->assertSame('#DC2626', $theme->primary_color);
        $this->assertSame('#B91C1C', $theme->secondary_color);
        $this->assertSame('Poppins', $theme->font_family);

        // Preview route accessible by owner
        $previewResponse = $this->actingAs($user)->get(route('builder.preview', $website));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('--color-primary: #DC2626', false);
        $previewResponse->assertSee('Sambal Bu Dewi');

        // -------------------------------------------------------------
        // Step 6: Website Publish Validation & Toggling Status
        // -------------------------------------------------------------
        $publishValidator = app(WebsitePublishValidator::class);
        $validationResult = $publishValidator->validate($website);
        $this->assertTrue($validationResult->isValid(), 'Publish validation should pass when profile and products are ready.');
        $this->assertEmpty($validationResult->getErrors());

        Livewire::actingAs($user)
            ->test(TemplateSelector::class)
            ->call('togglePublish')
            ->assertSee('Selamat! Website toko online Anda berhasil diterbitkan.');

        $website->refresh();
        $this->assertSame('published', $website->status);
        $this->assertNotNull($website->published_at);

        // -------------------------------------------------------------
        // Step 7: Public Storefront Visit
        // -------------------------------------------------------------
        $this->assertDatabaseCount('website_visits', 0);

        // Unauthenticated visitor visits the published site
        $publicVisitResponse = $this->get("/{$website->slug}");
        $publicVisitResponse->assertStatus(200);
        $publicVisitResponse->assertSee('Sambal Bu Dewi');
        $publicVisitResponse->assertSee('Sensasi Pedas Nagih Asli Nusantara');
        $publicVisitResponse->assertSee('Sensasi Pedas Nagih Asli Sambal Bu Dewi');
        $publicVisitResponse->assertSee('Sambal Bawang Pedas Nampol');
        $publicVisitResponse->assertSee('Sambal Terasi Juara');

        // Verify website visit analytics recorded
        $this->assertDatabaseHas('website_visits', [
            'website_id' => $website->id,
        ]);
        $this->assertSame(1, WebsiteVisit::where('website_id', $website->id)->count());

        // -------------------------------------------------------------
        // Step 8: Public Product Detail View
        // -------------------------------------------------------------
        $detailResponse = $this->get("/{$website->slug}/produk/{$product1->slug}");
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Sambal Bawang Pedas Nampol');
        $detailResponse->assertSee('Pedas Sedang');
        $detailResponse->assertSee('Super Pedas');
        $detailResponse->assertSee('Rp 30.000');
        $detailResponse->assertSee('wa.me/6281298765432', false);

        // -------------------------------------------------------------
        // Step 9: WhatsApp Ordering Click Analytics
        // -------------------------------------------------------------
        $this->assertDatabaseCount('whatsapp_clicks', 0);

        // 1st click from product detail
        $clickDetailResponse = $this->postJson("/{$website->slug}/wa-click", [
            'product_id' => $product1->id,
            'source' => 'product_detail',
        ]);
        $clickDetailResponse->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('whatsapp_clicks', [
            'website_id' => $website->id,
            'product_id' => $product1->id,
            'source' => 'product_detail',
        ]);

        // 2nd click from floating button
        $clickFloatingResponse = $this->postJson("/{$website->slug}/wa-click", [
            'source' => 'floating_button',
        ]);
        $clickFloatingResponse->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('whatsapp_clicks', [
            'website_id' => $website->id,
            'product_id' => null,
            'source' => 'floating_button',
        ]);

        $this->assertSame(2, WhatsappClick::where('website_id', $website->id)->count());

        // -------------------------------------------------------------
        // Step 10: UMKM Dashboard Metrics Reflection
        // -------------------------------------------------------------
        $dashboardResponse = $this->actingAs($user)->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Sambal Bu Dewi');
        $dashboardResponse->assertSee('published');

        Livewire::actingAs($user)
            ->test(DashboardIndex::class)
            ->assertSet('totalProducts', 2)
            ->assertSet('totalViews', 1)
            ->assertSet('totalWaClicks', 2)
            ->assertSee('Sambal Bu Dewi')
            ->assertSee('published');

        // -------------------------------------------------------------
        // Step 11: Superadmin Dashboard Platform Metric Reflection
        // -------------------------------------------------------------
        $admin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'admin@inamikro.com',
            'role' => 'admin',
        ]);

        $adminResponse = $this->actingAs($admin)->get('/admin/dashboard');
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Sambal Bu Dewi');

        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->assertSet('totalUmkm', 1)
            ->assertSet('totalPublishedWebsites', 1)
            ->assertSet('totalProducts', 2)
            ->assertSet('totalVisits', 1)
            ->assertSet('totalWaClicks', 2)
            ->assertSee('Sambal Bu Dewi');

        // -------------------------------------------------------------
        // Step 12: Unpublish Lifecycle & Public 404 Verification
        // -------------------------------------------------------------
        Livewire::actingAs($user)
            ->test(TemplateSelector::class)
            ->call('togglePublish')
            ->assertSee('Website toko berhasil diubah menjadi Draft.');

        $website->refresh();
        $this->assertSame('draft', $website->status);

        // Public visitor can no longer access draft site
        $this->get("/{$website->slug}")->assertStatus(404);
        $this->get("/{$website->slug}/produk/{$product1->slug}")->assertStatus(404);

        // Wa-click returns 404 for draft site
        $this->postJson("/{$website->slug}/wa-click", ['source' => 'product_detail'])->assertStatus(404);
    }

    /**
     * Test the seeded demo UMKM store verification:
     * - Runs DatabaseSeeder / DemoUmkmSeeder
     * - Verifies seeded store structure, user, business, products, banners, theme
     * - Public visitors can access store and product details
     * - WhatsApp click events are recorded properly
     * - Dashboard and Superadmin metrics reflect seeded data
     */
    public function test_seeded_demo_store_verification(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Verify Seeded User & Business
        $demoUser = User::where('email', 'demo@inamikro.com')->first();
        $this->assertNotNull($demoUser, 'Demo UMKM user should be seeded.');
        $this->assertSame('umkm', $demoUser->role);

        $business = Business::where('user_id', $demoUser->id)->first();
        $this->assertNotNull($business, 'Demo business should be seeded.');
        $this->assertSame('Kopi Nusantara', $business->name);
        $this->assertSame('kopi-nusantara', $business->slug);
        $this->assertSame('6281234567890', $business->whatsapp);

        // 2. Verify Seeded Website & Theme
        $website = Website::where('business_id', $business->id)->first();
        $this->assertNotNull($website, 'Demo website should be seeded.');
        $this->assertSame('kopi-nusantara', $website->slug);
        $this->assertSame('published', $website->status);
        $this->assertNotNull($website->published_at);

        $theme = Theme::where('website_id', $website->id)->first();
        $this->assertNotNull($theme, 'Demo theme should be seeded.');
        $this->assertSame('#8B5A2B', $theme->primary_color);
        $this->assertSame('Inter', $theme->font_family);

        // 3. Verify Seeded Products
        $products = Product::where('business_id', $business->id)->get();
        $this->assertGreaterThanOrEqual(3, $products->count(), 'Seeded store should have at least 3 products.');

        $gayoProduct = Product::where('business_id', $business->id)
            ->where('slug', 'arabika-gayo-single-origin-250g')
            ->first();
        $this->assertNotNull($gayoProduct);
        $this->assertSame('Arabika Gayo Single Origin 250g', $gayoProduct->name);
        $this->assertEquals(75000, (float) $gayoProduct->price);
        $this->assertEquals(65000, (float) $gayoProduct->discount_price);
        $this->assertIsArray($gayoProduct->variant_options);
        $this->assertContains('Biji Utuh', $gayoProduct->variant_options);

        // 4. Verify Public Storefront Access
        $publicResponse = $this->get('/kopi-nusantara');
        $publicResponse->assertStatus(200);
        $publicResponse->assertSee('Kopi Nusantara');
        $publicResponse->assertSee('Cita Rasa Kopi Asli Indonesia');
        $publicResponse->assertSee('Arabika Gayo Single Origin 250g');
        $publicResponse->assertSee('Robusta Temanggung 250g');
        $publicResponse->assertSee('Es Kopi Susu Gula Aren 1 Liter');
        $publicResponse->assertSee('Nikmati Kopi Terbaik Nusantara');

        // Verify website visit recorded
        $this->assertDatabaseHas('website_visits', [
            'website_id' => $website->id,
        ]);

        // 5. Verify Public Product Detail Access
        $detailResponse = $this->get('/kopi-nusantara/produk/arabika-gayo-single-origin-250g');
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('Arabika Gayo Single Origin 250g');
        $detailResponse->assertSee('Biji Utuh');
        $detailResponse->assertSee('Giling Kasar');
        $detailResponse->assertSee('Giling Halus');
        $detailResponse->assertSee('Rp 65.000');
        $detailResponse->assertSee('wa.me/6281234567890', false);

        // 6. Verify WhatsApp Click on Demo Product
        $initialClickCount = WhatsappClick::where('website_id', $website->id)->count();

        $clickResponse = $this->postJson('/kopi-nusantara/wa-click', [
            'product_id' => $gayoProduct->id,
            'source' => 'product_detail',
        ]);
        $clickResponse->assertStatus(200)->assertJson(['success' => true]);

        $newClickCount = WhatsappClick::where('website_id', $website->id)->count();
        $this->assertSame($initialClickCount + 1, $newClickCount);

        // 7. Verify Demo UMKM Dashboard
        $dashboardResponse = $this->actingAs($demoUser)->get('/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('Kopi Nusantara');
        $dashboardResponse->assertSee('published');

        Livewire::actingAs($demoUser)
            ->test(DashboardIndex::class)
            ->assertSet('totalProducts', 3)
            ->assertSee('Kopi Nusantara')
            ->assertSee('published');

        // 8. Verify Superadmin Dashboard reflects Demo UMKM
        $admin = User::factory()->create(['role' => 'admin']);
        $adminDashboardResponse = $this->actingAs($admin)->get('/admin/dashboard');
        $adminDashboardResponse->assertStatus(200);
        $adminDashboardResponse->assertSee('Kopi Nusantara');

        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->assertSee('Kopi Nusantara')
            ->assertSet('totalUmkm', 1)
            ->assertSet('totalPublishedWebsites', 1);
    }
}
