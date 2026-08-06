<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Database\Eloquent;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Database\Query\Builder as BaseContract;

/**
 * This interface is intentionally empty and exists to improve IDE support.
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
interface Builder extends BaseContract
{
}
