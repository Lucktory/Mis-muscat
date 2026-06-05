<!DOCTYPE html>
<html lang="en" class="bg-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'MIS Logistics — Customer Tracking' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans text-slate-700 antialiased">

    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('logo.png') }}" alt="MIS Logistics" class="h-10 w-auto">
                <span class="hidden text-sm font-semibold tracking-wide text-slate-900 sm:inline">
                    Muscat International Shipping &amp; Logistics
                </span>
            </a>
            <nav class="hidden gap-6 text-sm font-medium text-slate-600 md:flex">
                <a href="{{ route('home') }}" class="hover:text-[#0f3b66]">Track shipment</a>
                <a href="#contact" class="hover:text-[#0f3b66]">Contact</a>
            </nav>
        </div>
    </header>

    <main class="min-h-[calc(100vh-140px)]">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <footer id="contact" class="border-t border-slate-200 bg-slate-50">
        <div class="mx-auto max-w-7xl px-6 py-10 text-sm text-slate-600">
            <div class="grid gap-8 md:grid-cols-3">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Muscat</div>
                    <p class="mt-2 leading-relaxed">
                        Office 104, Al Khaleel Building, Way 240, Building 439,<br>
                        Al Ghubra North, Muscat, Sultanate of Oman
                    </p>
                    <p class="mt-2">+968 2449 8710 &middot; +968 2449 4680</p>
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-900">Sohar</div>
                    <p class="mt-2">+968 2449 8177 &middot; +968 9730 3264</p>
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-900">General enquiries</div>
                    <p class="mt-2">info@mismuscat.com</p>
                </div>
            </div>
            <div class="mt-8 border-t border-slate-200 pt-6 text-xs text-slate-500">
                Customer-facing tracking demonstration. Bookings and tracking IDs in this view are illustrative; the technical layer is real.
            </div>
        </div>
    </footer>

    @livewire('chat-widget')

    @livewireScripts
</body>
</html>
