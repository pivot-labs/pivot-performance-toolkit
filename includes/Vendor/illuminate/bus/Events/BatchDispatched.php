<?php

namespace PerformanceToolkit\Vendor\Illuminate\Bus\Events;

use PerformanceToolkit\Vendor\Illuminate\Bus\Batch;

class BatchDispatched
{
    /**
     * The batch instance.
     *
     * @var \PerformanceToolkit\Vendor\Illuminate\Bus\Batch
     */
    public $batch;

    /**
     * Create a new event instance.
     *
     * @param  \PerformanceToolkit\Vendor\Illuminate\Bus\Batch  $batch
     * @return void
     */
    public function __construct(Batch $batch)
    {
        $this->batch = $batch;
    }
}
