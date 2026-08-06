<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use ArrayAccess;
use IteratorAggregate;

interface ValidatedData extends Arrayable, ArrayAccess, IteratorAggregate
{
    //
}
