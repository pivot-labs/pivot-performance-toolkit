<?php

declare(strict_types=1);

namespace PerformanceToolkit\Vendor\Doctrine\Inflector;

interface WordInflector
{
    public function inflect(string $word): string;
}
