<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/*
 * AIS client — live + fallback hybrid
 *
 * Reads cached real positions from `php artisan ais:listen` first
 * (which writes to ais:vessel:{mmsi} via the AISStream.io WebSocket
 * feed). Falls back to static stub coordinates if no live position
 * has been received yet — so the demo always renders something,
 * even before the listener starts.
 */
class AisClient
{
    /**
     * @return array{lat: float, lng: float, name?: ?string, source?: string, received_at?: string}|null
     */
    public function latest(?string $mmsi): ?array
    {
        if ($mmsi === null || $mmsi === '') {
            return null;
        }

        $cached = Cache::get("ais:vessel:{$mmsi}");
        if (is_array($cached) && isset($cached['lat'], $cached['lng'])) {
            return $cached;
        }

        $fallback = $this->fallbackPositions()[$mmsi] ?? null;
        if ($fallback === null) {
            return null;
        }

        return $fallback + ['source' => 'static'];
    }

    /**
     * Static coordinate map per MMSI — used when no live AIS position
     * has been received for that vessel yet. Positions are spread
     * across realistic shipping regions so the demo never shows an
     * "everything is in Oman" cluster.
     */
    private function fallbackPositions(): array
    {
        return [
            '538001234' => ['lat' => 21.40,  'lng' => 62.30],  // mid-Arabian Sea
            '538009876' => ['lat' => 16.94,  'lng' => 54.01],  // Salalah Port
            '247234567' => ['lat' => 25.02,  'lng' => 55.13],  // Jebel Ali
            '563101234' => ['lat' => 22.30,  'lng' => 58.40],  // Oman highway
            '525012345' => ['lat' => -4.20,  'lng' => 49.80],  // off East Africa
            '466011223' => ['lat' => 25.30,  'lng' => 51.50],  // Doha
            '255805234' => ['lat' => 31.20,  'lng' => 32.34],  // Suez
            '227093890' => ['lat' => 35.40,  'lng' => 15.10],  // mid-Mediterranean
            '419075123' => ['lat' => 18.95,  'lng' => 72.85],  // Mumbai
            '353136000' => ['lat' => 5.00,   'lng' => -10.00], // EVER GIVEN approximate (West Africa transit, will be live-overwritten by listener)
        ];
    }
}
