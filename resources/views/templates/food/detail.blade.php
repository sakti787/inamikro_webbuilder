@extends('templates.layouts.base')

@section('title', ($product->name ?? 'Menu') . ' - ' . ($business?->name ?? 'Kuliner Nusantara'))
@section('meta_description', Str::limit(strip_tags($product->description ?? ''), 160))

@section('content')
@php
    $business = $website->business;
    $rawWa = preg_replace('/[^0-9]/', '', $business?->whatsapp ?? '');
    $waNumber = str_starts_with($rawWa, '0') ? '62' . substr($rawWa, 1) : $rawWa;
    $waUrl = $waNumber ? 'https://wa.me/' . $waNumber : null;
    $hasDiscount = $product->discount_price && $product->discount_price < $product->price;
    $effectivePrice = $hasDiscount ? $product->discount_price : $product->price;
    $primaryImg = $product->primaryImage?->image ?? $product->images->first()?->image;
    $backLink = request()->routeIs('builder.preview') ? route('builder.preview', $website) : url()->previous();
@endphp

<!-- Navigation Header -->
<header class="bg-white border-b border-amber-100 sticky top-0 z-30 shadow-xs">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-18 flex items-center justify-between">
        <a href="{{ $backLink }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-700 hover:text-primary transition py-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Menu</span>
        </a>

        <span class="text-sm font-black text-slate-900">{{ $business?->name }}</span>

        @if ($waUrl)
            <a 
                href="{{ $waUrl }}" 
                target="_blank"
                class="theme-bg-primary text-white px-4 py-2 rounded-xl text-xs font-bold shadow-xs hover:opacity-95 transition"
            >
                Order Meja / Antar
            </a>
        @else
            <div></div>
        @endif
    </div>
</header>

<!-- Main Food Detail View -->
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8 sm:py-14">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-amber-800/70 mb-6 font-medium">
        <a href="{{ $backLink }}" class="hover:underline">Menu</a>
        <span>/</span>
        @if ($product->category)
            <span>{{ $product->category->name }}</span>
            <span>/</span>
        @endif
        <span class="text-slate-900 font-bold truncate">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 bg-white rounded-3xl p-6 sm:p-10 border border-amber-100 shadow-sm items-start">
        <!-- Food Image Showcase -->
        <div class="space-y-4">
            <div class="aspect-4/3 rounded-2xl bg-amber-50 overflow-hidden relative border border-amber-100 shadow-xs">
                @if ($primaryImg)
                    <img src="{{ Storage::url($primaryImg) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-amber-300">
                        <svg class="w-20 h-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                @endif

                @if ($hasDiscount)
                    <span class="absolute top-4 left-4 bg-red-600 text-white text-xs font-black px-3 py-1 rounded-lg shadow-md uppercase">
                        Harga Spesial
                    </span>
                @elseif ($product->label)
                    <span class="absolute top-4 left-4 theme-bg-primary text-white text-xs font-bold px-3 py-1 rounded-lg shadow-md">
                        {{ $product->label }}
                    </span>
                @endif
            </div>

            <!-- Extra Food Photos Carousel/Thumbs -->
            @if ($product->images && $product->images->count() > 1)
                <div class="flex gap-2.5 overflow-x-auto pb-1">
                    @foreach ($product->images as $img)
                        <div class="w-16 h-16 rounded-xl overflow-hidden border border-amber-200 shrink-0">
                            <img src="{{ Storage::url($img->image) }}" alt="Foto Menu" class="w-full h-full object-cover">
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Food Details & Ordering Action -->
        <div class="space-y-6">
            <div>
                @if ($product->category)
                    <span class="text-xs font-black uppercase tracking-wider text-amber-700 block mb-1">
                        {{ $product->category->name }}
                    </span>
                @endif

                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-snug">
                    {{ $product->name }}
                </h1>

                @if ($product->label)
                    <div class="mt-2">
                        <span class="inline-block px-3 py-0.5 rounded-md text-xs font-black theme-bg-primary text-white">
                            {{ $product->label }}
                        </span>
                    </div>
                @endif
            </div>

            <!-- Price Highlight -->
            <div class="bg-amber-50/70 rounded-2xl p-4 border border-amber-200/80">
                <span class="text-[11px] font-bold text-slate-500 uppercase block mb-1">Harga Menu</span>
                <div class="flex items-baseline gap-2.5">
                    <span class="text-3xl font-black theme-text-primary">
                        Rp {{ number_format($effectivePrice, 0, ',', '.') }}
                    </span>
                    @if ($hasDiscount)
                        <span class="text-sm text-slate-400 line-through">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </span>
                    @endif
                </div>
                <div class="mt-2 text-xs text-amber-900 flex items-center gap-1.5 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Siap Dimasak & Disajikan Hangat</span>
                </div>
            </div>

            <!-- Description / Ingredients -->
            <div class="space-y-2">
                <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">Deskripsi & Racikan Menu</h3>
                <div class="text-sm text-slate-600 leading-relaxed">
                    {!! nl2br(e($product->description ?? 'Hidangan istimewa yang dibuat dengan bahan berkualitas dan higienis.')) !!}
                </div>
            </div>

            <!-- Operating Hours & Delivery Note -->
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1">
                <div class="flex items-center gap-1.5 font-bold text-slate-800">
                    <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Jam Buka Resto/Kafe:</span>
                </div>
                <p>
                    {{ is_array($business?->operating_hours) ? (array_values($business->operating_hours)[0] ?? 'Buka Setiap Hari') : ($business?->operating_hours ?? 'Buka Setiap Hari') }}
                </p>
            </div>

            <!-- Direct WhatsApp Fast Order Button -->
            <div class="space-y-2.5 pt-2">
                @if ($waUrl)
                    @php
                        $foodMessage = "Halo {$business?->name}, saya ingin memesan menu:\n"
                            . "- {$product->name}\n"
                            . "- Harga: Rp " . number_format($effectivePrice, 0, ',', '.') . "\n"
                            . "Mohon info ketersediaan dan estimasi waktu penyajian.";
                    @endphp
                    <a 
                        href="{{ $waUrl }}?text={{ urlencode($foodMessage) }}" 
                        target="_blank"
                        class="w-full theme-bg-primary text-white py-3.5 px-6 rounded-2xl text-sm font-bold flex items-center justify-center gap-2 shadow-md hover:opacity-95 transition"
                    >
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                        </svg>
                        <span>Pesan Menu Ini via WhatsApp</span>
                    </a>
                @endif

                <a 
                    href="{{ $backLink }}" 
                    class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition text-center block"
                >
                    Kembali ke Daftar Menu Lengkap
                </a>
            </div>
        </div>
    </div>
</main>

<!-- Footer -->
<footer class="bg-slate-950 text-slate-400 py-6 px-4 text-center text-xs mt-auto">
    <p>&copy; {{ date('Y') }} {{ $business?->name }}. Kuliner Pilihan Terbaik. Didukung oleh Inamikro.</p>
</footer>
@endsection
