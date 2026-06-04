<?php

namespace App\Livewire;

use Livewire\Component;

class ChatWidget extends Component
{
    public bool $open = false;
    public string $input = '';

    /** @var array<int, array{role: string, body: string, ts: string}> */
    public array $messages = [];

    public function mount(): void
    {
        $this->messages[] = [
            'role' => 'system',
            'body' => "Welcome to MIS Logistics tracking.\n\nEnter your booking reference, container number, or bill of lading to see live status. Type HELP to see what else this assistant can do.",
            'ts'   => now()->format('H:i'),
        ];
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function send(): void
    {
        $text = trim($this->input);
        if ($text === '') return;

        $this->messages[] = [
            'role' => 'user',
            'body' => $text,
            'ts'   => now()->format('H:i'),
        ];
        $this->input = '';

        $this->respond($text);
    }

    private function respond(string $text): void
    {
        $upper = strtoupper($text);

        if ($upper === 'HELP' || $upper === '?') {
            $this->reply(
                "Available commands:\n".
                "- Send a booking, container, or B/L reference to see live status\n".
                "- LIST  — show all demo bookings available in this view\n".
                "- LOG <booking>  — show the audit trail for a booking\n".
                "- DOCS <booking>  — show document availability\n".
                "- HUMAN  — connect to a person on the MIS team"
            );
            return;
        }

        if ($upper === 'LIST') {
            $ids = array_keys(config('demo-bookings', []));
            $this->reply("Demo bookings available:\n\n" . implode("\n", array_map(fn ($i) => '- ' . $i, $ids)));
            return;
        }

        if ($upper === 'HUMAN' || $upper === 'AGENT' || $upper === 'TALK TO PERSON') {
            $this->reply(
                "Connecting you to Joju Pradeep, Commercial Manager.\n".
                "Current queue: 0 customers. Average response: 4 minutes.\n\n".
                "Your conversation context will be passed along — no need to re-explain."
            );
            return;
        }

        // LOG <booking>
        if (preg_match('/^LOG\s+(MIS-\d{4}-\d{4})$/i', $text, $m)) {
            $booking = config('demo-bookings.' . strtoupper($m[1]));
            if (! $booking) {
                $this->reply("No booking matching {$m[1]}. Type LIST to see available demo references.");
                return;
            }
            $lines = ["Audit trail — " . strtoupper($m[1]) . "\n"];
            foreach ($booking['audit'] as $e) {
                $lines[] = $e['ts'] . "  ·  " . str_replace('_', ' ', $e['action']) . "  ·  " . $e['actor'] . "\n   " . $e['detail'];
            }
            $this->reply(implode("\n", $lines) . "\n\nEvery event is timestamped, attributed, and immutable.");
            return;
        }

        // DOCS <booking>
        if (preg_match('/^DOCS\s+(MIS-\d{4}-\d{4})$/i', $text, $m)) {
            $booking = config('demo-bookings.' . strtoupper($m[1]));
            if (! $booking) {
                $this->reply("No booking matching {$m[1]}.");
                return;
            }
            $lines = ["Documents — " . strtoupper($m[1]) . "\n"];
            foreach ($booking['documents'] as $name => $state) {
                $lines[] = "- {$name}: {$state}";
            }
            $this->reply(implode("\n", $lines));
            return;
        }

        // Plain booking reference
        if (preg_match('/^MIS-\d{4}-\d{4}$/i', $text)) {
            $key = strtoupper($text);
            $booking = config('demo-bookings.' . $key);
            if (! $booking) {
                $this->reply("No booking matching {$key}. Type LIST to see available demo references.");
                return;
            }

            $body  = "Booking {$key}\n";
            $body .= "Status: " . $booking['status_label'] . "\n";
            $body .= "Route:  " . $booking['origin'] . "  →  " . $booking['destination'] . "\n";
            $body .= "Vessel: " . $booking['vessel_name'] . "\n";
            $body .= "ETA:    " . $booking['eta_label'] . "\n";
            $body .= "Customs: " . $booking['customs'] . "\n\n";
            $body .= "Open the full tracking view: " . route('track.show', ['id' => $key]) . "\n\n";
            $body .= "Type  LOG {$key}  to see the audit trail.";

            $this->reply($body);
            return;
        }

        // Container or B/L lookup
        if (preg_match('/^[A-Z]{3,4}-?\d{6,}$/i', preg_replace('/\s+/', '', $text))) {
            $needle = strtoupper(preg_replace('/\s+/', '', $text));
            foreach (config('demo-bookings', []) as $key => $b) {
                $cnum = strtoupper(str_replace(' ', '', $b['container_no'] ?? ''));
                if (str_contains($cnum, $needle)) {
                    $this->reply("Found booking {$key} for container reference {$needle}.\n\nSend  {$key}  to see status, or open " . route('track.show', ['id' => $key]));
                    return;
                }
            }
            $this->reply("No booking matches reference {$needle}. Try a booking number like MIS-2026-0421, or type LIST.");
            return;
        }

        $this->reply("I can help with tracking, documents, audit trails, or connect you to a human.\n\nTry a booking number like MIS-2026-0421, or type HELP.");
    }

    private function reply(string $body): void
    {
        $this->messages[] = [
            'role' => 'system',
            'body' => $body,
            'ts'   => now()->format('H:i'),
        ];
        $this->dispatch('scroll-chat-bottom');
    }

    public function render()
    {
        return view('livewire.chat-widget');
    }
}
