# ai-brain/laravel-connector

Pusht den Health-Snapshot einer Laravel-App (Queue / DB / failed_jobs / Uptime)
an **AI Brain**, das schwellwertbasiert alarmiert (Task + Push):

- **Queue**: ältester unbearbeiteter Job älter als eine Schwelle → „Worker läuft,
  hängt aber" (kritisch).
- **DB**: nicht erreichbar → kritisch.
- **failed_jobs**: Zuwachs seit dem letzten Push → Warnung.
- **Uptime / Version**: informativ.

**Verbindungsweg = der bestehende AI-Brain-Bridge-MCP-Weg (OAuth `mcp:use`).**
Kein neuer Weg, kein geteiltes Secret in der App: der Connector ruft AI Brains
`report-app-health`-Tool über dieselbe One-Click-Verbindung wie die anderen
Produkte. Baut auf `peppermint/ai-brain-bridge`.

## Installation

Voraussetzung: Die App hat die **AI-Brain-Bridge** eingebunden und ist per
One-Click verbunden (`php artisan ai-brain:connect <code>`). Ist das der Fall,
genügt:

```bash
composer require ai-brain/laravel-connector
```

**Das ist alles — kein ENV nötig (Zero-Config).** Der Connector

- übernimmt als App-Namen automatisch `APP_NAME`,
- lässt das Projekt leer → AI Brain ordnet die App serverseitig dem Projekt der
  One-Click-Verbindung zu (der OAuth-Client ist auf genau ein Projekt gescopet),
- **plant sich selbst** (alle 5 min, `withoutOverlapping`) — es genügt, dass der
  App-Scheduler läuft (`schedule:run` per Cron, bei Forge Standard).

### Optional überschreiben

Nur falls nötig (mehrere Apps im selben Projekt unterscheiden, andere Frequenz):

```dotenv
AI_BRAIN_CONNECTOR_APP="SFP Backend"        # Anzeigename, Default: APP_NAME
AI_BRAIN_CONNECTOR_PROJECT=<projekt-slug>   # nur nötig, wenn der OAuth-Client mehr als ein Projekt sieht
AI_BRAIN_CONNECTOR_ENABLED=true
AI_BRAIN_CONNECTOR_SCHEDULE=everyFiveMinutes
```

Config veröffentlichen (optional): `php artisan vendor:publish --tag=ai-brain-connector-config`.

> Composer-Repos: Sowohl `peppermint/ai-brain-bridge` als auch dieses Package
> liegen als VCS-/Git-Repositories vor — die `repositories`-Einträge dafür müssen
> in der `composer.json` der App stehen (wie beim Bridge-SDK bereits üblich).

## Verifizieren

```bash
# Metriken ansehen, ohne zu senden:
php artisan ai-brain-connector:push-health --dry-run

# Einmal echt pushen:
php artisan ai-brain-connector:push-health
```

Nach dem Push erscheint die App in AI Brain (`app_healths`); bei Überschreitung
einer Schwelle wird automatisch ein `[App] …`-Alert-Task erstellt (und bei
Erholung wieder geschlossen).

## Voraussetzungen

- Queue-Check nutzt die `jobs`-Tabelle (database-Queue). Bei Redis-Queue ist der
  Queue-Wert `null` — DB / failed_jobs / Uptime funktionieren weiterhin.
- Die App muss per Bridge-SDK mit AI Brain verbunden sein (OAuth `mcp:use`).
- AI Brain kennt das Ziel-`AI_BRAIN_CONNECTOR_PROJECT` als Projekt und hat das
  `report-app-health`-Tool (ab #2990 Phase A).

## Ausbaustufen (geplant)

Der Connector ist die Basis für weitergehende Laravel-Überwachung (Telescope-/
Nightwatch-artig), additiv: Exceptions/Errors als Alert, langsame Queries,
Request-/Scheduler-Metriken. Alles über denselben MCP-Weg.
