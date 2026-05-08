<?php

declare(strict_types=1);

namespace PerformanceToolkit\Contracts;

interface ModuleInterface
{
    public function register(): void;
}

