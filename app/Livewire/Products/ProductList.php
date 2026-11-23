<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class ProductList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $categoryFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle product availability status.
     */
    public function toggleAvailability(Product|int $product): void
    {
        if (is_int($product)) {
            $product = Product::findOrFail($product);
        }

        $this->authorize('update', $product);

        $product->update([
            'is_available' => ! $product->is_available,
        ]);

        session()->flash('success', 'Status ketersediaan produk berhasil diperbarui.');
    }

    /**
     * Delete a product and its associated images.
     */
    public function deleteProduct(Product|int $product): void
    {
        if (is_int($product)) {
            $product = Product::findOrFail($product);
        }

        $this->authorize('delete', $product);

        foreach ($product->images as $image) {
            if ($image->image && Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }
            $image->delete();
        }

        $product->delete();

        session()->flash('success', 'Produk berhasil dihapus.');
    }

    public function render(): View
    {
        $user = Auth::user();
        $business = $user?->business;

        $categories = $business
            ? ProductCategory::where('business_id', $business->id)->orderBy('name')->get()
            : collect();

        $products = $business
            ? Product::where('business_id', $business->id)
                ->when($this->search !== '', function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%');
                })
                ->when($this->categoryFilter !== '', function ($query) {
                    $query->where('category_id', $this->categoryFilter);
                })
                ->with(['category', 'primaryImage'])
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->paginate(15)
            : Product::whereRaw('1 = 0')->paginate(15);

        return view('livewire.products.product-list', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
