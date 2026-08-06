<?php

declare(strict_types=1);

namespace PivotPerformanceToolkit\Vendor\Doctrine\Inflector;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class NoopWordInflector implements WordInflector
{
    public function inflect(string $word): string
    {
        return $word;
    }
}
