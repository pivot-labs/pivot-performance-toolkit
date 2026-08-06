<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Auth\Access;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Authorizable
{
    /**
     * Determine if the entity has a given ability.
     *
     * @param  iterable|string  $abilities
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($abilities, $arguments = []);
}
