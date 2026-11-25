<?php

namespace Tests\Unit;

use App\Models\Site;
use App\Models\User;
use App\Services\SlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_clean_slug_from_business_name(): void
    {
        $service = new SlugService;

        $this->assertSame('dapur-bu-sari', $service->generateUniqueSlug('Dapur Bu Sari!'));
        $this->assertSame('usaha', $service->generateUniqueSlug(''));
        $this->assertSame('usaha', $service->generateUniqueSlug('---!!!###'));
    }

    public function test_avoids_reserved_slugs(): void
    {
        $service = new SlugService;

        $this->assertSame('login-2', $service->generateUniqueSlug('Login'));
        $this->assertSame('studio-2', $service->generateUniqueSlug('Studio'));
        $this->assertSame('api-2', $service->generateUniqueSlug('API'));
        $this->assertSame('admin-2', $service->generateUniqueSlug('Admin'));
        $this->assertSame('storage-2', $service->generateUniqueSlug('Storage'));
    }

    public function test_appends_numeric_suffix_when_slug_already_exists_in_users_or_sites(): void
    {
        $service = new SlugService;

        $firstUser = User::factory()->create([
            'business_name' => 'Kopi Senja',
            'slug' => 'kopi-senja',
        ]);

        $this->assertSame('kopi-senja-2', $service->generateUniqueSlug('Kopi Senja'));

        $secondUser = User::factory()->create([
            'business_name' => 'Kopi Senja 2',
            'slug' => 'kopi-senja-2',
        ]);

        Site::factory()->for($firstUser)->create([
            'slug' => 'kopi-senja',
        ]);

        Site::factory()->for($secondUser)->create([
            'slug' => 'kopi-senja-3',
        ]);

        $this->assertSame('kopi-senja-4', $service->generateUniqueSlug('Kopi Senja'));
    }

    public function test_allows_same_slug_when_ignoring_current_user_id(): void
    {
        $service = new SlugService;

        $user = User::factory()->create([
            'business_name' => 'Bakso Mantap',
            'slug' => 'bakso-mantap',
        ]);

        Site::factory()->for($user)->create([
            'slug' => 'bakso-mantap',
        ]);

        $this->assertSame('bakso-mantap', $service->generateUniqueSlug('Bakso Mantap', $user->id));
        $this->assertSame('bakso-mantap-2', $service->generateUniqueSlug('Bakso Mantap'));
    }
}
