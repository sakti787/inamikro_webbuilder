@extends('templates.layouts.base')

@section('title', ($business?->name ?? 'Modern Business') . ' - Solusi Bisnis & Layanan Terpercaya')

@section('content')
@php
    $business = $website->business;
    $waGenerator = app(\App\Services\WhatsAppUrlGenerator::class);
    $waNumber = $waGenerator->cleanPhoneNumber($business?->whatsapp);
    $waUrl = $waNumber ? 'https://wa.me/' . $waNumber : null;
    $activeBanners = $business?->banners ? $business->banners->where('is_active', true) : collect();
    $products = $business?->products ? $business->products->where('is_available', true) : collect();
    $galleries = $business?->galleries ?? collect();
    $testimonials = $business?->testimonials ? $business->testimonials->where('is_active', true) : collect();
@endphp

<!-- Modern Navigation Bar -->
<header class="bg-white/90 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30 transition">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
        <!-- Logo & Brand Name -->
        <a href="#hero" class="flex items-center gap-3.5 group">
            @if ($business?->logo)
                <img src="{{ Storage::url($business->logo) }}" alt="{{ $business->name }}" class="w-11 h-11 rounded-xl object-cover border border-slate-200 shadow-xs">
            @else
                <div class="w-11 h-11 rounded-xl theme-bg-primary text-white flex items-center justify-center font-extrabold text-lg shadow-sm">
                    {{ strtoupper(substr($business?->name ?? 'B', 0, 1)) }}
                </div>
            @endif
            <div>
                <span class="text-base sm:text-lg font-extrabold tracking-tight text-slate-900 group-hover:text-primary transition leading-tight block">
                    {{ $business?->name ?? 'Modern Business' }}
                </span>
                @if ($business?->tagline)
                    <span class="text-[11px] text-slate-500 font-normal leading-none block mt-0.5">{{ $business->tagline }}</span>
                @endif
            </div>
        </a>

        <!-- Desktop Nav Links -->
        <nav class="hidden md:flex items-center gap-7 text-xs font-semibold text-slate-600 uppercase tracking-wider">
            <a href="#features" class="hover:text-primary transition">Keunggulan</a>
            <a href="#products" class="hover:text-primary transition">Produk & Layanan</a>
            @if ($galleries->isNotEmpty())
                <a href="#gallery" class="hover:text-primary transition">Galeri</a>
            @endif
            @if ($testimonials->isNotEmpty())
                <a href="#testimonials" class="hover:text-primary transition">Ulasan</a>
            @endif
            <a href="#contact" class="hover:text-primary transition">Kontak</a>
        </nav>

        <!-- Right WhatsApp Action -->
        @if ($waUrl)
            @php
                $headerWaUrl = $waGenerator->forGeneralInquiry($business?->whatsapp, $business?->name);
            @endphp
            <a 
                href="{{ $headerWaUrl }}" 
                target="_blank"
                rel="noopener noreferrer"
                onclick="trackWaClick(event, '{{ $headerWaUrl }}', '{{ $website->slug }}', null, 'header_nav')"
                class="theme-bg-primary text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm hover:opacity-95 transition flex items-center gap-2"
            >
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                </svg>
                <span>Konsultasi Sekarang</span>
            </a>
        @endif
    </div>
</header>

<!-- Hero Section -->
<section id="hero" class="py-12 sm:py-20 px-4 bg-gradient-to-b from-white to-slate-50 border-b border-slate-200/60">
    <div class="max-w-6xl mx-auto">
        @if ($activeBanners->isNotEmpty())
            @php $mainBanner = $activeBanners->first(); @endphp
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14 items-center">
                <div class="space-y-5 text-left">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold theme-text-primary bg-slate-100 border border-slate-200 uppercase tracking-wider">
                        <span class="w-2 h-2 rounded-full theme-bg-primary"></span>
                        Solusi Unggulan
                    </span>

                    <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                        {{ $mainBanner->title }}
                    </h1>

                    @if ($mainBanner->subtitle)
                        <p class="text-sm sm:text-base text-slate-600 leading-relaxed max-w-xl">
                            {{ $mainBanner->subtitle }}
                        </p>
                    @endif

                    <div class="pt-3 flex flex-wrap items-center gap-3">
                        <a 
                            href="{{ $mainBanner->button_url ?? '#products' }}" 
                            class="theme-bg-primary text-white px-7 py-3.5 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:opacity-95 transition"
                        >
                            {{ $mainBanner->button_text ?? 'Lihat Produk & Layanan' }}
                        </a>
                        <a 
                            href="#features" 
                            class="px-6 py-3.5 rounded-xl text-xs sm:text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition"
                        >
                            Keunggulan Kami
                        </a>
                    </div>
                </div>

                <div class="relative rounded-3xl overflow-hidden shadow-xl aspect-16/10 bg-slate-100 border border-slate-200">
                    <img src="{{ Storage::url($mainBanner->image) }}" alt="{{ $mainBanner->title }}" class="w-full h-full object-cover">
                </div>
            </div>
        @else
            <!-- Default Hero Section -->
            <div class="text-center max-w-3xl mx-auto space-y-6">
                <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-bold theme-text-primary bg-white shadow-xs border border-slate-200 uppercase tracking-wider">
                    <span>★</span>
                    {{ $business?->category?->name ?? 'Layanan Profesional & Terpercaya' }}
                </span>

                <h1 class="text-3xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                    {{ $business?->name }}
                </h1>

                <p class="text-sm sm:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    {{ $business?->description ?? 'Menghadirkan produk dan solusi bermutu tinggi yang dirancang untuk mendukung kebutuhan dan kesuksesan Anda.' }}
                </p>

                <div class="pt-2 flex flex-wrap justify-center gap-3">
                    <a href="#products" class="theme-bg-primary text-white px-8 py-3.5 rounded-xl text-xs sm:text-sm font-bold shadow-md hover:opacity-95 transition">
                        Jelajahi Solusi Kami
                    </a>
                    @if ($waUrl)
                        @php
                            $heroWaUrl = $waGenerator->forGeneralInquiry($business?->whatsapp, $business?->name);
                        @endphp
                        <a 
                            href="{{ $heroWaUrl }}" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            onclick="trackWaClick(event, '{{ $heroWaUrl }}', '{{ $website->slug }}', null, 'hero_section')"
                            class="bg-white text-slate-800 border border-slate-300 px-6 py-3.5 rounded-xl text-xs sm:text-sm font-bold hover:bg-slate-50 transition"
                        >
                            Konsultasi via WhatsApp
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </div>
</section>

<!-- Value Propositions / Keunggulan Kami -->
<section id="features" class="py-14 sm:py-20 px-4 bg-white border-b border-slate-200/70">
    <div class="max-w-6xl mx-auto">
        <div class="text-center max-w-xl mx-auto mb-12 space-y-2">
            <span class="text-xs font-bold uppercase tracking-widest theme-text-primary">Mengapa Memilih Kami</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Keunggulan Layanan Kami</h2>
            <p class="text-xs sm:text-sm text-slate-500">Komitmen kami dalam memberikan standar mutu dan kepuasan terbaik</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 hover:border-slate-300 transition">
                <div class="w-12 h-12 rounded-xl theme-bg-primary text-white flex items-center justify-center font-bold text-lg shadow-xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Kualitas Terjamin & Terpercaya</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Setiap produk dan layanan melewati kontrol kualitas yang ketat demi kepuasan menyeluruh.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 hover:border-slate-300 transition">
                <div class="w-12 h-12 rounded-xl theme-bg-secondary text-white flex items-center justify-center font-bold text-lg shadow-xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Respon Cepat & Profesional</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Tim kami selalu siap mendengarkan kebutuhan Anda dengan respon tanggap dan solusi tepat.
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3 hover:border-slate-300 transition">
                <div class="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-lg shadow-xs">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Harga Transparan & Bersaing</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Menawarkan nilai terbaik tanpa biaya tersembunyi untuk kemitraan jangka panjang yang kokoh.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Products & Catalog Section -->
<section id="products" class="py-14 sm:py-20 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-10 gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest theme-text-primary">Katalog Pilihan</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight mt-1">Produk & Layanan Unggulan</h2>
            </div>
            <p class="text-xs text-slate-500 max-w-sm">Temukan berbagai varian produk terbaik yang disesuaikan dengan kebutuhan Anda</p>
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
                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-lg transition flex flex-col justify-between overflow-hidden group">
                        <div>
                            <!-- Product Image Aspect 16:10 -->
                            <a href="{{ $detailLink }}" class="aspect-16/10 bg-slate-100 overflow-hidden relative block">
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
                                    <span class="absolute top-3 left-3 bg-rose-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-md shadow-xs uppercase">
                                        Diskon Spesial
                                    </span>
                                @elseif ($product->label)
                                    <span class="absolute top-3 left-3 bg-slate-900 text-white text-[10px] font-semibold px-2.5 py-1 rounded-md shadow-xs">
                                        {{ $product->label }}
                                    </span>
                                @endif
                            </a>

                            <!-- Card Body -->
                            <div class="p-5 space-y-2">
                                @if ($product->category)
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">
                                        {{ $product->category->name }}
                                    </span>
                                @endif

                                <a href="{{ $detailLink }}" class="font-bold text-slate-900 text-base group-hover:text-primary transition line-clamp-2">
                                    {{ $product->name }}
                                </a>

                                @if ($product->description)
                                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                        {{ $product->description }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- Card Footer: Price & CTA -->
                        <div class="p-5 pt-0">
                            <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
                                <div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400 block">Harga</span>
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="text-base font-extrabold theme-text-primary">
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
                                        $productWaUrl = $waGenerator->forProduct(
                                            $business->whatsapp,
                                            $business->name,
                                            $product->name,
                                            $effectivePrice
                                        );
                                    @endphp
                                    <a 
                                        href="{{ $productWaUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        onclick="trackWaClick(event, '{{ $productWaUrl }}', '{{ $website->slug }}', {{ $product->id }}, 'catalog_card')"
                                        class="theme-bg-primary text-white px-4 py-2 rounded-xl text-xs font-bold hover:opacity-95 transition flex items-center gap-1.5 shadow-xs"
                                    >
                                        <span>Pesan</span>
                                    </a>
                                @else
                                    <a href="{{ $detailLink }}" class="text-xs font-bold theme-text-primary underline">Lihat Detail</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty Product Catalog -->
            <div class="max-w-md mx-auto text-center py-12 px-6 rounded-2xl bg-white border border-dashed border-slate-200">
                <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Katalog Belum Tersedia</h4>
                <p class="text-xs text-slate-500 mt-1">Daftar produk dan layanan sedang dipersiapkan untuk Anda.</p>
            </div>
        @endif
    </div>
</section>

<!-- Gallery Section if available -->
@if ($galleries->isNotEmpty())
    <section id="gallery" class="py-14 sm:py-20 px-4 bg-white border-t border-slate-200/80">
        <div class="max-w-6xl mx-auto">
            <div class="text-center max-w-xl mx-auto mb-10 space-y-2">
                <span class="text-xs font-bold uppercase tracking-widest theme-text-primary">Dokumentasi</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Galeri & Portofolio</h2>
                <p class="text-xs sm:text-sm text-slate-500">Momen dan hasil karya nyata dari dedikasi kami</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach ($galleries as $gallery)
                    <div class="aspect-square rounded-2xl overflow-hidden bg-slate-100 relative group border border-slate-200/70 shadow-xs">
                        <img src="{{ Storage::url($gallery->image) }}" alt="{{ $gallery->caption ?? 'Galeri' }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        @if ($gallery->caption)
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition p-3 flex items-end">
                                <span class="text-xs text-white font-medium">{{ $gallery->caption }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<!-- Testimonials Section if available -->
@if ($testimonials->isNotEmpty())
    <section id="testimonials" class="py-14 sm:py-20 px-4 bg-slate-50 border-t border-slate-200/80">
        <div class="max-w-6xl mx-auto">
            <div class="text-center max-w-xl mx-auto mb-12 space-y-2">
                <span class="text-xs font-bold uppercase tracking-widest theme-text-primary">Testimoni</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Apa Kata Klien Kami</h2>
                <p class="text-xs sm:text-sm text-slate-500">Ulasan jujur dari pelanggan setia kami</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($testimonials as $testimonial)
                    <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs space-y-3">
                        <div class="flex items-center gap-1 text-amber-400">
                            @for ($i = 0; $i < ($testimonial->rating ?? 5); $i++)
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endfor
                        </div>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed italic">
                            "{{ $testimonial->content }}"
                        </p>
                        <span class="text-xs font-bold text-slate-900 block">— {{ $testimonial->customer_name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<!-- Contact & Location Section -->
<section id="contact" class="py-14 sm:py-20 px-4 bg-white border-t border-slate-200/80">
    <div class="max-w-6xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start">
            <div class="space-y-4">
                <span class="text-xs font-bold uppercase tracking-widest theme-text-primary">Hubungi Kami</span>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ $business?->name }}</h3>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    {{ $business?->description ?? 'Kami berkomitmen untuk terus memberikan pelayanan terbaik kepada pelanggan kami. Jangan ragu untuk menghubungi kami.' }}
                </p>

                @if ($business?->address)
                    <div class="pt-3 flex items-start gap-3 text-xs sm:text-sm text-slate-700">
                        <svg class="w-5 h-5 text-slate-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ $business->address }}</span>
                    </div>
                @endif
            </div>

            <div class="bg-slate-50 rounded-3xl p-6 sm:p-8 border border-slate-200/80 space-y-4">
                <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Saluran Komunikasi</h4>

                <div class="space-y-2.5 text-xs text-slate-600">
                    @if ($business?->whatsapp)
                        <div class="flex items-center justify-between py-1 border-b border-slate-200/60">
                            <span class="font-medium text-slate-500">WhatsApp Bisnis:</span>
                            <a href="{{ $waUrl }}" target="_blank" class="font-bold text-slate-900 hover:text-primary transition">{{ $business->whatsapp }}</a>
                        </div>
                    @endif

                    @if ($business?->phone)
                        <div class="flex items-center justify-between py-1 border-b border-slate-200/60">
                            <span class="font-medium text-slate-500">Telepon Kantor:</span>
                            <span class="font-bold text-slate-900">{{ $business->phone }}</span>
                        </div>
                    @endif

                    @if ($business?->email)
                        <div class="flex items-center justify-between py-1 border-b border-slate-200/60">
                            <span class="font-medium text-slate-500">Email:</span>
                            <span class="font-bold text-slate-900">{{ $business->email }}</span>
                        </div>
                    @endif

                    @if ($business?->operating_hours)
                        <div class="pt-2">
                            <span class="font-bold text-slate-800 block mb-1">Jam Operasional:</span>
                            @if (is_array($business->operating_hours))
                                <div class="grid grid-cols-2 gap-1 text-[11px]">
                                    @foreach ($business->operating_hours as $day => $hours)
                                        <span class="capitalize text-slate-500">{{ $day }}</span>
                                        <span class="font-medium text-slate-800 text-right">{{ $hours }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-slate-600">{{ $business->operating_hours }}</p>
                            @endif
                        </div>
                    @endif
                </div>

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
                            class="w-full theme-bg-primary text-white py-3 rounded-xl text-xs font-bold text-center block shadow-xs hover:opacity-95 transition"
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
        <p class="text-slate-300 font-bold">{{ $business?->name }}</p>
        <p>&copy; {{ date('Y') }} {{ $business?->name }}. Solusi Bisnis & Layanan Terpercaya. Didukung oleh Inamikro.</p>
    </div>
</footer>
@endsection
