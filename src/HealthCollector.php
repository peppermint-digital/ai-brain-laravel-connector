<?php

namespace AiBrain\Connector;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Sammelt den App-Health-Snapshot RECHTELOS aus der App selbst: ältester
 * unbearbeiteter Queue-Job (database-Queue), Queue-Größe, failed_jobs, DB-Ping,
 * Uptime. Alles fail-safe — fehlt eine Tabelle/DB, wird das Feld null.
 */
class HealthCollector
{
    /**
     * @return array<string, mixed>
     */
    public function collect(): array
    {
        $latency = null;
        $dbOk = $this->databaseOk($latency);

        return [
            'app_name' => (string) (config('ai-brain-connector.app_name') ?: config('app.name', 'app')),
            'project' => config('ai-brain-connector.project') ?: null,
            'hostname' => gethostname() ?: null,
            'queue_oldest_pending_seconds' => $this->queueOldestPendingSeconds(),
            'queue_size' => $this->queueSize(),
            'failed_jobs' => $this->failedJobs(),
            'db_ok' => $dbOk,
            'db_latency_ms' => $latency,
            'uptime_seconds' => $this->uptimeSeconds(),
            'app_version' => $this->appVersion(),
            'php_version' => PHP_VERSION,
        ];
    }

    /**
     * Alter (Sekunden) des ältesten unbearbeiteten Jobs in der `jobs`-Tabelle
     * (database-Queue). Nur verfügbare (available_at ≤ jetzt), noch nicht
     * reservierte Jobs zählen → fängt „Worker läuft, verarbeitet aber nichts".
     */
    protected function queueOldestPendingSeconds(): ?int
    {
        try {
            if (! Schema::hasTable('jobs')) {
                return null;
            }

            $now = time();
            $oldest = DB::table('jobs')
                ->whereNull('reserved_at')
                ->where('available_at', '<=', $now)
                ->min('created_at');

            if ($oldest === null) {
                return 0; // keine wartenden Jobs = kein Rückstand
            }

            return max(0, $now - (int) $oldest);
        } catch (Throwable) {
            return null;
        }
    }

    protected function queueSize(): ?int
    {
        try {
            return Schema::hasTable('jobs') ? (int) DB::table('jobs')->whereNull('reserved_at')->count() : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected function failedJobs(): ?int
    {
        try {
            return Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected function databaseOk(?int &$latencyMs): bool
    {
        try {
            $start = microtime(true);
            DB::select('select 1');
            $latencyMs = (int) round((microtime(true) - $start) * 1000);

            return true;
        } catch (Throwable) {
            $latencyMs = null;

            return false;
        }
    }

    protected function uptimeSeconds(): ?int
    {
        $raw = @file_get_contents('/proc/uptime');
        if ($raw === false) {
            return null;
        }
        $parts = explode(' ', trim($raw));

        return isset($parts[0]) && is_numeric($parts[0]) ? (int) round((float) $parts[0]) : null;
    }

    protected function appVersion(): ?string
    {
        return env('APP_VERSION') ?: null;
    }
}
