<?php

namespace AiBrain\Connector\Recorders;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Sammelt Queries oberhalb einer Zeitschwelle zwischen zwei Health-Pushes.
 *
 * Aggregiert nach normalisiertem SQL (Werte durch `?` ersetzt), damit dieselbe
 * Query mit tausend verschiedenen Parametern EIN Eintrag bleibt statt tausend.
 * Bindings werden nie übertragen — dort stünden sonst echte Nutzdaten.
 *
 * Der Listener läuft auf jeder Query, tut aber nur einen Zahlenvergleich; erst
 * oberhalb der Schwelle wird geschrieben. Das ist der übliche Preis (Telescope
 * macht dasselbe) und über `slow_queries.enabled` abschaltbar.
 */
class SlowQueryRecorder
{
    public const CACHE_KEY = 'ai_brain_connector:slow_queries';

    public function record(QueryExecuted $event): void
    {
        try {
            $thresholdMs = $this->thresholdMs();
            if ($event->time < $thresholdMs) {
                return;
            }

            $sql = $this->normalize($event->sql);
            $fingerprint = sha1($sql);
            $timeMs = (int) round($event->time);

            $items = Cache::get(self::CACHE_KEY, []);
            if (! is_array($items)) {
                $items = [];
            }

            if (! isset($items[$fingerprint]) && count($items) >= $this->maxItems() * 4) {
                return;
            }

            if (isset($items[$fingerprint])) {
                $items[$fingerprint]['count']++;
                $items[$fingerprint]['max_ms'] = max($items[$fingerprint]['max_ms'], $timeMs);
                $items[$fingerprint]['total_ms'] += $timeMs;
            } else {
                $items[$fingerprint] = [
                    'sql' => $sql,
                    'connection' => $event->connectionName,
                    'count' => 1,
                    'max_ms' => $timeMs,
                    'total_ms' => $timeMs,
                ];
            }

            Cache::put(self::CACHE_KEY, $items, now()->addHours(6));
        } catch (Throwable) {
            // Monitoring darf die App nie stören.
        }
    }

    /**
     * Gesammelte Slow Queries holen und das Fenster leeren.
     * `$flush = false` für --dry-run: schauen, ohne das Fenster zu verbrauchen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pull(bool $flush = true): array
    {
        try {
            $items = Cache::get(self::CACHE_KEY, []);
            if ($flush) {
                Cache::forget(self::CACHE_KEY);
            }

            if (! is_array($items) || $items === []) {
                return [];
            }

            usort($items, fn (array $a, array $b): int => ($b['max_ms'] ?? 0) <=> ($a['max_ms'] ?? 0));

            return array_slice(array_values($items), 0, $this->maxItems());
        } catch (Throwable) {
            return [];
        }
    }

    public function thresholdMs(): int
    {
        return max(1, (int) config('ai-brain-connector.slow_queries.threshold_ms', 1000));
    }

    protected function maxItems(): int
    {
        return max(1, (int) config('ai-brain-connector.slow_queries.max_items', 10));
    }

    /**
     * SQL auf ein Muster reduzieren: Zahlen- und String-Literale zu `?`,
     * lange IN-Listen zusammenfassen, Whitespace normalisieren.
     */
    protected function normalize(string $sql): string
    {
        $sql = preg_replace("/'[^']*'/", '?', $sql) ?? $sql;
        $sql = preg_replace('/\b\d+\b/', '?', $sql) ?? $sql;
        $sql = preg_replace('/\?(\s*,\s*\?)+/', '?, …', $sql) ?? $sql;
        $sql = trim(preg_replace('/\s+/', ' ', $sql) ?? $sql);

        return mb_strlen($sql) > 300 ? mb_substr($sql, 0, 300).'…' : $sql;
    }
}
