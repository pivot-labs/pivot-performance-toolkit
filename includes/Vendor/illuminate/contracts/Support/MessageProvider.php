<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support;

interface MessageProvider
{
    /**
     * Get the messages for the instance.
     *
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support\MessageBag
     */
    public function getMessageBag();
}
