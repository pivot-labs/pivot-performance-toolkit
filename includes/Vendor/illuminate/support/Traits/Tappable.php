<?php

namespace PerformanceToolkit\Vendor\Illuminate\Support\Traits;

trait Tappable
{
    /**
     * Call the given Closure with this instance then return the instance.
     *
     * @param  (callable($this): mixed)|null  $callback
     * @return ($callback is null ? \PerformanceToolkit\Vendor\Illuminate\Support\HigherOrderTapProxy : $this)
     */
    public function tap($callback = null)
    {
        return performancetoolkit_vendor_tap($this, $callback);
    }
}
