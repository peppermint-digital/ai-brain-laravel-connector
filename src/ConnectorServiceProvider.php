<?php

namespace AiBrain\Connector;

use AiBrain\Connector\Console\PushHealthCommand;
use AiBrain\Connector\Recorders\ExceptionRecorder;
use AiBrain\Connector\Recorders\SlowQueryRecorder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Bindet den ai-brain/laravel-connector in eine Laravel-App ein: registriert
 * die Config, das Push-Command und plant es automatisch (kein manuelles
 * Scheduler-Editieren nötig). Steuerung rein über ENV / die publizierte Config.
 */
class ConnectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ai-brain-connector.php', 'ai-brain-connector');
    }

    public function boot(): void
    {
        $this->registerRecorders();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ai-brain-connector.php' => $this->app->configPath('ai-brain-connector.php'),
            ], 'ai-brain-connector-config');

            $this->commands([PushHealthCommand::class]);
        }

        // Auto-Scheduling: läuft, sobald `enabled` + URL/Secret gesetzt sind.
        $this->app->booted(function () {
            if (! config('ai-brain-connector.enabled')) {
                return;
            }

            $schedule = $this->app->make(Schedule::class);
            $event = $schedule->command('ai-brain-connector:push-health')->withoutOverlapping();

            $method = (string) config('ai-brain-connector.schedule', 'everyFiveMinutes');
            if (method_exists($event, $method)) {
                $event->{$method}();
            } else {
                $event->everyFiveMinutes();
            }
        });
    }

    /**
     * Fehler- und Slow-Query-Erfassung anhängen (beide einzeln abschaltbar).
     * Als Singletons, damit Recorder und Collector dieselbe Instanz benutzen.
     * Läuft bewusst auch in der Konsole — Queue-Worker sind der Ort, an dem die
     * interessanten Fehler passieren.
     */
    protected function registerRecorders(): void
    {
        if (! config('ai-brain-connector.enabled')) {
            return;
        }

        $this->app->singleton(ExceptionRecorder::class);
        $this->app->singleton(SlowQueryRecorder::class);

        if (config('ai-brain-connector.exceptions.enabled', true)) {
            Event::listen(MessageLogged::class, function (MessageLogged $event): void {
                $this->app->make(ExceptionRecorder::class)->record($event);
            });
        }

        if (config('ai-brain-connector.slow_queries.enabled', true)) {
            Event::listen(QueryExecuted::class, function (QueryExecuted $event): void {
                $this->app->make(SlowQueryRecorder::class)->record($event);
            });
        }
    }
}
