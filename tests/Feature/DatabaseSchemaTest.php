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
use App\Models\WebsiteVisit;
use App\Models\WhatsappClick;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoUmkmSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_business_website_theme_relationships_work(): void
    {
        $user = User::factory()->create(['role' => 'umkm']);
        $category = BusinessCategory::create([
            'name' => 'Kuliner',
            'slug' => 'kuliner',
        ]);
        $business = Business::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Kopi Mantap',
            'slug' => 'kopi-mantap',
            'whatsapp' => '08123456789',
        ]);
        $template = Template::create([
            'name' => 'Minimalist Store',
            'slug' => 'minimalist',
            'view_path' => 'templates.minimalist.index',
            'is_active' => true,
        ]);
        $website = Website::create([
            'business_id' => $business->id,
            'template_id' => $template->id,
            'slug' => 'kopi-mantap',
            'status' => 'draft',
        ]);
        $theme = Theme::create([
            'website_id' => $website->id,
            'primary_color' => '#3B82F6',
            'secondary_color' => '#1E40AF',
            'font_family' => 'Inter',
        ]);

        $this->assertEquals('Kopi Mantap', $user->business->name);
        $this->assertEquals('kopi-mantap', $business->website->slug);
        $this->assertEquals('#3B82F6', $website->theme->primary_color);
        $this->assertEquals('Kuliner', $business->category->name);
        $this->assertTrue($category->businesses->contains($business));
        $this->assertEquals($business->id, $website->business->id);
    }

    public function test_businesses_table_has_features_json_column_and_casts_to_array(): void
    {
        $user = User::factory()->create(['role' => 'umkm']);
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Toko Fitur',
            'slug' => 'toko-fitur',
            'whatsapp' => '081234567890',
            'features' => [
                'enabled' => true,
                'badge' => 'Mengapa Memilih Kami',
                'title' => 'Keunggulan Layanan Kami',
                'subtitle' => 'Komitmen mutu terbaik',
                'items' => [
                    ['title' => 'Kualitas Terjamin', 'description' => 'Kontrol ketat.'],
                ],
            ],
        ]);

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
        ]);

        $fresh = $business->fresh();
        $this->assertIsArray($fresh->features);
        $this->assertTrue($fresh->features['enabled']);
        $this->assertSame('Mengapa Memilih Kami', $fresh->features['badge']);
        $this->assertSame('Kualitas Terjamin', $fresh->features['items'][0]['title']);
    }

    public function test_business_products_categories_images_relationships_work(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Batik Cantik',
            'slug' => 'batik-cantik',
            'whatsapp' => '08123456789',
        ]);

        $prodCategory = ProductCategory::create([
            'business_id' => $business->id,
            'name' => 'Pakaian Pria',
            'slug' => 'pakaian-pria',
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'category_id' => $prodCategory->id,
            'name' => 'Kemeja Batik Tulis',
            'slug' => 'kemeja-batik-tulis',
            'description' => 'Kemeja batik tulis katun halus',
            'price' => 250000.00,
            'discount_price' => 200000.00,
            'stock' => 15,
            'label' => 'Best Seller',
            'variant_options' => ['M', 'L', 'XL'],
            'is_available' => true,
        ]);

        $image = ProductImage::create([
            'product_id' => $product->id,
            'image' => 'products/batik-1.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue($business->products->contains($product));
        $this->assertTrue($business->productCategories->contains($prodCategory));
        $this->assertEquals('Pakaian Pria', $product->category->name);
        $this->assertTrue($product->images->contains($image));
        $this->assertEquals('products/batik-1.jpg', $product->primaryImage->image);
        $this->assertEquals($product->id, $image->product->id);
        $this->assertEquals(['M', 'L', 'XL'], $product->variant_options);
    }

    public function test_banners_galleries_testimonials_relationships_work(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Bakso Sedap',
            'slug' => 'bakso-sedap',
            'whatsapp' => '08987654321',
        ]);

        $banner = Banner::create([
            'business_id' => $business->id,
            'image' => 'banners/hero.jpg',
            'title' => 'Diskon Pembukaan 50%',
            'subtitle' => 'Khusus minggu ini',
            'button_text' => 'Pesan Sekarang',
            'button_url' => '#produk',
            'is_active' => true,
        ]);

        $gallery = BusinessGallery::create([
            'business_id' => $business->id,
            'image' => 'gallery/suasana.jpg',
            'caption' => 'Suasana Resto',
            'sort_order' => 1,
        ]);

        $testimonial = Testimonial::create([
            'business_id' => $business->id,
            'customer_name' => 'Budi Santoso',
            'content' => 'Basonya enak dan kuahnya gurih mantap!',
            'rating' => 5,
            'is_active' => true,
        ]);

        $this->assertTrue($business->banners->contains($banner));
        $this->assertTrue($business->galleries->contains($gallery));
        $this->assertTrue($business->testimonials->contains($testimonial));
        $this->assertEquals($business->id, $banner->business->id);
        $this->assertEquals($business->id, $gallery->business->id);
        $this->assertEquals($business->id, $testimonial->business->id);
    }

    public function test_website_analytics_relationships_work(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Warung Kopi',
            'slug' => 'warung-kopi',
            'whatsapp' => '08123456789',
        ]);
        $template = Template::create([
            'name' => 'Minimalist',
            'slug' => 'minimalist',
            'view_path' => 'templates.minimalist',
        ]);
        $website = Website::create([
            'business_id' => $business->id,
            'template_id' => $template->id,
            'slug' => 'warung-kopi',
            'status' => 'published',
        ]);
        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Espresso',
            'slug' => 'espresso',
            'price' => 18000.00,
        ]);

        $visit = WebsiteVisit::create([
            'website_id' => $website->id,
            'visited_at' => now(),
            'visitor_hash' => hash('sha256', 'test-ip'),
            'referrer' => 'https://instagram.com',
        ]);

        $click = WhatsappClick::create([
            'website_id' => $website->id,
            'product_id' => $product->id,
            'clicked_at' => now(),
            'source' => 'product_detail',
        ]);

        $this->assertTrue($website->visits->contains($visit));
        $this->assertTrue($website->whatsappClicks->contains($click));
        $this->assertEquals($website->id, $visit->website->id);
        $this->assertEquals($website->id, $click->website->id);
        $this->assertEquals($product->id, $click->product->id);
    }

    public function test_seeders_populate_initial_data_correctly(): void
    {
        $this->seed(TemplateSeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(DemoUmkmSeeder::class);

        $this->assertDatabaseCount('templates', 3);
        $this->assertDatabaseHas('templates', ['slug' => 'minimalist']);
        $this->assertDatabaseHas('templates', ['slug' => 'food']);
        $this->assertDatabaseHas('templates', ['slug' => 'modern']);

        $this->assertDatabaseCount('business_categories', 5);

        $this->assertDatabaseHas('users', ['email' => 'demo@inamikro.com']);
        $this->assertDatabaseHas('businesses', ['slug' => 'kopi-nusantara']);
        $this->assertDatabaseHas('websites', ['slug' => 'kopi-nusantara']);
        $this->assertDatabaseHas('themes', ['font_family' => 'Inter']);
    }

    public function test_website_slug_must_be_unique(): void
    {
        $user1 = User::factory()->create();
        $business1 = Business::create([
            'user_id' => $user1->id,
            'name' => 'Bisnis Satu',
            'slug' => 'bisnis-satu',
            'whatsapp' => '08123456789',
        ]);
        $template = Template::create([
            'name' => 'Minimalist',
            'slug' => 'minimalist',
            'view_path' => 'templates.minimalist',
        ]);

        Website::create([
            'business_id' => $business1->id,
            'template_id' => $template->id,
            'slug' => 'toko-serba-ada',
            'status' => 'draft',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        $user2 = User::factory()->create();
        $business2 = Business::create([
            'user_id' => $user2->id,
            'name' => 'Bisnis Dua',
            'slug' => 'bisnis-dua',
            'whatsapp' => '08987654321',
        ]);

        Website::create([
            'business_id' => $business2->id,
            'template_id' => $template->id,
            'slug' => 'toko-serba-ada',
            'status' => 'draft',
        ]);
    }
}
