<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Broadcasting;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface HasBroadcastChannel
{
    /**
     * Get the broadcast channel route definition that is associated with the given entity.
     *
     * @return string
     */
    public function broadcastChannelRoute();

    /**
     * Get the broadcast channel name that is associated with the given entity.
     *
     * @return string
     */
    public function broadcastChannel();
}
