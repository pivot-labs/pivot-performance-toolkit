<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Mail;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface MailQueue
{
    /**
     * Queue a new e-mail message for sending.
     *
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  string|null  $queue
     * @return mixed
     */
    public function queue($view, $queue = null);

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Mail\Mailable|string|array  $view
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $view, $queue = null);
}
