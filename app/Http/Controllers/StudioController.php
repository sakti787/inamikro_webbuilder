<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Site;
use App\Models\TemplateSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudioController extends Controller
{
    /**
     * Display the INAweb Studio editor and site management workspace for the authenticated UMKM user.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        /** @var Site|null $site */
        $site = $user->site()->first();
        $publicBaseUrl = rtrim(url('/'), '/');
        $publicSiteUrl = url('/'.$user->slug);

        $studioConfig = [
            'userName' => $user->name,
            'businessName' => $user->business_name,
            'siteSlug' => $user->slug,
            'publicBaseUrl' => $publicBaseUrl,
            'publicSiteUrl' => $publicSiteUrl,
            'activeTemplates' => TemplateSetting::activeTemplateIds(),
            'adminContact' => AppSetting::adminContact(),
            'autoApprove' => AppSetting::autoApproveEnabled(),
            'initialSite' => $site ? [
                'id' => $site->id,
                'template_id' => $site->template_id,
                'slug' => $site->slug,
                'status' => $site->status,
                'brand_color' => $site->brand_color,
                'content' => $site->content,
                'revision_note' => $site->revision_note,
                'published_at' => $site->published_at?->toIso8601String(),
                'updated_at' => $site->updated_at?->toIso8601String(),
            ] : null,
            'csrfToken' => csrf_token(),
            'routes' => [
                'siteShow' => route('api.site.show'),
                'siteSave' => route('api.site.upsert'),
                'sitePublish' => route('api.site.publish'),
                'siteDelete' => route('api.site.destroy'),
                'mediaStore' => route('api.media.store'),
                'mediaDelete' => route('api.media.destroy'),
                'logout' => route('logout'),
            ],
        ];

        return view('studio.index', [
            'user' => $user,
            'site' => $site,
            'publicBaseUrl' => $publicBaseUrl,
            'publicSiteUrl' => $publicSiteUrl,
            'studioConfig' => $studioConfig,
        ]);
    }
}
