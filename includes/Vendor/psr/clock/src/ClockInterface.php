<?php

namespace PivotPerformanceToolkit\Vendor\Psr\Clock;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use DateTimeImmutable;

interface ClockInterface
{
    /**
     * Returns the current time as a DateTimeImmutable Object
     */
    public function now(): DateTimeImmutable;
}
