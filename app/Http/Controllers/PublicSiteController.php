<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\SiteHtmlSanitizer;
use Illuminate\Http\Response;

class PublicSiteController extends Controller
{
    /**
     * Serve the published UMKM microsite HTML with strict Content-Security-Policy headers.
     */
    public function __invoke(string $slug): Response
    {
        $site = Site::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return response($site->rendered_html ?? '', 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Security-Policy', SiteHtmlSanitizer::cspHeader());
    }
}
