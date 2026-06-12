<?php

namespace PerformanceToolkit\Vendor\Illuminate\Events;

use Closure;

if (! function_exists('PerformanceToolkit\Vendor\Illuminate\Events\queueable')) {
    /**
     * Create a new queued Closure event listener.
     *
     * @param  \Closure  $closure
     * @return \PerformanceToolkit\Vendor\Illuminate\Events\QueuedClosure
     */
    function queueable(Closure $closure)
    {
        return new QueuedClosure($closure);
    }
}
