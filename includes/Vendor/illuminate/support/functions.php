<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Support;

use PivotPerformanceToolkit\Vendor\Illuminate\Support\Defer\DeferredCallback;
use PivotPerformanceToolkit\Vendor\Illuminate\Support\Defer\DeferredCallbackCollection;
use PivotPerformanceToolkit\Vendor\Illuminate\Support\Process\PhpExecutableFinder;

if (! function_exists('PivotPerformanceToolkit\Vendor\Illuminate\Support\defer')) {
    /**
     * Defer execution of the given callback.
     *
     * @param  callable|null  $callback
     * @param  string|null  $name
     * @param  bool  $always
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Support\Defer\DeferredCallback
     */
    function defer(?callable $callback = null, ?string $name = null, bool $always = false)
    {
        if ($callback === null) {
            return app(DeferredCallbackCollection::class);
        }

        return pivotperformancetoolkit_vendor_tap(
            new DeferredCallback($callback, $name, $always),
            fn ($deferred) => app(DeferredCallbackCollection::class)[] = $deferred
        );
    }
}

if (! function_exists('PivotPerformanceToolkit\Vendor\Illuminate\Support\php_binary')) {
    /**
     * Determine the PHP Binary.
     *
     * @return string
     */
    function php_binary()
    {
        return (new PhpExecutableFinder)->find(false) ?: 'php';
    }
}

if (! function_exists('PivotPerformanceToolkit\Vendor\Illuminate\Support\artisan_binary')) {
    /**
     * Determine the proper Artisan executable.
     *
     * @return string
     */
    function artisan_binary()
    {
        return defined('ARTISAN_BINARY') ? ARTISAN_BINARY : 'artisan';
    }
}
