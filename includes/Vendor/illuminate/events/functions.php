<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Events;

use Closure;

if (! function_exists('PivotPerformanceToolkit\Vendor\Illuminate\Events\queueable')) {
    /**
     * Create a new queued Closure event listener.
     *
     * @param  \Closure  $closure
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Events\QueuedClosure
     */
    function queueable(Closure $closure)
    {
        return new QueuedClosure($closure);
    }
}
