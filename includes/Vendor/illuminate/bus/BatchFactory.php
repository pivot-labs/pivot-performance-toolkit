<?php

namespace PerformanceToolkit\Vendor\Illuminate\Bus;

use PerformanceToolkit\Vendor\Carbon\CarbonImmutable;
use PerformanceToolkit\Vendor\Illuminate\Contracts\Queue\Factory as QueueFactory;

class BatchFactory
{
    /**
     * The queue factory implementation.
     *
     * @var \PerformanceToolkit\Vendor\Illuminate\Contracts\Queue\Factory
     */
    protected $queue;

    /**
     * Create a new batch factory instance.
     *
     * @param  \PerformanceToolkit\Vendor\Illuminate\Contracts\Queue\Factory  $queue
     * @return void
     */
    public function __construct(QueueFactory $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Create a new batch instance.
     *
     * @param  \PerformanceToolkit\Vendor\Illuminate\Bus\BatchRepository  $repository
     * @param  string  $id
     * @param  string  $name
     * @param  int  $totalJobs
     * @param  int  $pendingJobs
     * @param  int  $failedJobs
     * @param  array  $failedJobIds
     * @param  array  $options
     * @param  \PerformanceToolkit\Vendor\Carbon\CarbonImmutable  $createdAt
     * @param  \PerformanceToolkit\Vendor\Carbon\CarbonImmutable|null  $cancelledAt
     * @param  \PerformanceToolkit\Vendor\Carbon\CarbonImmutable|null  $finishedAt
     * @return \PerformanceToolkit\Vendor\Illuminate\Bus\Batch
     */
    public function make(BatchRepository $repository,
                         string $id,
                         string $name,
                         int $totalJobs,
                         int $pendingJobs,
                         int $failedJobs,
                         array $failedJobIds,
                         array $options,
                         CarbonImmutable $createdAt,
                         ?CarbonImmutable $cancelledAt,
                         ?CarbonImmutable $finishedAt)
    {
        return new Batch($this->queue, $repository, $id, $name, $totalJobs, $pendingJobs, $failedJobs, $failedJobIds, $options, $createdAt, $cancelledAt, $finishedAt);
    }
}
