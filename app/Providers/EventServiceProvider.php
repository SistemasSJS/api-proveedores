<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use App\Listeners\QueueFailedListener;

class EventServiceProvider extends ServiceProvider
{
  protected $listen = [
    Registered::class => [
      SendEmailVerificationNotification::class,
    ],
    JobFailed::class => [
      QueueFailedListener::class,
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
