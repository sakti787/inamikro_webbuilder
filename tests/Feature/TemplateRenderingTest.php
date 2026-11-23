<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\BusinessGallery;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\Template;
use App\Models\Testimonial;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateRenderingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TemplateSeeder::class);

        $this->user = User::factory()->create(['role' => 'umkm']);

        $category = BusinessCategory::create([
            'name' => 'Kuliner & F&B',
            'slug' => 'kuliner-fb',
        ]);

        $this->business = Business::create([
            'user_id' => $this->user->id,
            'category_id' => $category->id,
            'name' => 'Kopi Kenangan Senja',
            'slug' => 'kopi-kenangan-senja',
            'tagline' => 'Secangkir Cerita Dalam Setiap Tegukan',
            'description' => 'Kedai kopi pilihan dengan biji kopi Nusantara terbaik.',
            'phone' => '02112345678',
            'whatsapp' => '081234567890',
            'email' => 'halo@kopisenja.com',
            'address' => 'Jl. Boulevard Raya No. 45, Jakarta Selatan',
            'operating_hours' => [
                'senin - jumat' => '08:00 - 22:00',
                'sabtu - minggu' => '09:00 - 23:00',
            ],
        ]);

        $prodCategory = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Signature Coffee',
            'slug' => 'signature-coffee',
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $prodCategory->id,
            'name' => 'Kopi Susu Gula Aren Spesial',
            'slug' => 'kopi-susu-gula-aren-spesial',
            'description' => 'Espresso ganda berpadu susu segar dan gula aren murni berkualitas tinggi.',
            'price' => 28000,
            'discount_price' => 24000,
            'stock' => 50,
            'label' => 'Best Seller',
            'is_available' => true,
        ]);

        ProductImage::create([
            'product_id' => $this->product->id,
            'image' => 'products/kopi-aren.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        Banner::create([
            'business_id' => $this->business->id,
            'image' => 'banners/hero-kopi.jpg',
            'title' => 'Promo Spesial Musim Kopi',
            'subtitle' => 'Diskon hingga 30% untuk seluruh varian espresso',
            'button_text' => 'Pesan Sekarang',
            'button_url' => '#catalog',
            'is_active' => true,
        ]);

        BusinessGallery::create([
            'business_id' => $this->business->id,
            'image' => 'galleries/ambiance-1.jpg',
            'caption' => 'Suasana santai di kedai kami',
            'sort_order' => 1,
        ]);

        Testimonial::create([
            'business_id' => $this->business->id,
            'customer_name' => 'Budi Santoso',
            'content' => 'Rasa kopinya mantap sekali dan pelayanannya sangat cepat!',
            'rating' => 5,
            'is_active' => true,
        ]);
    }

    public function test_all_three_templates_render_successfully_with_same_business_data(): void
    {
        $templates = Template::whereIn('slug', ['minimalist', 'food', 'modern'])->get();
        $this->assertCount(3, $templates);

        foreach ($templates as $template) {
            $website = Website::create([
                'business_id' => $this->business->id,
                'template_id' => $template->id,
                'slug' => 'website-'.$template->slug,
                'status' => 'draft',
            ]);

            Theme::create([
                'website_id' => $website->id,
                'primary_color' => '#10B981',
                'secondary_color' => '#047857',
                'font_family' => 'Poppins',
            ]);

            $website->load([
                'business.banners',
                'business.products.primaryImage',
                'business.galleries',
                'business.testimonials',
                'business.category',
                'business.productCategories',
                'theme',
                'template',
            ]);

            $rendered = view($template->view_path, ['website' => $website, 'business' => $website->business])->render();

            $this->assertStringContainsString('Kopi Kenangan Senja', $rendered, "Template {$template->slug} should contain business name");
            $this->assertStringContainsString('Promo Spesial Musim Kopi', $rendered, "Template {$template->slug} should contain banner title");
            $this->assertStringContainsString('Kopi Susu Gula Aren Spesial', $rendered, "Template {$template->slug} should contain product name");
            $this->assertStringContainsString('24.000', $rendered, "Template {$template->slug} should contain formatted price");
            $this->assertStringContainsString('wa.me/6281234567890', str_replace(' ', '', $rendered), "Template {$template->slug} should contain WhatsApp link");
        }
    }

    public function test_all_three_template_detail_pages_render_successfully_with_product(): void
    {
        $templates = [
            'minimalist' => 'templates.minimalist.detail',
            'food' => 'templates.food.detail',
            'modern' => 'templates.modern.detail',
        ];

        foreach ($templates as $slug => $detailViewPath) {
            $template = Template::where('slug', $slug)->firstOrFail();

            $website = Website::create([
                'business_id' => $this->business->id,
                'template_id' => $template->id,
                'slug' => 'detail-test-'.$slug,
                'status' => 'draft',
            ]);

            Theme::create([
                'website_id' => $website->id,
                'primary_color' => '#DC2626',
                'secondary_color' => '#991B1B',
                'font_family' => 'Outfit',
            ]);

            $website->load(['business', 'theme', 'template']);

            $rendered = view($detailViewPath, [
                'website' => $website,
                'business' => $website->business,
                'product' => $this->product->load('primaryImage', 'images', 'category'),
            ])->render();

            $this->assertStringContainsString('Kopi Susu Gula Aren Spesial', $rendered, "Detail for {$slug} should contain product name");
            $this->assertStringContainsString('Espresso ganda berpadu susu segar', $rendered, "Detail for {$slug} should contain product description");
            $this->assertStringContainsString('24.000', $rendered, "Detail for {$slug} should contain discount price");
            $this->assertStringContainsString('wa.me/6281234567890', str_replace(' ', '', $rendered), "Detail for {$slug} should contain WhatsApp link");
            $this->assertStringContainsString('Best Seller', $rendered, "Detail for {$slug} should contain product label/badge");
        }
    }

    public function test_css_variables_and_postmessage_script_are_rendered_in_templates(): void
    {
        $template = Template::where('slug', 'minimalist')->firstOrFail();

        $website = Website::create([
            'business_id' => $this->business->id,
            'template_id' => $template->id,
            'slug' => 'theme-check-website',
            'status' => 'draft',
        ]);

        Theme::create([
            'website_id' => $website->id,
            'primary_color' => '#8B5CF6',
            'secondary_color' => '#6D28D9',
            'font_family' => 'Montserrat',
        ]);

        $rendered = view($template->view_path, [
            'website' => $website->load(['business', 'theme', 'template']),
            'business' => $website->business,
        ])->render();

        $this->assertStringContainsString('--color-primary: #8B5CF6;', $rendered);
        $this->assertStringContainsString('--color-secondary: #6D28D9;', $rendered);
        $this->assertStringContainsString("--font-family: 'Montserrat', sans-serif;", $rendered);
        $this->assertStringContainsString("window.addEventListener('message'", $rendered);
        $this->assertStringContainsString("event.data.type === 'UPDATE_THEME'", $rendered);
    }

    public function test_templates_handle_empty_state_gracefully(): void
    {
        $emptyBusiness = Business::create([
            'user_id' => $this->user->id,
            'name' => 'Toko Kosong Tanpa Produk',
            'slug' => 'toko-kosong-tanpa-produk',
            'whatsapp' => '087777777777',
        ]);

        $templates = Template::whereIn('slug', ['minimalist', 'food', 'modern'])->get();

        foreach ($templates as $template) {
            $website = Website::create([
                'business_id' => $emptyBusiness->id,
                'template_id' => $template->id,
                'slug' => 'empty-site-'.$template->slug,
                'status' => 'draft',
            ]);

            Theme::create([
                'website_id' => $website->id,
            ]);

            $rendered = view($template->view_path, [
                'website' => $website->load(['business', 'theme', 'template']),
                'business' => $emptyBusiness,
            ])->render();

            $this->assertStringContainsString('Toko Kosong Tanpa Produk', $rendered);
        }
    }

    public function test_preview_controller_renders_active_template_and_product_detail(): void
    {
        $template = Template::where('slug', 'food')->firstOrFail();

        $website = Website::create([
            'business_id' => $this->business->id,
            'template_id' => $template->id,
            'slug' => 'preview-food-website',
            'status' => 'draft',
        ]);

        Theme::create([
            'website_id' => $website->id,
            'primary_color' => '#F59E0B',
            'secondary_color' => '#D97706',
            'font_family' => 'Poppins',
        ]);

        // 1. Preview main landing page
        $response = $this->actingAs($this->user)->get(route('builder.preview', $website));
        $response->assertStatus(200);
        $response->assertSee('Kopi Kenangan Senja');
        $response->assertSee('--color-primary: #F59E0B', false);

        // 2. Preview product detail via query param
        $responseDetail = $this->actingAs($this->user)->get(route('builder.preview', [
            'website' => $website,
            'product_id' => $this->product->id,
        ]));
        $responseDetail->assertStatus(200);
        $responseDetail->assertSee('Kopi Susu Gula Aren Spesial');
        $responseDetail->assertSee('Espresso ganda berpadu susu segar');
    }
}
