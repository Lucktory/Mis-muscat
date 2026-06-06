@extends('layouts.customer')

@php
    $statusStyles = [
        'in_transit' => ['bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'border' => 'border-blue-200'],
        'at_port'    => ['bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'border' => 'border-blue-200'],
        'picked_up'  => ['bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'border' => 'border-blue-200'],
        'booked'     => ['bg' => 'bg-slate-100', 'text' => 'text-slate-700',  'border' => 'border-slate-200'],
        'delivered'  => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600',  'border' => 'border-slate-200'],
        'exception'  => ['bg' => 'bg-red-50',    'text' => 'text-red-700',    'border' => 'border-red-200'],
    ];
    $style = $statusStyles[$booking['status_code']] ?? $statusStyles['booked'];
    $position = app(\App\Services\AisClient::class)->latest($booking['vessel_mmsi'] ?? null);

    // Port gazetteer for origin/destination markers and route polyline
    $ports = [
        'Mumbai, India'                  => [19.05,  72.85],
        'Mumbai (BOM), India'            => [19.09,  72.86],
        'Mumbai (NSA), India'            => [18.94,  72.95],
        'Sohar, Oman'                    => [24.34,  56.71],
        'Sohar Port, Oman'               => [24.50,  56.65],
        'Salalah, Oman'                  => [16.94,  54.01],
        'Muscat, Oman'                   => [23.61,  58.59],
        'Muscat Industrial Estate'       => [23.61,  58.59],
        'Jebel Ali, UAE'                 => [25.02,  55.13],
        'Antwerp, Belgium'               => [51.22,   4.40],
        'Houston (IAH), USA'             => [29.76, -95.36],
        'Doha, Qatar'                    => [25.30,  51.50],
        'Dar es Salaam, Tanzania'        => [ -6.82,  39.28],
        'Duqm, Oman'                     => [19.65,  57.71],
        'Sohar Industrial Estate'        => [24.34,  56.71],
    ];
    $originCoords = $ports[$booking['origin']] ?? null;
    $destCoords   = $ports[$booking['destination']] ?? null;
@endphp

@section('content')
<section class="bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex flex-wrap items-baseline justify-between gap-4">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Booking</div>
                <h1 class="mt-1 font-mono text-2xl font-semibold tracking-tight text-slate-900">{{ $id }}</h1>
            </div>
            <div>
                <span class="inline-flex items-center rounded-md border {{ $style['border'] }} {{ $style['bg'] }} px-3 py-1 text-sm font-medium {{ $style['text'] }}">
                    {{ $booking['status_label'] }}
                </span>
            </div>
        </div>
        <p class="mt-2 text-sm text-slate-600">{{ $booking['origin'] }} &nbsp;&rarr;&nbsp; {{ $booking['destination'] }}</p>
    </div>
</section>

<section class="bg-white">
    <div class="mx-auto grid max-w-7xl gap-8 px-6 py-10 lg:grid-cols-3">

        <div class="lg:col-span-2 space-y-8">

            <div class="overflow-hidden rounded-lg border border-slate-200">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <div class="flex items-baseline justify-between">
                        <h2 class="text-sm font-semibold text-slate-900">Vessel position</h2>
                        @if($position && ($position['source'] ?? null) === 'aisstream.io')
                            <span class="text-xs text-blue-700">
                                Live AIS &middot; received {{ \Illuminate\Support\Carbon::parse($position['received_at'] ?? now())->diffForHumans() }}
                                <span class="ml-1 inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-blue-500 align-middle"></span>
                            </span>
                        @elseif($position)
                            <span class="text-xs text-slate-500">Last known position</span>
                        @else
                            <span class="text-xs text-slate-500">No AIS feed (intra-Oman or air freight)</span>
                        @endif
                    </div>
                </div>

                @if($position)
                <div id="vessel-map" class="h-80 w-full"></div>
                @push('scripts')
                <style>
                    .vessel-marker-div { position: relative; width: 40px; height: 40px; }
                    .vessel-dot {
                        position: absolute; left: 50%; top: 50%;
                        transform: translate(-50%, -50%);
                        width: 14px; height: 14px;
                        background: #3b82f6;
                        border: 2px solid #0f3b66;
                        border-radius: 50%;
                        z-index: 2;
                        box-shadow: 0 0 0 2px rgba(255,255,255,0.9);
                    }
                    .vessel-pulse-ring {
                        position: absolute; left: 50%; top: 50%;
                        transform: translate(-50%, -50%);
                        width: 14px; height: 14px;
                        border: 2px solid #3b82f6;
                        border-radius: 50%;
                        opacity: 0;
                        animation: vessel-pulse 1.8s ease-out infinite;
                        z-index: 1;
                    }
                    .vessel-pulse-ring.delay { animation-delay: 0.9s; }
                    @keyframes vessel-pulse {
                        0%   { transform: translate(-50%, -50%) scale(0.6); opacity: 0.85; }
                        100% { transform: translate(-50%, -50%) scale(4.5); opacity: 0; }
                    }
                    .port-marker-div {
                        background: #ffffff;
                        border: 2px solid #475569;
                        border-radius: 50%;
                        width: 12px; height: 12px;
                    }
                    .port-marker-div.destination {
                        border-color: #0f3b66;
                        background: #0f3b66;
                        width: 14px; height: 14px;
                    }
                </style>
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
                        crossorigin=""></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const lat = {{ $position['lat'] }};
                        const lng = {{ $position['lng'] }};
                        const map = L.map('vessel-map', {
                            zoomControl: true,
                            attributionControl: false,
                        }).setView([lat, lng], 4);
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                            maxZoom: 19,
                        }).addTo(map);

                        // Pulsing vessel marker (current position)
                        const vesselIcon = L.divIcon({
                            className: 'vessel-marker-div',
                            html: '<div class="vessel-pulse-ring"></div>' +
                                  '<div class="vessel-pulse-ring delay"></div>' +
                                  '<div class="vessel-dot"></div>',
                            iconSize: [40, 40],
                            iconAnchor: [20, 20],
                        });
                        const vesselMarker = L.marker([lat, lng], { icon: vesselIcon }).addTo(map);
                        vesselMarker.bindTooltip(
                            "{{ ($position['name'] ?? null) ? 'MV ' . addslashes($position['name']) : addslashes($booking['vessel_name']) }}" +
                            "<br><span style='font-size:10px;color:#64748b'>Current position</span>",
                            { permanent: false, direction: 'top', offset: [0, -8] }
                        );

                        // Route points: origin → current → destination
                        const routePoints = [];

                        @if($originCoords)
                        const originLatLng = [{{ $originCoords[0] }}, {{ $originCoords[1] }}];
                        const originIcon = L.divIcon({
                            className: 'port-marker-div',
                            iconSize: [12, 12],
                            iconAnchor: [6, 6],
                        });
                        L.marker(originLatLng, { icon: originIcon }).addTo(map)
                            .bindTooltip("Origin: {{ addslashes($booking['origin']) }}", { permanent: false, direction: 'top', offset: [0, -4] });
                        routePoints.push(originLatLng);
                        @endif

                        routePoints.push([lat, lng]);

                        @if($destCoords)
                        const destLatLng = [{{ $destCoords[0] }}, {{ $destCoords[1] }}];
                        const destIcon = L.divIcon({
                            className: 'port-marker-div destination',
                            iconSize: [14, 14],
                            iconAnchor: [7, 7],
                        });
                        L.marker(destLatLng, { icon: destIcon }).addTo(map)
                            .bindTooltip("Destination: {{ addslashes($booking['destination']) }}", { permanent: false, direction: 'top', offset: [0, -4] });
                        routePoints.push(destLatLng);
                        @endif

                        // Route polyline: solid completed leg + dashed remaining leg
                        @if($originCoords)
                        L.polyline([routePoints[0], [lat, lng]], {
                            color: '#0f3b66', weight: 2, opacity: 0.7,
                        }).addTo(map);
                        @endif
                        @if($destCoords)
                        L.polyline([[lat, lng], routePoints[routePoints.length - 1]], {
                            color: '#3b82f6', weight: 2, opacity: 0.5, dashArray: '6, 8',
                        }).addTo(map);
                        @endif

                        // Fit map to all route points
                        if (routePoints.length > 1) {
                            map.fitBounds(L.latLngBounds(routePoints), { padding: [50, 50], maxZoom: 6 });
                        }
                    });
                </script>
                @endpush
                @else
                <div class="px-4 py-12 text-center text-sm text-slate-500">
                    {{ $booking['last_position'] }}
                </div>
                @endif

                <div class="border-t border-slate-200 bg-white px-4 py-3 text-xs text-slate-600">
                    <span class="font-medium text-slate-900">
                        {{ ($position['name'] ?? null) ? 'MV ' . $position['name'] : $booking['vessel_name'] }}
                    </span>
                    @if($position)
                        &middot; {{ number_format($position['lat'], 2) }}&deg;, {{ number_format($position['lng'], 2) }}&deg;
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Audit trail</h2>
                </div>
                <ol class="divide-y divide-slate-100">
                    @foreach(array_reverse($booking['audit']) as $event)
                    <li class="px-4 py-3">
                        <div class="flex items-baseline justify-between gap-4">
                            <div>
                                <div class="font-mono text-xs text-slate-500">{{ $event['ts'] }}</div>
                                <div class="mt-0.5 text-sm font-medium text-slate-900">{{ str_replace('_', ' ', $event['action']) }}</div>
                                <div class="mt-0.5 text-sm text-slate-600">{{ $event['detail'] }}</div>
                            </div>
                            <div class="shrink-0 text-xs text-slate-500">{{ $event['actor'] }}</div>
                        </div>
                    </li>
                    @endforeach
                </ol>
                <div class="border-t border-slate-200 bg-slate-50 px-4 py-2 text-xs text-slate-500">
                    Every event is timestamped, attributed, and immutable.
                </div>
            </div>

        </div>

        <aside class="space-y-6">
            <div class="overflow-hidden rounded-lg border border-slate-200">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Shipment</h2>
                </div>
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="grid grid-cols-3 gap-3 px-4 py-2.5">
                        <dt class="text-slate-500">Cargo</dt>
                        <dd class="col-span-2 text-slate-900">{{ $booking['cargo'] }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-4 py-2.5">
                        <dt class="text-slate-500">Container</dt>
                        <dd class="col-span-2 font-mono text-slate-900">{{ $booking['container_no'] }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-4 py-2.5">
                        <dt class="text-slate-500">Shipper</dt>
                        <dd class="col-span-2 text-slate-900">{{ $booking['shipper'] }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-4 py-2.5">
                        <dt class="text-slate-500">Consignee</dt>
                        <dd class="col-span-2 text-slate-900">{{ $booking['consignee'] }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-4 py-2.5">
                        <dt class="text-slate-500">ETA</dt>
                        <dd class="col-span-2 text-slate-900">{{ $booking['eta_label'] }}</dd>
                    </div>
                    <div class="grid grid-cols-3 gap-3 px-4 py-2.5">
                        <dt class="text-slate-500">Customs</dt>
                        <dd class="col-span-2 text-slate-900">{{ $booking['customs'] }}</dd>
                    </div>
                </dl>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">Documents</h2>
                </div>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach($booking['documents'] as $name => $state)
                    <li class="flex items-center justify-between px-4 py-2.5">
                        <span class="font-medium text-slate-900">{{ $name }}</span>
                        @if($state === 'available')
                            <span class="rounded bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">Available</span>
                        @elseif($state === 'pending')
                            <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Pending</span>
                        @else
                            <span class="rounded bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">{{ ucfirst($state) }}</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
                <div class="border-t border-slate-200 bg-slate-50 px-4 py-2 text-xs text-slate-500">
                    Document downloads available after identity verification.
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Need a person?</div>
                <p class="mt-2 text-slate-700">
                    WhatsApp <span class="font-medium text-slate-900">Joju Pradeep</span>, Commercial Manager,
                    or use the chat widget at the bottom of this page.
                </p>
            </div>
        </aside>

    </div>
</section>

@stack('scripts')
@endsection
