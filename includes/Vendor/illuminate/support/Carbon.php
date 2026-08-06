<?php

namespace PivotPerformanceToolkit\Vendor\Illuminate\Support;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use PivotPerformanceToolkit\Vendor\Carbon\Carbon as BaseCarbon;
use PivotPerformanceToolkit\Vendor\Carbon\CarbonImmutable as BaseCarbonImmutable;
use PivotPerformanceToolkit\Vendor\Illuminate\Support\Traits\Conditionable;
use PivotPerformanceToolkit\Vendor\Illuminate\Support\Traits\Dumpable;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\Ulid;

class Carbon extends BaseCarbon
{
    use Conditionable, Dumpable;

    /**
     * {@inheritdoc}
     */
    public static function setTestNow(mixed $testNow = null): void
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }

    /**
     * Create a Carbon instance from a given ordered UUID or ULID.
     */
    public static function createFromId(Uuid|Ulid|string $id): static
    {
        if (is_string($id)) {
            $id = Ulid::isValid($id) ? Ulid::fromString($id) : Uuid::fromString($id);
        }

        return static::createFromInterface($id->getDateTime());
    }
}
