<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Foundation;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface CachesRoutes
{
    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    public function routesAreCached();

    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath();
}
