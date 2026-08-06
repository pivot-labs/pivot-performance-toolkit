<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Renderable
{
    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render();
}
