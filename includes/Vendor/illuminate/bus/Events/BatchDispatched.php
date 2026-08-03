<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Bus\Events;

use PivotPerformanceToolkit\Vendor\Illuminate\Bus\Batch;

class BatchDispatched
{
    /**
     * The batch instance.
     *
     * @var \PivotPerformanceToolkit\Vendor\Illuminate\Bus\Batch
     */
    public $batch;

    /**
     * Create a new event instance.
     *
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Bus\Batch  $batch
     * @return void
     */
    public function __construct(Batch $batch)
    {
        $this->batch = $batch;
    }
}
