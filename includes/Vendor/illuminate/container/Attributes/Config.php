<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Container\Attributes;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Attribute;
use PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container;
use PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Config implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public string $key, public mixed $default = null)
    {
    }

    /**
     * Resolve the configuration value.
     *
     * @param  self  $attribute
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container  $container
     * @return mixed
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->make('config')->get($attribute->key, $attribute->default);
    }
}
