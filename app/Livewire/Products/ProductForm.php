<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
class ProductForm extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $name = '';

    public ?int $category_id = null;

    public $price = '';

    public $discount_price = null;

    public ?string $description = '';

    public $stock = null;

    public ?string $label = '';

    public bool $is_available = true;

    /**
     * @var array<int, string>|string
     */
    public $variant_options = [];

    /**
     * @var array<int, TemporaryUploadedFile>
     */
    public array $images = [];

    /**
     * @var Collection<int, ProductImage>
     */
    public $existingImages;

    public bool $isEditing = false;

    /**
     * Validation rules.
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:product_categories,id',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'description' => 'nullable|string',
            'stock' => 'nullable|integer|min:0',
            'label' => 'nullable|string|max:50',
            'is_available' => 'boolean',
            'images.*' => 'nullable|image|max:2048',
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
            'name.required' => 'Nama produk wajib diisi.',
            'name.max' => 'Nama produk maksimal 255 karakter.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'price.required' => 'Harga produk wajib diisi.',
            'price.numeric' => 'Harga produk harus berupa angka.',
            'price.min' => 'Harga produk minimal Rp 0.',
            'discount_price.numeric' => 'Harga promo harus berupa angka.',
            'discount_price.min' => 'Harga promo minimal Rp 0.',
            'discount_price.lt' => 'Harga diskon harus lebih kecil dari harga normal.',
            'stock.integer' => 'Stok produk harus berupa angka bulat.',
            'stock.min' => 'Stok produk minimal 0.',
            'label.max' => 'Label maksimal 50 karakter.',
            'images.*.image' => 'Berkas gambar harus berupa gambar.',
            'images.*.max' => 'Ukuran gambar maksimal 2MB.',
        ];
    }

    public function mount(?Product $product = null): void
    {
        $this->existingImages = collect();

        if ($product && $product->exists) {
            $this->authorize('update', $product);

            $this->product = $product;
            $this->isEditing = true;
            $this->name = $product->name;
            $this->category_id = $product->category_id;
            $this->price = (string) $product->price;
            $this->discount_price = $product->discount_price !== null ? (string) $product->discount_price : null;
            $this->description = $product->description ?? '';
            $this->stock = $product->stock ?? 0;
            $this->label = $product->label ?? '';
            $this->is_available = (bool) $product->is_available;
            $this->variant_options = $product->variant_options ?? [];
            $this->loadExistingImages();
        }
    }

    /**
     * Load existing images for the product.
     */
    private function loadExistingImages(): void
    {
        if ($this->product && $this->product->exists) {
            $this->existingImages = $this->product->images()->orderBy('sort_order')->get();
        }
    }

    /**
     * Delete an existing image from disk and database.
     */
    public function deleteImage(int $imageId): void
    {
        if (! $this->product) {
            return;
        }

        $this->authorize('update', $this->product);

        $image = $this->product->images()->where('id', $imageId)->firstOrFail();

        if ($image->image && Storage::disk('public')->exists($image->image)) {
            Storage::disk('public')->delete($image->image);
        }

        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $nextPrimary = $this->product->images()->orderBy('sort_order')->first();
            if ($nextPrimary) {
                $nextPrimary->update(['is_primary' => true]);
            }
        }

        $this->loadExistingImages();
        session()->flash('image_status', 'Foto berhasil dihapus.');
    }

    /**
     * Set an existing image as primary.
     */
    public function setPrimaryImage(int $imageId): void
    {
        if (! $this->product) {
            return;
        }

        $this->authorize('update', $this->product);

        $this->product->images()->update(['is_primary' => false]);
        $image = $this->product->images()->where('id', $imageId)->firstOrFail();
        $image->update(['is_primary' => true]);

        $this->loadExistingImages();
        session()->flash('image_status', 'Foto utama berhasil diubah.');
    }

    /**
     * Remove newly uploaded image from queue before saving.
     */
    public function removeNewImage(int $index): void
    {
        if (isset($this->images[$index])) {
            unset($this->images[$index]);
            $this->images = array_values($this->images);
        }
    }

    /**
     * Save product (create or update).
     */
    public function save()
    {
        // Normalize empty discount price to null
        if ($this->discount_price === '' || $this->discount_price === null) {
            $this->discount_price = null;
        }

        $this->validate();

        $user = Auth::user();
        $business = $user?->business;

        if (! $business) {
            abort(403, 'Anda belum memiliki profil usaha.');
        }

        if ($this->isEditing && $this->product) {
            $this->authorize('update', $this->product);
        }

        // Generate unique slug within the business
        $baseSlug = Str::slug($this->name);
        if ($baseSlug === '') {
            $baseSlug = 'produk';
        }

        $slug = $baseSlug;
        $counter = 1;
        while ($this->slugExists($business->id, $slug)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        // Parse variant options
        $variants = [];
        if (is_array($this->variant_options)) {
            $variants = array_values(array_filter(array_map('trim', $this->variant_options), fn ($v) => $v !== ''));
        } elseif (is_string($this->variant_options)) {
            $variants = array_values(array_filter(array_map('trim', explode(',', $this->variant_options)), fn ($v) => $v !== ''));
        }

        $data = [
            'category_id' => $this->category_id ?: null,
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description ?: null,
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price !== null ? (float) $this->discount_price : null,
            'stock' => ($this->stock !== '' && $this->stock !== null) ? (int) $this->stock : 0,
            'label' => $this->label ?: null,
            'variant_options' => ! empty($variants) ? $variants : null,
            'is_available' => (bool) $this->is_available,
        ];

        if ($this->isEditing && $this->product) {
            $this->product->update($data);
            $product = $this->product;
        } else {
            $data['business_id'] = $business->id;
            $data['sort_order'] = ((int) Product::where('business_id', $business->id)->max('sort_order')) + 1;
            $product = Product::create($data);
        }

        // Handle uploaded images
        if (! empty($this->images)) {
            $hasPrimary = $product->images()->where('is_primary', true)->exists();
            $maxSort = (int) $product->images()->max('sort_order');

            foreach ($this->images as $index => $uploadedFile) {
                $path = $uploadedFile->store('products', 'public');
                $isPrimary = (! $hasPrimary && $index === 0);

                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $path,
                    'is_primary' => $isPrimary,
                    'sort_order' => $maxSort + $index + 1,
                ]);

                if ($isPrimary) {
                    $hasPrimary = true;
                }
            }

            $this->images = [];
        }

        session()->flash('success', 'Produk berhasil disimpan.');

        return redirect()->route('dashboard.products');
    }

    /**
     * Check if slug already exists in this business (excluding current product).
     */
    private function slugExists(int $businessId, string $slug): bool
    {
        $query = Product::where('business_id', $businessId)->where('slug', $slug);

        if ($this->product && $this->product->exists) {
            $query->where('id', '!=', $this->product->id);
        }

        return $query->exists();
    }

    public function render(): View
    {
        $business = Auth::user()?->business;
        $categories = $business
            ? ProductCategory::where('business_id', $business->id)->orderBy('name')->get()
            : collect();

        return view('livewire.products.product-form', [
            'categories' => $categories,
        ]);
    }
}
