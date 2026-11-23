<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\TemplateManagement;
use App\Livewire\Admin\UserManagement;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use App\Models\Template;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteVisit;
use App\Models\WhatsappClick;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Template::create([
            'name' => 'Minimalist Store',
            'slug' => 'minimalist',
            'thumbnail' => 'templates/minimalist.png',
            'view_path' => 'templates.minimalist.index',
            'description' => 'Template bersih dan elegan untuk produk fashion, aksesoris, dan kerajinan tangan.',
            'is_active' => true,
        ]);

        Template::create([
            'name' => 'Food & Beverage',
            'slug' => 'food',
            'thumbnail' => 'templates/food.png',
            'view_path' => 'templates.food.index',
            'description' => 'Template menggugah selera untuk resto, kafe, bakery, dan usaha kuliner.',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login_from_admin_routes(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
        $this->get('/admin/templates')->assertRedirect('/login');
    }

    public function test_umkm_user_is_forbidden_from_admin_routes(): void
    {
        $umkmUser = User::factory()->create(['role' => 'umkm']);

        $this->actingAs($umkmUser)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($umkmUser)->get('/admin/users')->assertForbidden();
        $this->actingAs($umkmUser)->get('/admin/templates')->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard_and_view_platform_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $category = BusinessCategory::create([
            'name' => 'Fashion',
            'slug' => 'fashion',
        ]);

        // Create 2 UMKM users
        $umkm1 = User::factory()->create(['name' => 'Siti Aminah', 'role' => 'umkm']);
        $biz1 = Business::create([
            'user_id' => $umkm1->id,
            'category_id' => $category->id,
            'name' => 'Batik Cantik Aminah',
            'slug' => 'batik-cantik-aminah',
            'whatsapp' => '081234567891',
        ]);
        $web1 = Website::create([
            'business_id' => $biz1->id,
            'template_id' => Template::first()->id,
            'slug' => 'batikaminah',
            'status' => 'published',
            'published_at' => now(),
        ]);
        Product::create([
            'business_id' => $biz1->id,
            'name' => 'Batik Tulis Sutra',
            'slug' => 'batik-tulis-sutra',
            'price' => 250000,
            'stock' => 10,
            'is_available' => true,
        ]);
        WebsiteVisit::create([
            'website_id' => $web1->id,
            'visited_at' => now(),
            'visitor_hash' => 'hash1',
        ]);
        WhatsappClick::create([
            'website_id' => $web1->id,
            'clicked_at' => now(),
            'source' => 'landing_page',
        ]);

        $umkm2 = User::factory()->create(['name' => 'Joko Widodo', 'role' => 'umkm']);
        $biz2 = Business::create([
            'user_id' => $umkm2->id,
            'category_id' => $category->id,
            'name' => 'Kopi Mantap Joko',
            'slug' => 'kopi-mantap-joko',
            'whatsapp' => '081234567892',
        ]);
        Website::create([
            'business_id' => $biz2->id,
            'template_id' => Template::first()->id,
            'slug' => 'kopijoko',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('Batik Cantik Aminah');
        $response->assertSee('Kopi Mantap Joko');

        Livewire::actingAs($admin)
            ->test(AdminDashboard::class)
            ->assertSee('2') // 2 UMKM
            ->assertSee('1') // 1 published
            ->assertSee('Batik Cantik Aminah')
            ->assertSee('Kopi Mantap Joko');
    }

    public function test_admin_can_view_and_search_umkm_users_in_user_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $category = BusinessCategory::create([
            'name' => 'Kuliner',
            'slug' => 'kuliner',
        ]);

        $userA = User::factory()->create([
            'name' => 'Ahmad Dahlan',
            'email' => 'ahmad@example.com',
            'role' => 'umkm',
        ]);
        Business::create([
            'user_id' => $userA->id,
            'category_id' => $category->id,
            'name' => 'Kedai Soto Ahmad',
            'slug' => 'kedai-soto-ahmad',
            'whatsapp' => '081234567893',
        ]);

        $userB = User::factory()->create([
            'name' => 'Bambang Tri',
            'email' => 'bambang@example.com',
            'role' => 'umkm',
        ]);
        Business::create([
            'user_id' => $userB->id,
            'category_id' => $category->id,
            'name' => 'Bengkel Motor Bambang',
            'slug' => 'bengkel-motor-bambang',
            'whatsapp' => '081234567894',
        ]);

        $this->actingAs($admin)->get(route('admin.users'))->assertOk();

        Livewire::actingAs($admin)
            ->test(UserManagement::class)
            ->assertSee('Ahmad Dahlan')
            ->assertSee('Kedai Soto Ahmad')
            ->assertSee('Bambang Tri')
            ->assertSee('Bengkel Motor Bambang')
            ->set('search', 'Ahmad')
            ->assertSee('Ahmad Dahlan')
            ->assertDontSee('Bambang Tri')
            ->set('search', 'bambang@example.com')
            ->assertSee('Bambang Tri')
            ->assertDontSee('Ahmad Dahlan');
    }

    public function test_admin_can_view_templates_and_toggle_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = Template::where('slug', 'minimalist')->first();
        $this->assertTrue($template->is_active);

        $this->actingAs($admin)->get(route('admin.templates'))->assertOk();

        Livewire::actingAs($admin)
            ->test(TemplateManagement::class)
            ->assertSee('Minimalist Store')
            ->assertSee('Food & Beverage')
            ->call('toggleStatus', $template->id);

        $this->assertFalse($template->fresh()->is_active);

        Livewire::actingAs($admin)
            ->test(TemplateManagement::class)
            ->call('toggleStatus', $template->id);

        $this->assertTrue($template->fresh()->is_active);
    }

    public function test_admin_layout_renders_navigation_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee(route('admin.dashboard'));
        $response->assertSee(route('admin.users'));
        $response->assertSee(route('admin.templates'));
        $response->assertSee(route('logout'));
    }

    public function test_database_seeder_creates_default_super_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@inamikro.com',
            'role' => 'admin',
            'name' => 'Super Administrator',
        ]);
    }
}
