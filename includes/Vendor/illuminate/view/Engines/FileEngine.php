<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\View\Engines;

use PivotPerformanceToolkit\Vendor\Illuminate\Contracts\View\Engine;
use PivotPerformanceToolkit\Vendor\Illuminate\Filesystem\Filesystem;

class FileEngine implements Engine
{
    /**
     * The filesystem instance.
     *
     * @var \PivotPerformanceToolkit\Vendor\Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new file engine instance.
     *
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function get($path, array $data = [])
    {
        return $this->files->get($path);
    }
}
