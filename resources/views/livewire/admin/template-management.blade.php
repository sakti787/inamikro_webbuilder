<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Kelola Template Website</h2>
            <p class="text-sm text-slate-600 mt-1">
                Atur ketersediaan template desain untuk seluruh pengguna UMKM saat membuat atau mengubah website mereka.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20">
                Total: {{ $templates->count() }} Template
            </span>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($templates as $template)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col transition hover:shadow-md">
                <!-- Template Header / Thumbnail area -->
                <div class="h-44 bg-slate-100 border-b border-slate-100 flex items-center justify-center relative p-4 overflow-hidden group">
                    @if ($template->slug === 'minimalist')
                        <div class="w-full h-full bg-linear-to-tr from-slate-200 to-slate-100 rounded-xl p-3 flex flex-col justify-between shadow-inner">
                            <div class="h-3 w-1/3 bg-slate-400/50 rounded"></div>
                            <div class="space-y-1.5">
                                <div class="h-2 w-3/4 bg-slate-400/30 rounded"></div>
                                <div class="h-2 w-1/2 bg-slate-400/30 rounded"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="h-10 bg-white rounded shadow-xs"></div>
                                <div class="h-10 bg-white rounded shadow-xs"></div>
                                <div class="h-10 bg-white rounded shadow-xs"></div>
                            </div>
                        </div>
                    @elseif ($template->slug === 'food')
                        <div class="w-full h-full bg-linear-to-tr from-amber-100 to-orange-50 rounded-xl p-3 flex flex-col justify-between shadow-inner">
                            <div class="h-3 w-1/3 bg-amber-500/50 rounded"></div>
                            <div class="space-y-1.5">
                                <div class="h-2 w-3/4 bg-amber-400/40 rounded"></div>
                                <div class="h-2 w-1/2 bg-amber-400/40 rounded"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="h-10 bg-white rounded shadow-xs"></div>
                                <div class="h-10 bg-white rounded shadow-xs"></div>
                                <div class="h-10 bg-white rounded shadow-xs"></div>
                            </div>
                        </div>
                    @else
                        <div class="w-full h-full bg-linear-to-tr from-indigo-100 to-blue-50 rounded-xl p-3 flex flex-col justify-between shadow-inner">
                            <div class="h-3 w-1/3 bg-indigo-500/50 rounded"></div>
                            <div class="space-y-1.5">
                                <div class="h-2 w-3/4 bg-indigo-400/40 rounded"></div>
                                <div class="h-2 w-1/2 bg-indigo-400/40 rounded"></div>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="h-10 bg-white rounded shadow-xs"></div>
                                <div class="h-10 bg-white rounded shadow-xs"></div>
                                <div class="h-10 bg-white rounded shadow-xs"></div>
                            </div>
                        </div>
                    @endif

                    <div class="absolute top-3 right-3">
                        @if ($template->is_active)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 shadow-xs bg-white/90 backdrop-blur-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 ring-1 ring-slate-300/40 shadow-xs bg-white/90 backdrop-blur-xs">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                Nonaktif
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Template Body -->
                <div class="p-5 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-base font-bold text-slate-900">{{ $template->name }}</h3>
                            <span class="font-mono text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded">{{ $template->slug }}</span>
                        </div>
                        <p class="text-xs text-slate-600 mt-2 line-clamp-2 leading-relaxed">
                            {{ $template->description ?? 'Template standar platform Inamikro.' }}
                        </p>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <div class="text-xs text-slate-500 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <span>{{ $template->websites_count }} Website</span>
                        </div>

                        <button 
                            type="button"
                            wire:click="toggleStatus({{ $template->id }})"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition {{ $template->is_active ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' }}"
                        >
                            <span wire:loading.remove wire:target="toggleStatus({{ $template->id }})">
                                {{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </span>
                            <span wire:loading wire:target="toggleStatus({{ $template->id }})">
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 text-sm bg-white rounded-2xl border border-slate-200">
                Belum ada data template di sistem.
            </div>
        @endforelse
    </div>
</div>
