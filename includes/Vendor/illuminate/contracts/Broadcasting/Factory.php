<?php

namespace PerformanceToolkit\Vendor\Illuminate\Contracts\Broadcasting;

interface Factory
{
    /**
     * Get a broadcaster implementation by name.
     *
     * @param  string|null  $name
     * @return \PerformanceToolkit\Vendor\Illuminate\Contracts\Broadcasting\Broadcaster
     */
    public function connection($name = null);
}
