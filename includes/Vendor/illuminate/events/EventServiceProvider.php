<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Events;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use PivotPerformanceToolkit\Vendor\Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('events', function ($app) {
            return (new Dispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            })->setTransactionManagerResolver(function () use ($app) {
                return $app->bound('db.transactions')
                    ? $app->make('db.transactions')
                    : null;
            });
        });
    }
}
