<div>
    <!-- Header with Back Button -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a 
                href="{{ route('dashboard.products') }}" 
                class="p-2 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-800 hover:bg-slate-50 transition shadow-sm"
                title="Kembali ke Daftar Produk"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    {{ $isEditing ? 'Ubah Produk: ' . $product->name : 'Tambah Produk Baru' }}
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ $isEditing ? 'Perbarui informasi rincian, harga, dan foto produk ini.' : 'Isi formulir di bawah ini untuk menambahkan produk baru ke katalog toko Anda.' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a 
                href="{{ route('dashboard.products') }}" 
                class="px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-semibold rounded-xl shadow-sm transition"
            >
                Batal
            </a>
            <button 
                type="button" 
                wire:click="save" 
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm shadow-blue-500/20 transition focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
            >
                <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Simpan Produk</span>
            </button>
        </div>
    </div>

    <!-- Notifications -->
    @if (session()->has('image_status'))
        <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-800 flex items-center justify-between text-sm">
            <span>{{ session('image_status') }}</span>
            <button type="button" @click="$el.parentElement.remove()" class="text-blue-500 hover:text-blue-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- 1. Informasi Dasar Produk -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-5">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Informasi Utama</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Nama Produk -->
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Nama Produk <span class="text-red-500">*</span>
                    </label>
                    <input 
                        wire:model="name" 
                        type="text" 
                        id="name" 
                        placeholder="Contoh: Kopi Susu Gula Aren, Baju Batik Tulis" 
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border @error('name') border-red-300 focus:border-red-500 @else border-slate-200 focus:border-blue-500 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition"
                    >
                    @error('name')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kategori Produk -->
                <div>
                    <label for="category_id" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Kategori Produk
                    </label>
                    <select 
                        wire:model="category_id" 
                        id="category_id" 
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border @error('category_id') border-red-300 @else border-slate-200 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition text-slate-700"
                    >
                        <option value="">Pilih Kategori (Opsional)</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Label / Badge -->
                <div>
                    <label for="label" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Label / Badge (Opsional)
                    </label>
                    <input 
                        wire:model="label" 
                        type="text" 
                        id="label" 
                        placeholder="Contoh: Promo, Best Seller, Baru, Favorit" 
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border @error('label') border-red-300 @else border-slate-200 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    >
                    @error('label')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Deskripsi Produk -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Deskripsi Produk
                    </label>
                    <textarea 
                        wire:model="description" 
                        id="description" 
                        rows="3" 
                        placeholder="Jelaskan keunggulan produk, bahan, cara pemakaian, atau rasa..." 
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border @error('description') border-red-300 @else border-slate-200 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    ></textarea>
                    @error('description')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- 2. Harga & Inventaris -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-5">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Harga & Ketersediaan</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Harga Normal -->
                <div>
                    <label for="price" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Harga Normal (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-slate-400">Rp</span>
                        <input 
                            wire:model="price" 
                            type="number" 
                            step="any"
                            id="price" 
                            placeholder="0" 
                            class="w-full pl-11 pr-3.5 py-2.5 text-sm bg-slate-50 border @error('price') border-red-300 focus:border-red-500 @else border-slate-200 focus:border-blue-500 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition font-medium"
                        >
                    </div>
                    @error('price')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Harga Diskon -->
                <div>
                    <label for="discount_price" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Harga Promo / Coret (Rp)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-sm font-bold text-slate-400">Rp</span>
                        <input 
                            wire:model="discount_price" 
                            type="number" 
                            step="any"
                            id="discount_price" 
                            placeholder="Kosongkan jika tidak ada promo" 
                            class="w-full pl-11 pr-3.5 py-2.5 text-sm bg-slate-50 border @error('discount_price') border-red-300 focus:border-red-500 @else border-slate-200 focus:border-blue-500 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition font-medium"
                        >
                    </div>
                    @error('discount_price')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Stok -->
                <div>
                    <label for="stock" class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Jumlah Stok
                    </label>
                    <input 
                        wire:model="stock" 
                        type="number" 
                        id="stock" 
                        placeholder="Kosongkan jika tidak terbatas" 
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border @error('stock') border-red-300 @else border-slate-200 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    >
                    @error('stock')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Ketersediaan Toggle -->
            <div class="pt-2 flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        wire:model="is_available" 
                        class="sr-only peer"
                    >
                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-slate-800">Status Ketersediaan Produk</span>
                    <span class="text-xs text-slate-500">Jika dinonaktifkan, produk tidak dapat dipesan oleh pengunjung.</span>
                </div>
            </div>
        </div>

        <!-- 3. Opsi Varian -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Opsi Varian Produk</h2>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Pilihan Varian / Rasa / Ukuran
                </label>
                <p class="text-xs text-slate-500 mb-2">
                    Masukkan varian yang tersedia. Anda dapat memisahkan dengan tanda koma (contoh: <code>Panas, Dingin, Less Sugar</code> atau <code>S, M, L, XL</code>).
                </p>
                @php
                    $variantString = is_array($variant_options) ? implode(', ', $variant_options) : $variant_options;
                @endphp
                <input 
                    type="text" 
                    value="{{ $variantString }}"
                    @input="$wire.set('variant_options', $event.target.value.split(',').map(s => s.trim()).filter(Boolean))"
                    placeholder="Contoh: Merah, Biru, Hijau" 
                    class="w-full px-3.5 py-2.5 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                >
            </div>
        </div>

        <!-- 4. Foto & Galeri Produk -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm space-y-5">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3">Foto Produk</h2>

            <!-- Existing Images List (if editing) -->
            @if ($isEditing && $existingImages && $existingImages->isNotEmpty())
                <div>
                    <h3 class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-3">Foto Tersimpan</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-4">
                        @foreach ($existingImages as $img)
                            <div class="relative group rounded-xl overflow-hidden border {{ $img->is_primary ? 'ring-2 ring-blue-600 border-transparent' : 'border-slate-200' }} bg-slate-100">
                                <img 
                                    src="{{ asset('storage/' . $img->image) }}" 
                                    alt="Foto produk" 
                                    class="w-full h-32 object-cover"
                                >
                                @if ($img->is_primary)
                                    <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-blue-600 text-white text-[10px] font-bold shadow-sm">
                                        Foto Utama
                                    </span>
                                @endif

                                <!-- Actions Overlay -->
                                <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2 p-2">
                                    @if (! $img->is_primary)
                                        <button 
                                            type="button" 
                                            wire:click="setPrimaryImage({{ $img->id }})" 
                                            class="w-full py-1 text-[11px] font-semibold bg-white/90 hover:bg-white text-slate-800 rounded-lg shadow-sm"
                                        >
                                            Jadikan Utama
                                        </button>
                                    @endif
                                    <button 
                                        type="button" 
                                        wire:click="deleteImage({{ $img->id }})" 
                                        wire:confirm="Hapus foto ini?" 
                                        class="w-full py-1 text-[11px] font-semibold bg-red-600 hover:bg-red-700 text-white rounded-lg shadow-sm"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- New Image Upload Dropzone -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Unggah Foto Baru
                </label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:border-blue-400 transition bg-slate-50/50">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-slate-600 justify-center items-center">
                            <label for="images-upload" class="relative cursor-pointer bg-white rounded-md font-semibold text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                <span>Pilih satu atau beberapa foto</span>
                                <input 
                                    id="images-upload" 
                                    wire:model="images" 
                                    type="file" 
                                    multiple 
                                    accept="image/*" 
                                    class="sr-only"
                                >
                            </label>
                        </div>
                        <p class="text-xs text-slate-500">Mendukung format PNG, JPG, GIF hingga maksimal 2MB per gambar.</p>
                    </div>
                </div>

                @error('images.*')
                    <p class="text-xs text-red-600 mt-2 font-medium">{{ $message }}</p>
                @enderror

                <!-- Previews of newly selected images -->
                @if (! empty($images))
                    <div class="mt-4">
                        <h4 class="text-xs font-semibold text-slate-600 mb-2">Foto yang akan diunggah:</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-3">
                            @foreach ($images as $index => $img)
                                <div class="relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 group">
                                    <img 
                                        src="{{ $img->temporaryUrl() }}" 
                                        alt="Preview" 
                                        class="w-full h-24 object-cover"
                                    >
                                    <button 
                                        type="button" 
                                        wire:click="removeNewImage({{ $index }})" 
                                        class="absolute top-1 right-1 p-1 rounded-full bg-slate-900/70 text-white hover:bg-red-600 transition"
                                        title="Batal unggah foto ini"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Submit Footer -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a 
                href="{{ route('dashboard.products') }}" 
                class="px-5 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-semibold rounded-xl shadow-sm transition"
            >
                Batal
            </a>
            <button 
                type="submit" 
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm shadow-blue-500/20 transition focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
            >
                <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ $isEditing ? 'Simpan Perubahan' : 'Simpan Produk' }}</span>
            </button>
        </div>
    </form>
</div>
