<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Queue;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface EntityResolver
{
    /**
     * Resolve the entity for the given ID.
     *
     * @param  string  $type
     * @param  mixed  $id
     * @return mixed
     */
    public function resolve($type, $id);
}
