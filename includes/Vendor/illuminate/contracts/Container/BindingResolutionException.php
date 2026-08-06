<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Container;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Exception;
use PivotPerformanceToolkit\Vendor\Psr\Container\ContainerExceptionInterface;

class BindingResolutionException extends Exception implements ContainerExceptionInterface
{
    //
}
