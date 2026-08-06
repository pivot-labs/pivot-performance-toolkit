<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Support\Testing\Fakes;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use PivotPerformanceToolkit\Vendor\Illuminate\Bus\PendingBatch;
use PivotPerformanceToolkit\Vendor\Illuminate\Support\Collection;

class PendingBatchFake extends PendingBatch
{
    /**
     * The fake bus instance.
     *
     * @var \PivotPerformanceToolkit\Vendor\Illuminate\Support\Testing\Fakes\BusFake
     */
    protected $bus;

    /**
     * Create a new pending batch instance.
     *
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Support\Testing\Fakes\BusFake  $bus
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Support\Collection  $jobs
     * @return void
     */
    public function __construct(BusFake $bus, Collection $jobs)
    {
        $this->bus = $bus;
        $this->jobs = $jobs;
    }

    /**
     * Dispatch the batch.
     *
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Bus\Batch
     */
    public function dispatch()
    {
        return $this->bus->recordPendingBatch($this);
    }

    /**
     * Dispatch the batch after the response is sent to the browser.
     *
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Bus\Batch
     */
    public function dispatchAfterResponse()
    {
        return $this->bus->recordPendingBatch($this);
    }
}
