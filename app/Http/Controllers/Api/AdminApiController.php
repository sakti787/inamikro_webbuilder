<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReviseSiteRequest;
use App\Http\Requests\Api\SaveAdminContactRequest;
use App\Http\Requests\Api\SaveAdminNoteRequest;
use App\Http\Requests\Api\SaveSiteRequest;
use App\Http\Requests\Api\ToggleAutoApproveRequest;
use App\Http\Requests\Api\ToggleTemplateRequest;
use App\Models\AdminLog;
use App\Models\AppSetting;
use App\Models\Site;
use App\Models\SiteHistory;
use App\Models\TemplateSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminApiController extends Controller
{
    /**
     * Display names for the 8 official Studio templates.
     *
     * @var array<string, string>
     */
    private const array TEMPLATE_NAMES = [
        'warung' => 'Warung Rasa',
        'butik' => 'Butik Ayu',
        'jasa' => 'Jasa Pro',
        'toko' => 'Toko Kelontong',
        'warung-2' => 'Dapur Bunda',
        'butik-2' => 'Galeri Loka',
        'jasa-2' => 'Bengkel Sigap',
        'toko-2' => 'Sembako Jaya',
    ];

    /**
     * Return the full Admin Panel bootstrap dataset (UMKM, templates, settings, logs).
     *
     * @return array{
     *     umkm: array<int, array<string, mixed>>,
     *     templates: array<int, array{template_id: string, is_active: bool}>,
     *     settings: array{auto_approve: bool, admin_name: string, admin_whatsapp: string},
     *     logs: array<int, array<string, mixed>>
     * }
     */
    public static function bootstrapPayload(): array
    {
        $umkm = User::query()
            ->where('role', 'umkm')
            ->with([
                'site' => fn ($query) => $query->with([
                    'histories' => fn ($historyQuery) => $historyQuery->latest('id'),
                ]),
            ])
            ->orderBy('id')
            ->get()
            ->map(fn (User $user): array => self::formatUmkmItem($user))
            ->values()
            ->all();

        return [
            'umkm' => $umkm,
            'templates' => TemplateSetting::allWithDefaults(),
            'settings' => self::settingsPayload(),
            'logs' => self::recentLogsPayload(),
        ];
    }

    /**
     * Format a single UMKM user and its optional site/history into the Admin UI contract.
     *
     * @return array<string, mixed>
     */
    public static function formatUmkmItem(User $user): array
    {
        /** @var Site|null $site */
        $site = $user->relationLoaded('site') ? $user->getRelation('site') : $user->site()->first();
        $slug = $site?->slug ?? $user->slug;
        $statusKey = $site !== null ? (string) $site->status : 'draft';

        if (! in_array($statusKey, ['published', 'pending', 'draft'], true)) {
            $statusKey = 'draft';
        }

        $histories = [];
        if ($site !== null) {
            $historyCollection = $site->relationLoaded('histories')
                ? $site->histories->sortByDesc('id')->values()
                : $site->histories()->latest('id')->get();

            $histories = $historyCollection
                ->map(fn (SiteHistory $history): array => [
                    'id' => $history->id,
                    'title' => $history->title,
                    'text' => $history->description ?? '',
                    't' => $history->created_at?->locale('id')->diffForHumans() ?? 'Baru saja',
                    'at' => $history->created_at?->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        return [
            'id' => $user->id,
            'has_site' => $site !== null,
            'email' => $user->email,
            'name' => $user->name,
            'business_name' => $user->business_name,
            'slug' => $slug,
            'public_url' => url('/'.$slug),
            'status' => self::statusLabel($statusKey),
            'status_key' => $statusKey,
            'changed' => $site?->updated_at?->locale('id')->diffForHumans() ?? 'Belum ada draf',
            'updated_at' => $site?->updated_at?->toIso8601String(),
            'note' => $site?->admin_note ?? '',
            'revision_note' => $site?->revision_note,
            'rendered_html' => $site?->rendered_html,
            'completeness' => self::computeCompleteness($site),
            'history' => $histories,
            'state' => self::formatState($user, $site),
        ];
    }

    /**
     * Format an AdminLog entry for API responses.
     *
     * @return array<string, mixed>
     */
    public static function formatLogItem(AdminLog $log): array
    {
        return [
            'id' => $log->id,
            'title' => $log->title,
            'text' => $log->description ?? '',
            'who' => $log->who,
            'kind' => $log->kind,
            'at' => $log->created_at?->toIso8601String(),
        ];
    }

    /**
     * Return the full Admin Panel bootstrap JSON payload.
     */
    public function bootstrap(): JsonResponse
    {
        return response()->json(self::bootstrapPayload());
    }

    /**
     * Return paginated admin activity logs (8 entries per page, newest first).
     */
    public function logs(): JsonResponse
    {
        $paginator = AdminLog::query()
            ->latest('id')
            ->paginate(8);

        $items = $paginator->getCollection()
            ->map(fn (AdminLog $log): array => self::formatLogItem($log))
            ->values()
            ->all();

        return response()->json([
            'items' => $items,
            'page' => $paginator->currentPage(),
            'pages' => max(1, $paginator->lastPage()),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * Approve a complete UMKM site for publication.
     */
    public function approve(User $user): JsonResponse
    {
        $site = $this->resolveUmkmSite($user);
        $content = is_array($site->content) ? $site->content : [];

        if (trim((string) $site->rendered_html) === '' || ! SaveSiteRequest::hasCompletePublishContent($content)) {
            throw ValidationException::withMessages([
                'site' => [
                    'Situs belum memenuhi 4/4 syarat wajib atau belum memiliki pratinjau HTML. Gunakan tombol Minta revisi untuk mengembalikan ke UMKM.',
                ],
            ]);
        }

        /** @var User $admin */
        $admin = request()->user();

        DB::transaction(function () use ($user, $site, $admin): void {
            $site->update([
                'status' => 'published',
                'published_at' => now(),
                'revision_note' => null,
            ]);

            $this->recordModeration(
                $user,
                $site,
                $admin,
                'Disetujui tayang',
                'Admin pendamping menyetujui situs untuk tayang.',
                'approve',
            );
        });

        return $this->umkmMutationResponse($user);
    }

    /**
     * Return a UMKM site back to draft with an actionable revision note while preserving admin_note.
     */
    public function revise(ReviseSiteRequest $request, User $user): JsonResponse
    {
        $site = $this->resolveUmkmSite($user);
        $reason = (string) $request->validated('reason');
        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($user, $site, $admin, $reason): void {
            $site->update([
                'status' => 'draft',
                'revision_note' => $reason,
            ]);

            $this->recordModeration(
                $user,
                $site,
                $admin,
                'Diminta revisi',
                $reason,
                'revise',
            );
        });

        return $this->umkmMutationResponse($user);
    }

    /**
     * Update the internal admin note on a UMKM site without altering revision_note.
     */
    public function saveNote(SaveAdminNoteRequest $request, User $user): JsonResponse
    {
        $site = $this->resolveUmkmSite($user);
        $note = trim((string) ($request->validated('note') ?? ''));
        $description = $note !== '' ? $note : 'Catatan dikosongkan.';
        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($user, $site, $admin, $note, $description): void {
            $site->update([
                'admin_note' => $note !== '' ? $note : null,
            ]);

            $this->recordModeration(
                $user,
                $site,
                $admin,
                'Catatan diperbarui',
                $description,
                'note',
            );
        });

        return $this->umkmMutationResponse($user);
    }

    /**
     * Activate or deactivate one of the 8 Studio templates and record an activity log.
     */
    public function toggleTemplate(ToggleTemplateRequest $request, string $templateId): JsonResponse
    {
        $validated = $request->validated();
        $resolvedTemplateId = (string) $validated['template_id'];
        $isActive = (bool) $validated['is_active'];
        $templateName = self::TEMPLATE_NAMES[$resolvedTemplateId] ?? $resolvedTemplateId;
        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($resolvedTemplateId, $isActive, $templateName, $admin): void {
            TemplateSetting::query()->updateOrCreate(
                ['template_id' => $resolvedTemplateId],
                ['is_active' => $isActive],
            );

            AdminLog::create([
                'actor_id' => $admin->id,
                'title' => $isActive ? 'Template diaktifkan' : 'Template dinonaktifkan',
                'description' => $templateName.' '.($isActive ? 'kini aktif dipakai UMKM.' : 'tidak lagi dipakai UMKM.'),
                'who' => $templateName,
                'kind' => 'template',
            ]);
        });

        return response()->json([
            'template' => [
                'template_id' => $resolvedTemplateId,
                'is_active' => $isActive,
            ],
            'templates' => TemplateSetting::allWithDefaults(),
            'logs' => self::recentLogsPayload(),
        ]);
    }

    /**
     * Toggle automatic approval and batch-publish eligible complete pending sites when enabled.
     */
    public function toggleAutoApprove(ToggleAutoApproveRequest $request): JsonResponse
    {
        $enabled = (bool) $request->validated('auto_approve');
        /** @var User $admin */
        $admin = $request->user();
        $approvedCount = 0;

        DB::transaction(function () use ($enabled, $admin, &$approvedCount): void {
            AppSetting::setValue('auto_approve', $enabled ? '1' : '0');

            AdminLog::create([
                'actor_id' => $admin->id,
                'title' => $enabled ? 'Auto setujui diaktifkan' : 'Auto setujui dimatikan',
                'description' => $enabled
                    ? 'Situs dengan 4/4 syarat langsung tayang.'
                    : 'Setiap situs kembali ditinjau manual.',
                'who' => 'Pengaturan antrean',
                'kind' => 'settings',
            ]);

            if (! $enabled) {
                return;
            }

            $pendingSites = Site::query()
                ->with('user')
                ->where('status', 'pending')
                ->get();

            foreach ($pendingSites as $site) {
                $content = is_array($site->content) ? $site->content : [];

                if (trim((string) $site->rendered_html) === '' || ! SaveSiteRequest::hasCompletePublishContent($content)) {
                    continue;
                }

                $site->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'revision_note' => null,
                ]);

                if ($site->user !== null) {
                    $this->recordModeration(
                        $site->user,
                        $site,
                        $admin,
                        'Disetujui otomatis',
                        'Auto setujui aktif: 4 dari 4 syarat terpenuhi.',
                        'auto',
                    );
                }

                $approvedCount++;
            }
        });

        return response()->json([
            'auto_approve' => $enabled,
            'approved_count' => $approvedCount,
            ...self::bootstrapPayload(),
        ]);
    }

    /**
     * Save the companion admin name and normalized WhatsApp contact number.
     */
    public function saveContact(SaveAdminContactRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $adminName = trim((string) ($validated['admin_name'] ?? ''));

        if ($adminName === '') {
            $adminName = 'Tim INAmikro';
        }

        $adminWhatsapp = trim((string) ($validated['admin_whatsapp'] ?? ''));
        /** @var User $admin */
        $admin = $request->user();

        DB::transaction(function () use ($adminName, $adminWhatsapp, $admin): void {
            AppSetting::setValue('admin_name', $adminName);
            AppSetting::setValue('admin_whatsapp', $adminWhatsapp);

            AdminLog::create([
                'actor_id' => $admin->id,
                'title' => 'Kontak pendamping diperbarui',
                'description' => $adminWhatsapp !== ''
                    ? "Nama: {$adminName} · WhatsApp: {$adminWhatsapp}"
                    : "Nama: {$adminName} · WhatsApp dikosongkan.",
                'who' => 'Pengaturan',
                'kind' => 'settings',
            ]);
        });

        return response()->json([
            'settings' => self::settingsPayload(),
            'contact' => AppSetting::adminContact(),
            'logs' => self::recentLogsPayload(),
        ]);
    }

    /**
     * Ensure the route target user is a UMKM account and already has a site record.
     */
    private function resolveUmkmSite(User $user): Site
    {
        if ($user->role !== 'umkm') {
            abort(404);
        }

        /** @var Site|null $site */
        $site = $user->site()->first();

        if ($site === null) {
            throw ValidationException::withMessages([
                'site' => ['UMKM ini belum membuat draf situs di Studio.'],
            ]);
        }

        return $site;
    }

    /**
     * Persist synchronized site history and admin audit log entries for a moderation event.
     */
    private function recordModeration(
        User $umkmUser,
        Site $site,
        User $admin,
        string $title,
        string $description,
        string $logKind,
    ): void {
        SiteHistory::create([
            'site_id' => $site->id,
            'actor_id' => $admin->id,
            'title' => $title,
            'description' => $description,
        ]);

        AdminLog::create([
            'actor_id' => $admin->id,
            'title' => $title,
            'description' => $description,
            'who' => self::umkmDisplayName($umkmUser, $site),
            'kind' => $logKind,
        ]);
    }

    /**
     * Return a refreshed UMKM item and recent logs payload after a site mutation.
     */
    private function umkmMutationResponse(User $user): JsonResponse
    {
        $user->load([
            'site' => fn ($query) => $query->with([
                'histories' => fn ($historyQuery) => $historyQuery->latest('id'),
            ]),
        ]);

        return response()->json([
            'umkm' => self::formatUmkmItem($user),
            'logs' => self::recentLogsPayload(),
        ]);
    }

    /**
     * Return normalized application settings for the Admin Panel.
     *
     * @return array{auto_approve: bool, admin_name: string, admin_whatsapp: string}
     */
    private static function settingsPayload(): array
    {
        $contact = AppSetting::adminContact();

        return [
            'auto_approve' => AppSetting::autoApproveEnabled(),
            'admin_name' => $contact['name'],
            'admin_whatsapp' => $contact['whatsapp'],
        ];
    }

    /**
     * Fetch the latest admin activity logs ordered newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function recentLogsPayload(int $limit = 200): array
    {
        return AdminLog::query()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (AdminLog $log): array => self::formatLogItem($log))
            ->values()
            ->all();
    }

    /**
     * Compute how many of the 4 required publish checklist fields are populated.
     */
    private static function computeCompleteness(?Site $site): int
    {
        if ($site === null) {
            return 0;
        }

        $content = is_array($site->content) ? $site->content : [];
        $score = 0;

        if (trim((string) ($content['nama'] ?? '')) !== '') {
            $score++;
        }

        if (trim((string) ($content['wa'] ?? '')) !== '') {
            $score++;
        }

        if (trim((string) ($content['jam'] ?? '')) !== '') {
            $score++;
        }

        if (SaveSiteRequest::hasNamedProduct($content['produk'] ?? null)) {
            $score++;
        }

        return $score;
    }

    /**
     * Build a normalized template state array compatible with templates.js.
     *
     * @return array<string, mixed>
     */
    private static function formatState(User $user, ?Site $site): array
    {
        $content = ($site !== null && is_array($site->content)) ? $site->content : [];
        $templateId = $site?->template_id ?? 'warung';

        return [
            'template' => $templateId,
            'variant' => (bool) ($content['variant'] ?? str_ends_with($templateId, '-2')),
            'brand' => $site?->brand_color ?? '#dc2626',
            'nama' => $site !== null ? (string) ($content['nama'] ?? '') : $user->business_name,
            'tagline' => (string) ($content['tagline'] ?? ''),
            'kategori' => (string) ($content['kategori'] ?? ''),
            'deskripsi' => (string) ($content['deskripsi'] ?? ''),
            'heroImg' => (string) ($content['heroImg'] ?? ''),
            'wa' => (string) ($content['wa'] ?? ''),
            'ig' => (string) ($content['ig'] ?? ''),
            'alamat' => (string) ($content['alamat'] ?? ''),
            'jam' => (string) ($content['jam'] ?? ''),
            'produk' => is_array($content['produk'] ?? null) ? array_values($content['produk']) : [],
        ];
    }

    /**
     * Map an internal site status key to its Indonesian UI label.
     */
    private static function statusLabel(string $statusKey): string
    {
        return match ($statusKey) {
            'published' => 'Tayang',
            'pending' => 'Menunggu',
            default => 'Draf',
        };
    }

    /**
     * Resolve the UMKM display name used in admin activity logs.
     */
    private static function umkmDisplayName(User $user, ?Site $site): string
    {
        $contentName = ($site !== null && is_array($site->content))
            ? trim((string) ($site->content['nama'] ?? ''))
            : '';

        return $contentName !== '' ? $contentName : $user->business_name;
    }
}
