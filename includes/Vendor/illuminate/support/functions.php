<?php

namespace PerformanceToolkit\Vendor\Illuminate\Support;

use PerformanceToolkit\Vendor\Illuminate\Support\Defer\DeferredCallback;
use PerformanceToolkit\Vendor\Illuminate\Support\Defer\DeferredCallbackCollection;
use PerformanceToolkit\Vendor\Illuminate\Support\Process\PhpExecutableFinder;

if (! function_exists('PerformanceToolkit\Vendor\Illuminate\Support\defer')) {
    /**
     * Defer execution of the given callback.
     *
     * @param  callable|null  $callback
     * @param  string|null  $name
     * @param  bool  $always
     * @return \PerformanceToolkit\Vendor\Illuminate\Support\Defer\DeferredCallback
     */
    function defer(?callable $callback = null, ?string $name = null, bool $always = false)
    {
        if ($callback === null) {
            return app(DeferredCallbackCollection::class);
        }

        return performancetoolkit_vendor_tap(
            new DeferredCallback($callback, $name, $always),
            fn ($deferred) => app(DeferredCallbackCollection::class)[] = $deferred
        );
    }
}

if (! function_exists('PerformanceToolkit\Vendor\Illuminate\Support\php_binary')) {
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

if (! function_exists('PerformanceToolkit\Vendor\Illuminate\Support\artisan_binary')) {
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
