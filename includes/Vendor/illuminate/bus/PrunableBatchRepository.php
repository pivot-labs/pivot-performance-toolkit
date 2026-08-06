<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Bus;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use DateTimeInterface;

interface PrunableBatchRepository extends BatchRepository
{
    /**
     * Prune all of the entries older than the given date.
     *
     * @param  \DateTimeInterface  $before
     * @return int
     */
    public function prune(DateTimeInterface $before);
}
