<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Admin Dashboard</h2>
            <p class="text-sm text-slate-600 mt-1">
                Platform Overview &mdash; Ringkasan metrik performa platform dan aktivitas ekosistem UMKM secara real-time.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a 
                href="{{ route('admin.users') }}" 
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition shadow-xs"
            >
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <span>Kelola Pengguna</span>
            </a>
            <a 
                href="{{ route('admin.templates') }}" 
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 transition shadow-xs"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>
                <span>Kelola Template</span>
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <!-- 1. Total UMKM -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total UMKM</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalUmkm) }}</p>
                </div>
                <div class="rounded-xl bg-indigo-50 p-3 text-indigo-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-slate-500">
                <span>Pengguna terdaftar</span>
            </div>
        </div>

        <!-- 2. Website Published -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Website Terbit</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalPublishedWebsites) }}</p>
                </div>
                <div class="rounded-xl bg-emerald-50 p-3 text-emerald-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-emerald-600 font-medium">
                <span>Status Published</span>
            </div>
        </div>

        <!-- 3. Total Produk -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Produk</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalProducts) }}</p>
                </div>
                <div class="rounded-xl bg-amber-50 p-3 text-amber-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-slate-500">
                <span>Katalog platform</span>
            </div>
        </div>

        <!-- 4. Website Traffic -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Total Pengunjung</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalVisits) }}</p>
                </div>
                <div class="rounded-xl bg-blue-50 p-3 text-blue-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-slate-500">
                <span>Total page views</span>
            </div>
        </div>

        <!-- 5. WA Clicks -->
        <div class="relative overflow-hidden rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Klik WhatsApp</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($totalWaClicks) }}</p>
                </div>
                <div class="rounded-xl bg-teal-50 p-3 text-teal-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-teal-600 font-medium">
                <span>Konversi prospek</span>
            </div>
        </div>
    </div>

    <!-- Recent Businesses Section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Pendaftaran UMKM Terbaru</h3>
                <p class="text-xs text-slate-500 mt-0.5">Daftar usaha UMKM yang baru saja mendaftar di sistem.</p>
            </div>
            <a href="{{ route('admin.users') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                Lihat Semua &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-100 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 px-6">Usaha & Pemilik</th>
                        <th class="py-3.5 px-6">Kategori</th>
                        <th class="py-3.5 px-6">Website / Domain</th>
                        <th class="py-3.5 px-6">Status Website</th>
                        <th class="py-3.5 px-6">Terdaftar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentBusinesses as $business)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-4 px-6">
                                <div class="font-semibold text-slate-900">{{ $business->name }}</div>
                                <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                                    <span>{{ $business->user?->name ?? '-' }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $business->user?->email ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $business->category?->name ?? 'Umum' }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                @if ($business->website)
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono text-xs text-slate-600">/{{ $business->website->slug }}</span>
                                        <a 
                                            href="{{ url('/' . $business->website->slug) }}" 
                                            target="_blank" 
                                            class="text-indigo-600 hover:text-indigo-700"
                                            title="Buka Website"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 italic">Belum dibuat</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if ($business->website?->status === 'published')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Published
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Draft
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500">
                                {{ $business->created_at?->translatedFormat('d M Y') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-sm">
                                Belum ada data UMKM yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
