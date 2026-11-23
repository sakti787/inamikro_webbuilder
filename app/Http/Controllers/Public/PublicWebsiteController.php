<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Website;
use App\Models\WebsiteVisit;
use App\Models\WhatsappClick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicWebsiteController extends Controller
{
    /**
     * Display the public website for the given slug.
     */
    public function show(Request $request, string $slug): View
    {
        $website = Website::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'business.banners',
                'business.products.primaryImage',
                'business.products.images',
                'business.products.category',
                'business.galleries',
                'business.testimonials',
                'business.category',
                'business.productCategories',
                'theme',
                'template',
            ])
            ->first();

        if (! $website) {
            abort(404, 'Website tidak ditemukan atau belum dipublikasikan.');
        }

        $templateView = $website->template?->view_path;
        if (empty($templateView) || ! view()->exists($templateView)) {
            abort(404, 'Template website tidak tersedia.');
        }

        $this->recordVisit($website, $request);

        return view($templateView, [
            'website' => $website,
            'business' => $website->business,
        ]);
    }

    /**
     * Display the product detail page on the public website.
     */
    public function productDetail(Request $request, string $slug, string $product_slug): View
    {
        $website = Website::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'business',
                'theme',
                'template',
            ])
            ->first();

        if (! $website) {
            abort(404, 'Website tidak ditemukan atau belum dipublikasikan.');
        }

        if (! $website->business) {
            abort(404, 'Data usaha tidak ditemukan.');
        }

        $product = $website->business->products()
            ->where('slug', $product_slug)
            ->where('is_available', true)
            ->with(['primaryImage', 'images', 'category'])
            ->first();

        if (! $product) {
            abort(404, 'Produk tidak ditemukan atau tidak tersedia.');
        }

        $template = $website->template;
        $detailView = null;

        if ($template?->view_path) {
            $candidate = str_replace('.index', '.detail', $template->view_path);
            if (view()->exists($candidate)) {
                $detailView = $candidate;
            }
        }

        if (! $detailView && $template?->slug) {
            $candidate = "templates.{$template->slug}.detail";
            if (view()->exists($candidate)) {
                $detailView = $candidate;
            }
        }

        if (! $detailView) {
            abort(404, 'Halaman detail produk tidak tersedia.');
        }

        return view($detailView, [
            'website' => $website,
            'business' => $website->business,
            'product' => $product,
        ]);
    }

    /**
     * Record a WhatsApp button click event for conversion analytics.
     */
    public function recordWaClick(Request $request, string $slug): JsonResponse
    {
        $website = Website::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (! $website) {
            abort(404, 'Website tidak ditemukan atau belum dipublikasikan.');
        }

        $validated = $request->validate([
            'product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where('business_id', $website->business_id),
            ],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        WhatsappClick::create([
            'website_id' => $website->id,
            'product_id' => $validated['product_id'] ?? null,
            'source' => $validated['source'] ?? 'landing_page',
            'clicked_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Click recorded',
        ]);
    }

    /**
     * Record a website visit into the website_visits table.
     */
    protected function recordVisit(Website $website, Request $request): void
    {
        try {
            $ip = $request->ip() ?? '127.0.0.1';
            $userAgent = $request->userAgent() ?? '';
            $visitorHash = hash('sha256', "{$ip}|{$userAgent}");

            WebsiteVisit::create([
                'website_id' => $website->id,
                'visited_at' => now(),
                'visitor_hash' => $visitorHash,
                'referrer' => $request->header('referer'),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
