<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use App\Services\WhatsAppUrlGenerator;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppOrderingTest extends TestCase
{
    use RefreshDatabase;

    private Template $template;

    private User $user;

    private Business $business;

    private Product $product;

    private Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);
        $this->template = Template::where('slug', 'minimalist')->firstOrFail();

        $this->user = User::factory()->create(['role' => 'umkm']);

        $category = BusinessCategory::create([
            'name' => 'Fashion & Craft',
            'slug' => 'fashion-craft',
        ]);

        $this->business = Business::create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'name' => 'Batik Cantik Nusantara',
            'slug' => 'batik-cantik-nusantara',
            'tagline' => 'Elegansi Batik Nusantara',
            'description' => 'Toko batik tulis dan cap modern berkualitas tinggi.',
            'phone' => '02112345678',
            'whatsapp' => '0812-3456-7890',
            'email' => 'halo@batikcantik.com',
            'address' => 'Jl. Malioboro No. 45, Yogyakarta',
        ]);

        $prodCategory = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Kemeja Batik',
            'slug' => 'kemeja-batik',
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $prodCategory->id,
            'name' => 'Kemeja Sutra Premium',
            'slug' => 'kemeja-sutra-premium',
            'description' => 'Kemeja batik sutra halus bermotif klasik kontemporer.',
            'price' => 150000,
            'discount_price' => 135000,
            'stock' => 20,
            'label' => 'Best Seller',
            'variant_options' => ['M', 'L', 'XL'],
            'is_available' => true,
        ]);

        $this->website = Website::create([
            'business_id' => $this->business->id,
            'template_id' => $this->template->id,
            'slug' => 'toko-batik-cantik',
            'status' => 'published',
        ]);

        Theme::create([
            'website_id' => $this->website->id,
            'primary_color' => '#2563EB',
            'secondary_color' => '#1E40AF',
            'font_family' => 'Inter',
        ]);
    }

    public function test_whatsapp_url_generator_cleans_phone_numbers_correctly(): void
    {
        $generator = new WhatsAppUrlGenerator;

        $this->assertEquals('6281234567890', $generator->cleanPhoneNumber('0812-3456-7890'));
        $this->assertEquals('6281234567890', $generator->cleanPhoneNumber('+62 812 3456 7890'));
        $this->assertEquals('6281234567890', $generator->cleanPhoneNumber('81234567890'));
        $this->assertEquals('6281234567890', $generator->cleanPhoneNumber('6281234567890'));
        $this->assertEquals('6281234567890', $generator->cleanPhoneNumber('+62812-3456-7890'));
        $this->assertEquals('', $generator->cleanPhoneNumber(null));
        $this->assertEquals('', $generator->cleanPhoneNumber(''));
    }

    public function test_whatsapp_url_generator_formats_product_order_message(): void
    {
        $generator = new WhatsAppUrlGenerator;

        $url = $generator->generate(
            rawPhone: '0812-3456-7890',
            businessName: 'Batik Cantik',
            productName: 'Kemeja Sutra',
            price: 150000
        );

        $this->assertStringStartsWith('https://wa.me/6281234567890', $url);
        $this->assertStringContainsString('Kemeja%20Sutra', $url);

        $decoded = urldecode($url);
        $this->assertStringContainsString('Halo Batik Cantik', $decoded);
        $this->assertStringContainsString('Kemeja Sutra', $decoded);
        $this->assertStringContainsString('Rp 150.000', $decoded);
        $this->assertStringContainsString('Apakah produk ini masih tersedia?', $decoded);
    }

    public function test_whatsapp_url_generator_formats_product_order_message_with_variant(): void
    {
        $generator = new WhatsAppUrlGenerator;

        $url = $generator->forProduct(
            phone: '0812-3456-7890',
            businessName: 'Batik Cantik',
            productName: 'Kemeja Sutra',
            price: 150000,
            variant: 'Ukuran L'
        );

        $this->assertStringStartsWith('https://wa.me/6281234567890', $url);
        $decoded = urldecode($url);
        $this->assertStringContainsString('Ukuran L', $decoded);
        $this->assertStringContainsString('Rp 150.000', $decoded);
    }

    public function test_whatsapp_url_generator_formats_general_floating_inquiry_message(): void
    {
        $generator = new WhatsAppUrlGenerator;

        $url = $generator->forGeneralInquiry(
            phone: '0812-3456-7890',
            businessName: 'Batik Cantik'
        );

        $this->assertStringStartsWith('https://wa.me/6281234567890', $url);
        $decoded = urldecode($url);
        $this->assertStringContainsString('Halo Batik Cantik', $decoded);
        $this->assertStringContainsString('katalog', strtolower($decoded));
    }

    public function test_clicking_whatsapp_button_records_analytics_event(): void
    {
        $response = $this->postJson("/{$this->website->slug}/wa-click", [
            'product_id' => $this->product->id,
            'source' => 'product_detail',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Click recorded',
            ]);

        $this->assertDatabaseHas('whatsapp_clicks', [
            'website_id' => $this->website->id,
            'product_id' => $this->product->id,
            'source' => 'product_detail',
        ]);
    }

    public function test_floating_whatsapp_button_click_records_analytics_event(): void
    {
        $response = $this->postJson("/{$this->website->slug}/wa-click", [
            'source' => 'floating_button',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Click recorded',
            ]);

        $this->assertDatabaseHas('whatsapp_clicks', [
            'website_id' => $this->website->id,
            'product_id' => null,
            'source' => 'floating_button',
        ]);
    }

    public function test_whatsapp_click_endpoint_returns_404_for_draft_or_nonexistent_website(): void
    {
        $draftWebsite = Website::create([
            'business_id' => $this->business->id,
            'template_id' => $this->template->id,
            'slug' => 'draft-store-slug',
            'status' => 'draft',
        ]);

        $this->postJson("/{$draftWebsite->slug}/wa-click", [
            'source' => 'floating_button',
        ])->assertStatus(404);

        $this->postJson('/toko-tidak-ada-12345/wa-click', [
            'source' => 'floating_button',
        ])->assertStatus(404);
    }

    public function test_whatsapp_click_endpoint_validates_payload(): void
    {
        $this->postJson("/{$this->website->slug}/wa-click", [
            'product_id' => 999999,
            'source' => 'product_detail',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);

        $this->postJson("/{$this->website->slug}/wa-click", [
            'source' => str_repeat('a', 51),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['source']);
    }

    public function test_floating_whatsapp_button_is_rendered_on_published_public_landing_page(): void
    {
        $response = $this->get("/{$this->website->slug}");

        $response->assertStatus(200);
        $response->assertSee('fixed bottom-6 right-6 z-50', false);
        $response->assertSee('https://wa.me/6281234567890', false);
        $response->assertSee('floating_button', false);
    }

    public function test_product_detail_page_renders_whatsapp_order_cta_with_tracking_attributes(): void
    {
        $response = $this->get("/{$this->website->slug}/produk/{$this->product->slug}");

        $response->assertStatus(200);
        $response->assertSee('trackWaClick', false);
        $response->assertSee('product_detail', false);
        $response->assertSee("{$this->product->id}", false);
        $response->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_all_three_templates_render_whatsapp_cta_buttons_correctly(): void
    {
        $templates = Template::whereIn('slug', ['minimalist', 'food', 'modern'])->get();
        $this->assertCount(3, $templates);

        foreach ($templates as $tmpl) {
            $website = Website::create([
                'business_id' => $this->business->id,
                'template_id' => $tmpl->id,
                'slug' => "toko-{$tmpl->slug}",
                'status' => 'published',
            ]);

            Theme::create([
                'website_id' => $website->id,
                'primary_color' => '#10B981',
                'secondary_color' => '#047857',
                'font_family' => 'Inter',
            ]);

            // Index
            $resIndex = $this->get("/{$website->slug}");
            $resIndex->assertStatus(200);
            $resIndex->assertSee('fixed bottom-6 right-6 z-50', false);
            $resIndex->assertSee('trackWaClick', false);
            $resIndex->assertSee('catalog_card', false);

            // Detail
            $resDetail = $this->get("/{$website->slug}/produk/{$this->product->slug}");
            $resDetail->assertStatus(200);
            $resDetail->assertSee('trackWaClick', false);
            $resDetail->assertSee('product_detail', false);
        }
    }
}
