<?php

declare (strict_types=1);
namespace PerformanceToolkit\Vendor\Carbon\Doctrine;

use PerformanceToolkit\Vendor\Carbon\Carbon;
use DateTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\VarDateTimeType;
class DateTimeType extends VarDateTimeType implements CarbonDoctrineType
{
    /** @use \CarbonTypeConverter<PerformanceToolkit\Vendor\Carbon> */
    use CarbonTypeConverter;
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PerformanceToolkit\Vendor\Carbon
    {
        return $this->doConvertToPHPValue($value);
    }
}