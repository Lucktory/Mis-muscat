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
        'Sohar Free Zone, Oman'          => [24.40,  56.60],
        'Salalah, Oman'                  => [16.94,  54.01],
        'Salalah Free Zone, Oman'        => [16.97,  54.05],
        'Muscat, Oman'                   => [23.61,  58.59],
        'Muscat Industrial Estate'       => [23.61,  58.59],
        'Jebel Ali, UAE'                 => [25.02,  55.13],
        'Antwerp, Belgium'               => [51.22,   4.40],
        'Houston (IAH), USA'             => [29.76, -95.36],
        'Doha, Qatar'                    => [25.30,  51.50],
        'Dar es Salaam, Tanzania'        => [ -6.82,  39.28],
        'Duqm, Oman'                     => [19.65,  57.71],
        'Sohar Industrial Estate'        => [24.34,  56.71],
        'Singapore'                      => [ 1.29, 103.85],
        'Frankfurt (FRA), Germany'       => [50.04,   8.56],
        'Umm Qasr, Iraq'                 => [30.04,  47.93],
        'Mombasa, Kenya'                 => [-4.04,  39.66],
        'Abu Dhabi, UAE'                 => [24.46,  54.36],
        'Yokohama, Japan'                => [35.45, 139.65],
        'Rotterdam, Netherlands'         => [51.95,   4.13],
        'Manama, Bahrain'                => [26.22,  50.59],
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
                        width: 14px; height: 14px;
                        box-shadow: 0 0 0 2px rgba(255,255,255,0.9);
                    }
                    .port-marker-div.destination {
                        border-color: #0f3b66;
                        background: #0f3b66;
                        width: 16px; height: 16px;
                    }
                    .port-label {
                        background: rgba(255,255,255,0.95);
                        border: 1px solid rgba(148, 163, 184, 0.4);
                        border-radius: 4px;
                        box-shadow: 0 1px 2px rgba(0,0,0,0.08);
                        color: #334155;
                        font-size: 11px;
                        font-weight: 500;
                        padding: 2px 7px;
                        white-space: nowrap;
                    }
                    .port-label.destination {
                        color: #0f3b66;
                        border-color: rgba(15, 59, 102, 0.4);
                    }
                    .port-label::before { display: none !important; }
                    .leaflet-tooltip.port-label:before { display: none !important; }
                    .leaflet-bar a.map-action-btn {
                        background: #ffffff;
                        color: #0f3b66;
                        display: flex; align-items: center; justify-content: center;
                        width: 30px; height: 30px;
                    }
                    .leaflet-bar a.map-action-btn:hover { background: #f1f5f9; }
                    .leaflet-bar a.map-action-btn svg { width: 16px; height: 16px; }
                    #vessel-map:fullscreen, #vessel-map:-webkit-full-screen {
                        width: 100vw !important; height: 100vh !important; background: #fff;
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

                        // Route points: origin → (waypoints, if defined) → current → destination
                        const routePoints = [];
                        @php $waypoints = $booking['route_waypoints'] ?? null; @endphp
                        @if($waypoints)
                        const waypoints = @json($waypoints);
                        @else
                        const waypoints = [];
                        @endif

                        @if($originCoords)
                        const originLatLng = [{{ $originCoords[0] }}, {{ $originCoords[1] }}];
                        const originIcon = L.divIcon({
                            className: 'port-marker-div',
                            iconSize: [14, 14],
                            iconAnchor: [7, 7],
                        });
                        L.marker(originLatLng, { icon: originIcon }).addTo(map)
                            .bindTooltip("{{ addslashes($booking['origin']) }}", {
                                permanent: true, direction: 'bottom', offset: [0, 6], className: 'port-label'
                            });
                        routePoints.push(originLatLng);
                        @endif

                        // Include waypoints in the bounds so the whole shipping lane is visible on initial fit
                        for (const wp of waypoints) { routePoints.push(wp); }
                        routePoints.push([lat, lng]);

                        @if($destCoords)
                        const destLatLng = [{{ $destCoords[0] }}, {{ $destCoords[1] }}];
                        const destIcon = L.divIcon({
                            className: 'port-marker-div destination',
                            iconSize: [16, 16],
                            iconAnchor: [8, 8],
                        });
                        L.marker(destLatLng, { icon: destIcon }).addTo(map)
                            .bindTooltip("{{ addslashes($booking['destination']) }}", {
                                permanent: true, direction: 'bottom', offset: [0, 7], className: 'port-label destination'
                            });
                        routePoints.push(destLatLng);
                        @endif

                        // Route polyline: realistic path through shipping-lane waypoints
                        // (Strait of Hormuz, Bab-el-Mandeb, Suez, Gibraltar, etc.) when defined,
                        // otherwise straight lines origin → vessel → destination.
                        if (waypoints.length > 0 && {{ $originCoords ? 'true' : 'false' }} && {{ $destCoords ? 'true' : 'false' }}) {
                            const fullPath = [originLatLng, ...waypoints, destLatLng];
                            const currentLatLng = [lat, lng];

                            // Find the waypoint closest to current vessel position to split sailed/remaining
                            let closestIdx = 0;
                            let closestDist = Infinity;
                            for (let i = 0; i < fullPath.length; i++) {
                                const d = Math.sqrt(
                                    Math.pow(fullPath[i][0] - lat, 2) +
                                    Math.pow(fullPath[i][1] - lng, 2)
                                );
                                if (d < closestDist) { closestDist = d; closestIdx = i; }
                            }

                            const sailedPath    = fullPath.slice(0, closestIdx + 1).concat([currentLatLng]);
                            const remainingPath = [currentLatLng].concat(fullPath.slice(closestIdx + 1));

                            L.polyline(sailedPath, {
                                color: '#0f3b66', weight: 2.5, opacity: 0.75,
                            }).addTo(map);
                            L.polyline(remainingPath, {
                                color: '#3b82f6', weight: 2, opacity: 0.55, dashArray: '6, 8',
                            }).addTo(map);
                        } else {
                            @if($originCoords)
                            L.polyline([originLatLng, [lat, lng]], {
                                color: '#0f3b66', weight: 2, opacity: 0.7,
                            }).addTo(map);
                            @endif
                            @if($destCoords)
                            L.polyline([[lat, lng], destLatLng], {
                                color: '#3b82f6', weight: 2, opacity: 0.5, dashArray: '6, 8',
                            }).addTo(map);
                            @endif
                        }

                        // Fit map to all route points initially
                        const routeBounds = L.latLngBounds(routePoints);
                        if (routePoints.length > 1) {
                            map.fitBounds(routeBounds, { padding: [50, 50], maxZoom: 6 });
                        }

                        // "Center on vessel" + "Show full route" map controls
                        const MapActionControl = L.Control.extend({
                            options: { position: 'topright' },
                            onAdd: function () {
                                const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');

                                const focusBtn = L.DomUtil.create('a', 'map-action-btn', div);
                                focusBtn.href = '#';
                                focusBtn.title = 'Centrar en el barco / Center on vessel';
                                focusBtn.setAttribute('role', 'button');
                                focusBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="2.5"/><circle cx="12" cy="12" r="8.5"/><line x1="12" y1="2.5" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="21.5"/><line x1="2.5" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="21.5" y2="12"/></svg>';
                                L.DomEvent.on(focusBtn, 'click', function (e) {
                                    L.DomEvent.preventDefault(e);
                                    L.DomEvent.stopPropagation(e);
                                    map.flyTo([lat, lng], 7, { duration: 0.8 });
                                });

                                const routeBtn = L.DomUtil.create('a', 'map-action-btn', div);
                                routeBtn.href = '#';
                                routeBtn.title = 'Ver ruta completa / Show full route';
                                routeBtn.setAttribute('role', 'button');
                                routeBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="5 4 5 11 11 11 11 17"/><circle cx="5" cy="4" r="1.5"/><circle cx="11" cy="17" r="1.5" fill="currentColor"/><polyline points="11 17 18 17 18 6"/><circle cx="18" cy="6" r="1.5"/></svg>';
                                L.DomEvent.on(routeBtn, 'click', function (e) {
                                    L.DomEvent.preventDefault(e);
                                    L.DomEvent.stopPropagation(e);
                                    if (routePoints.length > 1) {
                                        map.flyToBounds(routeBounds, { padding: [50, 50], maxZoom: 6, duration: 0.8 });
                                    }
                                });

                                const fsBtn = L.DomUtil.create('a', 'map-action-btn', div);
                                fsBtn.href = '#';
                                fsBtn.title = 'Pantalla completa / Fullscreen';
                                fsBtn.setAttribute('role', 'button');
                                const enterIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 9 4 4 9 4"/><polyline points="20 9 20 4 15 4"/><polyline points="4 15 4 20 9 20"/><polyline points="20 15 20 20 15 20"/></svg>';
                                const exitIcon  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 4 4 4 4 9"/><polyline points="15 4 20 4 20 9"/><polyline points="9 20 4 20 4 15"/><polyline points="15 20 20 20 20 15"/></svg>';
                                fsBtn.innerHTML = enterIcon;
                                L.DomEvent.on(fsBtn, 'click', function (e) {
                                    L.DomEvent.preventDefault(e);
                                    L.DomEvent.stopPropagation(e);
                                    const el = document.getElementById('vessel-map');
                                    if (!document.fullscreenElement) {
                                        if (el.requestFullscreen) el.requestFullscreen();
                                        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
                                    } else {
                                        if (document.exitFullscreen) document.exitFullscreen();
                                        else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
                                    }
                                });
                                document.addEventListener('fullscreenchange', function () {
                                    fsBtn.innerHTML = document.fullscreenElement ? exitIcon : enterIcon;
                                    setTimeout(function () { map.invalidateSize(); }, 120);
                                });
                                document.addEventListener('webkitfullscreenchange', function () {
                                    fsBtn.innerHTML = document.fullscreenElement ? exitIcon : enterIcon;
                                    setTimeout(function () { map.invalidateSize(); }, 120);
                                });

                                L.DomEvent.disableClickPropagation(div);
                                return div;
                            }
                        });
                        new MapActionControl().addTo(map);
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
