@extends('templates.layouts.base')

@section('title', ($product->name ?? 'Detail Produk & Layanan') . ' - ' . ($business?->name ?? 'Modern Business'))
@section('meta_description', Str::limit(strip_tags($product->description ?? ''), 160))

@section('content')
@php
    $business = $website->business;
    $waGenerator = app(\App\Services\WhatsAppUrlGenerator::class);
    $waNumber = $waGenerator->cleanPhoneNumber($business?->whatsapp);
    $waUrl = $waNumber ? 'https://wa.me/' . $waNumber : null;
    $hasDiscount = $product->discount_price && $product->discount_price < $product->price;
    $effectivePrice = $hasDiscount ? $product->discount_price : $product->price;
    $primaryImg = $product->primaryImage?->image ?? $product->images->first()?->image;
    $backLink = request()->routeIs('builder.preview') ? route('builder.preview', $website) : route('public.website', $website->slug);
@endphp

<!-- Navigation Bar -->
<header class="bg-white border-b border-slate-200/80 sticky top-0 z-30 shadow-xs">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-18 flex items-center justify-between">
        <a href="{{ $backLink }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-primary transition py-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Beranda</span>
        </a>

        <span class="text-sm font-extrabold text-slate-900">{{ $business?->name }}</span>

        @if ($waUrl)
            @php
                $headerWaUrl = $waGenerator->forGeneralInquiry($business?->whatsapp, $business?->name);
            @endphp
            <a 
                href="{{ $headerWaUrl }}" 
                target="_blank"
                rel="noopener noreferrer"
                onclick="trackWaClick(event, '{{ $headerWaUrl }}', '{{ $website->slug }}', null, 'header_nav')"
                class="theme-bg-primary text-white px-4 py-2 rounded-xl text-xs font-bold shadow-xs hover:opacity-95 transition"
            >
                Konsultasi WhatsApp
            </a>
        @else
            <div></div>
        @endif
    </div>
</header>

<!-- Main Product / Service Detail -->
<main class="max-w-6xl mx-auto px-4 sm:px-6 py-10 sm:py-16">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-400 mb-8 font-medium">
        <a href="{{ $backLink }}" class="hover:text-slate-600">Beranda</a>
        <span>/</span>
        @if ($product->category)
            <span>{{ $product->category->name }}</span>
            <span>/</span>
        @endif
        <span class="text-slate-900 font-bold truncate">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14 items-start">
        <!-- Image & Gallery Container -->
        <div class="space-y-4">
            <div class="aspect-16/10 sm:aspect-4/3 rounded-3xl bg-slate-100 overflow-hidden relative border border-slate-200/80 shadow-sm">
                @if ($primaryImg)
                    <img src="{{ Storage::url($primaryImg) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-slate-300">
                        <svg class="w-20 h-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                @endif

                @if ($hasDiscount)
                    <span class="absolute top-4 left-4 bg-rose-600 text-white text-xs font-bold px-3 py-1 rounded-lg shadow-md uppercase">
                        Penawaran Khusus
                    </span>
                @elseif ($product->label)
                    <span class="absolute top-4 left-4 bg-slate-900 text-white text-xs font-semibold px-3 py-1 rounded-lg shadow-md">
                        {{ $product->label }}
                    </span>
                @endif
            </div>

            @if ($product->images && $product->images->count() > 1)
                <div class="flex gap-3 overflow-x-auto pb-2">
                    @foreach ($product->images as $img)
                        <div class="w-20 h-16 rounded-xl overflow-hidden border border-slate-200 shrink-0">
                            <img src="{{ Storage::url($img->image) }}" alt="Preview" class="w-full h-full object-cover">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Detail Information & WhatsApp Ordering -->
        <div class="space-y-6">
            <div>
                @if ($product->category)
                    <span class="text-xs uppercase font-extrabold tracking-widest text-slate-400 block mb-1">
                        {{ $product->category->name }}
                    </span>
                @endif

                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 tracking-tight leading-tight">
                    {{ $product->name }}
                </h1>

                @if ($product->label)
                    <div class="mt-2.5">
                        <span class="inline-block px-3 py-1 rounded-md text-xs font-bold theme-bg-primary text-white">
                            {{ $product->label }}
                        </span>
                    </div>
                @endif
            </div>

            <!-- Price Card -->
            <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Investasi / Harga</span>
                <div class="flex items-baseline gap-3">
                    <span class="text-3xl sm:text-4xl font-extrabold theme-text-primary">
                        Rp {{ number_format($effectivePrice, 0, ',', '.') }}
                    </span>
                    @if ($hasDiscount)
                        <span class="text-base text-slate-400 line-through">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </span>
                    @endif
                </div>

                <div class="pt-2 flex items-center gap-2 text-xs text-slate-600">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ $product->stock > 0 ? 'Ketersediaan: ' . $product->stock . ' Unit' : 'Stok Menipis / Hubungi Kami' }}</span>
                </div>
            </div>

            <!-- Description -->
            <div class="space-y-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Spesifikasi & Informasi Detail</h3>
                <div class="text-sm text-slate-600 leading-relaxed space-y-2">
                    {!! nl2br(e($product->description ?? 'Deskripsi lengkap belum disediakan untuk produk/layanan ini.')) !!}
                </div>
            </div>

            <!-- Variant Options if any -->
            @if (!empty($product->variant_options) && is_array($product->variant_options))
                <div class="space-y-2 pt-1">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Pilihan Varian / Tipe</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($product->variant_options as $variant)
                            <span class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 bg-slate-50">
                                {{ is_string($variant) ? $variant : json_encode($variant) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Value Props Checklist -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 text-xs text-slate-700">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-[10px]">✓</span>
                    <span>100% Produk Berkualitas</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-[10px]">✓</span>
                    <span>Dukungan Pelanggan Cepat</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-[10px]">✓</span>
                    <span>Transaksi Aman & Transparan</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-[10px]">✓</span>
                    <span>Garansi Kepuasan Pelanggan</span>
                </div>
            </div>

            <!-- WhatsApp Direct Action -->
            <div class="pt-4 space-y-3">
                @if ($waUrl)
                    @php
                        $productWaUrl = $waGenerator->forProduct(
                            $business->whatsapp,
                            $business->name,
                            $product->name,
                            $effectivePrice,
                            !empty($product->variant_options) && is_array($product->variant_options) ? implode(', ', $product->variant_options) : null
                        );
                    @endphp
                    <a 
                        href="{{ $productWaUrl }}" 
                        target="_blank"
                        rel="noopener noreferrer"
                        onclick="trackWaClick(event, '{{ $productWaUrl }}', '{{ $website->slug }}', {{ $product->id }}, 'product_detail')"
                        class="w-full theme-bg-primary text-white py-4 px-6 rounded-2xl text-sm font-bold flex items-center justify-center gap-2.5 shadow-md hover:opacity-95 transition"
                    >
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                        </svg>
                        <span>Pesan / Konsultasi via WhatsApp</span>
                    </a>
                @endif

                <a 
                    href="{{ $backLink }}" 
                    class="w-full py-3 px-6 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition text-center block"
                >
                    Kembali ke Beranda & Katalog
                </a>
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="bg-slate-900 text-slate-400 py-8 px-4 text-center text-xs mt-auto border-t border-slate-800">
    <p>&copy; {{ date('Y') }} {{ $business?->name }}. Solusi Bisnis & Layanan Terpercaya. Didukung oleh Inamikro.</p>
</footer>
@endsection
