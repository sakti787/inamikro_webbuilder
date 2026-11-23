<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Kelola Pengguna UMKM</h2>
            <p class="text-sm text-slate-600 mt-1">
                Daftar lengkap seluruh pemilik UMKM yang terdaftar di platform beserta status websitenya.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-600/20">
                Total: {{ $users->total() }} UMKM
            </span>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row gap-4 items-center justify-between">
        <div class="relative w-full sm:w-96">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Cari nama, email, atau nama usaha..." 
                class="w-full pl-10 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
            >
        </div>
        @if ($search)
            <button 
                wire:click="$set('search', '')" 
                class="text-xs text-slate-500 hover:text-slate-800 underline self-start sm:self-auto"
            >
                Reset Pencarian
            </button>
        @endif
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-100 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">Pengguna</th>
                        <th class="py-3.5 px-6">Nama Bisnis & Kategori</th>
                        <th class="py-3.5 px-6">Website / Domain</th>
                        <th class="py-3.5 px-6">Status Website</th>
                        <th class="py-3.5 px-6">Terdaftar Sejak</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        @php
                            $business = $user->business;
                            $website = $business?->website;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-4 px-6">
                                <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $user->email }}</div>
                                @if ($business?->whatsapp)
                                    <div class="text-[11px] text-teal-600 font-medium mt-0.5 flex items-center gap-1">
                                        <span>WA: {{ $business->whatsapp }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if ($business)
                                    <div class="font-medium text-slate-800">{{ $business->name }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">
                                        {{ $business->category?->name ?? 'Kategori Umum' }}
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Belum mengisi profil usaha</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if ($website && $website->slug)
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-xs text-slate-700 bg-slate-100 px-2 py-0.5 rounded">
                                            /{{ $website->slug }}
                                        </span>
                                        <a 
                                            href="{{ url('/' . $website->slug) }}" 
                                            target="_blank" 
                                            class="text-indigo-600 hover:text-indigo-800 p-1"
                                            title="Buka Website"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Belum disetel</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if ($website?->status === 'published')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Published
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500">
                                {{ $user->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400 text-sm">
                                <svg class="w-8 h-8 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                Tidak ditemukan data pengguna UMKM yang sesuai kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
