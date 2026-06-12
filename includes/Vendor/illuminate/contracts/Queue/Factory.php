<?php

namespace PerformanceToolkit\Vendor\Illuminate\Contracts\Queue;

interface Factory
{
    /**
     * Resolve a queue connection instance.
     *
     * @param  string|null  $name
     * @return \PerformanceToolkit\Vendor\Illuminate\Contracts\Queue\Queue
     */
    public function connection($name = null);
}
