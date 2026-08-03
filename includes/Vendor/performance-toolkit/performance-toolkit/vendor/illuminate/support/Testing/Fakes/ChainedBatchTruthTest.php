<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Support\Testing\Fakes;

use Closure;

class ChainedBatchTruthTest
{
    /**
     * The underlying truth test.
     *
     * @var \Closure(\PivotPerformanceToolkit\Vendor\Illuminate\Bus\PendingBatch): bool
     */
    protected $callback;

    /**
     * Create a new truth test instance.
     *
     * @param  \Closure(\PivotPerformanceToolkit\Vendor\Illuminate\Bus\PendingBatch): bool  $callback
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Invoke the truth test with the given pending batch.
     *
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Bus\PendingBatch  $pendingBatch
     * @return bool
     */
    public function __invoke($pendingBatch)
    {
        return call_user_func($this->callback, $pendingBatch);
    }
}
