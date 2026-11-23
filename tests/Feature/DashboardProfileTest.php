<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Dashboard\ProfileForm;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use App\Models\Template;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteVisit;
use App\Models\WhatsappClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Template::create([
            'name' => 'Minimalist Store',
            'slug' => 'minimalist',
            'view_path' => 'templates.minimalist.index',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_dashboard_or_profile(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/dashboard/profile')->assertRedirect('/login');
    }

    public function test_umkm_can_view_dashboard_with_accurate_metric_counts(): void
    {
        $user = User::factory()->create(['role' => 'umkm']);
        $category = BusinessCategory::create([
            'name' => 'Kuliner',
            'slug' => 'kuliner',
        ]);

        $business = Business::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Warung Kopi Mantap',
            'slug' => 'warung-kopi-mantap',
            'whatsapp' => '081234567890',
        ]);

        $template = Template::first();
        $website = Website::create([
            'business_id' => $business->id,
            'template_id' => $template->id,
            'slug' => 'warung-kopi-mantap',
            'status' => 'published',
        ]);

        // Create 2 available products, 1 unavailable product
        Product::create([
            'business_id' => $business->id,
            'name' => 'Kopi Tubruk',
            'slug' => 'kopi-tubruk',
            'price' => 10000,
            'stock' => 20,
            'is_available' => true,
        ]);
        Product::create([
            'business_id' => $business->id,
            'name' => 'Kopi Susu Gula Aren',
            'slug' => 'kopi-susu-gula-aren',
            'price' => 15000,
            'stock' => 15,
            'is_available' => true,
        ]);
        Product::create([
            'business_id' => $business->id,
            'name' => 'Kopi Habis',
            'slug' => 'kopi-habis',
            'price' => 12000,
            'stock' => 0,
            'is_available' => false,
        ]);

        // Create 3 visits
        for ($i = 0; $i < 3; $i++) {
            WebsiteVisit::create([
                'website_id' => $website->id,
                'visited_at' => now(),
                'visitor_hash' => hash('sha256', "ip-{$i}"),
            ]);
        }

        // Create 2 WhatsApp clicks
        for ($j = 0; $j < 2; $j++) {
            WhatsappClick::create([
                'website_id' => $website->id,
                'clicked_at' => now(),
                'source' => 'landing_page',
            ]);
        }

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSeeLivewire(DashboardIndex::class);
        $response->assertSee('Warung Kopi Mantap');
        $response->assertSee('published');

        Livewire::actingAs($user)
            ->test(DashboardIndex::class)
            ->assertSet('totalProducts', 2)
            ->assertSet('totalViews', 3)
            ->assertSet('totalWaClicks', 2)
            ->assertSee('Warung Kopi Mantap')
            ->assertSee('published');
    }

    public function test_umkm_can_view_and_update_business_profile_info(): void
    {
        $user = User::factory()->create(['role' => 'umkm']);
        $cat1 = BusinessCategory::create(['name' => 'Fashion', 'slug' => 'fashion']);
        $cat2 = BusinessCategory::create(['name' => 'Kuliner', 'slug' => 'kuliner']);

        $business = Business::create([
            'user_id' => $user->id,
            'category_id' => $cat1->id,
            'name' => 'Nama Lama Toko',
            'slug' => 'nama-lama-toko',
            'whatsapp' => '0811111111',
            'phone' => '021123456',
            'description' => 'Deskripsi lama.',
            'address' => 'Alamat lama',
            'operating_hours' => 'Senin - Jumat 09:00 - 17:00',
        ]);

        $response = $this->actingAs($user)->get('/dashboard/profile');
        $response->assertStatus(200);
        $response->assertSeeLivewire(ProfileForm::class);

        Livewire::actingAs($user)
            ->test(ProfileForm::class)
            ->assertSet('name', 'Nama Lama Toko')
            ->assertSet('category_id', $cat1->id)
            ->assertSet('whatsapp', '0811111111')
            ->assertSet('phone', '021123456')
            ->assertSet('description', 'Deskripsi lama.')
            ->assertSet('address', 'Alamat lama')
            ->set('name', 'Kedai Kopi Inamikro')
            ->set('category_id', $cat2->id)
            ->set('whatsapp', '081298765432')
            ->set('phone', '08123456789')
            ->set('description', 'Kopi asli pilihan nusantara.')
            ->set('address', 'Jl. Malioboro No. 45, Yogyakarta')
            ->set('operating_hours', 'Senin - Minggu: 08:00 - 22:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Profil usaha berhasil diperbarui.');

        $business->refresh();
        $this->assertSame('Kedai Kopi Inamikro', $business->name);
        $this->assertSame($cat2->id, $business->category_id);
        $this->assertSame('081298765432', $business->whatsapp);
        $this->assertSame('08123456789', $business->phone);
        $this->assertSame('Kopi asli pilihan nusantara.', $business->description);
        $this->assertSame('Jl. Malioboro No. 45, Yogyakarta', $business->address);
    }

    public function test_umkm_can_upload_logo_and_it_is_stored_in_public_disk(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'umkm']);
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Toko Logo',
            'slug' => 'toko-logo',
            'whatsapp' => '08123456789',
        ]);

        $logoFile = UploadedFile::fake()->create('brand-logo.png', 100, 'image/png');

        Livewire::actingAs($user)
            ->test(ProfileForm::class)
            ->set('logo', $logoFile)
            ->call('save')
            ->assertHasNoErrors();

        $business->refresh();
        $this->assertNotNull($business->logo);
        $this->assertStringStartsWith('logos/', $business->logo);
        Storage::disk('public')->assertExists($business->logo);
    }

    public function test_profile_validation_rules_are_enforced(): void
    {
        $user = User::factory()->create(['role' => 'umkm']);
        Business::create([
            'user_id' => $user->id,
            'name' => 'Toko Validasi',
            'slug' => 'toko-validasi',
            'whatsapp' => '08123456789',
        ]);

        Livewire::actingAs($user)
            ->test(ProfileForm::class)
            ->set('name', '')
            ->set('whatsapp', '')
            ->set('category_id', 99999)
            ->call('save')
            ->assertHasErrors(['name', 'whatsapp', 'category_id']);

        // Test invalid logo file (not an image or > 2MB)
        $invalidFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        Livewire::actingAs($user)
            ->test(ProfileForm::class)
            ->set('logo', $invalidFile)
            ->call('save')
            ->assertHasErrors(['logo']);
    }

    public function test_dashboard_renders_quick_actions(): void
    {
        $user = User::factory()->create(['role' => 'umkm']);
        Business::create([
            'user_id' => $user->id,
            'name' => 'Toko Aksi',
            'slug' => 'toko-aksi',
            'whatsapp' => '08123456789',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('/dashboard/products');
        $response->assertSee('/dashboard/builder');
        $response->assertSee('/dashboard/profile');
    }

    public function test_dashboard_handles_business_without_website_gracefully(): void
    {
        $user = User::factory()->create(['role' => 'umkm']);
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Toko Tanpa Web',
            'slug' => 'toko-tanpa-web',
            'whatsapp' => '08123456789',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Toko Tanpa Web');

        Livewire::actingAs($user)
            ->test(DashboardIndex::class)
            ->assertSet('totalProducts', 0)
            ->assertSet('totalViews', 0)
            ->assertSet('totalWaClicks', 0)
            ->assertSet('website', null);
    }

    public function test_profile_update_preserves_existing_logo_when_not_reuploaded(): void
    {
        $user = User::factory()->create(['role' => 'umkm']);
        $business = Business::create([
            'user_id' => $user->id,
            'name' => 'Toko Logo Lama',
            'slug' => 'toko-logo-lama',
            'whatsapp' => '08123456789',
            'logo' => 'logos/existing-logo.png',
        ]);

        Livewire::actingAs($user)
            ->test(ProfileForm::class)
            ->assertSet('existingLogo', 'logos/existing-logo.png')
            ->set('name', 'Toko Logo Baru Name')
            ->call('save')
            ->assertHasNoErrors();

        $business->refresh();
        $this->assertSame('Toko Logo Baru Name', $business->name);
        $this->assertSame('logos/existing-logo.png', $business->logo);
    }
}
