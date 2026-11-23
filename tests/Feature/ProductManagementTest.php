<?php

namespace Tests\Feature;

use App\Livewire\Banners\BannerManager;
use App\Livewire\Products\ProductForm;
use App\Livewire\Products\ProductList;
use App\Models\Banner;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\Template;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Template::create([
            'name' => 'Minimalist Store',
            'slug' => 'minimalist',
            'view_path' => 'templates.minimalist.index',
            'is_active' => true,
        ]);

        $bizCategory = BusinessCategory::create([
            'name' => 'Kuliner',
            'slug' => 'kuliner',
        ]);

        $this->user = User::factory()->create(['role' => 'umkm']);

        $this->business = Business::create([
            'user_id' => $this->user->id,
            'category_id' => $bizCategory->id,
            'name' => 'Kopi Senja',
            'slug' => 'kopi-senja',
            'whatsapp' => '081234567890',
        ]);

        Website::create([
            'business_id' => $this->business->id,
            'template_id' => Template::first()->id,
            'slug' => 'kopi-senja',
            'status' => 'published',
        ]);

        $this->category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Minuman',
            'slug' => 'minuman',
        ]);
    }

    public function test_guest_cannot_access_product_management_or_banner_management(): void
    {
        $this->get('/dashboard/products')->assertRedirect('/login');
        $this->get('/dashboard/products/create')->assertRedirect('/login');
        $this->get('/dashboard/banners')->assertRedirect('/login');
    }

    public function test_umkm_can_view_product_list_with_search_and_category_filter(): void
    {
        $catSnack = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Camilan',
            'slug' => 'camilan',
        ]);

        $prod1 = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $this->category->id,
            'name' => 'Es Kopi Susu',
            'slug' => 'es-kopi-susu',
            'price' => 18000,
            'discount_price' => 15000,
            'label' => 'Promo',
            'is_available' => true,
        ]);

        $prod2 = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $catSnack->id,
            'name' => 'Roti Bakar Keju',
            'slug' => 'roti-bakar-keju',
            'price' => 25000,
            'label' => 'Best Seller',
            'is_available' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/dashboard/products');
        $response->assertOk();
        $response->assertSee('Es Kopi Susu');
        $response->assertSee('Roti Bakar Keju');
        $response->assertSee('Promo');
        $response->assertSee('Best Seller');
        $response->assertSee(number_format(18000, 0, ',', '.'));

        Livewire::actingAs($this->user)
            ->test(ProductList::class)
            ->assertSee('Es Kopi Susu')
            ->assertSee('Roti Bakar Keju')
            ->set('search', 'Kopi')
            ->assertSee('Es Kopi Susu')
            ->assertDontSee('Roti Bakar Keju')
            ->set('search', '')
            ->set('categoryFilter', (string) $catSnack->id)
            ->assertSee('Roti Bakar Keju')
            ->assertDontSee('Es Kopi Susu');
    }

    public function test_umkm_can_create_product_with_multiple_images_and_variants(): void
    {
        Storage::fake('public');

        $img1 = UploadedFile::fake()->create('kopi1.jpg', 100, 'image/jpeg');
        $img2 = UploadedFile::fake()->create('kopi2.jpg', 100, 'image/jpeg');

        Livewire::actingAs($this->user)
            ->test(ProductForm::class)
            ->set('name', 'Kopi Tubruk Robusta')
            ->set('category_id', $this->category->id)
            ->set('price', 15000)
            ->set('discount_price', 12000)
            ->set('stock', 25)
            ->set('description', 'Kopi tubruk murni khas nusantara.')
            ->set('label', 'Promo')
            ->set('variant_options', ['Panas', 'Dingin'])
            ->set('images', [$img1, $img2])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard.products'));

        $product = Product::where('slug', 'kopi-tubruk-robusta')->first();
        $this->assertNotNull($product);
        $this->assertEquals($this->business->id, $product->business_id);
        $this->assertEquals(15000, (float) $product->price);
        $this->assertEquals(12000, (float) $product->discount_price);
        $this->assertEquals(['Panas', 'Dingin'], $product->variant_options);

        $this->assertCount(2, $product->images);
        $primaryImage = $product->primaryImage;
        $this->assertNotNull($primaryImage);
        $this->assertTrue($primaryImage->is_primary);

        Storage::disk('public')->assertExists($primaryImage->image);
    }

    public function test_umkm_can_edit_product_and_delete_image_and_set_primary(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'business_id' => $this->business->id,
            'category_id' => $this->category->id,
            'name' => 'Cappuccino Original',
            'slug' => 'cappuccino-original',
            'price' => 22000,
            'is_available' => true,
        ]);

        $img1Path = UploadedFile::fake()->create('cap1.jpg', 100, 'image/jpeg')->store('products', 'public');
        $img2Path = UploadedFile::fake()->create('cap2.jpg', 100, 'image/jpeg')->store('products', 'public');

        $image1 = ProductImage::create([
            'product_id' => $product->id,
            'image' => $img1Path,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $image2 = ProductImage::create([
            'product_id' => $product->id,
            'image' => $img2Path,
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProductForm::class, ['product' => $product])
            ->assertSet('name', 'Cappuccino Original')
            ->assertSet('price', '22000.00')
            ->call('setPrimaryImage', $image2->id)
            ->call('deleteImage', $image1->id)
            ->set('name', 'Cappuccino Special')
            ->set('price', 25000)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard.products'));

        $this->assertDatabaseMissing('product_images', ['id' => $image1->id]);
        Storage::disk('public')->assertMissing($img1Path);

        $image2->refresh();
        $this->assertTrue($image2->is_primary);

        $product->refresh();
        $this->assertEquals('Cappuccino Special', $product->name);
        $this->assertEquals(25000, (float) $product->price);
    }

    public function test_umkm_cannot_edit_another_umkm_product(): void
    {
        $userB = User::factory()->create(['role' => 'umkm']);
        $bizCategory = BusinessCategory::first();
        $businessB = Business::create([
            'user_id' => $userB->id,
            'category_id' => $bizCategory->id,
            'name' => 'Toko Sebelah',
            'slug' => 'toko-sebelah',
            'whatsapp' => '089876543210',
        ]);

        $productB = Product::create([
            'business_id' => $businessB->id,
            'name' => 'Barang Toko Sebelah',
            'slug' => 'barang-toko-sebelah',
            'price' => 50000,
            'is_available' => true,
        ]);

        // HTTP GET request should be forbidden (403)
        $this->actingAs($this->user)
            ->get("/dashboard/products/{$productB->id}/edit")
            ->assertForbidden();

        // Direct Livewire test should be forbidden
        Livewire::actingAs($this->user)
            ->test(ProductForm::class, ['product' => $productB])
            ->assertForbidden();
    }

    public function test_umkm_can_toggle_product_availability(): void
    {
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Matcha Latte',
            'slug' => 'matcha-latte',
            'price' => 20000,
            'is_available' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProductList::class)
            ->call('toggleAvailability', $product->id);

        $product->refresh();
        $this->assertFalse($product->is_available);

        Livewire::actingAs($this->user)
            ->test(ProductList::class)
            ->call('toggleAvailability', $product->id);

        $product->refresh();
        $this->assertTrue($product->is_available);
    }

    public function test_umkm_cannot_toggle_or_delete_another_umkm_product(): void
    {
        $userB = User::factory()->create(['role' => 'umkm']);
        $businessB = Business::create([
            'user_id' => $userB->id,
            'category_id' => BusinessCategory::first()->id,
            'name' => 'Usaha B',
            'slug' => 'usaha-b',
            'whatsapp' => '081111111111',
        ]);

        $productB = Product::create([
            'business_id' => $businessB->id,
            'name' => 'Produk Milik B',
            'slug' => 'produk-milik-b',
            'price' => 30000,
            'is_available' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProductList::class)
            ->call('toggleAvailability', $productB->id)
            ->assertForbidden();

        Livewire::actingAs($this->user)
            ->test(ProductList::class)
            ->call('deleteProduct', $productB->id)
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $productB->id]);
    }

    public function test_umkm_can_delete_product_and_its_images(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Cold Brew',
            'slug' => 'kopi-cold-brew',
            'price' => 28000,
            'is_available' => true,
        ]);

        $imgPath = UploadedFile::fake()->create('coldbrew.jpg', 100, 'image/jpeg')->store('products', 'public');
        $img = ProductImage::create([
            'product_id' => $product->id,
            'image' => $imgPath,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProductList::class)
            ->call('deleteProduct', $product->id);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['id' => $img->id]);
        Storage::disk('public')->assertMissing($imgPath);
    }

    public function test_product_form_validates_inputs_strictly(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProductForm::class)
            ->set('name', '')
            ->set('price', -5000)
            ->set('discount_price', 10000)
            ->call('save')
            ->assertHasErrors(['name', 'price', 'discount_price']);

        Livewire::actingAs($this->user)
            ->test(ProductForm::class)
            ->set('name', 'Menu Diskon Salah')
            ->set('price', 10000)
            ->set('discount_price', 15000) // discount_price > price
            ->call('save')
            ->assertHasErrors(['discount_price']);

        // Oversized image test (> 2048 KB)
        $oversizedImage = UploadedFile::fake()->create('large.jpg', 3000, 'image/jpeg');
        Livewire::actingAs($this->user)
            ->test(ProductForm::class)
            ->set('name', 'Kopi Mahal')
            ->set('price', 20000)
            ->set('images', [$oversizedImage])
            ->call('save')
            ->assertHasErrors(['images.0']);
    }

    public function test_umkm_can_add_toggle_and_delete_hero_banner(): void
    {
        Storage::fake('public');

        $bannerImage = UploadedFile::fake()->create('promo-hero.jpg', 150, 'image/jpeg');

        $test = Livewire::actingAs($this->user)
            ->test(BannerManager::class)
            ->set('image', $bannerImage)
            ->set('title', 'Spesial Akhir Pekan')
            ->set('subtitle', 'Diskon hingga 40% semua menu')
            ->set('button_text', 'Pesan via WA')
            ->set('button_url', 'https://wa.me/081234567890')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $banner = Banner::where('business_id', $this->business->id)->first();
        $this->assertNotNull($banner);
        $this->assertEquals('Spesial Akhir Pekan', $banner->title);
        $this->assertEquals('Diskon hingga 40% semua menu', $banner->subtitle);
        $this->assertTrue($banner->is_active);
        Storage::disk('public')->assertExists($banner->image);

        // Toggle active status
        $test->call('toggleBanner', $banner->id);
        $banner->refresh();
        $this->assertFalse($banner->is_active);

        // Delete banner
        $imagePath = $banner->image;
        $test->call('deleteBanner', $banner->id);
        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_umkm_cannot_toggle_or_delete_another_umkm_banner(): void
    {
        $userB = User::factory()->create(['role' => 'umkm']);
        $businessB = Business::create([
            'user_id' => $userB->id,
            'category_id' => BusinessCategory::first()->id,
            'name' => 'Usaha B',
            'slug' => 'usaha-b',
            'whatsapp' => '081111111111',
        ]);

        $bannerB = Banner::create([
            'business_id' => $businessB->id,
            'image' => 'banners/dummy.jpg',
            'title' => 'Banner B',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(BannerManager::class)
            ->call('toggleBanner', $bannerB->id)
            ->assertForbidden();

        Livewire::actingAs($this->user)
            ->test(BannerManager::class)
            ->call('deleteBanner', $bannerB->id)
            ->assertForbidden();

        $this->assertDatabaseHas('banners', ['id' => $bannerB->id]);
    }
}
