<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PublishSiteRequest;
use App\Http\Requests\Api\SaveSiteRequest;
use App\Services\SiteHtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteApiController extends Controller
{
    /**
     * Return the authenticated UMKM user's site record and permanent public URL.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'site' => $user->site()->first(),
            'slug' => $user->slug,
            'public_url' => url('/'.$user->slug),
        ]);
    }

    /**
     * Create or update the authenticated UMKM user's site draft while preserving permanent slug
     * and guarding valid published HTML during incomplete edits.
     */
    public function upsert(SaveSiteRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $existingSite = $user->site()->first();
        $content = $validated['content'];

        $attributes = [
            'template_id' => $validated['template_id'],
            'slug' => $user->slug,
            'brand_color' => $validated['brand_color'] ?? $existingSite?->brand_color ?? '#dc2626',
            'content' => $content,
        ];

        $incomingHtml = $validated['rendered_html'] ?? null;
        $hasIncomingHtml = is_string($incomingHtml) && trim($incomingHtml) !== '';

        if ($existingSite === null) {
            $attributes['status'] = 'draft';
            $attributes['rendered_html'] = $hasIncomingHtml
                ? SiteHtmlSanitizer::sanitize($incomingHtml)
                : null;
        } elseif ($existingSite->status === 'published') {
            if ($hasIncomingHtml && SaveSiteRequest::hasCompletePublishContent($content)) {
                $attributes['rendered_html'] = SiteHtmlSanitizer::sanitize($incomingHtml);
            }
        } elseif ($hasIncomingHtml) {
            $attributes['rendered_html'] = SiteHtmlSanitizer::sanitize($incomingHtml);
        }

        $site = $user->site()->updateOrCreate(
            ['user_id' => $user->id],
            $attributes,
        );

        return response()->json([
            'site' => $site->fresh(),
            'slug' => $user->slug,
            'public_url' => url('/'.$user->slug),
        ]);
    }

    /**
     * Validate publish checklist requirements, sanitize HTML, and mark the user's site published.
     */
    public function publish(PublishSiteRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $existingSite = $user->site()->first();

        $site = $user->site()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'template_id' => $validated['template_id'],
                'slug' => $user->slug,
                'status' => 'published',
                'brand_color' => $validated['brand_color'] ?? $existingSite?->brand_color ?? '#dc2626',
                'content' => $validated['content'],
                'rendered_html' => SiteHtmlSanitizer::sanitize($validated['rendered_html']),
                'published_at' => now(),
            ],
        );

        return response()->json([
            'site' => $site->fresh(),
            'slug' => $user->slug,
            'public_url' => url('/'.$user->slug),
        ]);
    }

    /**
     * Delete the authenticated UMKM user's site record and uploaded media directory.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->site()->delete();
        Storage::disk('public')->deleteDirectory('sites/'.$user->id);

        return response()->json([
            'deleted' => true,
        ]);
    }
}
