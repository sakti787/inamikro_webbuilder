<div class="space-y-6">
    <!-- Top Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl p-6 sm:p-8 text-white shadow-md relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white/20 text-white backdrop-blur-sm mb-3">
                Ringkasan Usaha Anda
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Selamat Datang, {{ auth()->user()?->name }}!
            </h1>
            <p class="mt-2 text-sm sm:text-base text-blue-100 leading-relaxed">
                Kelola katalog produk, pantau trafik pengunjung, dan sesuaikan tampilan website usaha <strong class="text-white">{{ $business?->name }}</strong> dengan mudah.
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <a 
                    href="{{ Route::has('dashboard.builder') ? route('dashboard.builder') : url('/dashboard/builder') }}" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-blue-700 text-sm font-bold shadow-xs hover:bg-blue-50 transition"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <span>Buka Builder Studio</span>
                </a>

                @if ($website && $website->slug)
                    <a 
                        href="{{ url('/s/' . $website->slug) }}" 
                        target="_blank" 
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-800/60 hover:bg-blue-800 text-white text-sm font-semibold border border-white/20 transition"
                    >
                        <span>Lihat Website</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                @endif
            </div>
        </div>

        <!-- Decorative background shapes -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Stat: Active Products -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Produk Aktif</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900">{{ $totalProducts }}</span>
                <span class="text-xs text-slate-500 font-medium">tersedia</span>
            </div>
            <div class="mt-3 text-xs text-slate-500">
                <a href="{{ url('/dashboard/products') }}" class="text-blue-600 font-semibold hover:underline">Kelola katalog &rarr;</a>
            </div>
        </div>

        <!-- Stat: Website Views -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pengunjung Website</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900">{{ number_format($totalViews) }}</span>
                <span class="text-xs text-slate-500 font-medium">kali dilihat</span>
            </div>
            <p class="mt-3 text-xs text-slate-500">Statistik trafik landing page</p>
        </div>

        <!-- Stat: WhatsApp Clicks -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Klik WhatsApp</span>
                <span class="p-2 rounded-xl bg-green-50 text-green-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </span>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-slate-900">{{ number_format($totalWaClicks) }}</span>
                <span class="text-xs text-slate-500 font-medium">calon pelanggan</span>
            </div>
            <p class="mt-3 text-xs text-slate-500">Peluang pesanan via WhatsApp</p>
        </div>

        <!-- Stat: Website Status -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Status Website</span>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </span>
            </div>
            <div class="mt-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ ($website?->status ?? 'draft') === 'published' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' }}">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ ($website?->status ?? 'draft') === 'published' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    {{ $website?->status ?? 'draft' }}
                </span>
            </div>
            <p class="mt-3 text-xs text-slate-500 truncate">
                Tema: <span class="font-medium text-slate-700">{{ $website?->template?->name ?? 'Minimalist Store' }}</span>
            </p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
        <h2 class="text-base font-bold text-slate-900 tracking-tight mb-4">
            Aksi Cepat
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a 
                href="{{ url('/dashboard/products') }}" 
                class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-blue-500 hover:bg-blue-50/50 transition group"
            >
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center group-hover:scale-105 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-blue-600 transition">Katalog Produk</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Tambah atau kelola katalog produk</p>
                </div>
            </a>

            <a 
                href="{{ Route::has('dashboard.builder') ? route('dashboard.builder') : url('/dashboard/builder') }}" 
                class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-purple-500 hover:bg-purple-50/50 transition group"
            >
                <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center group-hover:scale-105 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-purple-600 transition">Builder Studio</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Sesuaikan warna, font, & layout</p>
                </div>
            </a>

            <a 
                href="{{ route('dashboard.profile') }}" 
                class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition group"
            >
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-105 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 group-hover:text-emerald-600 transition">Profil Usaha</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Ubah nama, WhatsApp, & jam buka</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Business Info Overview -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-slate-900 tracking-tight">
                Informasi Bisnis Anda
            </h2>
            <a href="{{ route('dashboard.profile') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                Ubah Profil &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-xs font-medium text-slate-500">Nama Usaha:</span>
                <p class="font-semibold text-slate-900 mt-0.5">{{ $business?->name }}</p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-xs font-medium text-slate-500">Kontak WhatsApp:</span>
                <p class="font-semibold text-slate-900 mt-0.5">{{ $business?->whatsapp }}</p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-xs font-medium text-slate-500">Nomor Telepon:</span>
                <p class="font-semibold text-slate-900 mt-0.5">{{ $business?->phone ?? '-' }}</p>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-xs font-medium text-slate-500">Alamat Usaha:</span>
                <p class="font-semibold text-slate-900 mt-0.5">{{ $business?->address ?? '-' }}</p>
            </div>
        </div>
    </div>
</div>
