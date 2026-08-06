<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface MessageProvider
{
    /**
     * Get the messages for the instance.
     *
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support\MessageBag
     */
    public function getMessageBag();
}
