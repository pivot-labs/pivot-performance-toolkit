<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Container\Attributes;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class DB extends Database
{
    //
}
