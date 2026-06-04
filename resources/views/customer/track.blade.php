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
                        @if($position)
                            <span class="text-xs text-slate-500">
                                Live AIS &middot; refreshed
                                <span class="ml-1 inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-blue-500 align-middle"></span>
                            </span>
                        @else
                            <span class="text-xs text-slate-500">No AIS feed (intra-Oman or air freight)</span>
                        @endif
                    </div>
                </div>

                @if($position)
                <div id="vessel-map" class="h-80 w-full"></div>
                @push('scripts')
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
                        }).setView([lat, lng], 5);
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                            maxZoom: 19,
                        }).addTo(map);
                        const marker = L.circleMarker([lat, lng], {
                            radius: 8,
                            color: '#0f3b66',
                            fillColor: '#3b82f6',
                            fillOpacity: 0.9,
                            weight: 2,
                        }).addTo(map);
                        marker.bindTooltip("{{ $booking['vessel_name'] }}", { permanent: false, direction: 'top' }).openTooltip();
                    });
                </script>
                @endpush
                @else
                <div class="px-4 py-12 text-center text-sm text-slate-500">
                    {{ $booking['last_position'] }}
                </div>
                @endif

                <div class="border-t border-slate-200 bg-white px-4 py-3 text-xs text-slate-600">
                    <span class="font-medium text-slate-900">{{ $booking['vessel_name'] }}</span>
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
