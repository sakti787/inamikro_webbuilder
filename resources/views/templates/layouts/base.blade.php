<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col selection:bg-primary selection:text-white">
    @yield('content')
    {{ $slot ?? '' }}
</body>
</html>
