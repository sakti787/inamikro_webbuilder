@extends('templates.layouts.base')

@section('title', ($business?->name ?? 'Kuliner Nusantara') . ' - Menu & Pemesanan')

@section('content')
@php
    $business = $website->business;
    $waGenerator = app(\App\Services\WhatsAppUrlGenerator::class);
    $waNumber = $waGenerator->cleanPhoneNumber($business?->whatsapp);
    $waUrl = $waNumber ? 'https://wa.me/' . $waNumber : null;
    $activeBanners = $business?->banners ? $business->banners->where('is_active', true) : collect();
    $products = $business?->products ? $business->products->where('is_available', true) : collect();
    $testimonials = $business?->testimonials ? $business->testimonials->where('is_active', true) : collect();
@endphp

<!-- Culinary Top Highlight Bar -->
<div class="theme-bg-secondary text-white py-2.5 px-4 text-xs font-medium tracking-wide">
    <div class="max-w-6xl mx-auto flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Buka Setiap Hari: {{ is_array($business?->operating_hours) ? (array_values($business->operating_hours)[0] ?? '08:00 - 22:00 WIB') : ($business?->operating_hours ?? '08:00 - 22:00 WIB') }}</span>
        </div>
        @if ($business?->address)
            <div class="hidden sm:flex items-center gap-1.5 opacity-90 truncate max-w-md">
                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                <span class="truncate">{{ $business->address }}</span>
            </div>
        @endif
    </div>
</div>

<!-- Navigation Header -->
<header class="bg-white border-b border-amber-100 shadow-xs sticky top-0 z-30">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
        <!-- Logo & Brand Name -->
        <a href="#menu" class="flex items-center gap-3 group">
            @if ($business?->logo)
                <img src="{{ Storage::url($business->logo) }}" alt="{{ $business->name }}" class="w-12 h-12 rounded-2xl object-cover border-2 border-amber-100 shadow-xs">
            @else
                <div class="w-12 h-12 rounded-2xl theme-bg-primary text-white flex items-center justify-center font-black text-xl shadow-xs">
                    {{ strtoupper(substr($business?->name ?? 'F', 0, 1)) }}
                </div>
            @endif
            <div>
                <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight leading-tight group-hover:text-primary transition">
                    {{ $business?->name ?? 'Food & Beverage' }}
                </h1>
                <p class="text-xs text-amber-700 font-medium leading-none mt-0.5">
                    {{ $business?->tagline ?? 'Lezat, Bersih, dan Terjangkau' }}
                </p>
            </div>
        </a>

        <!-- Quick Order WhatsApp CTA -->
        @if ($waUrl)
            @php
                $headerWaUrl = $waGenerator->forGeneralInquiry($business?->whatsapp, $business?->name);
            @endphp
            <a 
                href="{{ $headerWaUrl }}" 
                target="_blank"
                rel="noopener noreferrer"
                onclick="trackWaClick(event, '{{ $headerWaUrl }}', '{{ $website->slug }}', null, 'header_nav')"
                class="theme-bg-primary text-white px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:opacity-95 transition flex items-center gap-2"
            >
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                </svg>
                <span>Pesan Sekarang</span>
            </a>
        @endif
    </div>
</header>

<!-- Hero Section with Culinary Appetizing Banner -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 pt-6">
    @if ($activeBanners->isNotEmpty())
        @php $heroBanner = $activeBanners->first(); @endphp
        <div class="relative rounded-3xl overflow-hidden shadow-lg aspect-16/9 sm:aspect-21/9 bg-slate-900 flex items-end">
            <img src="{{ Storage::url($heroBanner->image) }}" alt="{{ $heroBanner->title }}" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
            <div class="relative z-10 p-6 sm:p-12 text-white max-w-2xl space-y-3">
                <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold theme-bg-primary text-white shadow-xs">
                    Rekomendasi Spesial
                </span>
                <h2 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight">
                    {{ $heroBanner->title }}
                </h2>
                @if ($heroBanner->subtitle)
                    <p class="text-xs sm:text-sm text-slate-200 line-clamp-2">
                        {{ $heroBanner->subtitle }}
                    </p>
                @endif
                <div class="pt-2">
                    <a 
                        href="{{ $heroBanner->button_url ?? '#menu' }}" 
                        class="inline-flex items-center gap-2 theme-bg-primary text-white px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:opacity-95 transition"
                    >
                        <span>{{ $heroBanner->button_text ?? 'Lihat Menu Lengkap' }}</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    @else
        <!-- Warm Culinary Default Banner -->
        <div class="rounded-3xl bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200/80 p-8 sm:p-16 text-center shadow-xs">
            <span class="inline-block px-3.5 py-1 rounded-full text-xs font-bold theme-text-primary bg-white shadow-xs border border-amber-200">
                {{ $business?->category?->name ?? 'Kuliner & F&B Terfavorit' }}
            </span>
            <h2 class="text-3xl sm:text-5xl font-black text-slate-900 mt-4 tracking-tight">
                {{ $business?->name }}
            </h2>
            <p class="text-sm sm:text-base text-slate-600 max-w-xl mx-auto mt-3 leading-relaxed">
                {{ $business?->description ?? 'Menyajikan aneka hidangan dan minuman lezat dengan bumbu resep istimewa serta bahan-bahan segar setiap hari.' }}
            </p>
            <div class="mt-6 flex justify-center gap-3">
                <a href="#menu" class="theme-bg-primary text-white px-6 py-3 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:opacity-95 transition">
                    Pilih Menu Favorit
                </a>
            </div>
        </div>
    @endif
</section>

<!-- Info Highlights Bar (Jam Buka, Alamat, Pesan Cepat) -->
<section class="max-w-6xl mx-auto px-4 sm:px-6 pt-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-4 border border-amber-100 shadow-xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider block">Jam Buka</span>
                <span class="text-xs font-semibold text-slate-800">
                    {{ is_array($business?->operating_hours) ? (array_values($business->operating_hours)[0] ?? 'Buka Setiap Hari') : ($business?->operating_hours ?? 'Buka Setiap Hari') }}
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-amber-100 shadow-xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold text-orange-800 uppercase tracking-wider block">Alamat Toko</span>
                <span class="text-xs font-semibold text-slate-800 truncate block">
                    {{ $business?->address ?? 'Lokasi outlet fisik' }}
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 border border-amber-100 shadow-xs flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                </svg>
            </div>
            <div>
                <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block">Pesan Antar / Meja</span>
                <span class="text-xs font-semibold text-slate-800">
                    {{ $business?->whatsapp ?? 'Langsung via WhatsApp' }}
                </span>
            </div>
        </div>
    </div>
</section>

<!-- Menu Catalog Section -->
<section id="menu" class="py-12 sm:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center max-w-xl mx-auto mb-10 space-y-2">
            <span class="text-xs font-black uppercase tracking-widest theme-text-primary">Menu Spesial</span>
            <h3 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Daftar Menu & Minuman</h3>
            <p class="text-xs sm:text-sm text-slate-500">Pilih menu favorit Anda dan pesan secara instan via WhatsApp</p>
        </div>

        @if ($products->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($products as $product)
                    @php
                        $primaryImg = $product->primaryImage?->image ?? $product->images->first()?->image;
                        $detailLink = request()->routeIs('builder.preview')
                            ? route('builder.preview', ['website' => $website, 'product_id' => $product->id])
                            : route('public.product', ['slug' => $website->slug, 'product_slug' => $product->slug]);
                        $hasDiscount = $product->discount_price && $product->discount_price < $product->price;
                        $effectivePrice = $hasDiscount ? $product->discount_price : $product->price;
                    @endphp
                    <div class="bg-white rounded-3xl p-4 border border-amber-100 shadow-xs hover:shadow-lg transition flex flex-col justify-between group">
                        <div>
                            <!-- Food Photo -->
                            <a href="{{ $detailLink }}" class="aspect-4/3 rounded-2xl bg-amber-50 overflow-hidden relative block mb-4">
                                @if ($primaryImg)
                                    <img src="{{ Storage::url($primaryImg) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-amber-200">
                                        <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                        </svg>
                                    </div>
                                @endif

                                @if ($hasDiscount)
                                    <span class="absolute top-3 left-3 bg-red-600 text-white text-[10px] font-black px-2.5 py-1 rounded-lg shadow-xs uppercase">
                                        Hemat
                                    </span>
                                @elseif ($product->label)
                                    <span class="absolute top-3 left-3 theme-bg-primary text-white text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-xs">
                                        {{ $product->label }}
                                    </span>
                                @endif
                            </a>

                            <!-- Food Title & Description -->
                            @if ($product->category)
                                <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block mb-1">
                                    {{ $product->category->name }}
                                </span>
                            @endif

                            <a href="{{ $detailLink }}" class="text-base font-bold text-slate-900 group-hover:text-primary transition line-clamp-1">
                                {{ $product->name }}
                            </a>

                            @if ($product->description)
                                <p class="text-xs text-slate-500 mt-1.5 line-clamp-2 leading-relaxed">
                                    {{ $product->description }}
                                </p>
                            @endif
                        </div>

                        <!-- Price & Order CTA -->
                        <div class="mt-5 pt-3 border-t border-amber-50 flex items-center justify-between gap-3">
                            <div>
                                <span class="text-[10px] text-slate-400 block uppercase font-medium">Harga</span>
                                <div class="flex items-baseline gap-1.5">
                                    <span class="text-base font-black theme-text-primary">
                                        Rp {{ number_format($effectivePrice, 0, ',', '.') }}
                                    </span>
                                    @if ($hasDiscount)
                                        <span class="text-xs text-slate-400 line-through">
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if ($waUrl)
                                @php
                                    $menuWaUrl = $waGenerator->forProduct(
                                        $business->whatsapp,
                                        $business->name,
                                        $product->name,
                                        $effectivePrice
                                    );
                                @endphp
                                <a 
                                    href="{{ $menuWaUrl }}" 
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    onclick="trackWaClick(event, '{{ $menuWaUrl }}', '{{ $website->slug }}', {{ $product->id }}, 'catalog_card')"
                                    class="theme-bg-primary text-white px-3.5 py-2 rounded-xl text-xs font-bold hover:opacity-95 transition flex items-center gap-1.5 shadow-xs"
                                >
                                    <span>Pesan Menu</span>
                                </a>
                            @else
                                <a href="{{ $detailLink }}" class="text-xs font-bold theme-text-primary underline">Detail</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty Menu State -->
            <div class="max-w-md mx-auto text-center py-12 px-6 rounded-3xl bg-amber-50/60 border border-dashed border-amber-200">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Menu Belum Ditambahkan</h4>
                <p class="text-xs text-slate-500 mt-1">Daftar hidangan lezat kami akan segera hadir.</p>
            </div>
        @endif
    </div>
</section>

<!-- Testimonials Section if available -->
@if ($testimonials->isNotEmpty())
    <section class="py-12 bg-amber-50/40 border-y border-amber-100 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-8">
                <span class="text-xs font-bold uppercase tracking-widest text-amber-700">Ulasan Pelanggan</span>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">Kata Mereka yang Sudah Mencoba</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($testimonials as $testimonial)
                    <div class="bg-white rounded-2xl p-6 border border-amber-100 shadow-xs space-y-3">
                        <div class="flex items-center gap-1 text-amber-400">
                            @for ($i = 0; $i < ($testimonial->rating ?? 5); $i++)
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed italic">
                            "{{ $testimonial->content }}"
                        </p>
                        <span class="text-xs font-bold text-slate-900 block">— {{ $testimonial->customer_name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<!-- Location & Operating Hours Section -->
<section class="py-14 px-4 bg-white">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
        <div class="space-y-4">
            <span class="text-xs font-bold uppercase tracking-widest text-amber-700">Kunjungi Outlet Kami</span>
            <h3 class="text-2xl font-black text-slate-900">{{ $business?->name }}</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
                {{ $business?->description ?? 'Nikmati suasana santai bersama hidangan lezat dan minuman segar pilihan kami.' }}
            </p>

            @if ($business?->address)
                <div class="pt-2 flex items-start gap-3 text-xs text-slate-700">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>{{ $business->address }}</span>
                </div>
            @endif
        </div>

        <div class="bg-amber-50/70 rounded-3xl p-6 sm:p-8 border border-amber-200/70 space-y-4">
            <h4 class="text-sm font-black text-slate-900 uppercase tracking-wider">Jadwal Operasional</h4>
            @if ($business?->operating_hours)
                <div class="space-y-2 text-xs">
                    @if (is_array($business->operating_hours))
                        @foreach ($business->operating_hours as $day => $hours)
                            <div class="flex items-center justify-between py-1 border-b border-amber-100">
                                <span class="capitalize text-slate-600 font-medium">{{ $day }}</span>
                                <span class="font-bold text-slate-900">{{ $hours }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-xs text-slate-700 font-medium">{{ $business->operating_hours }}</p>
                    @endif
                </div>
            @endif

            @if ($waUrl)
                @php
                    $reservationWaUrl = $waGenerator->forGeneralInquiry($business->whatsapp, $business->name);
                @endphp
                <div class="pt-2">
                    <a 
                        href="{{ $reservationWaUrl }}" 
                        target="_blank"
                        rel="noopener noreferrer"
                        onclick="trackWaClick(event, '{{ $reservationWaUrl }}', '{{ $website->slug }}', null, 'contact_section')"
                        class="w-full theme-bg-primary text-white py-3 rounded-xl text-xs font-bold text-center block shadow-xs hover:opacity-95 transition"
                    >
                        Reservasi / Pesan Antar via WhatsApp
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-slate-950 text-slate-400 py-8 px-4 text-center text-xs mt-auto">
    <p>&copy; {{ date('Y') }} {{ $business?->name }}. Kuliner Halal, Higienis & Lezat. Didukung oleh Inamikro.</p>
</footer>
@endsection
