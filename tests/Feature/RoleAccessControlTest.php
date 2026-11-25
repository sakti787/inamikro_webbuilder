<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_redirects_to_admin_panel_and_umkm_login_redirects_to_studio(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('sandiaman123'),
        ]);

        $umkm = User::factory()->create([
            'role' => 'umkm',
            'email' => 'umkm@example.com',
            'password' => Hash::make('sandiaman123'),
        ]);

        // Admin logging in after hitting /studio as a guest must NOT be sent to /studio.
        $this->get(route('studio'))->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'sandiaman123',
        ])->assertRedirect(route('admin'));

        $this->assertAuthenticatedAs($admin);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        // UMKM logging in after hitting /admin as a guest must NOT be sent to /admin.
        $this->get('/admin')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => 'umkm@example.com',
            'password' => 'sandiaman123',
        ])->assertRedirect(route('studio'));

        $this->assertAuthenticatedAs($umkm);
    }

    public function test_root_redirects_based_on_user_role(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));

        $umkm = User::factory()->create(['role' => 'umkm']);
        $this->actingAs($umkm)
            ->get('/')
            ->assertRedirect(route('studio'));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get('/')
            ->assertRedirect(route('admin'));
    }

    public function test_umkm_cannot_access_admin_panel_or_admin_apis(): void
    {
        $umkm = User::factory()->create(['role' => 'umkm']);

        $this->actingAs($umkm)
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs($umkm)
            ->getJson('/admin')
            ->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_admin_cannot_access_umkm_apis_and_studio_redirects_admin_to_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('studio'))
            ->assertRedirect(route('admin'));

        $this->actingAs($admin)
            ->getJson('/api/site')
            ->assertForbidden();

        $this->actingAs($admin)
            ->putJson('/api/site', [])
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson('/api/site/publish')
            ->assertForbidden();

        $this->actingAs($admin)
            ->deleteJson('/api/site')
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson('/api/media', [])
            ->assertForbidden();

        $this->actingAs($admin)
            ->deleteJson('/api/media', [])
            ->assertForbidden();
    }
}
