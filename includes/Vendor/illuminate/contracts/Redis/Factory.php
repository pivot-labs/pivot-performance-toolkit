<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Redis;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Factory
{
    /**
     * Get a Redis connection by name.
     *
     * @param  string|null  $name
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connection($name = null);
}
