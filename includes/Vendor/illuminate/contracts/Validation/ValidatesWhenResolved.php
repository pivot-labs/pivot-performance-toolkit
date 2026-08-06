<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Validation;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface ValidatesWhenResolved
{
    /**
     * Validate the given class instance.
     *
     * @return void
     */
    public function validateResolved();
}
