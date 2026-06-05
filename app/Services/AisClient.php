<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/*
 * AIS client — stub implementation
 *
 * Returns fake but coordinate-realistic vessel positions per MMSI.
 * Positions are spread across the Arabian Sea, Indian Ocean,
 * Mediterranean, Suez Canal, and the Strait of Hormuz so the
 * customer dashboard shows credible global movement, not a fleet
 * clustered around Oman. Designed so that swapping to a live feed
 * (AISStream.io WebSocket or VesselFinder REST) replaces only the
 * body of latest(), not the call sites. Cached for 60 seconds to
 * mirror the real feed cadence.
 */
class AisClient
{
    /**
     * @return array{lat: float, lng: float}|null
     */
    public function latest(?string $mmsi): ?array
    {
        if ($mmsi === null || $mmsi === '') {
            return null;
        }

        return Cache::remember("ais:vessel:{$mmsi}", now()->addSeconds(60), function () use ($mmsi) {
            return $this->positions()[$mmsi] ?? null;
        });
    }

    /**
     * Static coordinate map per MMSI in the demo dataset. Each
     * position is realistic for the booking's route at the demo's
     * notional "now". Live integration will replace this map.
     */
    private function positions(): array
    {
        return [
            // Pacific Crown — mid-Arabian Sea, Mumbai-bound for Sohar
            '538001234' => ['lat' => 21.40,  'lng' => 62.30],

            // Sohar Heavy — at Salalah Port awaiting customs (south Oman coast)
            '538009876' => ['lat' => 16.94,  'lng' => 54.01],

            // MSC Astrid — delivered Jebel Ali
            '247234567' => ['lat' => 25.02,  'lng' => 55.13],

            // Salalah Express — Adam highway, en route Sohar
            '563101234' => ['lat' => 22.30,  'lng' => 58.40],

            // Arabia Express — Indian Ocean, off East Africa, southbound to Dar es Salaam
            '525012345' => ['lat' => -4.20,  'lng' => 49.80],

            // Doha Reefer — delivered Qatar
            '466011223' => ['lat' => 25.30,  'lng' => 51.50],

            // MSC Galatea — Suez transit, US-bound via Mediterranean
            '255805234' => ['lat' => 31.20,  'lng' => 32.34],

            // CMA CGM Atlas — mid-Mediterranean, east of Malta, Antwerp-bound
            '227093890' => ['lat' => 35.40,  'lng' => 15.10],

            // India Star — delivered Mumbai (NSA)
            '419075123' => ['lat' => 18.95,  'lng' => 72.85],
        ];
    }
}
