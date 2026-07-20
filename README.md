# ai-brain/laravel-connector

Pusht den Health-Snapshot einer Laravel-App (Queue / DB / failed_jobs / Uptime)
signiert an **AI Brain**, das schwellwertbasiert alarmiert (Task + Push):

- **Queue**: ältester unbearbeiteter Job älter als eine Schwelle → „Worker läuft,
  hängt aber" (kritisch).
- **DB**: nicht erreichbar → kritisch.
- **failed_jobs**: Zuwachs seit dem letzten Push → Warnung.
- **Uptime / Version**: informativ.

Der Push läuft über AI Brains signierten Event-Bus (`POST /api/v1/events`,
`type=app.health`) mit dem geteilten HMAC-Secret — **kein per-App-OAuth-Client
nötig**, keine neue Egress-Fläche.

## Installation

```bash
composer require ai-brain/laravel-connector
php artisan vendor:publish --tag=ai-brain-connector-config   # optional
```

`.env` der App:

```dotenv
AI_BRAIN_URL=https://brain.proxy.peppermint-digital.com
AI_BRAIN_EVENTS_SECRET=<dasselbe Secret wie AI Brains AI_BRAIN_EVENTS_SECRET>
AI_BRAIN_CONNECTOR_PROJECT=<projekt-slug-in-ai-brain>   # z.B. schuelerferienpass-backend
AI_BRAIN_CONNECTOR_APP="SFP Backend"                    # Anzeigename (eindeutig pro Projekt)
# optional:
AI_BRAIN_CONNECTOR_ENABLED=true
AI_BRAIN_CONNECTOR_SCHEDULE=everyFiveMinutes
AI_BRAIN_CONNECTOR_TIMEOUT=5
```

Das Package **plant sich selbst** (alle 5 min, `withoutOverlapping`) — es genügt,
dass der App-Scheduler läuft (`schedule:run` per Cron, bei Forge Standard).

## Verifizieren

```bash
# Payload ansehen, ohne zu senden:
php artisan ai-brain-connector:push-health --dry-run

# Einmal echt pushen:
php artisan ai-brain-connector:push-health
```

Nach dem Push erscheint die App in AI Brain (`app_healths`); bei Überschreitung
einer Schwelle wird automatisch ein `[App] …`-Alert-Task erstellt (und bei
Erholung wieder geschlossen).

## Voraussetzungen

- Queue-Check nutzt die `jobs`-Tabelle (database-Queue). Bei Redis-Queue ist der
  Queue-Wert `null` (kein `jobs`-Table) — DB/failed_jobs/Uptime funktionieren
  weiterhin.
- AI Brain muss den `app.health`-Event-Listener haben (ab #2990 Phase B) und das
  Ziel-`AI_BRAIN_CONNECTOR_PROJECT` als Projekt kennen.
