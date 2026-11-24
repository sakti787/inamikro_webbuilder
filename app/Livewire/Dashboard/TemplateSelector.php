<?php

namespace App\Livewire\Dashboard;

use App\Models\Template;
use App\Models\Website;
use App\Services\SlugService;
use App\Services\WebsitePublishValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.dashboard')]
#[Title('Pilihan Tampilan')]
class TemplateSelector extends Component
{
    public Website $website;

    /**
     * Initialize the component with the authenticated user's website.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $business = $user?->business;

        if (! $business) {
            abort(403, 'Akses ditolak: Profil UMKM belum ditemukan.');
        }

        $website = $business->website;

        if (! $website) {
            $defaultTemplate = Template::query()->where('is_active', true)->first();

            $website = $business->website()->create([
                'template_id' => $defaultTemplate?->id ?? 1,
                'slug' => app(SlugService::class)->generateUniqueSlug($business->name),
                'status' => 'draft',
            ]);
        }

        if (! $website->theme) {
            $website->theme()->create([
                'primary_color' => '#0F172A',
                'secondary_color' => '#334155',
                'background_color' => '#FFFFFF',
                'text_color' => '#1F2937',
                'font_family' => 'Inter',
                'button_style' => 'rounded',
                'border_radius' => '8px',
            ]);
        }

        $website->load(['theme', 'template']);
        $this->website = $website;
    }

    /**
     * Select a template and apply curated styling presets.
     */
    public function selectTemplate(int $templateId): void
    {
        $this->authorize('update', $this->website);

        $template = Template::query()
            ->where('id', $templateId)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            abort(404, 'Template tidak ditemukan atau tidak aktif.');
        }

        DB::transaction(function () use ($template) {
            $this->website->update([
                'template_id' => $template->id,
            ]);

            /** @var array<string, array{primary_color: string, secondary_color: string, font_family: string}> $presets */
            $presets = [
                'minimalist' => [
                    'primary_color' => '#0F172A',
                    'secondary_color' => '#334155',
                    'font_family' => 'Inter',
                ],
                'food' => [
                    'primary_color' => '#DC2626',
                    'secondary_color' => '#B91C1C',
                    'font_family' => 'Poppins',
                ],
                'modern' => [
                    'primary_color' => '#2563EB',
                    'secondary_color' => '#1D4ED8',
                    'font_family' => 'Plus Jakarta Sans',
                ],
            ];

            $preset = $presets[$template->slug] ?? [
                'primary_color' => '#0F172A',
                'secondary_color' => '#334155',
                'font_family' => 'Inter',
            ];

            $this->website->theme()->updateOrCreate(
                ['website_id' => $this->website->id],
                [
                    'primary_color' => $preset['primary_color'],
                    'secondary_color' => $preset['secondary_color'],
                    'font_family' => $preset['font_family'],
                ]
            );
        });

        $this->website->refresh();
        $this->website->load(['theme', 'template']);

        session()->flash('success', "Tampilan toko berhasil diubah ke {$template->name}!");
    }

    /**
     * Toggle the publication status of the website with readiness validation.
     */
    public function togglePublish(): void
    {
        $this->authorize('publish', $this->website);

        if ($this->website->status === 'published') {
            DB::transaction(function () {
                $this->website->update([
                    'status' => 'draft',
                    'published_at' => null,
                ]);
            });

            $this->website->refresh();
            session()->flash('success', 'Website toko berhasil diubah menjadi Draft.');
        } else {
            $result = app(WebsitePublishValidator::class)->validate($this->website);

            if (! $result->isValid()) {
                session()->flash('publish_error', implode(' ', $result->getErrors()));

                return;
            }

            DB::transaction(function () {
                $this->website->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);
            });

            $this->website->refresh();
            session()->flash('success', 'Selamat! Website toko online Anda berhasil diterbitkan.');
        }
    }

    /**
     * Render the template selector view.
     */
    public function render(): View
    {
        $templates = Template::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return view('livewire.dashboard.template-selector', [
            'templates' => $templates,
        ]);
    }
}
