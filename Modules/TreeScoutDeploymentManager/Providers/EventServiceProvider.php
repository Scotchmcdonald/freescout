<?php

namespace Modules\TreeScoutDeploymentManager\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the module.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Listen to CRM ClientStatusChanged if available (Core Blindness: use string keys)
        // 'Modules\Crm\Events\ClientStatusChanged' => [
        //     'Modules\TreeScoutDeploymentManager\Listeners\SuspendDeploymentsOnClientDeactivation',
        // ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
