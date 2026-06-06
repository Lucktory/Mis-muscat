<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;

class AisListenCommand extends Command
{
    protected $signature = 'ais:listen
        {--mmsi=* : Override the MMSIs to subscribe to (defaults to vessel_mmsi values in demo-bookings.php)}
        {--no-filter : Subscribe to all vessels in the bounding box, no MMSI filter (diagnostic mode)}
        {--reconnect-delay=5 : Seconds to wait before reconnecting after a dropout}';

    protected $description = 'Subscribe to AISStream.io and cache real vessel positions for the customer-portal map';

    public function handle(): int
    {
        $apiKey = config('services.aisstream.key');

        if (empty($apiKey)) {
            $this->error('AISSTREAM_API_KEY is not configured in .env');
            return self::FAILURE;
        }

        $noFilter = (bool) $this->option('no-filter');
        $mmsis    = $noFilter ? [] : $this->resolveMmsis();

        if ($noFilter) {
            $this->info('Diagnostic mode: subscribing to all vessels globally (no MMSI filter)');
        } else {
            if (empty($mmsis)) {
                $this->error('No MMSIs to subscribe to. Either pass --mmsi=... or add vessel_mmsi values to config/demo-bookings.php.');
                return self::FAILURE;
            }
            $this->info('Subscribing to AISStream for ' . count($mmsis) . ' vessels:');
            foreach ($mmsis as $m) {
                $this->line('  - ' . $m);
            }
        }

        $endpoint     = config('services.aisstream.endpoint');
        $reconnectGap = max(1, (int) $this->option('reconnect-delay'));
        $loop         = Loop::get();
        $connector    = new Connector($loop);

        $connect = null;
        $connect = function () use (&$connect, $connector, $endpoint, $apiKey, $mmsis, $loop, $reconnectGap) {
            $connector($endpoint)->then(
                function (WebSocket $conn) use ($apiKey, $mmsis, $loop, $reconnectGap, &$connect) {
                    $this->info('[' . now()->format('H:i:s') . '] Connected to ' . config('services.aisstream.endpoint'));

                    $subscribe = [
                        'APIKey'             => $apiKey,
                        'BoundingBoxes'      => [[[-90.0, -180.0], [90.0, 180.0]]], // global
                        'FilterMessageTypes' => ['PositionReport'],
                    ];
                    if (! empty($mmsis)) {
                        $subscribe['FiltersShipMMSI'] = array_values(array_map('strval', $mmsis));
                    }
                    $conn->send(json_encode($subscribe));
                    $this->line('  → subscription sent');

                    $conn->on('message', function (MessageInterface $msg) {
                        $this->handleMessage((string) $msg);
                    });

                    $conn->on('close', function ($code, $reason) use ($loop, $reconnectGap, &$connect) {
                        $this->warn(sprintf('[%s] WebSocket closed (code=%s, reason=%s). Reconnecting in %ds…',
                            now()->format('H:i:s'), $code, $reason ?: '(none)', $reconnectGap));
                        $loop->addTimer($reconnectGap, fn () => $connect());
                    });

                    $conn->on('error', function (\Throwable $e) {
                        $this->warn('  ! error: ' . $e->getMessage());
                    });
                },
                function (\Throwable $e) use ($loop, $reconnectGap, &$connect) {
                    $this->error(sprintf('[%s] Could not connect: %s. Retrying in %ds…',
                        now()->format('H:i:s'), $e->getMessage(), $reconnectGap));
                    $loop->addTimer($reconnectGap, fn () => $connect());
                }
            );
        };

        $connect();
        $loop->run();

        return self::SUCCESS;
    }

    private function resolveMmsis(): array
    {
        $opt = $this->option('mmsi');
        if (! empty($opt)) {
            return array_values(array_unique(array_filter(array_map('trim', $opt))));
        }

        return collect(config('demo-bookings', []))
            ->pluck('vessel_mmsi')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function handleMessage(string $raw): void
    {
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return;
        }

        $type = $data['MessageType'] ?? null;

        if ($type === 'Error') {
            $this->error('AISStream error: ' . ($data['Error'] ?? '(no detail)'));
            return;
        }

        if ($type !== 'PositionReport') {
            return;
        }

        $report = $data['Message']['PositionReport'] ?? null;
        $meta   = $data['MetaData'] ?? [];

        $mmsi = $meta['MMSI'] ?? ($report['UserID'] ?? null);
        $lat  = $report['Latitude'] ?? null;
        $lng  = $report['Longitude'] ?? null;

        if (! $mmsi || $lat === null || $lng === null) {
            return;
        }

        $mmsi = (string) $mmsi;
        $name = isset($meta['ShipName']) ? trim((string) $meta['ShipName']) : null;

        Cache::put("ais:vessel:{$mmsi}", [
            'lat'         => (float) $lat,
            'lng'         => (float) $lng,
            'name'        => $name !== '' ? $name : null,
            'sog'         => $report['Sog'] ?? null,
            'cog'         => $report['Cog'] ?? null,
            'received_at' => now()->toIso8601String(),
            'source'      => 'aisstream.io',
        ], now()->addHours(6));

        $this->line(sprintf('[%s] %s  %-28s  %8.4f, %8.4f',
            now()->format('H:i:s'),
            $mmsi,
            $name ? \Illuminate\Support\Str::limit($name, 26, '') : '(unnamed)',
            $lat,
            $lng
        ));
    }
}
