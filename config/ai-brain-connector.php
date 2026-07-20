<?php

return [
    // AI-Brain-Basis-URL, z.B. https://brain.proxy.peppermint-digital.com
    'url' => env('AI_BRAIN_URL', ''),

    // Geteiltes Event-HMAC-Secret (dasselbe wie AI Brains AI_BRAIN_EVENTS_SECRET).
    'secret' => env('AI_BRAIN_EVENTS_SECRET', ''),

    // Projekt-Slug in AI Brain, dem diese App zugeordnet wird (Alert-Projekt).
    'project' => env('AI_BRAIN_CONNECTOR_PROJECT', ''),

    // Anzeigename der App in AI Brain (eindeutig pro Projekt).
    'app_name' => env('AI_BRAIN_CONNECTOR_APP', env('APP_NAME', 'app')),

    // Master-Schalter.
    'enabled' => (bool) env('AI_BRAIN_CONNECTOR_ENABLED', true),

    // Scheduler-Frequenz (Methodenname auf dem Schedule-Event), z.B.
    // everyFiveMinutes, everyTenMinutes, everyFifteenMinutes.
    'schedule' => env('AI_BRAIN_CONNECTOR_SCHEDULE', 'everyFiveMinutes'),

    // HTTP-Timeout des Pushs in Sekunden.
    'timeout' => (int) env('AI_BRAIN_CONNECTOR_TIMEOUT', 5),
];
