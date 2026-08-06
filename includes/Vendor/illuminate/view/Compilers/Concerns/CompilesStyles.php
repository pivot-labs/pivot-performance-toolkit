<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\View\Compilers\Concerns;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


trait CompilesStyles
{
    /**
     * Compile the conditional style statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileStyle($expression)
    {
        $expression = is_null($expression) ? '([])' : $expression;

        return "style=\"<?php echo \Illuminate\Support\Arr::toCssStyles{$expression} ?>\"";
    }
}
