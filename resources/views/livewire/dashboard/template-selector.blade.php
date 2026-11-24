<div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pilihan Tampilan</h1>
                    @if ($website->status === 'published')
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            Website Aktif / Terbit
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            Draft / Belum Terbit
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-slate-500 leading-relaxed">
                    Pilih gaya desain yang paling cocok untuk usaha Anda. Desain, warna, dan jenis font akan otomatis disesuaikan secara profesional.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                @if ($website->slug)
                    <a 
                        href="{{ route('public.website', $website->slug) }}" 
                        target="_blank" 
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 text-sm font-semibold shadow-xs transition"
                    >
                        <span>Lihat Toko Online</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                @endif

                <button 
                    type="button"
                    wire:click="togglePublish" 
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-xs transition {{ $website->status === 'published' ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}"
                >
                    <span wire:loading.remove wire:target="togglePublish">
                        {{ $website->status === 'published' ? 'Jadikan Draft' : 'Terbitkan Website' }}
                    </span>
                    <span wire:loading wire:target="togglePublish" class="inline-flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4 text-current" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Memproses...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Alerts: Publish Error -->
    @if (session()->has('publish_error'))
        <div x-data="{ show: true }" x-show="show" class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start justify-between gap-3 shadow-xs">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-rose-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <h4 class="font-bold text-sm text-rose-900">Belum Siap Diterbitkan</h4>
                    <p class="text-xs sm:text-sm text-rose-700 mt-0.5">{{ session('publish_error') }}</p>
                </div>
            </div>
            <button @click="show = false" class="text-rose-400 hover:text-rose-700 p-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Alerts: Success -->
    @if (session()->has('success'))
        <div x-data="{ show: true }" x-show="show" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start justify-between gap-3 shadow-xs">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <p class="text-xs sm:text-sm font-medium text-emerald-900">{{ session('success') }}</p>
            </div>
            <button @click="show = false" class="text-emerald-400 hover:text-emerald-700 p-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Template Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach ($templates as $tpl)
            @php
                $isActive = (int) $website->template_id === (int) $tpl->id;

                $industryRecommendations = [
                    'minimalist' => 'Cocok untuk: Fashion, Kriya, Aksesoris, & Lifestyle',
                    'food' => 'Cocok untuk: Kuliner, Makanan, Minuman, Kafe, Katering, & Frozen Food',
                    'modern' => 'Cocok untuk: Toko Kelontong, Bengkel, Laundry, Ritel, & Jasa Umum',
                ];

                $chip = $industryRecommendations[$tpl->slug] ?? 'Cocok untuk aneka usaha dan toko online';

                $colorThemes = [
                    'minimalist' => [
                        'banner' => 'from-slate-900 to-slate-800',
                        'accent' => '#0F172A',
                        'palette' => 'Monochrome & Slate',
                        'font' => 'Inter',
                    ],
                    'food' => [
                        'banner' => 'from-rose-600 to-red-700',
                        'accent' => '#DC2626',
                        'palette' => 'Warm Ruby & Crimson',
                        'font' => 'Poppins',
                    ],
                    'modern' => [
                        'banner' => 'from-blue-600 to-indigo-700',
                        'accent' => '#2563EB',
                        'palette' => 'Royal Blue & Modern',
                        'font' => 'Plus Jakarta Sans',
                    ],
                ];

                $meta = $colorThemes[$tpl->slug] ?? [
                    'banner' => 'from-slate-800 to-slate-700',
                    'accent' => '#0F172A',
                    'palette' => 'Standard',
                    'font' => 'Inter',
                ];
            @endphp

            <div class="bg-white rounded-2xl border transition-all duration-200 overflow-hidden flex flex-col justify-between {{ $isActive ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md' : 'border-slate-200 hover:border-slate-300 shadow-xs' }}">
                <div>
                    <!-- Visual Mockup Accent Header -->
                    <div class="h-36 bg-gradient-to-br {{ $meta['banner'] }} p-4 relative flex flex-col justify-between text-white overflow-hidden">
                        <div class="flex items-center justify-between z-10">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-white/20 backdrop-blur-xs text-white">
                                {{ $meta['palette'] }}
                            </span>
                            @if ($isActive)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-500 text-white shadow-xs">
                                    Sedang Digunakan
                                </span>
                            @endif
                        </div>

                        <!-- Mini Mockup Illustration -->
                        <div class="absolute inset-x-6 -bottom-3 bg-white/10 backdrop-blur-md rounded-t-xl p-3 border border-white/20 shadow-lg transform group-hover:translate-y-1 transition">
                            <div class="flex items-center gap-1.5 mb-2">
                                <span class="w-2 h-2 rounded-full bg-white/40"></span>
                                <span class="w-2 h-2 rounded-full bg-white/40"></span>
                                <span class="w-2 h-2 rounded-full bg-white/40"></span>
                                <div class="h-2 w-20 rounded bg-white/30 ml-2"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="h-8 rounded bg-white/20"></div>
                                <div class="h-8 rounded bg-white/20"></div>
                                <div class="h-8 rounded bg-white/20"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5 space-y-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">{{ $tpl->name }}</h3>
                            <p class="mt-1 text-xs text-slate-500 leading-relaxed">{{ $tpl->description }}</p>
                        </div>

                        <!-- Industry Recommendation Chip -->
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-xs font-semibold text-slate-700 leading-relaxed block">
                                {{ $chip }}
                            </span>
                        </div>

                        <!-- Curated Design Specs -->
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3.5 h-3.5 rounded-full border border-slate-300 shadow-2xs" style="background-color: {{ $meta['accent'] }};"></span>
                                <span class="font-medium text-slate-600">Preset Warna</span>
                            </div>
                            <div class="font-medium text-slate-600">
                                Font: <span class="font-bold text-slate-800">{{ $meta['font'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Footer Action -->
                <div class="p-5 pt-0">
                    @if ($isActive)
                        <div class="w-full py-2.5 px-4 rounded-xl bg-emerald-50 text-emerald-700 font-bold text-center text-sm border border-emerald-200 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Sedang Digunakan</span>
                        </div>
                    @else
                        <button 
                            type="button"
                            wire:click="selectTemplate({{ $tpl->id }})" 
                            wire:loading.attr="disabled"
                            class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-center text-sm shadow-xs transition flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="selectTemplate({{ $tpl->id }})">
                                Gunakan Desain Ini
                            </span>
                            <span wire:loading wire:target="selectTemplate({{ $tpl->id }})" class="inline-flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Menerapkan...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
