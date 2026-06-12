<?php

namespace PerformanceToolkit\Vendor\Illuminate\Support\Facades;

/**
 * @method static \PerformanceToolkit\Vendor\Illuminate\Pipeline\Pipeline send(mixed $passable)
 * @method static \PerformanceToolkit\Vendor\Illuminate\Pipeline\Pipeline through(mixed $pipes)
 * @method static \PerformanceToolkit\Vendor\Illuminate\Pipeline\Pipeline pipe(mixed $pipes)
 * @method static \PerformanceToolkit\Vendor\Illuminate\Pipeline\Pipeline via(string $method)
 * @method static mixed then(\Closure $destination)
 * @method static mixed thenReturn()
 * @method static \PerformanceToolkit\Vendor\Illuminate\Pipeline\Pipeline finally(\Closure $callback)
 * @method static \PerformanceToolkit\Vendor\Illuminate\Pipeline\Pipeline withinTransaction(string|null|\UnitEnum|false $withinTransaction = null)
 * @method static \PerformanceToolkit\Vendor\Illuminate\Pipeline\Pipeline setContainer(\PerformanceToolkit\Vendor\Illuminate\Contracts\Container\Container $container)
 * @method static \PerformanceToolkit\Vendor\Illuminate\Pipeline\Pipeline|mixed when(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static \PerformanceToolkit\Vendor\Illuminate\Pipeline\Pipeline|mixed unless(\Closure|mixed|null $value = null, callable|null $callback = null, callable|null $default = null)
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \PerformanceToolkit\Vendor\Illuminate\Pipeline\Pipeline
 */
class Pipeline extends Facade
{
    /**
     * Indicates if the resolved instance should be cached.
     *
     * @var bool
     */
    protected static $cached = false;

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pipeline';
    }
}
