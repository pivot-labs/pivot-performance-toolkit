<?php

namespace PerformanceToolkit\Vendor\Illuminate\Support\Testing\Fakes;

use PerformanceToolkit\Vendor\Illuminate\Bus\PendingBatch;
use PerformanceToolkit\Vendor\Illuminate\Support\Collection;

class PendingBatchFake extends PendingBatch
{
    /**
     * The fake bus instance.
     *
     * @var \PerformanceToolkit\Vendor\Illuminate\Support\Testing\Fakes\BusFake
     */
    protected $bus;

    /**
     * Create a new pending batch instance.
     *
     * @param  \PerformanceToolkit\Vendor\Illuminate\Support\Testing\Fakes\BusFake  $bus
     * @param  \PerformanceToolkit\Vendor\Illuminate\Support\Collection  $jobs
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
     * @return \PerformanceToolkit\Vendor\Illuminate\Bus\Batch
     */
    public function dispatch()
    {
        return $this->bus->recordPendingBatch($this);
    }

    /**
     * Dispatch the batch after the response is sent to the browser.
     *
     * @return \PerformanceToolkit\Vendor\Illuminate\Bus\Batch
     */
    public function dispatchAfterResponse()
    {
        return $this->bus->recordPendingBatch($this);
    }
}
