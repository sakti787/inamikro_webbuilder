<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Display the UMKM login page.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate an UMKM user session.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => 'Email atau kata sandi salah.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = $request->user();
        $intended = $request->session()->pull('url.intended');
        $intendedPath = is_string($intended) ? (string) parse_url($intended, PHP_URL_PATH) : '';
        $isAdminPath = $intendedPath === '/admin' || str_starts_with($intendedPath, '/admin/');
        $isApiPath = $intendedPath === '/api' || str_starts_with($intendedPath, '/api/');

        if ($user?->role === 'admin') {
            if (is_string($intended) && $isAdminPath && ! $isApiPath) {
                return redirect()->to($intended);
            }

            return redirect()->route('admin');
        }

        if (is_string($intended) && $intended !== '' && ! $isAdminPath && ! $isApiPath) {
            return redirect()->to($intended);
        }

        return redirect()->route('studio');
    }

    /**
     * Display the UMKM registration page.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new UMKM user with a permanent unique slug.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $slug = SlugService::generateUniqueSlug($validated['business_name']);

        $user = User::create([
            'name' => $validated['name'],
            'business_name' => $validated['business_name'],
            'slug' => $slug,
            'role' => 'umkm',
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('studio');
    }

    /**
     * Log the user out and invalidate the current session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
