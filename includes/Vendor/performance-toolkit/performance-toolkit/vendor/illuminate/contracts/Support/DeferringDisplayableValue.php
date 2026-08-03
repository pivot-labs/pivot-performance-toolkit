<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support;

interface DeferringDisplayableValue
{
    /**
     * Resolve the displayable value that the class is deferring.
     *
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support\Htmlable|string
     */
    public function resolveDisplayableValue();
}
