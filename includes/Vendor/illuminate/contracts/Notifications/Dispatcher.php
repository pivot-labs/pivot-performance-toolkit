<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Notifications;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Dispatcher
{
    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification);

    /**
     * Send the given notification immediately.
     *
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @param  array|null  $channels
     * @return void
     */
    public function sendNow($notifiables, $notification, ?array $channels = null);
}
