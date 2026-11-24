<div class="max-w-4xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Profil Usaha
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Kelola informasi bisnis, kontak WhatsApp, jam buka, dan logo brand UMKM Anda.
            </p>
        </div>
        <div>
            <a 
                href="{{ route('dashboard') }}" 
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 transition shadow-xs"
            >
                &larr; Kembali ke Dashboard
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl bg-emerald-50 p-4 border border-emerald-200 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-3">
                <span class="flex-shrink-0 text-emerald-600">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                </span>
                <p class="text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Status Banner / Last Edited Card -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-start gap-3.5">
                <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <h2 class="text-sm font-bold text-slate-900">
                            Data Profil Tersimpan
                        </h2>
                        @if ($lastUpdatedFormatted)
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full w-fit">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Terakhir diperbarui: {{ $lastUpdatedFormatted }}
                                <span class="text-slate-400 font-normal">({{ $lastUpdatedHuman }})</span>
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        @if ($lastUpdatedFormatted)
                            Seluruh data di bawah ini adalah data yang saat ini aktif tampil pada website toko online Anda.
                        @else
                            Profil toko baru dan belum pernah diperbarui.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Logo Brand Section -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
            <h2 class="text-base font-bold text-slate-900 mb-1">
                Logo Usaha
            </h2>
            <p class="text-xs text-slate-500 mb-6">
                Logo akan ditampilkan pada header website toko online Anda dan kartu profil.
            </p>

            <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                <!-- Preview Avatar -->
                <div class="flex-shrink-0">
                    @if ($logo && method_exists($logo, 'isPreviewable') && $logo->isPreviewable())
                        <div class="relative w-24 h-24 rounded-2xl overflow-hidden ring-2 ring-blue-500 shadow-md">
                            <img src="{{ $logo->temporaryUrl() }}" alt="Preview Logo" class="w-full h-full object-cover">
                            <span class="absolute bottom-0 inset-x-0 bg-blue-600/90 text-white text-[10px] font-bold py-0.5 text-center">Baru</span>
                        </div>
                    @elseif ($existingLogo)
                        <div class="flex flex-col items-center space-y-1.5">
                            <div class="w-24 h-24 rounded-2xl overflow-hidden ring-1 ring-slate-200 shadow-xs bg-slate-50">
                                <img src="{{ asset('storage/' . $existingLogo) }}" alt="{{ $name }}" class="w-full h-full object-cover">
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Logo Aktif Saat Ini
                            </span>
                        </div>
                    @else
                        <div class="w-24 h-24 rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-200/60 flex items-center justify-center font-bold text-2xl shadow-xs">
                            {{ strtoupper(substr($name ?: 'U', 0, 2)) }}
                        </div>
                    @endif
                </div>

                <!-- File Input & Hints -->
                <div class="flex-1 space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">
                        Unggah Logo Baru
                    </label>
                    <input 
                        type="file" 
                        wire:model="logo" 
                        accept="image/png, image/jpeg, image/jpg, image/webp"
                        class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition cursor-pointer"
                    >
                    <p class="text-xs text-slate-400">
                        Format didukung: PNG, JPG, JPEG, WebP. Maksimal 2MB (2048 KB).
                    </p>
                    <div wire:loading wire:target="logo" class="text-xs text-blue-600 font-medium">
                        Mengunggah pratinjau gambar...
                    </div>
                    @error('logo')
                        <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Basic Business Info -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-5">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                Informasi Dasar Usaha
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Business Name -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nama Usaha <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        wire:model="name" 
                        placeholder="Contoh: Kopi Nusantara Jaya" 
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        required
                    >
                    @error('name')
                        <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Business Category Dropdown -->
                <div>
                    <label for="category_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Kategori Usaha
                    </label>
                    <select 
                        id="category_id" 
                        wire:model="category_id" 
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white"
                    >
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Deskripsi Singkat Usaha
                </label>
                <textarea 
                    id="description" 
                    wire:model="description" 
                    rows="3" 
                    placeholder="Belum diisi (Jelaskan mengenai produk, keunikan, atau profil usaha Anda...)"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                ></textarea>
                @error('description')
                    <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Contact & WhatsApp Details -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-5">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                Kontak & Komunikasi Pelanggan
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- WhatsApp -->
                <div>
                    <label for="whatsapp" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nomor WhatsApp Bisnis <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="whatsapp" 
                            wire:model="whatsapp" 
                            placeholder="Contoh: 081234567890" 
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required
                        >
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        Nomor tujuan tombol pemesanan WhatsApp di landing page website toko.
                    </p>
                    @error('whatsapp')
                        <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Telephone / Phone -->
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nomor Telepon Toko (Opsional)
                    </label>
                    <input 
                        type="text" 
                        id="phone" 
                        wire:model="phone" 
                        placeholder="Belum diisi (Contoh: 021-1234567 atau 081234567890)" 
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                    @error('phone')
                        <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Location & Operating Hours -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-5">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">
                Lokasi & Jam Operasional
            </h2>

            <div class="space-y-5">
                <!-- Address -->
                <div>
                    <label for="address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Alamat Usaha / Toko Fisik
                    </label>
                    <textarea 
                        id="address" 
                        wire:model="address" 
                        rows="2" 
                        placeholder="Belum diisi (Alamat lengkap lokasi toko/workshop Anda)"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    ></textarea>
                    @error('address')
                        <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Operating Hours -->
                <div>
                    <label for="operating_hours" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Jam Operasional
                    </label>
                    <input 
                        type="text" 
                        id="operating_hours" 
                        wire:model="operating_hours" 
                        placeholder="Belum diisi (Contoh: Senin - Sabtu: 08:00 - 21:00)" 
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    >
                    <p class="text-xs text-slate-400 mt-1">
                        Informasi waktu buka toko yang akan ditampilkan di footer website.
                    </p>
                    @error('operating_hours')
                        <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Keunggulan Layanan (Features / Value Proposition Cards) -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 {{ $features_enabled ? 'border-b border-slate-100 pb-4' : '' }}">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-xl bg-amber-50 text-amber-600 flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900">
                            Keunggulan Layanan (Mengapa Memilih Kami)
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Tampilkan 3 poin nilai tambah atau keunggulan toko Anda untuk meyakinkan calon pembeli di landing page.
                        </p>
                    </div>
                </div>

                <div class="flex-shrink-0">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="features_enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        <span class="ml-3 text-sm font-semibold text-slate-900">Aktifkan Bagian Keunggulan di Landing Page</span>
                    </label>
                </div>
            </div>

            @if ($features_enabled)
                <div class="space-y-5 pt-1">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- Badge / Label Kecil -->
                        <div>
                            <label for="features_badge" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Label Badge (Kecil)
                            </label>
                            <input 
                                type="text" 
                                id="features_badge" 
                                wire:model="features_badge" 
                                value="{{ $features_badge }}"
                                placeholder="Contoh: Mengapa Memilih Kami" 
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            >
                            @error('features_badge')
                                <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Title -->
                        <div class="md:col-span-2">
                            <label for="features_title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Judul Utama <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="features_title" 
                                wire:model="features_title" 
                                value="{{ $features_title }}"
                                placeholder="Contoh: Keunggulan Layanan Kami" 
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                required
                            >
                            @error('features_title')
                                <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Subtitle -->
                    <div>
                        <label for="features_subtitle" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Sub-judul / Penjelasan Singkat
                        </label>
                        <input 
                            type="text" 
                            id="features_subtitle" 
                            wire:model="features_subtitle" 
                            value="{{ $features_subtitle }}"
                            placeholder="Contoh: Komitmen kami dalam memberikan standar mutu dan kepuasan terbaik bagi Anda." 
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        >
                        @error('features_subtitle')
                            <p class="text-xs text-red-600 font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 3 Cards Grid -->
                    <div class="pt-3 border-t border-slate-100">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                            3 Kartu Nilai Tambah / Keunggulan
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @for ($i = 0; $i < 3; $i++)
                                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                                    <div class="flex items-center gap-2 text-xs font-bold text-slate-700">
                                        <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-[11px] font-bold">{{ $i + 1 }}</span>
                                        Keunggulan #{{ $i + 1 }}
                                    </div>
                                    <div>
                                        <label for="features_item_{{ $i }}_title" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                            Judul Poin <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            id="features_item_{{ $i }}_title"
                                            wire:model="features_items.{{ $i }}.title" 
                                            value="{{ $features_items[$i]['title'] ?? '' }}"
                                            placeholder="Contoh: {{ $i === 0 ? 'Bahan Pilihan' : ($i === 1 ? 'Pengiriman Cepat' : 'Garansi Mutu') }}" 
                                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white"
                                            required
                                        >
                                        @error('features_items.' . $i . '.title')
                                            <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="features_item_{{ $i }}_description" class="block text-[11px] font-semibold text-slate-600 mb-1">
                                            Deskripsi Ringkas
                                        </label>
                                        <textarea 
                                            id="features_item_{{ $i }}_description"
                                            wire:model="features_items.{{ $i }}.description" 
                                            rows="2"
                                            placeholder="Deskripsi singkat keunggulan..."
                                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white"
                                        >{{ $features_items[$i]['description'] ?? '' }}</textarea>
                                        @error('features_items.' . $i . '.description')
                                            <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            @else
                <p class="text-xs text-slate-400 italic">
                    Bagian keunggulan saat ini dinonaktifkan. Aktifkan sakelar toggle di atas untuk menampilkan 3 kartu keunggulan di landing page.
                </p>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-3 pt-2">

            <button 
                type="submit" 
                wire:loading.attr="disabled"
                wire:target="save, logo"
                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-bold shadow-md shadow-blue-500/20 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="save">Simpan Perbarui Profil</span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Menyimpan...
                </span>
            </button>
        </div>
    </form>
</div>
