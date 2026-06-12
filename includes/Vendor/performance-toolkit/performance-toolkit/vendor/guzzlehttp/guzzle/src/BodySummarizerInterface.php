<?php

namespace PerformanceToolkit\Vendor\GuzzleHttp;

use PerformanceToolkit\Vendor\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
