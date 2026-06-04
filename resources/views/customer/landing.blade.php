@extends('layouts.customer')

@section('content')
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

        <div class="mt-8 text-xs text-slate-500">
            <span class="font-medium text-slate-600">Demo bookings to try:</span>
            <span class="ml-2 inline-flex flex-wrap justify-center gap-2">
                <a href="{{ route('track.show', ['id' => 'MIS-2026-0421']) }}" class="rounded border border-slate-200 bg-white px-2.5 py-1 font-mono text-slate-700 hover:border-[#0f3b66] hover:text-[#0f3b66]">MIS-2026-0421</a>
                <a href="{{ route('track.show', ['id' => 'MIS-2026-0419']) }}" class="rounded border border-slate-200 bg-white px-2.5 py-1 font-mono text-slate-700 hover:border-[#0f3b66] hover:text-[#0f3b66]">MIS-2026-0419</a>
                <a href="{{ route('track.show', ['id' => 'MIS-2026-0410']) }}" class="rounded border border-slate-200 bg-white px-2.5 py-1 font-mono text-slate-700 hover:border-[#0f3b66] hover:text-[#0f3b66]">MIS-2026-0410</a>
            </span>
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
