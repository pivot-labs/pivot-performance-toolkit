<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Foundation;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface ExceptionRenderer
{
    /**
     * Renders the given exception as HTML.
     *
     * @param  \Throwable  $throwable
     * @return string
     */
    public function render($throwable);
}
