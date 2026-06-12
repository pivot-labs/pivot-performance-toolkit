<?php

declare(strict_types=1);

namespace PerformanceToolkit\Vendor\Doctrine\Inflector\Rules\Spanish;

use PerformanceToolkit\Vendor\Doctrine\Inflector\GenericLanguageInflectorFactory;
use PerformanceToolkit\Vendor\Doctrine\Inflector\Rules\Ruleset;

final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset(): Ruleset
    {
        return Rules::getSingularRuleset();
    }

    protected function getPluralRuleset(): Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
