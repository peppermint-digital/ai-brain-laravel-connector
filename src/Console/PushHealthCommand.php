<?php

namespace AiBrain\Connector\Console;

use AiBrain\Connector\HealthCollector;
use Illuminate\Console\Command;
use Peppermint\AiBrainBridge\Facades\AiBrain;
use Throwable;

/**
 * Sammelt den App-Health-Snapshot und pusht ihn über den bestehenden
 * ai-brain-bridge-MCP-Weg (OAuth `mcp:use`) an AI Brains `report-app-health`-
 * Tool. AI Brain alarmiert schwellwertbasiert. KEIN eigener Verbindungsweg —
 * dieselbe One-Click-Verbindung wie die anderen Produkte.
 */
class PushHealthCommand extends Command
{
    protected $signature = 'ai-brain-connector:push-health {--dry-run : Nur den Payload ausgeben, nicht senden}';

    protected $description = 'Sammelt App-Health-Metriken und pusht sie über den AI-Brain-Bridge-MCP-Weg.';

    public function handle(HealthCollector $collector): int
    {
        if (! config('ai-brain-connector.enabled')) {
            $this->info('ai-brain-connector: deaktiviert.');

            return self::SUCCESS;
        }

        // Nur nicht-null-Werte senden (false/0 bleiben erhalten — z.B. db_ok=false).
        $metrics = array_filter($collector->collect(), fn ($v) => $v !== null);

        if ($this->option('dry-run')) {
            $this->line((string) json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        try {
            // Tool-Namen in AI Brain tragen das `-tool`-Suffix (Str::kebab des
            // Klassennamens inkl. „Tool") — analog report-codebase-audit-tool.
            AiBrain::call('report-app-health-tool', $metrics);
            $this->info("ai-brain-connector: Health gepusht ({$metrics['app_name']}).");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('ai-brain-connector: Push-Fehler — '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
