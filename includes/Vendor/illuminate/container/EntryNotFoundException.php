<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Container;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Exception;
use PivotPerformanceToolkit\Vendor\Psr\Container\NotFoundExceptionInterface;

class EntryNotFoundException extends Exception implements NotFoundExceptionInterface
{
    //
}
