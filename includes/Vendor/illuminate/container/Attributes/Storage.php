<?php

namespace PerformanceToolkit\Vendor\Illuminate\Container\Attributes;

use Attribute;
use PerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container;
use PerformanceToolkit\Vendor\Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Storage implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public ?string $disk = null)
    {
    }

    /**
     * Resolve the storage disk.
     *
     * @param  self  $attribute
     * @param  \PerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container  $container
     * @return \PerformanceToolkit\Vendor\Illuminate\Contracts\Filesystem\Filesystem
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->make('filesystem')->disk($attribute->disk);
    }
}
