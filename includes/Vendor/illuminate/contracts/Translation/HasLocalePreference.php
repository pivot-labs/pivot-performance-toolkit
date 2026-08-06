<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Translation;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface HasLocalePreference
{
    /**
     * Get the preferred locale of the entity.
     *
     * @return string|null
     */
    public function preferredLocale();
}
