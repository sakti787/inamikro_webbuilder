<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Template;
use App\Models\Theme;
use App\Models\User;
use App\Models\Website;
use App\Services\SlugService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            if ($request->user()->isAdmin()) {
                return redirect()->intended('/admin/dashboard');
            }

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Show the registration form.
     */
    public function showRegisterForm(): View
    {
        $categories = BusinessCategory::query()->orderBy('name')->get();

        return view('auth.register', compact('categories'));
    }

    /**
     * Handle user registration and auto-provisioning.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'business_name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:business_categories,id'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'umkm',
            ]);

            $whatsappNumber = $validated['whatsapp'] ?? $validated['phone'] ?? '08123456789';
            $phoneNumber = $validated['phone'] ?? $validated['whatsapp'] ?? null;
            $businessSlug = Str::slug($validated['business_name']) ?: 'business-'.$user->id;

            $business = Business::create([
                'user_id' => $user->id,
                'category_id' => $validated['category_id'] ?? null,
                'name' => $validated['business_name'],
                'slug' => $businessSlug,
                'phone' => $phoneNumber,
                'whatsapp' => $whatsappNumber,
            ]);

            $template = Template::query()->where('slug', 'minimalist')->first()
                ?? Template::query()->first()
                ?? Template::create([
                    'name' => 'Minimalist Store',
                    'slug' => 'minimalist',
                    'view_path' => 'templates.minimalist.index',
                    'is_active' => true,
                ]);

            $websiteSlug = app(SlugService::class)->generateUniqueSlug($validated['business_name']);

            $website = Website::create([
                'business_id' => $business->id,
                'template_id' => $template->id,
                'slug' => $websiteSlug,
                'status' => 'draft',
            ]);

            Theme::create([
                'website_id' => $website->id,
                'primary_color' => '#2563EB',
                'secondary_color' => '#1E40AF',
                'background_color' => '#FFFFFF',
                'text_color' => '#1F2937',
                'font_family' => 'Inter',
                'button_style' => 'rounded',
                'border_radius' => '8px',
            ]);

            return $user;
        });

        Auth::login($user);

        $request->session()->regenerate();

        return redirect('/dashboard');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
