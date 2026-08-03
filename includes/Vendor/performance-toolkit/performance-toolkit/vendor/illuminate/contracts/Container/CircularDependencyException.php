<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Container;

use Exception;
use PivotPerformanceToolkit\Vendor\Psr\Container\ContainerExceptionInterface;

class CircularDependencyException extends Exception implements ContainerExceptionInterface
{
    //
}
