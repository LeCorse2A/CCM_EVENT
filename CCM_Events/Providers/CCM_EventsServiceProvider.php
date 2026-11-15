<?php

namespace Modules\CCM_Events\Providers;

use App\Services\ModuleService;
use Illuminate\Support\ServiceProvider;

class CCM_EventsServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'CCM_Events';

    protected string $moduleNameLower = 'ccm_events';

    public function boot(): void
    {
        $this->registerViews();
        $this->registerAdminMenuLink();
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'ccm_events');
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerViews(): void
    {
        $sourcePath = __DIR__.'/../Resources/views';

        $this->loadViewsFrom($sourcePath, $this->moduleNameLower);

        $this->publishes([
            $sourcePath => resource_path('views/modules/'.$this->moduleNameLower),
        ], $this->moduleNameLower.'-module-views');
    }

    protected function registerAdminMenuLink(): void
    {
        /** @var ModuleService $moduleSvc */
        $moduleSvc = app(ModuleService::class);

        $moduleSvc->addAdminLink(
            'CCM Events',
            url('/ccm-events'),
            'pe-7s-date'
        );
    }

}