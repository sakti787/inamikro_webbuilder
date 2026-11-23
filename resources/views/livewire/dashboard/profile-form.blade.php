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
                        <div class="w-24 h-24 rounded-2xl overflow-hidden ring-1 ring-slate-200 shadow-xs bg-slate-50">
                            <img src="{{ asset('storage/' . $existingLogo) }}" alt="{{ $name }}" class="w-full h-full object-cover">
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
                    placeholder="Jelaskan mengenai produk, keunikan, atau profil usaha Anda..."
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
                        placeholder="Contoh: 021-1234567 atau 081234567890" 
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
                        placeholder="Alamat lengkap lokasi toko/workshop (misal: Jl. Sudirman No. 10, Jakarta Pusat)"
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
                        placeholder="Contoh: Senin - Sabtu: 08:00 - 21:00 (Minggu Libur)" 
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
