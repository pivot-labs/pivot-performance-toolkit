<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Mail;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Factory
{
    /**
     * Get a mailer instance by name.
     *
     * @param  string|null  $name
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Mail\Mailer
     */
    public function mailer($name = null);
}
