<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Pipeline;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Hub
{
    /**
     * Send an object through one of the available pipelines.
     *
     * @param  mixed  $object
     * @param  string|null  $pipeline
     * @return mixed
     */
    public function pipe($object, $pipeline = null);
}
