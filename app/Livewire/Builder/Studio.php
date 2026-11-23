<?php

namespace App\Livewire\Builder;

use App\Models\Template;
use App\Models\Website;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.builder')]
#[Title('Visual Studio Builder')]
class Studio extends Component
{
    public Website $website;

    public int $templateId;

    public string $primaryColor = '#2563EB';

    public string $secondaryColor = '#1E40AF';

    public string $fontFamily = 'Inter';

    public string $deviceView = 'desktop';

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'primaryColor' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondaryColor' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'fontFamily' => [
                'required',
                'string',
                Rule::in(['Inter', 'Poppins', 'Roboto', 'Plus Jakarta Sans', 'Montserrat', 'Playfair Display', 'Outfit']),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'primaryColor.required' => 'Warna primer wajib diisi.',
            'primaryColor.regex' => 'Format warna primer harus berupa kode HEX yang valid (contoh: #2563EB).',
            'secondaryColor.required' => 'Warna sekunder wajib diisi.',
            'secondaryColor.regex' => 'Format warna sekunder harus berupa kode HEX yang valid (contoh: #1E40AF).',
            'fontFamily.required' => 'Pilihan jenis font wajib diisi.',
        ];
    }

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
                'slug' => Str::slug($business->name) ?: 'store-'.$business->id,
                'status' => 'draft',
            ]);
        }

        if (! $website->theme) {
            $website->theme()->create([
                'primary_color' => '#2563EB',
                'secondary_color' => '#1E40AF',
                'background_color' => '#FFFFFF',
                'text_color' => '#1F2937',
                'font_family' => 'Inter',
                'button_style' => 'rounded',
                'border_radius' => '8px',
            ]);
            $website->load('theme');
        }

        $this->website = $website;
        $this->templateId = (int) $website->template_id;
        $this->primaryColor = $website->theme?->primary_color ?? '#2563EB';
        $this->secondaryColor = $website->theme?->secondary_color ?? '#1E40AF';
        $this->fontFamily = $website->theme?->font_family ?? 'Inter';
        $this->deviceView = 'desktop';
    }

    public function setDeviceView(string $view): void
    {
        if (in_array($view, ['desktop', 'tablet', 'mobile'], true)) {
            $this->deviceView = $view;
        }
    }

    public function saveTheme(): void
    {
        $this->validate();

        $this->website->theme()->updateOrCreate(
            ['website_id' => $this->website->id],
            [
                'primary_color' => $this->primaryColor,
                'secondary_color' => $this->secondaryColor,
                'font_family' => $this->fontFamily,
            ]
        );

        $this->website->load('theme');

        session()->flash('success', 'Tema website berhasil disimpan.');
        $this->dispatch('theme-saved');
    }

    public function changeTemplate(int $newTemplateId): void
    {
        $template = Template::query()
            ->where('id', $newTemplateId)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            abort(404, 'Template tidak ditemukan atau tidak aktif.');
        }

        $this->website->update([
            'template_id' => $template->id,
        ]);

        $this->templateId = $template->id;
        $this->website->refresh();

        session()->flash('success', "Template berhasil diubah ke {$template->name}.");
        $this->dispatch('template-changed', templateId: $template->id);
    }

    public function togglePublish(): void
    {
        if ($this->website->status === 'published') {
            $this->website->update([
                'status' => 'draft',
            ]);
            session()->flash('success', 'Website berhasil diubah menjadi Draft.');
        } else {
            $this->website->update([
                'status' => 'published',
                'published_at' => now(),
            ]);
            session()->flash('success', 'Website berhasil diterbitkan!');
        }

        $this->website->refresh();
        $this->dispatch('status-toggled', status: $this->website->status);
    }

    public function render(): View
    {
        $templates = Template::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return view('livewire.builder.studio', [
            'templates' => $templates,
        ]);
    }
}
