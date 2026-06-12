<?php

namespace PerformanceToolkit\Vendor\Illuminate\Contracts\Container;

use Exception;
use PerformanceToolkit\Vendor\Psr\Container\ContainerExceptionInterface;

class CircularDependencyException extends Exception implements ContainerExceptionInterface
{
    //
}
