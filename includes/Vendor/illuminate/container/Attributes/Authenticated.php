<?php

namespace PerformanceToolkit\Vendor\Illuminate\Container\Attributes;

use Attribute;
use PerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container;
use PerformanceToolkit\Vendor\Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Authenticated implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public ?string $guard = null)
    {
    }

    /**
     * Resolve the currently authenticated user.
     *
     * @param  self  $attribute
     * @param  \PerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container  $container
     * @return \PerformanceToolkit\Vendor\Illuminate\Contracts\Auth\Authenticatable|null
     */
    public static function resolve(self $attribute, Container $container)
    {
        return call_user_func($container->make('auth')->userResolver(), $attribute->guard);
    }
}
