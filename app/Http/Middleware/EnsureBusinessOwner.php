<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isUmkm() && ! $user->isAdmin()) {
            abort(403, 'Akses ditolak: Hanya pemilik UMKM yang dapat mengakses halaman ini.');
        }

        if ($user->isUmkm() && ! $user->business) {
            $business = $user->business()->create([
                'name' => $user->name.' Store',
                'slug' => Str::slug($user->name.' Store').'-'.$user->id,
                'whatsapp' => '08123456789',
            ]);
            $user->setRelation('business', $business);
        }

        return $next($request);
    }
}
