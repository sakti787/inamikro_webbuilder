<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PreviewController extends Controller
{
    /**
     * Display internal live preview shell for the website.
     */
    public function show(Website $website): View
    {
        $user = Auth::user();

        if (! $user || $user->cannot('view', $website)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki akses ke pratinjau website ini.');
        }

        $website->load([
            'business.banners',
            'business.products.primaryImage',
            'business.galleries',
            'business.category',
            'theme',
            'template',
        ]);

        $templateView = $website->template?->view_path;
        $templateViewExists = ! empty($templateView) && view()->exists($templateView);

        return view('builder.preview', [
            'website' => $website,
            'business' => $website->business,
            'templateViewExists' => $templateViewExists,
        ]);
    }
}
