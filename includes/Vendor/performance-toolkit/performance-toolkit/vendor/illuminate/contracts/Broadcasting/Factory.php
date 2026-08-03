<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Broadcasting;

interface Factory
{
    /**
     * Get a broadcaster implementation by name.
     *
     * @param  string|null  $name
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Broadcasting\Broadcaster
     */
    public function connection($name = null);
}
