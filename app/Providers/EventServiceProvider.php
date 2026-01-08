<?php

namespace App\Providers;

use App\Events\SpChangeEstadoGeneralEvent;
use App\Listeners\NotifyProveedorOnSpEstado;
use App\Listeners\SPOnChangeEstadoGeneral;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Registered::class => [
        //     SendEmailVerificationNotification::class,
        // ],

        //
        SpChangeEstadoGeneralEvent::class => [
            SPOnChangeEstadoGeneral::class,
            // NotifyProveedorOnSpEstado::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
