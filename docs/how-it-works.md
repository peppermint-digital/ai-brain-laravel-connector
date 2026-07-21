# Wie der Connector funktioniert

`ai-brain/laravel-connector` ist ein dünnes Paket, das den **Health-Snapshot**
einer Laravel-App an AI Brain pusht. Es ist ein **reiner Service-Modus-Verbraucher**:
der Push läuft in der Konsole / im Scheduler, es gibt keinen eingeloggten Nutzer,
also läuft er in AI Brain **userlos** (die Identität ist der OAuth-Client der App,
kein Mensch). Details zum Modell: siehe das Bridge-SDK,
[`docs/identity-modes.md`](https://github.com/peppermint-digital/ai-brain-bridge/blob/main/docs/identity-modes.md).

## Was gepusht wird

Der Command `ai-brain-connector:push-health` sammelt fail-safe (jeder Wert einzeln,
fehlt etwas → `null`) und schickt sie an AI Brains `report-app-health-tool`:

- **Queue**: Alter des ältesten unbearbeiteten Jobs (database-Queue) → erkennt
  „Worker läuft, hängt aber", Queue-Größe.
- **DB**: erreichbar? + Ping-Latenz.
- **failed_jobs**: Gesamtzahl (Alarm bei Zuwachs).
- **Uptime**, App-/PHP-Version.
- **Telescope-artig** (aggregiert, ohne Payloads/Stacktraces):
  - Exceptions seit dem letzten Push (Klasse + Ort + Häufigkeit).
  - langsame Queries über einer Schwelle (normalisiertes SQL, ohne Bindings).

AI Brain wertet **serverseitig** schwellwertbasiert aus und legt bei Bedarf einen
`[App]`-Alert-Task an (dedupt, Auto-Resolve bei Erholung, Push bei kritisch).

## Zero-Config

```bash
composer require ai-brain/laravel-connector
```

Mehr braucht es nicht, wenn die App per One-Click an AI Brain angebunden ist
(`php artisan ai-brain:connect <code>`):

- **App-Name** = Produkt-Slug der Anbindung (`ai-brain-bridge.source`); in
  Nicht-Production-Umgebungen mit `(env)`-Suffix, damit staging/prod nicht
  kollidieren.
- **Projekt** = wird serverseitig aus der Anbindung abgeleitet.
- Der Command **plant sich selbst** (`everyFiveMinutes`, `withoutOverlapping`).

## Damit der Push automatisch läuft

Der Self-Schedule feuert nur, wenn der **Laravel-Scheduler** der App läuft:

```
* * * * * cd /pfad/zur/app && php artisan schedule:run >> /dev/null 2>&1
```

Fehlt der `schedule:run`-Cron, kann alternativ der Push direkt getaktet werden:

```
*/5 * * * * php /pfad/zur/app/artisan ai-brain-connector:push-health
```

## Verifizieren

```bash
php artisan ai-brain-connector:push-health --dry-run   # sammelt, sendet nicht
php artisan ai-brain-connector:push-health             # echter Push
```

## Erweitern

Der Connector ist bewusst schmal. Für **darüber hinausgehende** App→Brain-Logik
(eigene Tools, nutzerbezogene Aktionen) nutzt man das **Bridge-SDK direkt**:

- **Service-Modus** (userlos, wie der Connector): `AiBrain::asService(fn () => …)`
  bzw. Calls aus Konsole/Queue.
- **User-Modus** (Delegation an einen echten End-Nutzer): Acting-User-Resolver
  setzen — siehe [Bridge `docs/identity-modes.md`](https://github.com/peppermint-digital/ai-brain-bridge/blob/main/docs/identity-modes.md).

Weitere Health-Metriken lassen sich additiv über dasselbe `report-app-health-tool`
ergänzen (Server-Parsing + Alerts bleiben zentral in AI Brain).
