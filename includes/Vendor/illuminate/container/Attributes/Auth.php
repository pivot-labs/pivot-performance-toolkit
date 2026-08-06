<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Container\Attributes;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use Attribute;
use PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container;
use PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Container\ContextualAttribute;

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
     * @param  \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container  $container
     * @return \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Auth\Guard|\PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Auth\StatefulGuard
     */
    public static function resolve(self $attribute, Container $container)
    {
        return $container->make('auth')->guard($attribute->guard);
    }
}
