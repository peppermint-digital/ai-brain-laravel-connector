<?php

namespace AiBrain\Connector\Console;

use AiBrain\Connector\HealthCollector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Sammelt den App-Health-Snapshot und pusht ihn als signiertes `app.health`-
 * Event an AI Brains Event-Bus (/api/v1/events). AI Brain routet es an den
 * AppHealthService und alarmiert schwellwertbasiert.
 */
class PushHealthCommand extends Command
{
    protected $signature = 'ai-brain-connector:push-health {--dry-run : Nur den Payload ausgeben, nicht senden}';

    protected $description = 'Sammelt App-Health-Metriken und pusht sie signiert an AI Brain.';

    public function handle(HealthCollector $collector): int
    {
        if (! config('ai-brain-connector.enabled')) {
            $this->info('ai-brain-connector: deaktiviert.');

            return self::SUCCESS;
        }

        $url = rtrim((string) config('ai-brain-connector.url'), '/');
        $secret = (string) config('ai-brain-connector.secret');

        if ($url === '' || $secret === '') {
            $this->error('ai-brain-connector: AI_BRAIN_URL oder AI_BRAIN_EVENTS_SECRET fehlt.');

            return self::FAILURE;
        }

        $payload = $collector->collect();

        $body = json_encode([
            'type' => 'app.health',
            'source' => $payload['app_name'],
            'idempotency_key' => 'app.health:'.$payload['app_name'].':'.time(),
            'payload' => $payload,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($this->option('dry-run')) {
            $this->line((string) $body);

            return self::SUCCESS;
        }

        $signature = 'sha256='.hash_hmac('sha256', (string) $body, $secret);

        try {
            $response = Http::timeout((int) config('ai-brain-connector.timeout', 5))
                ->withHeaders([
                    'X-Signature' => $signature,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->withBody((string) $body, 'application/json')
                ->post($url.'/api/v1/events');

            if ($response->successful()) {
                $this->info("ai-brain-connector: Health gepusht ({$payload['app_name']}).");

                return self::SUCCESS;
            }

            $this->error('ai-brain-connector: Push fehlgeschlagen — HTTP '.$response->status());

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('ai-brain-connector: Push-Fehler — '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
