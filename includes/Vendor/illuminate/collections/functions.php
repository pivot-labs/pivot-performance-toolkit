<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Support;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if (! function_exists('PivotPerformanceToolkit\Vendor\Illuminate\Support\enum_value')) {
    /**
     * Return a scalar value for the given value that might be an enum.
     *
     * @internal
     *
     * @template TValue
     * @template TDefault
     *
     * @param  TValue  $value
     * @param  TDefault|callable(TValue): TDefault  $default
     * @return ($value is empty ? TDefault : mixed)
     */
    function enum_value($value, $default = null)
    {
        return match (true) {
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,

            default => $value ?? pivotperformancetoolkit_vendor_value($default),
        };
    }
}
