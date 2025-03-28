<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\DriverCreated;
use App\Listeners\CreateAmoLeadListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DriverCreated::class => [
            CreateAmoLeadListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
