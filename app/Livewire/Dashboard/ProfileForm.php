<?php

namespace App\Livewire\Dashboard;

use App\Models\Business;
use App\Models\BusinessCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
#[Title('Profil Usaha')]
class ProfileForm extends Component
{
    use WithFileUploads;

    public const DEFAULT_FEATURES_BADGE = 'Mengapa Memilih Kami';

    public const DEFAULT_FEATURES_TITLE = 'Keunggulan Layanan Kami';

    public const DEFAULT_FEATURES_SUBTITLE = 'Komitmen kami dalam memberikan standar mutu dan kepuasan terbaik';

    public const DEFAULT_FEATURES_ITEMS = [
        [
            'title' => 'Kualitas Terjamin & Terpercaya',
            'description' => 'Setiap produk dan layanan melewati kontrol kualitas yang ketat demi kepuasan menyeluruh.',
        ],
        [
            'title' => 'Respon Cepat & Profesional',
            'description' => 'Tim kami selalu siap mendengarkan kebutuhan Anda dengan respon tanggap dan solusi tepat.',
        ],
        [
            'title' => 'Harga Transparan & Bersaing',
            'description' => 'Menawarkan nilai terbaik tanpa biaya tersembunyi untuk kemitraan jangka panjang yang kokoh.',
        ],
    ];

    public string $name = '';

    public ?int $category_id = null;

    public ?string $description = null;

    public ?string $address = null;

    public ?string $phone = null;

    public string $whatsapp = '';

    public ?string $operating_hours = null;

    /**
     * @var mixed
     */
    public $logo = null;

    public ?string $existingLogo = null;

    public ?string $lastUpdatedHuman = null;

    public ?string $lastUpdatedFormatted = null;

    public bool $features_enabled = false;

    public string $features_badge = self::DEFAULT_FEATURES_BADGE;

    public string $features_title = self::DEFAULT_FEATURES_TITLE;

    public string $features_subtitle = self::DEFAULT_FEATURES_SUBTITLE;

    /**
     * @var array<int, array<string, string>>
     */
    public array $features_items = [];

    /**
     * Validation rules for business profile.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:business_categories,id'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'operating_hours' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];

        if ($this->features_enabled) {
            $rules['features_badge'] = ['nullable', 'string', 'max:100'];
            $rules['features_title'] = ['required', 'string', 'max:255'];
            $rules['features_subtitle'] = ['nullable', 'string', 'max:255'];
            $rules['features_items'] = ['required', 'array', 'size:3'];
            $rules['features_items.*.title'] = ['required', 'string', 'max:255'];
            $rules['features_items.*.description'] = ['required', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * Validation messages in Indonesian.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Nama usaha wajib diisi.',
            'whatsapp.required' => 'Nomor WhatsApp bisnis wajib diisi.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'logo.image' => 'Logo harus berupa berkas gambar.',
            'logo.max' => 'Ukuran logo maksimal 2MB.',
            'features_title.required' => 'Judul keunggulan layanan wajib diisi.',
            'features_items.required' => 'Item keunggulan wajib diisi.',
            'features_items.size' => 'Bagian keunggulan harus terdiri dari 3 kartu.',
            'features_items.*.title.required' => 'Judul kartu keunggulan wajib diisi.',
            'features_items.*.description.required' => 'Deskripsi kartu keunggulan wajib diisi.',
        ];
    }

    public function mount(): void
    {
        $user = Auth::user();
        $business = $user?->business;

        if ($business) {
            $this->name = $business->name ?? '';
            $this->category_id = $business->category_id;
            $this->description = $business->description;
            $this->address = $business->address;
            $this->phone = $business->phone;
            $this->whatsapp = $business->whatsapp ?? '';
            $this->existingLogo = $business->logo;

            if (is_array($business->operating_hours)) {
                $this->operating_hours = implode("\n", $business->operating_hours);
            } else {
                $this->operating_hours = $business->operating_hours;
            }

            if (! empty($business->features) && is_array($business->features)) {
                $this->features_enabled = (bool) ($business->features['enabled'] ?? false);
                $this->features_badge = (string) ($business->features['badge'] ?? self::DEFAULT_FEATURES_BADGE);
                $this->features_title = (string) ($business->features['title'] ?? self::DEFAULT_FEATURES_TITLE);
                $this->features_subtitle = (string) ($business->features['subtitle'] ?? self::DEFAULT_FEATURES_SUBTITLE);
                $this->features_items = (array) ($business->features['items'] ?? []);
            }

            $this->updateLastEditedTimestamps($business);
        }
    }

    public function updatedFeaturesEnabled(mixed $value): void
    {
        if ((bool) $value) {
            if (empty($this->features_items)) {
                $this->features_items = self::DEFAULT_FEATURES_ITEMS;
            }
            if (empty($this->features_badge)) {
                $this->features_badge = self::DEFAULT_FEATURES_BADGE;
            }
            if (empty($this->features_title)) {
                $this->features_title = self::DEFAULT_FEATURES_TITLE;
            }
            if (empty($this->features_subtitle)) {
                $this->features_subtitle = self::DEFAULT_FEATURES_SUBTITLE;
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        $user = Auth::user();
        $business = $user?->business;

        if (! $business) {
            return;
        }

        $updateData = [
            'name' => $this->name,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'address' => $this->address,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'operating_hours' => $this->operating_hours,
            'features' => [
                'enabled' => $this->features_enabled,
                'badge' => $this->features_badge,
                'title' => $this->features_title,
                'subtitle' => $this->features_subtitle,
                'items' => $this->features_items,
            ],
        ];

        if ($this->logo) {
            if ($business->logo && Storage::disk('public')->exists($business->logo)) {
                Storage::disk('public')->delete($business->logo);
            }

            $path = $this->logo->store('logos', 'public');
            $updateData['logo'] = $path;
            $this->existingLogo = $path;
            $this->logo = null;
        }

        $business->update($updateData);
        $business->refresh();
        $this->updateLastEditedTimestamps($business);

        session()->flash('success', 'Profil usaha berhasil diperbarui.');
    }

    protected function updateLastEditedTimestamps(?Business $business): void
    {
        if ($business?->updated_at) {
            $this->lastUpdatedFormatted = $business->updated_at->locale('id')->isoFormat('D MMMM Y, HH:mm').' WIB';
            $this->lastUpdatedHuman = $business->updated_at->locale('id')->diffForHumans();
        }
    }

    public function render(): View
    {
        $categories = BusinessCategory::orderBy('name')->get();

        return view('livewire.dashboard.profile-form', [
            'categories' => $categories,
        ]);
    }
}
