<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $business?->name ?? 'Preview Website' }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|poppins:400,500,600,700|roboto:400,500,700|plus-jakarta-sans:400,500,600,700|montserrat:400,600,700|playfair-display:400,600,700|outfit:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --color-primary: {{ $website->theme->primary_color ?? '#2563EB' }};
            --color-secondary: {{ $website->theme->secondary_color ?? '#1E40AF' }};
            --font-family: '{{ $website->theme->font_family ?? 'Inter' }}', sans-serif;
        }

        body {
            font-family: var(--font-family);
        }

        .theme-bg-primary {
            background-color: var(--color-primary) !important;
        }

        .theme-bg-secondary {
            background-color: var(--color-secondary) !important;
        }

        .theme-text-primary {
            color: var(--color-primary) !important;
        }

        .theme-text-secondary {
            color: var(--color-secondary) !important;
        }

        .theme-border-primary {
            border-color: var(--color-primary) !important;
        }
    </style>

    <script>
        window.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'UPDATE_THEME') {
                const { primaryColor, secondaryColor, fontFamily } = event.data.payload;
                if (primaryColor) document.documentElement.style.setProperty('--color-primary', primaryColor);
                if (secondaryColor) document.documentElement.style.setProperty('--color-secondary', secondaryColor);
                if (fontFamily) document.documentElement.style.setProperty('--font-family', `'${fontFamily}', sans-serif`);
            }
        });
    </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">
    @if ($templateViewExists)
        @include($website->template->view_path, ['website' => $website, 'business' => $business])
    @else
        <!-- Top Announcement / Topbar -->
        <div class="theme-bg-secondary text-white py-2 px-4 text-center text-xs font-medium">
            <span>Selamat datang di toko resmi {{ $business?->name }}</span>
        </div>

        <!-- Navigation Header -->
        <header class="bg-white border-b border-slate-100 shadow-xs sticky top-0 z-20">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if ($business?->logo)
                        <img src="{{ Storage::url($business->logo) }}" alt="{{ $business->name }}" class="w-10 h-10 rounded-xl object-cover shadow-xs">
                    @else
                        <div class="w-10 h-10 rounded-xl theme-bg-primary text-white flex items-center justify-center font-bold text-lg shadow-sm">
                            {{ strtoupper(substr($business?->name ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h1 class="text-base sm:text-lg font-bold tracking-tight text-slate-900 leading-tight">
                            {{ $business?->name ?? 'UMKM Store' }}
                        </h1>
                        @if ($business?->tagline)
                            <p class="text-xs text-slate-500 font-normal leading-none mt-0.5">{{ $business->tagline }}</p>
                        @endif
                    </div>
                </div>

                @if ($business?->whatsapp)
                    <a 
                        href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $business->whatsapp) }}" 
                        target="_blank"
                        class="theme-bg-primary text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-xs flex items-center gap-2 hover:opacity-95 transition"
                    >
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                        </svg>
                        <span>Hubungi Kami</span>
                    </a>
                @endif
            </div>
        </header>

        <!-- Hero / Banner Section -->
        @php
            $activeBanners = $business?->banners ? $business->banners->where('is_active', true) : collect();
        @endphp

        @if ($activeBanners->isNotEmpty())
            <section class="max-w-6xl mx-auto px-4 sm:px-6 pt-6">
                <div class="rounded-2xl overflow-hidden shadow-sm relative aspect-21/9 bg-slate-200">
                    @php $firstBanner = $activeBanners->first(); @endphp
                    <img src="{{ Storage::url($firstBanner->image_path) }}" alt="{{ $firstBanner->title ?? 'Banner' }}" class="w-full h-full object-cover">
                    @if ($firstBanner->title || $firstBanner->subtitle)
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent flex flex-col justify-end p-6 text-white">
                            @if ($firstBanner->title)
                                <h2 class="text-xl sm:text-2xl font-bold">{{ $firstBanner->title }}</h2>
                            @endif
                            @if ($firstBanner->subtitle)
                                <p class="text-sm text-slate-200 mt-1">{{ $firstBanner->subtitle }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </section>
        @else
            <!-- Default Hero Section -->
            <section class="py-12 px-4 bg-gradient-to-b from-slate-100 to-slate-50 border-b border-slate-200/60">
                <div class="max-w-3xl mx-auto text-center space-y-4">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold theme-text-primary bg-white shadow-xs border border-slate-200">
                        {{ $business?->category?->name ?? 'UMKM Pilihan' }}
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                        {{ $business?->name }}
                    </h2>
                    <p class="text-sm sm:text-base text-slate-600 max-w-xl mx-auto leading-relaxed">
                        {{ $business?->description ?? 'Menyediakan aneka produk dan layanan berkualitas tinggi dengan harga ramah untuk seluruh pelanggan kami.' }}
                    </p>
                    @if ($business?->whatsapp)
                        <div class="pt-2">
                            <a 
                                href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $business->whatsapp) }}" 
                                target="_blank"
                                class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white theme-bg-primary shadow-md hover:shadow-lg transition"
                            >
                                <span>Pesan Sekarang via WhatsApp</span>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <!-- Main Content Area -->
        <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 py-10 space-y-12">
            <!-- Products Catalog Section -->
            <section>
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900 tracking-tight">Katalog Produk</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Pilihan produk terbaik dari kami</p>
                    </div>
                </div>

                @php
                    $products = $business?->products ? $business->products->where('is_available', true) : collect();
                @endphp

                @if ($products->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                        @foreach ($products as $product)
                            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col hover:shadow-md transition">
                                <div class="aspect-square bg-slate-100 relative overflow-hidden">
                                    @if ($product->primaryImage)
                                        <img src="{{ Storage::url($product->primaryImage->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-slate-300">
                                            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    @if ($product->discount_price && $product->discount_price < $product->price)
                                        <span class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-xs">
                                            Promo
                                        </span>
                                    @endif
                                </div>
                                <div class="p-4 flex-1 flex flex-col justify-between">
                                    <div>
                                        <h4 class="font-semibold text-slate-900 text-sm line-clamp-2">{{ $product->name }}</h4>
                                        <div class="mt-2 flex items-baseline gap-1.5">
                                            @if ($product->discount_price && $product->discount_price < $product->price)
                                                <span class="text-sm font-bold theme-text-primary">
                                                    Rp {{ number_format($product->discount_price, 0, ',', '.') }}
                                                </span>
                                                <span class="text-xs text-slate-400 line-through">
                                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-sm font-bold theme-text-primary">
                                                    Rp {{ number_format($product->price, 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                        <span class="text-[11px] text-slate-500">Stok: {{ $product->stock }}</span>
                                        @if ($business?->whatsapp)
                                            <a 
                                                href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $business->whatsapp) }}?text={{ urlencode('Halo ' . $business->name . ', saya tertarik dengan produk ' . $product->name) }}"
                                                target="_blank"
                                                class="px-2.5 py-1 rounded-lg text-xs font-semibold theme-bg-primary text-white hover:opacity-90 transition"
                                            >
                                                Pesan
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-8 text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-700">Belum ada produk ditampilkan</h4>
                        <p class="text-xs text-slate-500 mt-1">Tambahkan produk dari dashboard untuk menampilkan katalog di sini.</p>
                    </div>
                @endif
            </section>

            <!-- Business Information & Contact -->
            <section class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">Tentang {{ $business?->name }}</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">
                            {{ $business?->description ?? 'Kami berkomitmen untuk memberikan kualitas terbaik dan kepuasan pelanggan adalah prioritas utama kami.' }}
                        </p>
                        @if ($business?->address)
                            <div class="mt-4 flex items-start gap-2.5 text-xs text-slate-600">
                                <svg class="w-4 h-4 text-slate-400 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ $business->address }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Kontak & Operasional</h4>
                        <div class="space-y-2 text-xs text-slate-600">
                            @if ($business?->whatsapp)
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-slate-800 w-24">WhatsApp:</span>
                                    <span>{{ $business->whatsapp }}</span>
                                </div>
                            @endif
                            @if ($business?->phone)
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-slate-800 w-24">Telepon:</span>
                                    <span>{{ $business->phone }}</span>
                                </div>
                            @endif
                            @if ($business?->email)
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-slate-800 w-24">Email:</span>
                                    <span>{{ $business->email }}</span>
                                </div>
                            @endif
                            @if ($business?->operating_hours)
                                <div class="pt-2 border-t border-slate-100">
                                    <span class="font-medium text-slate-800 block mb-1">Jam Buka:</span>
                                    @if (is_array($business->operating_hours))
                                        <div class="grid grid-cols-2 gap-1 text-[11px]">
                                            @foreach ($business->operating_hours as $day => $hours)
                                                <div class="capitalize text-slate-500">{{ $day }}:</div>
                                                <div class="font-medium text-slate-700">{{ $hours }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span>{{ $business->operating_hours }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="bg-slate-900 text-slate-400 py-8 px-4 text-center text-xs mt-auto">
            <p>&copy; {{ date('Y') }} {{ $business?->name }}. Didukung oleh Inamikro.</p>
        </footer>
    @endif
</body>
</html>
