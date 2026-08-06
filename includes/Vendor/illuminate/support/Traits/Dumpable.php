<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Support\Traits;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


trait Dumpable
{
    /**
     * Dump the given arguments and terminate execution.
     *
     * @param  mixed  ...$args
     * @return never
     */
    public function dd(...$args)
    {
        dd($this, ...$args);
    }

    /**
     * Dump the given arguments.
     *
     * @param  mixed  ...$args
     * @return $this
     */
    public function dump(...$args)
    {
        dump($this, ...$args);

        return $this;
    }
}
