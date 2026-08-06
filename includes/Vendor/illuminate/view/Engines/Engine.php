<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\View\Engines;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


abstract class Engine
{
    /**
     * The view that was last to be rendered.
     *
     * @var string
     */
    protected $lastRendered;

    /**
     * Get the last view that was rendered.
     *
     * @return string
     */
    public function getLastRendered()
    {
        return $this->lastRendered;
    }
}
