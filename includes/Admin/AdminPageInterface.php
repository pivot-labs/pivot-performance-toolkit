<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

interface AdminPageInterface
{
    public function slug(): string;

    public function menuTitle(): string;

    public function pageTitle(): string;

    public function iconKey(): string;

    public function renderContent(): void;
}

