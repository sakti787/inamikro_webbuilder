<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_stores_business_name_slug_and_role(): void
    {
        $user = User::create([
            'name' => 'Sari Dewi',
            'business_name' => 'Dapur Bu Sari',
            'slug' => 'dapur-bu-sari',
            'email' => 'sari@example.com',
            'password' => 'password',
        ]);

        $freshUser = $user->fresh();

        $this->assertNotNull($freshUser);
        $this->assertSame('Dapur Bu Sari', $freshUser->business_name);
        $this->assertSame('dapur-bu-sari', $freshUser->slug);
        $this->assertSame('umkm', $freshUser->role);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'business_name' => 'Dapur Bu Sari',
            'slug' => 'dapur-bu-sari',
            'role' => 'umkm',
        ]);
    }

    public function test_sites_table_enforces_one_site_per_user_and_casts_content_to_array(): void
    {
        $user = User::factory()->create();
        $publishedAt = now();

        $site = Site::create([
            'user_id' => $user->id,
            'template_id' => 'kuliner',
            'slug' => $user->slug,
            'status' => 'published',
            'brand_color' => '#dc2626',
            'content' => [
                'hero' => [
                    'title' => 'Selamat Datang di Warung Kami',
                    'subtitle' => 'Masakan rumahan segar setiap hari',
                ],
                'products' => [
                    ['name' => 'Nasi Goreng', 'price' => 15000],
                ],
            ],
            'rendered_html' => '<html><body>Rendered</body></html>',
            'published_at' => $publishedAt,
        ]);

        $freshSite = $site->fresh();

        $this->assertNotNull($freshSite);
        $this->assertIsArray($freshSite->content);
        $this->assertSame('Selamat Datang di Warung Kami', $freshSite->content['hero']['title']);
        $this->assertInstanceOf(CarbonInterface::class, $freshSite->published_at);
        $this->assertTrue($user->site->is($freshSite));
        $this->assertTrue($freshSite->user->is($user));

        $this->expectException(QueryException::class);

        Site::create([
            'user_id' => $user->id,
            'template_id' => 'jasa',
            'slug' => $user->slug.'-second',
            'content' => ['hero' => ['title' => 'Duplicate']],
        ]);
    }
}
