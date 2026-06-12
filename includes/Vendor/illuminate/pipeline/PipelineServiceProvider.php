<?php

namespace PerformanceToolkit\Vendor\Illuminate\Pipeline;

use PerformanceToolkit\Vendor\Illuminate\Contracts\Pipeline\Hub as PipelineHubContract;
use PerformanceToolkit\Vendor\Illuminate\Contracts\Support\DeferrableProvider;
use PerformanceToolkit\Vendor\Illuminate\Support\ServiceProvider;

class PipelineServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            PipelineHubContract::class,
            Hub::class
        );

        $this->app->bind('pipeline', fn ($app) => new Pipeline($app));
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            PipelineHubContract::class,
            'pipeline',
        ];
    }
}
