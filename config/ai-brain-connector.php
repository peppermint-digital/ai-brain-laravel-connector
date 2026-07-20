<?php

return [
    // Projekt-Slug in AI Brain, dem diese App zugeordnet wird (Alert-Projekt).
    'project' => env('AI_BRAIN_CONNECTOR_PROJECT', ''),

    // Anzeigename der App in AI Brain (eindeutig pro Projekt).
    'app_name' => env('AI_BRAIN_CONNECTOR_APP', env('APP_NAME', 'app')),

    // Master-Schalter.
    'enabled' => (bool) env('AI_BRAIN_CONNECTOR_ENABLED', true),

    // Scheduler-Frequenz (Methodenname auf dem Schedule-Event), z.B.
    // everyFiveMinutes, everyTenMinutes, everyFifteenMinutes.
    'schedule' => env('AI_BRAIN_CONNECTOR_SCHEDULE', 'everyFiveMinutes'),
];

// Transport (URL, OAuth-Token) kommt aus dem ai-brain-bridge-SDK — dieselbe
// One-Click-Verbindung (`php artisan ai-brain:connect …`) wie bei den anderen
// Produkten. Hier KEIN eigenes Secret / keine eigene URL.
