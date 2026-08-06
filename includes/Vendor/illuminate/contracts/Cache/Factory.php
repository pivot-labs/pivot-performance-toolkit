<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Cache;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Factory
{
    /**
     * Get a cache store instance by name.
     *
     * @param  string|null  $name
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Cache\Repository
     */
    public function store($name = null);
}
