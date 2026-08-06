<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Cache;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface LockProvider
{
    /**
     * Get a lock instance.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null);

    /**
     * Restore a lock instance using the owner identifier.
     *
     * @param  string  $name
     * @param  string  $owner
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner);
}
