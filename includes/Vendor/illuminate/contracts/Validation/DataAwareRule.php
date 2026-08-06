<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Validation;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface DataAwareRule
{
    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData(array $data);
}
