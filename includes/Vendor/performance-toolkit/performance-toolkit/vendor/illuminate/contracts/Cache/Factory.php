<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Cache;

interface Factory
{
    /**
     * Get a cache store instance by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Cache\Repository
     */
    public function store($name = null);
}
