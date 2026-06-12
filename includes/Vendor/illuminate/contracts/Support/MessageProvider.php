<?php

namespace PerformanceToolkit\Vendor\Illuminate\Contracts\Support;

interface MessageProvider
{
    /**
     * Get the messages for the instance.
     *
     * @return \PerformanceToolkit\Vendor\Illuminate\Contracts\Support\MessageBag
     */
    public function getMessageBag();
}
