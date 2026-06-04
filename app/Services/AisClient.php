<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/*
 * AIS client — stub implementation
 *
 * Returns fake but coordinate-realistic vessel positions per MMSI.
 * Designed so that swapping to a live feed (AISStream.io WebSocket or
 * VesselFinder REST) replaces only the body of latest(), not the call
 * sites. Cached for 60 seconds to mirror the real feed cadence.
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
     * position is realistic for the booking's described route at the
     * demo's notional "now". Live integration will replace this map.
     */
    private function positions(): array
    {
        return [
            '538001234' => ['lat' => 23.94, 'lng' => 56.40],  // Strait of Hormuz — MV Pacific Crown
            '538009876' => ['lat' => 24.34, 'lng' => 56.71],  // Sohar Port berth
            '247234567' => ['lat' => 25.02, 'lng' => 55.13],  // Jebel Ali (delivered)
            '563101234' => ['lat' => 22.30, 'lng' => 58.40],  // Salalah Express, en route Sohar
            '525012345' => ['lat' => -8.12, 'lng' => 49.30],  // Indian Ocean, southbound
            '466011223' => ['lat' => 25.30, 'lng' => 51.50],  // Doha consignee
            '255805234' => ['lat' => 25.02, 'lng' => 55.13],  // Jebel Ali transhipment
            '227093890' => ['lat' => 30.05, 'lng' => 32.55],  // Suez Canal
            '419075123' => ['lat' => 19.05, 'lng' => 72.85],  // Mumbai (delivered)
        ];
    }
}
