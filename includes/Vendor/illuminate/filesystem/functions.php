<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Filesystem;

if (! function_exists('PivotPerformanceToolkit\Vendor\Illuminate\Filesystem\join_paths')) {
    /**
     * Join the given paths together.
     *
     * @param  string|null  $basePath
     * @param  string  ...$paths
     * @return string
     */
    function join_paths($basePath, ...$paths)
    {
        foreach ($paths as $index => $path) {
            if (empty($path) && $path !== '0') {
                unset($paths[$index]);
            } else {
                $paths[$index] = DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
            }
        }

        return $basePath.implode('', $paths);
    }
}
