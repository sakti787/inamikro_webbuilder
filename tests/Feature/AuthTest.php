<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_guest_to_login_and_authenticated_user_to_studio(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('studio'));
    }

    public function test_login_page_renders_in_indonesian(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Masuk ke INAweb Studio');
        $response->assertSee('Email');
        $response->assertSee('Kata Sandi');
        $response->assertSee('Ingat saya');
        $response->assertSee('Masuk');
        $response->assertSee('Daftar sekarang');
    }

    public function test_register_page_renders_with_business_name_field(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('Daftar Akun UMKM');
        $response->assertSee('Nama Lengkap');
        $response->assertSee('Nama Usaha');
        $response->assertSee('Email');
        $response->assertSee('Kata Sandi');
        $response->assertSee('Konfirmasi Kata Sandi');
        $response->assertSee('business_name');
    }

    public function test_umkm_can_register_and_gets_permanent_slug_and_umkm_role(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Sari Dewi',
            'business_name' => 'Warung Mbak Sari',
            'email' => 'sari@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertRedirect(route('studio'));
        $this->assertAuthenticated();

        $user = User::where('email', 'sari@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Sari Dewi', $user->name);
        $this->assertSame('Warung Mbak Sari', $user->business_name);
        $this->assertSame('warung-mbak-sari', $user->slug);
        $this->assertSame('umkm', $user->role);

        $this->post(route('logout'));
        $this->assertGuest();

        $reservedResponse = $this->post(route('register'), [
            'name' => 'Budi Santoso',
            'business_name' => 'Login',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $reservedResponse->assertRedirect(route('studio'));
        $this->assertAuthenticated();

        $reservedUser = User::where('email', 'budi@example.com')->first();

        $this->assertNotNull($reservedUser);
        $this->assertSame('login-2', $reservedUser->slug);
        $this->assertSame('umkm', $reservedUser->role);
    }

    public function test_register_validates_required_fields_and_unique_email_in_indonesian(): void
    {
        User::factory()->create([
            'email' => 'terdaftar@example.com',
        ]);

        $emptyResponse = $this->from(route('register'))
            ->post(route('register'), []);

        $emptyResponse->assertRedirect(route('register'));
        $emptyResponse->assertSessionHasErrors([
            'name' => 'Nama lengkap wajib diisi.',
            'business_name' => 'Nama usaha wajib diisi.',
            'email' => 'Email wajib diisi.',
            'password' => 'Kata sandi wajib diisi.',
        ]);

        $duplicateResponse = $this->from(route('register'))
            ->post(route('register'), [
                'name' => 'Andi Wijaya',
                'business_name' => 'Toko Andi',
                'email' => 'terdaftar@example.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'berbeda123',
            ]);

        $duplicateResponse->assertRedirect(route('register'));
        $duplicateResponse->assertSessionHasErrors([
            'email' => 'Email sudah terdaftar.',
            'password' => 'Konfirmasi kata sandi tidak cocok.',
        ]);
        $this->assertGuest();
    }

    public function test_umkm_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'umkm@example.com',
            'password' => Hash::make('sandiaman123'),
        ]);

        $failedResponse = $this->from(route('login'))
            ->post(route('login'), [
                'email' => 'umkm@example.com',
                'password' => 'salahpassword',
            ]);

        $failedResponse->assertRedirect(route('login'));
        $failedResponse->assertSessionHasErrors([
            'email' => 'Email atau kata sandi salah.',
        ]);
        $this->assertGuest();

        $loginResponse = $this->post(route('login'), [
            'email' => 'umkm@example.com',
            'password' => 'sandiaman123',
            'remember' => '1',
        ]);

        $loginResponse->assertRedirect(route('studio'));
        $this->assertAuthenticatedAs($user);

        $logoutResponse = $this->post(route('logout'));

        $logoutResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_failed_login_shows_inline_error_once_without_duplicate_alert(): void
    {
        User::factory()->create([
            'email' => 'umkm@example.com',
            'password' => Hash::make('sandiaman123'),
        ]);

        $response = $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login'), [
                'email' => 'umkm@example.com',
                'password' => 'salahpassword',
            ]);

        $response->assertOk();
        $response->assertSee('Email atau kata sandi salah.');
        $response->assertDontSee('role="alert"', false);
        $this->assertSame(1, substr_count((string) $response->getContent(), 'Email atau kata sandi salah.'));
    }
}
