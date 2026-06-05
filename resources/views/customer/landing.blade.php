@extends('layouts.customer')

@section('content')
<div x-data="{ show: !sessionStorage.getItem('mis_splash_v1') }"
     x-init="if (show) { sessionStorage.setItem('mis_splash_v1', '1'); setTimeout(() => show = false, 1300) }"
     x-show="show"
     x-cloak
     x-transition:leave="transition-opacity duration-300"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[60] flex flex-col items-center justify-center bg-white">
    <img src="{{ asset('logo.png') }}" alt="MIS Logistics" class="h-20 w-auto">
    <div class="mt-2 text-xs font-medium uppercase tracking-[0.2em] text-slate-500">Muscat International Shipping &amp; Logistics</div>
    <div class="mt-8 h-0.5 w-72 overflow-hidden rounded-full bg-slate-100">
        <div class="mis-splash-bar h-full bg-[#0f3b66]"></div>
    </div>
</div>

<section class="bg-gradient-to-b from-white to-slate-50">
    <div class="mx-auto max-w-4xl px-6 py-20 text-center">
        <p class="text-sm font-semibold uppercase tracking-wider text-[#0f3b66]">Customer Tracking</p>
        <h1 class="mt-3 text-4xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl">
            Track your shipment with MIS
        </h1>
        <p class="mx-auto mt-4 max-w-2xl text-base text-slate-600">
            Enter your booking reference, container number, or bill of lading. Live vessel position, customs status, and the full audit trail of every event on your shipment.
        </p>

        <form action="{{ route('track.lookup') }}" method="get"
              class="mx-auto mt-10 flex max-w-xl items-stretch gap-2 rounded-lg border border-slate-200 bg-white p-2 shadow-sm focus-within:border-[#0f3b66]">
            <input type="text" name="id" required autofocus
                   placeholder="MIS-2026-0421, MSCU-7841290, or your reference"
                   class="flex-1 rounded-md border-0 bg-transparent px-3 py-3 text-base text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-0">
            <button type="submit"
                    class="rounded-md bg-[#0f3b66] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0a2a4d]">
                Track
            </button>
        </form>

        <div class="mt-10">
            <div class="text-xs font-medium uppercase tracking-wider text-slate-500">Live demo bookings</div>
            <div class="mx-auto mt-3 flex max-w-3xl flex-wrap justify-center gap-2">
                @foreach(array_keys(config('demo-bookings', [])) as $bookingId)
                    @php
                        $b = config('demo-bookings.' . $bookingId);
                        $codeColors = [
                            'in_transit' => 'border-blue-200 text-blue-700 bg-blue-50',
                            'at_port'    => 'border-blue-200 text-blue-700 bg-blue-50',
                            'picked_up'  => 'border-blue-200 text-blue-700 bg-blue-50',
                            'booked'     => 'border-slate-200 text-slate-700 bg-white',
                            'delivered'  => 'border-slate-200 text-slate-500 bg-white',
                            'exception'  => 'border-red-200 text-red-700 bg-red-50',
                        ];
                        $cls = $codeColors[$b['status_code']] ?? 'border-slate-200 text-slate-700 bg-white';
                    @endphp
                    <a href="{{ route('track.show', ['id' => $bookingId]) }}"
                       class="inline-flex items-center gap-2 rounded-md border {{ $cls }} px-3 py-1.5 text-xs font-mono transition hover:border-[#0f3b66] hover:text-[#0f3b66]">
                        <span>{{ $bookingId }}</span>
                        <span class="text-[10px] uppercase tracking-wider opacity-70">{{ str_replace('_', ' ', $b['status_code']) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>

<section class="border-t border-slate-200 bg-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-6 py-16 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-[#0f3b66]">Live tracking</div>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Real-time vessel position</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                Every active sea shipment is linked to its vessel via AIS. See the ship on the map as it moves between ports, with position refreshed every minute.
            </p>
        </div>
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-[#0f3b66]">Evidence-grade</div>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">Audit trail on every event</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                Each status change is a timestamped, attributable record. Customs filings, document uploads, vessel departures &mdash; you see who, when, what, and where.
            </p>
        </div>
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-[#0f3b66]">Self-service</div>
            <h3 class="mt-2 text-lg font-semibold text-slate-900">No wait, no agent</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                Tracking, ETA, customs status, document checklist &mdash; available the moment a customer asks, without queuing for support.
            </p>
        </div>
    </div>
</section>
@endsection
