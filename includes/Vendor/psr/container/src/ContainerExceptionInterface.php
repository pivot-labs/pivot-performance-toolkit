<?php

namespace PivotPerformanceToolkit\Vendor\Psr\Container;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Throwable;

/**
 * Base interface representing a generic exception in a container.
 */
interface ContainerExceptionInterface extends Throwable
{
}
