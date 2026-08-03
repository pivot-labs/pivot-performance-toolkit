<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Filesystem;

interface Factory
{
    /**
     * Get a filesystem implementation.
     *
     * @param  string|null  $name
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Filesystem\Filesystem
     */
    public function disk($name = null);
}
