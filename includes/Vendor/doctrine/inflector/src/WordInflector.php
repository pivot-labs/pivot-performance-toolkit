<?php

declare(strict_types=1);

namespace PivotPerformanceToolkit\Vendor\Doctrine\Inflector;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface WordInflector
{
    public function inflect(string $word): string;
}
