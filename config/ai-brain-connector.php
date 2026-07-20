<?php

return [
    // Projekt-Slug in AI Brain (Alert-Projekt). LEER LASSEN für Zero-Config:
    // AI Brain leitet das Projekt serverseitig aus der One-Click-Verbindung ab
    // (der OAuth-Client ist auf genau ein Projekt gescopet). Nur setzen, wenn der
    // Client mehr als ein Projekt sieht und die App eindeutig zugeordnet werden muss.
    'project' => env('AI_BRAIN_CONNECTOR_PROJECT', ''),

    // Anzeigename der App in AI Brain (global eindeutig). LEER LASSEN für
    // Zero-Config: der Connector nimmt dann den Produkt-Slug der Bridge-Anbindung
    // (`ai-brain-bridge.source`), erst danach APP_NAME. Nur setzen, wenn mehrere
    // Apps/Worker-Hosts desselben Produkts getrennt auftauchen sollen.
    'app_name' => env('AI_BRAIN_CONNECTOR_APP'),

    // Master-Schalter.
    'enabled' => (bool) env('AI_BRAIN_CONNECTOR_ENABLED', true),

    // Scheduler-Frequenz (Methodenname auf dem Schedule-Event), z.B.
    // everyFiveMinutes, everyTenMinutes, everyFifteenMinutes.
    'schedule' => env('AI_BRAIN_CONNECTOR_SCHEDULE', 'everyFiveMinutes'),

    // Fehler-Erfassung: aggregiert geloggte Errors zwischen zwei Pushes
    // (Klasse + Ort + Häufigkeit, KEINE Stacktraces, KEINE Payloads).
    'exceptions' => [
        'enabled' => (bool) env('AI_BRAIN_CONNECTOR_EXCEPTIONS', true),
        'max_items' => (int) env('AI_BRAIN_CONNECTOR_EXCEPTIONS_MAX', 10),
    ],

    // Slow-Query-Erfassung: Queries oberhalb der Schwelle, aggregiert nach
    // normalisiertem SQL. Bindings werden nie übertragen.
    'slow_queries' => [
        'enabled' => (bool) env('AI_BRAIN_CONNECTOR_SLOW_QUERIES', true),
        'threshold_ms' => (int) env('AI_BRAIN_CONNECTOR_SLOW_QUERY_MS', 1000),
        'max_items' => (int) env('AI_BRAIN_CONNECTOR_SLOW_QUERIES_MAX', 10),
    ],
];

// Transport (URL, OAuth-Token) kommt aus dem ai-brain-bridge-SDK — dieselbe
// One-Click-Verbindung (`php artisan ai-brain:connect …`) wie bei den anderen
// Produkten. Hier KEIN eigenes Secret / keine eigene URL.
