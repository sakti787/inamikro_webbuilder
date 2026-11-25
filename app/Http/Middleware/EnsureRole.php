<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request and verify the authenticated user's role.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(401);
            }

            return redirect()->route('login');
        }

        if ($user->role !== $role) {
            if ($request->expectsJson() || $request->is('api/*')) {
                abort(403, 'Akses ditolak.');
            }

            if ($role === 'umkm' && $user->role === 'admin') {
                return redirect()->route('admin');
            }

            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
