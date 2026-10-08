<?php

namespace App\Providers;

use BladeUI\Icons\Factory as IconFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force blade-icons component registration.
        // Workaround: callAfterResolving(ViewFactory::class) in blade-icons
        // does not reliably fire during HTTP requests on Laravel 13.
        $this->app->make(IconFactory::class)->registerComponents();
    }
}
