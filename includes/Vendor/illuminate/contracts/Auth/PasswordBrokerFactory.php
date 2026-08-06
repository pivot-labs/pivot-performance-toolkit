<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Auth;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface PasswordBrokerFactory
{
    /**
     * Get a password broker instance by name.
     *
     * @param  string|null  $name
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker($name = null);
}
