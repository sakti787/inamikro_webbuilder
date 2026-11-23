<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Katalog Produk</h1>
            <p class="text-sm text-slate-500 mt-1">Kelola daftar produk, varian harga, ketersediaan, dan gambar katalog toko Anda.</p>
        </div>
        <div>
            <a 
                href="{{ route('dashboard.products.create') }}" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm shadow-blue-500/20 transition-all focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Produk
            </a>
        </div>
    </div>

    <!-- Flash Notification -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Filters & Search Toolbar -->
    <div class="mb-6 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between">
        <div class="flex flex-1 flex-col sm:flex-row gap-3">
            <!-- Search -->
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    wire:model.live.debounce.300ms="search" 
                    type="text" 
                    placeholder="Cari nama produk..." 
                    class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                >
            </div>

            <!-- Category Filter -->
            <div class="w-full sm:w-56">
                <select 
                    wire:model.live="categoryFilter" 
                    class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-slate-700"
                >
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($search !== '' || $categoryFilter !== '')
            <button 
                type="button" 
                wire:click="$set('search', ''); $set('categoryFilter', '')" 
                class="text-xs font-semibold text-blue-600 hover:text-blue-800 self-center"
            >
                Reset Filter
            </button>
        @endif
    </div>

    <!-- Products Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50/75 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Produk</th>
                        <th class="py-3.5 px-4">Kategori</th>
                        <th class="py-3.5 px-4">Harga</th>
                        <th class="py-3.5 px-4 text-center">Stok</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <!-- Product thumbnail & name -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-200">
                                        @if ($product->primaryImage)
                                            <img 
                                                src="{{ asset('storage/' . $product->primaryImage->image) }}" 
                                                alt="{{ $product->name }}" 
                                                class="w-full h-full object-cover"
                                            >
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-slate-400">
                                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-slate-800">{{ $product->name }}</span>
                                            @if ($product->label)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold {{ $product->label === 'Promo' ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                                    {{ $product->label }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-slate-400 mt-0.5">Slug: {{ $product->slug }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="py-3.5 px-4 text-slate-600">
                                @if ($product->category)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                        {{ $product->category->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">Tanpa Kategori</span>
                                @endif
                            </td>

                            <!-- Price -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if ($product->discount_price && $product->discount_price < $product->price)
                                    <div class="flex flex-col">
                                        <span class="font-bold text-red-600">Rp {{ number_format($product->discount_price, 0, ',', '.') }}</span>
                                        <span class="text-xs text-slate-400 line-through">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                                    </div>
                                @else
                                    <span class="font-semibold text-slate-800">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                                @endif
                            </td>

                            <!-- Stock -->
                            <td class="py-3.5 px-4 text-center">
                                @if ($product->stock !== null)
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $product->stock > 5 ? 'bg-slate-100 text-slate-700' : ($product->stock > 0 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-600') }}">
                                        {{ $product->stock }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">Selalu Ada</span>
                                @endif
                            </td>

                            <!-- Availability status -->
                            <td class="py-3.5 px-4 text-center">
                                <button 
                                    type="button" 
                                    wire:click="toggleAvailability({{ $product->id }})" 
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition cursor-pointer {{ $product->is_available ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' }}"
                                    title="Klik untuk mengubah status ketersediaan"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full {{ $product->is_available ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    <span>{{ $product->is_available ? 'Tersedia' : 'Nonaktif' }}</span>
                                </button>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a 
                                        href="{{ route('dashboard.products.edit', $product) }}" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition" 
                                        title="Ubah Produk"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>
                                    <button 
                                        type="button" 
                                        wire:click="deleteProduct({{ $product->id }})" 
                                        wire:confirm="Apakah Anda yakin ingin menghapus produk '{{ $product->name }}' beserta seluruh gambarnya?" 
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-red-600 hover:bg-red-50 transition" 
                                        title="Hapus Produk"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-semibold text-slate-700">Belum ada produk</h3>
                                    <p class="text-xs text-slate-500 mt-1 max-w-sm">Mulai tambahkan produk jualan Anda ke katalog agar pengunjung website dapat melihat dan memesannya.</p>
                                    <a 
                                        href="{{ route('dashboard.products.create') }}" 
                                        class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-semibold hover:bg-blue-700 transition"
                                    >
                                        + Tambah Produk Sekarang
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
