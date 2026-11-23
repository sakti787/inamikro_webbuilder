<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Product;
use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthAndTenantIsolationTest extends TestCase
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

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Masuk');
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Daftar Akun UMKM');
    }

    public function test_user_registration_automatically_provisions_user_business_website_and_theme(): void
    {
        $category = BusinessCategory::create([
            'name' => 'Kuliner',
            'slug' => 'kuliner',
        ]);

        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone' => '081234567890',
            'whatsapp' => '081234567890',
            'business_name' => 'Kopi Budi Mantap',
            'category_id' => $category->id,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        // 1. Verify User
        $user = User::where('email', 'budi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('umkm', $user->role);
        $this->assertTrue(Hash::check('secret123', $user->password));

        // 2. Verify Business
        $business = Business::where('user_id', $user->id)->first();
        $this->assertNotNull($business);
        $this->assertSame('Kopi Budi Mantap', $business->name);
        $this->assertSame('081234567890', $business->whatsapp);
        $this->assertSame($category->id, $business->category_id);

        // 3. Verify Website
        $website = Website::where('business_id', $business->id)->first();
        $this->assertNotNull($website);
        $this->assertSame('draft', $website->status);
        $this->assertNotEmpty($website->slug);

        // 4. Verify Theme
        $theme = Theme::where('website_id', $website->id)->first();
        $this->assertNotNull($theme);
        $this->assertSame('#2563EB', $theme->primary_color);
        $this->assertSame('Inter', $theme->font_family);
    }

    public function test_user_can_login_and_umkm_redirected_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'umkm@example.com',
            'password' => Hash::make('password123'),
            'role' => 'umkm',
        ]);

        Business::create([
            'user_id' => $user->id,
            'name' => 'Usaha Saya',
            'slug' => 'usaha-saya',
            'whatsapp' => '08123456789',
        ]);

        $response = $this->post('/login', [
            'email' => 'umkm@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_admin_user_redirected_to_admin_dashboard_on_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'admin123',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_user_redirected_to_login_when_accessing_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create(['role' => 'umkm']);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
    }

    public function test_umkm_cannot_access_or_edit_another_umkm_product(): void
    {
        // UMKM A
        $userA = User::factory()->create(['role' => 'umkm']);
        $businessA = Business::create([
            'user_id' => $userA->id,
            'name' => 'Toko A',
            'slug' => 'toko-a',
            'whatsapp' => '0811111111',
        ]);
        $productA = Product::create([
            'business_id' => $businessA->id,
            'name' => 'Produk A',
            'slug' => 'produk-a',
            'price' => 10000,
            'stock' => 5,
        ]);

        // UMKM B
        $userB = User::factory()->create(['role' => 'umkm']);
        $businessB = Business::create([
            'user_id' => $userB->id,
            'name' => 'Toko B',
            'slug' => 'toko-b',
            'whatsapp' => '0822222222',
        ]);
        $productB = Product::create([
            'business_id' => $businessB->id,
            'name' => 'Produk B',
            'slug' => 'produk-b',
            'price' => 20000,
            'stock' => 10,
        ]);

        // User A cannot edit User B's product
        $responseForbidden = $this->actingAs($userA)->get("/dashboard/products/{$productB->id}/edit");
        $responseForbidden->assertStatus(403);

        // User A can edit own product
        $responseOk = $this->actingAs($userA)->get("/dashboard/products/{$productA->id}/edit");
        $responseOk->assertStatus(200);
    }

    public function test_policies_enforce_business_product_and_website_tenant_isolation(): void
    {
        $userA = User::factory()->create(['role' => 'umkm']);
        $businessA = Business::create([
            'user_id' => $userA->id,
            'name' => 'Toko A',
            'slug' => 'toko-a',
            'whatsapp' => '0811111111',
        ]);
        $template = Template::first();
        $websiteA = Website::create([
            'business_id' => $businessA->id,
            'template_id' => $template->id,
            'slug' => 'toko-a',
            'status' => 'draft',
        ]);
        $productA = Product::create([
            'business_id' => $businessA->id,
            'name' => 'Produk A',
            'slug' => 'produk-a',
            'price' => 10000,
            'stock' => 5,
        ]);

        $userB = User::factory()->create(['role' => 'umkm']);
        $businessB = Business::create([
            'user_id' => $userB->id,
            'name' => 'Toko B',
            'slug' => 'toko-b',
            'whatsapp' => '0822222222',
        ]);
        $websiteB = Website::create([
            'business_id' => $businessB->id,
            'template_id' => $template->id,
            'slug' => 'toko-b',
            'status' => 'draft',
        ]);
        $productB = Product::create([
            'business_id' => $businessB->id,
            'name' => 'Produk B',
            'slug' => 'produk-b',
            'price' => 20000,
            'stock' => 10,
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        // BusinessPolicy: update
        $this->assertTrue(Gate::forUser($userA)->allows('update', $businessA));
        $this->assertFalse(Gate::forUser($userA)->allows('update', $businessB));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $businessB));

        // ProductPolicy: view, update, delete
        $this->assertTrue(Gate::forUser($userA)->allows('view', $productA));
        $this->assertTrue(Gate::forUser($userA)->allows('update', $productA));
        $this->assertTrue(Gate::forUser($userA)->allows('delete', $productA));

        $this->assertFalse(Gate::forUser($userA)->allows('view', $productB));
        $this->assertFalse(Gate::forUser($userA)->allows('update', $productB));
        $this->assertFalse(Gate::forUser($userA)->allows('delete', $productB));

        $this->assertTrue(Gate::forUser($admin)->allows('view', $productB));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $productB));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $productB));

        // WebsitePolicy: view, update, publish
        $this->assertTrue(Gate::forUser($userA)->allows('view', $websiteA));
        $this->assertTrue(Gate::forUser($userA)->allows('update', $websiteA));
        $this->assertTrue(Gate::forUser($userA)->allows('publish', $websiteA));

        $this->assertFalse(Gate::forUser($userA)->allows('view', $websiteB));
        $this->assertFalse(Gate::forUser($userA)->allows('update', $websiteB));
        $this->assertFalse(Gate::forUser($userA)->allows('publish', $websiteB));

        $this->assertTrue(Gate::forUser($admin)->allows('view', $websiteB));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $websiteB));
        $this->assertTrue(Gate::forUser($admin)->allows('publish', $websiteB));
    }
}
