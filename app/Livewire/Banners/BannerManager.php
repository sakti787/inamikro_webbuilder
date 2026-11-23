<?php

namespace App\Livewire\Banners;

use App\Models\Banner;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
class BannerManager extends Component
{
    use WithFileUploads;

    /**
     * @var TemporaryUploadedFile|null
     */
    public $image = null;

    public ?string $title = '';

    public ?string $subtitle = '';

    public ?string $button_text = '';

    public ?string $button_url = '';

    public bool $is_active = true;

    /**
     * Validation rules.
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'image' => 'required|image|max:2048',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Validation messages in Indonesian.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'image.required' => 'Gambar banner wajib diunggah.',
            'image.image' => 'Berkas harus berupa gambar.',
            'image.max' => 'Ukuran gambar banner maksimal 2MB.',
            'title.max' => 'Judul banner maksimal 255 karakter.',
            'subtitle.max' => 'Subjudul banner maksimal 255 karakter.',
            'button_text.max' => 'Teks tombol maksimal 100 karakter.',
            'button_url.max' => 'Tautan tombol maksimal 255 karakter.',
        ];
    }

    /**
     * Toggle banner active status.
     */
    public function toggleBanner(Banner|int $banner): void
    {
        if (is_int($banner)) {
            $banner = Banner::findOrFail($banner);
        }

        $this->authorize('update', $banner);

        $banner->update([
            'is_active' => ! $banner->is_active,
        ]);

        session()->flash('success', 'Status banner berhasil diubah.');
    }

    /**
     * Delete a banner.
     */
    public function deleteBanner(Banner|int $banner): void
    {
        if (is_int($banner)) {
            $banner = Banner::findOrFail($banner);
        }

        $this->authorize('delete', $banner);

        if ($banner->image && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        session()->flash('success', 'Banner berhasil dihapus.');
    }

    /**
     * Save new banner.
     */
    public function save(): void
    {
        $this->validate();

        $business = Auth::user()?->business;

        if (! $business) {
            abort(403, 'Anda belum memiliki profil usaha.');
        }

        $imagePath = $this->image->store('banners', 'public');

        Banner::create([
            'business_id' => $business->id,
            'image' => $imagePath,
            'title' => $this->title ?: null,
            'subtitle' => $this->subtitle ?: null,
            'button_text' => $this->button_text ?: null,
            'button_url' => $this->button_url ?: null,
            'is_active' => (bool) $this->is_active,
        ]);

        $this->reset(['image', 'title', 'subtitle', 'button_text', 'button_url']);
        $this->is_active = true;

        session()->flash('success', 'Banner promosi berhasil ditambahkan.');
    }

    public function render(): View
    {
        $business = Auth::user()?->business;

        $banners = $business
            ? Banner::where('business_id', $business->id)->latest()->get()
            : collect();

        return view('livewire.banners.banner-manager', [
            'banners' => $banners,
        ]);
    }
}
