<?php

namespace PivotPerformanceToolkit\Vendor\Psr\SimpleCache;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Exception interface for invalid cache arguments.
 *
 * When an invalid argument is passed it must throw an exception which implements
 * this interface
 */
interface InvalidArgumentException extends CacheException
{
}
