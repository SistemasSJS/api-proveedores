<?php

namespace App\Providers;

use App\Events\RequisicionCreated;
use App\Events\RequisicionStatusChanged;
use App\Events\CotizacionGenerated;
use App\Listeners\SendRequisicionNotification;
use App\Listeners\SendStatusChangeNotification;
use App\Listeners\SendCotizacionNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
  protected $listen = [
    Registered::class => [
      SendEmailVerificationNotification::class,
    ],
    RequisicionCreated::class => [
      SendRequisicionNotification::class,
    ],
    RequisicionStatusChanged::class => [
      SendStatusChangeNotification::class,
    ],
    CotizacionGenerated::class => [
      SendCotizacionNotification::class,
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
