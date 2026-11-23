<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $business?->name ?? 'UMKM Store')</title>

    <!-- SEO Meta & Open Graph Tags -->
    <meta name="description" content="@yield('meta_description', $business?->description ?? $business?->tagline ?? 'Website resmi ' . ($business?->name ?? 'UMKM'))">
    <meta property="og:title" content="@yield('title', $business?->name ?? 'UMKM Store')">
    <meta property="og:description" content="@yield('meta_description', $business?->description ?? $business?->tagline ?? 'Website resmi ' . ($business?->name ?? 'UMKM'))">
    <meta property="og:type" content="website">
    @if ($business?->logo)
        <meta property="og:image" content="{{ Storage::url($business->logo) }}">
    @endif

    <!-- Dynamic Google Fonts CDN (via bunny.net) -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|poppins:400,500,600,700|roboto:400,500,700|plus-jakarta-sans:400,500,600,700|montserrat:400,600,700|playfair-display:400,600,700|outfit:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind & Alpine.js Asset Bundle -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dynamic CSS Variables from Website Theme -->
    <style>
        :root {
            --color-primary: {{ $website->theme?->primary_color ?? '#2563EB' }};
            --color-secondary: {{ $website->theme?->secondary_color ?? '#1E40AF' }};
            --font-family: '{{ $website->theme?->font_family ?? 'Inter' }}', sans-serif;
        }

        body {
            font-family: var(--font-family);
        }

        .bg-primary { background-color: var(--color-primary); }
        .text-primary { color: var(--color-primary); }
        .border-primary { border-color: var(--color-primary); }
        .hover\:bg-primary:hover { background-color: var(--color-primary); }

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

    <!-- Real-time postMessage Theme Listener for Studio Builder -->
    <script>
        window.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'UPDATE_THEME') {
                const { primaryColor, secondaryColor, fontFamily, font } = event.data.payload || {};
                if (primaryColor) {
                    document.documentElement.style.setProperty('--color-primary', primaryColor);
                }
                if (secondaryColor) {
                    document.documentElement.style.setProperty('--color-secondary', secondaryColor);
                }
                const chosenFont = fontFamily || font;
                if (chosenFont) {
                    document.documentElement.style.setProperty('--font-family', `'${chosenFont}', sans-serif`);
                    document.body.style.fontFamily = `'${chosenFont}', sans-serif`;
                }
            }
        });

        window.trackWaClick = function(event, url, slug, productId = null, source = 'catalog_card') {
            try {
                const payload = JSON.stringify({
                    product_id: productId ? parseInt(productId, 10) : null,
                    source: source
                });
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                fetch(`/${slug}/wa-click`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: payload,
                    keepalive: true
                }).catch(() => {});
            } catch (e) {}
        };
    </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col selection:bg-primary selection:text-white">
    @yield('content')
    {{ $slot ?? '' }}

    @php
        $floatingWaUrl = null;
        if (! empty($business?->whatsapp)) {
            $floatingWaUrl = app(\App\Services\WhatsAppUrlGenerator::class)->forGeneralInquiry(
                $business->whatsapp,
                $business->name
            );
        }
    @endphp

    @if ($floatingWaUrl && isset($website))
        <!-- Floating WhatsApp Contact Button -->
        <a 
            href="{{ $floatingWaUrl }}" 
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Hubungi via WhatsApp"
            onclick="window.trackWaClick(event, '{{ $floatingWaUrl }}', '{{ $website->slug }}', null, 'floating_button')"
            class="fixed bottom-6 right-6 z-50 bg-[#25D366] hover:bg-[#20bd5a] text-white p-3.5 sm:p-4 rounded-full shadow-lg hover:shadow-2xl hover:scale-110 active:scale-95 transition-all duration-300 flex items-center justify-center group focus:outline-hidden"
        >
            <span class="sr-only">Chat WhatsApp</span>
            <svg class="w-7 h-7 sm:w-8 sm:h-8 fill-current drop-shadow-xs" viewBox="0 0 24 24">
                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
            </svg>
        </a>
    @endif
</body>
</html>
