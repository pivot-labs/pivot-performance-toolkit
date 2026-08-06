<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Queue;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface ClearableQueue
{
    /**
     * Delete all of the jobs from the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function clear($queue);
}
