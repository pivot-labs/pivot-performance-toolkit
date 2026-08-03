<?php

declare (strict_types=1);
namespace PivotPerformanceToolkit\Vendor\Carbon\Doctrine;

use PivotPerformanceToolkit\Vendor\Carbon\Carbon;
use DateTime;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\VarDateTimeType;
class DateTimeType extends VarDateTimeType implements CarbonDoctrineType
{
    /** @use \CarbonTypeConverter<PivotPerformanceToolkit\Vendor\Carbon> */
    use CarbonTypeConverter;
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PivotPerformanceToolkit\Vendor\Carbon
    {
        return $this->doConvertToPHPValue($value);
    }
}