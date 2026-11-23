<div>
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Banner Promosi Toko</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola slide banner utama di halaman beranda website toko Anda untuk menarik perhatian pengunjung.</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Form: Tambah Banner Baru (4 cols on lg) -->
        <div class="lg:col-span-5 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-5">Tambah Banner Baru</h2>

            <form wire:submit="save" class="space-y-4">
                <!-- Banner Image Upload -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Gambar Banner <span class="text-red-500">*</span>
                    </label>

                    @if ($image)
                        <div class="mb-3 relative rounded-xl overflow-hidden border border-slate-200 bg-slate-100 aspect-[16/9]">
                            <img src="{{ $image->temporaryUrl() }}" alt="Preview Banner" class="w-full h-full object-cover">
                            <button 
                                type="button" 
                                wire:click="$set('image', null)" 
                                class="absolute top-2 right-2 p-1 rounded-full bg-slate-900/70 text-white hover:bg-red-600 transition"
                                title="Batal pilihan gambar"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @else
                        <div class="flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:border-blue-400 transition bg-slate-50/50">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-10 w-10 text-slate-400" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <div class="flex text-sm text-slate-600 justify-center">
                                    <label for="banner-image" class="relative cursor-pointer bg-white rounded-md font-semibold text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                        <span>Pilih Gambar</span>
                                        <input id="banner-image" wire:model="image" type="file" accept="image/*" class="sr-only">
                                    </label>
                                </div>
                                <p class="text-xs text-slate-500">Rekomendasi rasio 16:9 atau 21:9, maks 2MB</p>
                            </div>
                        </div>
                    @endif

                    @error('image')
                        <p class="text-xs text-red-600 mt-1.5 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Judul -->
                <div>
                    <label for="title" class="block text-sm font-semibold text-slate-700 mb-1">
                        Judul Banner (Opsional)
                    </label>
                    <input 
                        wire:model="title" 
                        type="text" 
                        id="title" 
                        placeholder="Contoh: Promo Spesial Ramadhan" 
                        class="w-full px-3.5 py-2 text-sm bg-slate-50 border @error('title') border-red-300 @else border-slate-200 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    >
                    @error('title')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Subjudul -->
                <div>
                    <label for="subtitle" class="block text-sm font-semibold text-slate-700 mb-1">
                        Subjudul (Opsional)
                    </label>
                    <input 
                        wire:model="subtitle" 
                        type="text" 
                        id="subtitle" 
                        placeholder="Contoh: Diskon hingga 50% untuk semua menu favorit" 
                        class="w-full px-3.5 py-2 text-sm bg-slate-50 border @error('subtitle') border-red-300 @else border-slate-200 @enderror rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                    >
                    @error('subtitle')
                        <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tombol CTA: Teks & Link -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="button_text" class="block text-sm font-semibold text-slate-700 mb-1">
                            Teks Tombol
                        </label>
                        <input 
                            wire:model="button_text" 
                            type="text" 
                            id="button_text" 
                            placeholder="Lihat Promo" 
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                        >
                    </div>
                    <div>
                        <label for="button_url" class="block text-sm font-semibold text-slate-700 mb-1">
                            Tautan Tombol
                        </label>
                        <input 
                            wire:model="button_url" 
                            type="text" 
                            id="button_url" 
                            placeholder="https://..." 
                            class="w-full px-3.5 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
                        >
                    </div>
                </div>

                <!-- Status Aktif Toggle -->
                <div class="pt-2 flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                    <span class="text-sm font-medium text-slate-700">Tampilkan Banner (Aktif)</span>
                </div>

                <!-- Submit Button -->
                <div class="pt-3">
                    <button 
                        type="submit" 
                        wire:loading.attr="disabled"
                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm shadow-blue-500/20 transition focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                        <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>+ Tambahkan Banner</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- List: Banner Tersimpan (7 cols on lg) -->
        <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 mb-5">
                Daftar Banner ({{ $banners->count() }})
            </h2>

            <div class="space-y-4">
                @forelse ($banners as $banner)
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-slate-50 transition flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                        <!-- Banner Image & Meta -->
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-24 h-16 rounded-lg overflow-hidden bg-slate-200 flex-shrink-0 border border-slate-300">
                                <img 
                                    src="{{ asset('storage/' . $banner->image) }}" 
                                    alt="{{ $banner->title ?? 'Banner' }}" 
                                    class="w-full h-full object-cover"
                                >
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-semibold text-slate-800 truncate">
                                    {{ $banner->title ?: 'Tanpa Judul' }}
                                </h3>
                                @if ($banner->subtitle)
                                    <p class="text-xs text-slate-500 truncate mt-0.5">{{ $banner->subtitle }}</p>
                                @endif
                                @if ($banner->button_text)
                                    <span class="inline-flex items-center gap-1 mt-1 text-[11px] font-medium text-blue-600 bg-blue-50 px-2 py-0.5 rounded-md">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        {{ $banner->button_text }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Actions: Toggle & Delete -->
                        <div class="flex items-center gap-3 self-end sm:self-center">
                            <!-- Toggle Active Button -->
                            <button 
                                type="button" 
                                wire:click="toggleBanner({{ $banner->id }})" 
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold cursor-pointer transition {{ $banner->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-slate-200 text-slate-600 border border-slate-300 hover:bg-slate-300' }}"
                                title="Klik untuk mengubah status aktif"
                            >
                                <span class="w-1.5 h-1.5 rounded-full {{ $banner->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                <span>{{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </button>

                            <!-- Delete Banner Button -->
                            <button 
                                type="button" 
                                wire:click="deleteBanner({{ $banner->id }})" 
                                wire:confirm="Yakin ingin menghapus banner ini?" 
                                class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition"
                                title="Hapus Banner"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-700">Belum ada banner promosi</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-xs mx-auto">Gunakan formulir di sebelah kiri untuk mengunggah banner promosi pertama Anda.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
