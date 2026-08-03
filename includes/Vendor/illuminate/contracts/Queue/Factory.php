<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Queue;

interface Factory
{
    /**
     * Resolve a queue connection instance.
     *
     * @param  string|null  $name
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Queue\Queue
     */
    public function connection($name = null);
}
