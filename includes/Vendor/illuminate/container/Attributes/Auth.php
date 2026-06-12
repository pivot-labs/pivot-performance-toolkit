<?php

namespace PerformanceToolkit\Vendor\Illuminate\Container\Attributes;

use Attribute;
use PerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container;
use PerformanceToolkit\Vendor\Illuminate\Contracts\Container\ContextualAttribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Auth implements ContextualAttribute
{
    /**
     * Create a new class instance.
     */
    public function __construct(public ?string $guard = null)
    {
    }

    /**
     * Resolve the authentication guard.
     *
     * @param  self  $attribute
     * @param  \PerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container  $container
     * @return \PerformanceToolkit\Vendor\Illuminate\Contracts\Auth\Guard|\PerformanceToolkit\Vendor\Illuminate\Contracts\Auth\StatefulGuard
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->make('auth')->guard($attribute->guard);
    }
}
