<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Filesystem;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface Cloud extends Filesystem
{
    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path
     * @return string
     */
    public function url($path);
}
