@extends('templates.layouts.base')

@section('title', ($business?->name ?? 'Minimalist Store') . ' - Official Store')

@section('content')
@php
    $business = $website->business;
    $waGenerator = app(\App\Services\WhatsAppUrlGenerator::class);
    $waNumber = $waGenerator->cleanPhoneNumber($business?->whatsapp);
    $waUrl = $waNumber ? 'https://wa.me/' . $waNumber : null;
    $activeBanners = $business?->banners ? $business->banners->where('is_active', true) : collect();
    $products = $business?->products ? $business->products->where('is_available', true) : collect();
@endphp

<!-- Top Announcement Bar -->
<div class="bg-slate-900 text-slate-300 py-2 px-4 text-center text-xs tracking-wide">
    <span>{{ $business?->tagline ?? 'Selamat datang di toko resmi ' . ($business?->name ?? 'UMKM') }}</span>
</div>

<!-- Header / Navigation -->
<header class="bg-white/95 backdrop-blur-md border-b border-slate-100 sticky top-0 z-30 transition">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
        <!-- Logo & Brand Name -->
        <a href="#home" class="flex items-center gap-3 group">
            @if ($business?->logo)
                <img src="{{ Storage::url($business->logo) }}" alt="{{ $business->name }}" class="w-11 h-11 rounded-full object-cover border border-slate-200">
            @else
                <div class="w-11 h-11 rounded-full bg-slate-900 text-white flex items-center justify-center font-semibold text-lg tracking-wider">
                    {{ strtoupper(substr($business?->name ?? 'M', 0, 1)) }}
                </div>
            @endif
            <div class="flex flex-col">
                <span class="text-lg font-bold tracking-tight text-slate-900 group-hover:text-primary transition">
                    {{ $business?->name ?? 'Minimalist Store' }}
                </span>
                @if ($business?->category)
                    <span class="text-[11px] text-slate-400 font-medium tracking-wide uppercase">{{ $business->category->name }}</span>
                @endif
            </div>
        </a>

        <!-- Navigation Links & WhatsApp CTA -->
        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
            <a href="#catalog" class="hover:text-primary transition">Katalog</a>
            <a href="#about" class="hover:text-primary transition">Tentang Kami</a>
            <a href="#contact" class="hover:text-primary transition">Kontak</a>
        </nav>

        <div class="flex items-center gap-3">
            @if ($waUrl)
                @php
                    $headerWaUrl = $waGenerator->forGeneralInquiry($business?->whatsapp, $business?->name);
                @endphp
                <a 
                    href="{{ $headerWaUrl }}" 
                    target="_blank"
                    rel="noopener noreferrer"
                    onclick="trackWaClick(event, '{{ $headerWaUrl }}', '{{ $website->slug }}', null, 'header_nav')"
                    class="theme-bg-primary text-white px-5 py-2.5 rounded-full text-xs font-semibold shadow-xs hover:opacity-95 transition flex items-center gap-2"
                >
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                    </svg>
                    <span>Hubungi Kami</span>
                </a>
            @endif
        </div>
    </div>
</header>

<!-- Hero Section -->
<section id="home" class="py-10 sm:py-16 px-4">
    <div class="max-w-6xl mx-auto">
        @if ($activeBanners->isNotEmpty())
            @php $mainBanner = $activeBanners->first(); @endphp
            <div class="relative rounded-3xl overflow-hidden shadow-lg bg-slate-900 min-h-[360px] sm:min-h-[460px] flex items-center">
                <img src="{{ Storage::url($mainBanner->image) }}" alt="{{ $mainBanner->title }}" class="absolute inset-0 w-full h-full object-cover opacity-60">
                <div class="absolute inset-0 bg-gradient-to-t sm:bg-gradient-to-r from-black/85 via-black/50 to-transparent"></div>
                <div class="relative z-10 p-8 sm:p-16 max-w-2xl text-white space-y-4">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-white/20 backdrop-blur-md uppercase tracking-wider">
                        Koleksi Pilihan
                    </span>
                    <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">
                        {{ $mainBanner->title }}
                    </h1>
                    @if ($mainBanner->subtitle)
                        <p class="text-sm sm:text-base text-slate-200 leading-relaxed font-light">
                            {{ $mainBanner->subtitle }}
                        </p>
                    @endif
                    <div class="pt-4 flex items-center gap-4">
                        <a 
                            href="{{ $mainBanner->button_url ?? '#catalog' }}" 
                            class="theme-bg-primary text-white px-7 py-3 rounded-full text-sm font-semibold hover:opacity-95 transition shadow-md"
                        >
                            {{ $mainBanner->button_text ?? 'Jelajahi Produk' }}
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Minimalist Default Hero Banner -->
            <div class="text-center py-12 sm:py-20 px-6 rounded-3xl bg-white border border-slate-100 shadow-xs max-w-4xl mx-auto space-y-5">
                <span class="inline-block px-4 py-1.5 rounded-full text-xs font-semibold theme-text-primary bg-slate-50 border border-slate-200 tracking-wider uppercase">
                    {{ $business?->category?->name ?? 'Koleksi Eksklusif' }}
                </span>
                <h1 class="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight leading-tight">
                    {{ $business?->name }}
                </h1>
                <p class="text-slate-600 max-w-2xl mx-auto text-sm sm:text-base leading-relaxed font-light">
                    {{ $business?->description ?? 'Menghadirkan kurasi produk terbaik dengan estetika minimalis, mutu material premium, dan kenyamanan tanpa kompromi.' }}
                </p>
                <div class="pt-2">
                    <a href="#catalog" class="inline-flex items-center gap-2 theme-bg-primary text-white px-8 py-3.5 rounded-full text-sm font-semibold hover:opacity-95 transition shadow-sm">
                        <span>Lihat Koleksi Produk</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>
            </div>
        @endif
    </div>
</section>

<!-- Product Catalog Grid -->
<section id="catalog" class="py-12 sm:py-16 px-4 bg-white border-y border-slate-100">
    <div class="max-w-6xl mx-auto">
        <div class="text-center max-w-xl mx-auto mb-12 space-y-2">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Katalog Produk</h2>
            <p class="text-sm text-slate-500 font-light">Desain esensial, material premium, dan kualitas terbaik untuk Anda</p>
        </div>

        @if ($products->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5 sm:gap-8">
                @foreach ($products as $product)
                    @php
                        $primaryImg = $product->primaryImage?->image ?? $product->images->first()?->image;
                        $detailLink = request()->routeIs('builder.preview')
                            ? route('builder.preview', ['website' => $website, 'product_id' => $product->id])
                            : '#product-' . $product->id;
                        $hasDiscount = $product->discount_price && $product->discount_price < $product->price;
                        $effectivePrice = $hasDiscount ? $product->discount_price : $product->price;
                    @endphp
                    <div class="group flex flex-col bg-slate-50/60 rounded-2xl p-3 border border-slate-200/70 hover:border-slate-300 hover:shadow-md transition">
                        <!-- Product Image Aspect Square -->
                        <a href="{{ $detailLink }}" class="aspect-square rounded-xl bg-slate-100 overflow-hidden relative block mb-3">
                            @if ($primaryImg)
                                <img src="{{ Storage::url($primaryImg) }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-300">
                                    <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif

                            @if ($hasDiscount)
                                <span class="absolute top-2.5 left-2.5 bg-rose-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-xs uppercase tracking-wide">
                                    Promo
                                </span>
                            @elseif ($product->label)
                                <span class="absolute top-2.5 left-2.5 bg-slate-900/80 backdrop-blur-xs text-white text-[10px] font-medium px-2 py-0.5 rounded-full">
                                    {{ $product->label }}
                                </span>
                            @endif
                        </a>

                        <!-- Product Info -->
                        <div class="flex-1 flex flex-col justify-between space-y-2">
                            <div>
                                @if ($product->category)
                                    <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block mb-0.5">
                                        {{ $product->category->name }}
                                    </span>
                                @endif
                                <a href="{{ $detailLink }}" class="font-semibold text-slate-900 text-sm hover:text-primary transition line-clamp-2">
                                    {{ $product->name }}
                                </a>
                            </div>

                            <div class="pt-2">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-sm sm:text-base font-bold theme-text-primary">
                                        Rp {{ number_format($effectivePrice, 0, ',', '.') }}
                                    </span>
                                    @if ($hasDiscount)
                                        <span class="text-xs text-slate-400 line-through">
                                            Rp {{ number_format($product->price, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-2 pt-2 border-t border-slate-200/60">
                                    <a 
                                        href="{{ $detailLink }}" 
                                        class="text-xs font-semibold text-slate-700 hover:text-primary transition underline underline-offset-2"
                                    >
                                        Detail
                                    </a>

                                    @if ($waUrl)
                                        @php
                                            $cardWaUrl = $waGenerator->forProduct(
                                                $business->whatsapp,
                                                $business->name,
                                                $product->name,
                                                $effectivePrice
                                            );
                                        @endphp
                                        <a 
                                            href="{{ $cardWaUrl }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            onclick="trackWaClick(event, '{{ $cardWaUrl }}', '{{ $website->slug }}', {{ $product->id }}, 'catalog_card')"
                                            class="px-3 py-1 rounded-full text-xs font-semibold theme-bg-primary text-white hover:opacity-90 transition flex items-center gap-1 shadow-xs"
                                        >
                                            <span>Pesan</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty Catalog State -->
            <div class="max-w-md mx-auto text-center py-12 px-6 rounded-2xl bg-slate-50 border border-dashed border-slate-200">
                <div class="w-12 h-12 rounded-full bg-slate-200/80 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-slate-800">Katalog Belum Tersedia</h3>
                <p class="text-xs text-slate-500 mt-1">Produk pilihan kami sedang dalam tahap penataan.</p>
            </div>
        @endif
    </div>
</section>

<!-- About & Contact Section -->
<section id="about" class="py-14 sm:py-20 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-start">
            <!-- Business Story -->
            <div class="space-y-4">
                <span class="text-xs font-bold uppercase tracking-widest theme-text-primary">Tentang Kami</span>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    {{ $business?->name }}
                </h3>
                <p class="text-sm text-slate-600 leading-relaxed font-light">
                    {{ $business?->description ?? 'Berdedikasi untuk memberikan produk dan pengalaman berbelanja terbaik bagi setiap pelanggan kami.' }}
                </p>

                @if ($business?->address)
                    <div class="pt-4 flex items-start gap-3 text-xs text-slate-600">
                        <svg class="w-5 h-5 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="leading-relaxed">{{ $business->address }}</span>
                    </div>
                @endif
            </div>

            <!-- Contact & Operational Card -->
            <div id="contact" class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6">
                <h4 class="text-base font-bold text-slate-900 tracking-tight border-b border-slate-100 pb-3">Informasi Kontak & Jam Buka</h4>

                <div class="space-y-3 text-xs text-slate-600">
                    @if ($business?->whatsapp)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">WhatsApp Resmi</span>
                            <a href="{{ $waUrl }}" target="_blank" class="font-semibold text-slate-900 hover:text-primary transition">{{ $business->whatsapp }}</a>
                        </div>
                    @endif

                    @if ($business?->phone)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Telepon</span>
                            <span class="font-semibold text-slate-900">{{ $business->phone }}</span>
                        </div>
                    @endif

                    @if ($business?->email)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Email</span>
                            <span class="font-semibold text-slate-900">{{ $business->email }}</span>
                        </div>
                    @endif
                </div>

                @if ($business?->operating_hours)
                    <div class="pt-4 border-t border-slate-100">
                        <span class="text-xs font-semibold text-slate-700 block mb-2">Jam Operasional:</span>
                        @if (is_array($business->operating_hours))
                            <div class="grid grid-cols-2 gap-1.5 text-xs">
                                @foreach ($business->operating_hours as $day => $hours)
                                    <span class="capitalize text-slate-400">{{ $day }}</span>
                                    <span class="font-medium text-slate-800 text-right">{{ $hours }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-slate-600">{{ $business->operating_hours }}</p>
                        @endif
                    </div>
                @endif

                @if ($waUrl)
                    @php
                        $contactWaUrl = $waGenerator->forGeneralInquiry($business->whatsapp, $business->name);
                    @endphp
                    <div class="pt-2">
                        <a 
                            href="{{ $contactWaUrl }}" 
                            target="_blank"
                            rel="noopener noreferrer"
                            onclick="trackWaClick(event, '{{ $contactWaUrl }}', '{{ $website->slug }}', null, 'contact_section')"
                            class="w-full theme-bg-primary text-white py-3 rounded-full text-xs font-bold text-center block shadow-xs hover:opacity-95 transition"
                        >
                            Hubungi via WhatsApp
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-slate-900 text-slate-400 py-10 px-4 text-center text-xs mt-auto border-t border-slate-800">
    <div class="max-w-6xl mx-auto space-y-2">
        <p class="text-slate-300 font-semibold">{{ $business?->name }}</p>
        <p>&copy; {{ date('Y') }} {{ $business?->name }}. Seluruh hak cipta dilindungi. Didukung oleh Inamikro UMKM Platform.</p>
    </div>
</footer>
@endsection
