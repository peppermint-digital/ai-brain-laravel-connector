<?php

return [
    // Projekt-Slug in AI Brain (Alert-Projekt). LEER LASSEN für Zero-Config:
    // AI Brain leitet das Projekt serverseitig aus der One-Click-Verbindung ab
    // (der OAuth-Client ist auf genau ein Projekt gescopet). Nur setzen, wenn der
    // Client mehr als ein Projekt sieht und die App eindeutig zugeordnet werden muss.
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
