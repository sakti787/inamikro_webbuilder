<?php

namespace Database\Seeders;

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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUmkmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create or retrieve demo user
        $user = User::updateOrCreate(
            ['email' => 'demo@inamikro.com'],
            [
                'name' => 'Demo UMKM',
                'password' => Hash::make('password'),
                'role' => 'umkm',
                'email_verified_at' => now(),
            ]
        );

        // 2. Category
        $category = BusinessCategory::firstOrCreate(
            ['slug' => 'kuliner'],
            ['name' => 'Kuliner', 'description' => 'Makanan dan minuman']
        );

        // 3. Business
        $business = Business::updateOrCreate(
            ['user_id' => $user->id],
            [
                'category_id' => $category->id,
                'name' => 'Kopi Nusantara',
                'slug' => 'kopi-nusantara',
                'tagline' => 'Cita Rasa Kopi Asli Indonesia',
                'description' => 'Menyajikan aneka biji kopi pilihan langsung dari perkebunan nusantara dengan sangrai segar berkualitas tinggi.',
                'phone' => '081234567890',
                'whatsapp' => '6281234567890',
                'email' => 'info@kopinusantara.id',
                'address' => 'Jl. Malioboro No. 123, Danurejan, Kota Yogyakarta, D.I. Yogyakarta',
                'operating_hours' => [
                    'Senin - Jumat: 08:00 - 22:00',
                    'Sabtu - Minggu: 09:00 - 23:00',
                ],
                'logo' => 'businesses/kopi-logo.png',
            ]
        );

        // 4. Template
        $template = Template::where('slug', 'minimalist')->first()
            ?? Template::firstOrCreate(
                ['slug' => 'minimalist'],
                [
                    'name' => 'Minimalist Store',
                    'view_path' => 'templates.minimalist.index',
                    'is_active' => true,
                ]
            );

        // 5. Website
        $website = Website::updateOrCreate(
            ['business_id' => $business->id],
            [
                'template_id' => $template->id,
                'slug' => 'kopi-nusantara',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        // 6. Theme
        Theme::updateOrCreate(
            ['website_id' => $website->id],
            [
                'primary_color' => '#8B5A2B',
                'secondary_color' => '#3E2723',
                'background_color' => '#FFFDF9',
                'text_color' => '#271D18',
                'font_family' => 'Inter',
                'button_style' => 'rounded-lg',
                'border_radius' => '8px',
            ]
        );

        // 7. Banner
        Banner::updateOrCreate(
            ['business_id' => $business->id, 'title' => 'Nikmati Kopi Terbaik Nusantara'],
            [
                'image' => 'banners/kopi-banner.jpg',
                'subtitle' => 'Biji kopi pilihan nusantara dengan aroma kuat dan cita rasa otentik.',
                'button_text' => 'Pesan Sekarang',
                'button_url' => '#katalog',
                'is_active' => true,
            ]
        );

        // 8. Product Categories
        $catBeans = ProductCategory::updateOrCreate(
            ['business_id' => $business->id, 'slug' => 'biji-kopi'],
            ['name' => 'Biji Kopi Sangrai']
        );

        $catBrew = ProductCategory::updateOrCreate(
            ['business_id' => $business->id, 'slug' => 'siap-minum'],
            ['name' => 'Kopi Siap Minum']
        );

        // 9. Products & Images
        $product1 = Product::updateOrCreate(
            ['business_id' => $business->id, 'slug' => 'arabika-gayo-single-origin-250g'],
            [
                'category_id' => $catBeans->id,
                'name' => 'Arabika Gayo Single Origin 250g',
                'description' => 'Kopi Arabika premium dataran tinggi Gayo Aceh. Notes: fruity, caramel, low acidity.',
                'price' => 75000.00,
                'discount_price' => 65000.00,
                'stock' => 50,
                'label' => 'Best Seller',
                'variant_options' => ['Biji Utuh', 'Giling Kasar', 'Giling Halus'],
                'is_available' => true,
                'sort_order' => 1,
            ]
        );

        ProductImage::updateOrCreate(
            ['product_id' => $product1->id, 'image' => 'products/gayo.jpg'],
            ['is_primary' => true, 'sort_order' => 1]
        );

        $product2 = Product::updateOrCreate(
            ['business_id' => $business->id, 'slug' => 'robusta-temanggung-250g'],
            [
                'category_id' => $catBeans->id,
                'name' => 'Robusta Temanggung 250g',
                'description' => 'Robusta mantap lereng Gunung Sindoro. Karakter bold, chocolatey, dan crema tebal.',
                'price' => 45000.00,
                'discount_price' => null,
                'stock' => 80,
                'label' => 'New',
                'variant_options' => ['Biji Utuh', 'Giling Halus'],
                'is_available' => true,
                'sort_order' => 2,
            ]
        );

        ProductImage::updateOrCreate(
            ['product_id' => $product2->id, 'image' => 'products/temanggung.jpg'],
            ['is_primary' => true, 'sort_order' => 1]
        );

        $product3 = Product::updateOrCreate(
            ['business_id' => $business->id, 'slug' => 'es-kopi-susu-gula-aren-literan'],
            [
                'category_id' => $catBrew->id,
                'name' => 'Es Kopi Susu Gula Aren 1 Liter',
                'description' => 'Perpaduan espresso arabika robusta blend dengan susu segar dan gula aren murni.',
                'price' => 85000.00,
                'discount_price' => 75000.00,
                'stock' => 25,
                'label' => 'Promo',
                'variant_options' => ['Normal Sweet', 'Less Sugar'],
                'is_available' => true,
                'sort_order' => 3,
            ]
        );

        ProductImage::updateOrCreate(
            ['product_id' => $product3->id, 'image' => 'products/kopisusu-liter.jpg'],
            ['is_primary' => true, 'sort_order' => 1]
        );

        // 10. Business Galleries
        BusinessGallery::updateOrCreate(
            ['business_id' => $business->id, 'image' => 'gallery/proses-roasting.jpg'],
            ['caption' => 'Proses Roasting Biji Kopi Harian', 'sort_order' => 1]
        );
        BusinessGallery::updateOrCreate(
            ['business_id' => $business->id, 'image' => 'gallery/kedai-suasana.jpg'],
            ['caption' => 'Sudut Santai Barista Bar', 'sort_order' => 2]
        );

        // 11. Testimonials
        Testimonial::updateOrCreate(
            ['business_id' => $business->id, 'customer_name' => 'Dimas Pratama'],
            [
                'content' => 'Arabika Gayonya luar biasa harum! Pesan via WhatsApp pelayanannya sangat cepat dan ramah.',
                'rating' => 5,
                'is_active' => true,
            ]
        );
        Testimonial::updateOrCreate(
            ['business_id' => $business->id, 'customer_name' => 'Siti Rahmawati'],
            [
                'content' => 'Kopi susu literannya jadi langganan buat meeting kantor setiap hari Kamis. Rekomen banget!',
                'rating' => 5,
                'is_active' => true,
            ]
        );

        // 12. Analytics demo
        WebsiteVisit::create([
            'website_id' => $website->id,
            'visited_at' => now()->subHours(2),
            'visitor_hash' => hash('sha256', 'demo-visitor-1'),
            'referrer' => 'https://instagram.com',
        ]);

        WhatsappClick::create([
            'website_id' => $website->id,
            'product_id' => $product1->id,
            'clicked_at' => now()->subHour(),
            'source' => 'product_detail',
        ]);
    }
}
