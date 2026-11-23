<?php

namespace App\Http\Controllers\Builder;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PreviewController extends Controller
{
    /**
     * Display internal live preview shell for the website.
     */
    public function show(Request $request, Website $website): View
    {
        $user = Auth::user();

        if (! $user || $user->cannot('view', $website)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki akses ke pratinjau website ini.');
        }

        $website->load([
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
        ]);

        $business = $website->business;
        $templateView = $website->template?->view_path;

        // Check if previewing a specific product detail
        $productId = $request->query('product_id', $request->query('product'));
        if ($productId && $business) {
            $product = $business->products()
                ->where(function ($query) use ($productId) {
                    $query->where('id', $productId)
                        ->orWhere('slug', $productId);
                })
                ->with(['primaryImage', 'images', 'category'])
                ->first();

            if ($product && ! empty($templateView)) {
                $detailView = str_replace('.index', '.detail', $templateView);
                if (view()->exists($detailView)) {
                    return view($detailView, [
                        'website' => $website,
                        'business' => $business,
                        'product' => $product,
                    ]);
                }
            }
        }

        if (! empty($templateView) && view()->exists($templateView)) {
            return view($templateView, [
                'website' => $website,
                'business' => $business,
            ]);
        }

        return view('builder.preview', [
            'website' => $website,
            'business' => $business,
            'templateViewExists' => false,
        ]);
    }
}
