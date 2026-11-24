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

    /**
     * Validation rules for business profile.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:business_categories,id'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['required', 'string', 'max:30'],
            'operating_hours' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
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
            'name.required' => 'Nama usaha wajib diisi.',
            'whatsapp.required' => 'Nomor WhatsApp bisnis wajib diisi.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'logo.image' => 'Logo harus berupa berkas gambar.',
            'logo.max' => 'Ukuran logo maksimal 2MB.',
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

            $this->updateLastEditedTimestamps($business);
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
