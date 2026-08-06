<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Jsonable
{
    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0);
}
