<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface DeferrableProvider
{
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides();
}
