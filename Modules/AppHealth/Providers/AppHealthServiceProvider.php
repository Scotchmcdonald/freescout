<?php

declare(strict_types=1);

namespace Modules\AppHealth\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Modules\AppHealth\Contracts\HealthCheckContract;
use Modules\AppHealth\Contracts\MetricIngestionContract;
use Modules\AppHealth\Contracts\MetricRecorderContract;
use Modules\AppHealth\Contracts\TriggerEvaluatorContract;
use Modules\AppHealth\Http\Middleware\EnsureInternalAppHealthAccess;
use Modules\AppHealth\Http\Middleware\RecordHttpRouteMetrics;
use Modules\AppHealth\Jobs\EvaluateScalingTriggersJob;
use Modules\AppHealth\Services\HealthCheckService;
use Modules\AppHealth\Services\MetricRecorderService;
use Modules\AppHealth\Services\RuntimeMetricIngestionService;
use Modules\AppHealth\Services\TriggerEvaluationService;

class AppHealthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerSchedule();
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        app('router')->aliasMiddleware('apphealth.internal', EnsureInternalAppHealthAccess::class);
        app('router')->aliasMiddleware('apphealth.http.metrics', RecordHttpRouteMetrics::class);
    }

    public function register(): void
    {
        $this->app->bind(HealthCheckContract::class, HealthCheckService::class);
        $this->app->bind(MetricRecorderContract::class, MetricRecorderService::class);
        $this->app->bind(MetricIngestionContract::class, RuntimeMetricIngestionService::class);
        $this->app->bind(TriggerEvaluatorContract::class, TriggerEvaluationService::class);
    }

    private function registerConfig(): void
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('apphealth.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'apphealth');
    }

    private function registerRoutes(): void
    {
        if (! config('apphealth.enabled', true)) {
            return;
        }

        /** @var Router $router */
        $router = $this->app->make(Router::class);

        $router->middleware(['api', 'apphealth.http.metrics'])->group(__DIR__.'/../Routes/api.php');

        if (config('apphealth.operator_ui_enabled', true)) {
            $router->group([], __DIR__.'/../Routes/web.php');
        }
    }

    private function registerViews(): void
    {
        $sourcePath = __DIR__.'/../resources/views';
        $viewPath = resource_path('views/modules/apphealth');

        $this->publishes([$sourcePath => $viewPath], 'views');
        $this->loadViewsFrom([$sourcePath], 'apphealth');
    }

    private function registerSchedule(): void
    {
        if (! config('apphealth.scheduler.enabled', true)) {
            return;
        }

        $this->app->booted(function (): void {
            /** @var Schedule $schedule */
            $schedule = $this->app->make(Schedule::class);
            $configuredCron = config('apphealth.scheduler.cron', '*/15 * * * *');
            $cron = is_string($configuredCron) ? $configuredCron : '*/15 * * * *';

            $schedule->job(new EvaluateScalingTriggersJob)
                ->cron($cron)
                ->withoutOverlapping();
        });
    }
}
