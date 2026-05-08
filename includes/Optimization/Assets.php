<?php

declare(strict_types=1);

namespace PerformanceToolkit\Optimization;

use PerformanceToolkit\Contracts\ModuleInterface;
use PerformanceToolkit\Core\Settings;

final class Assets implements ModuleInterface
{
    private const EXCLUDED_HANDLES = array(
        'jquery',
        'jquery-core',
        'jquery-migrate',
        'wp-polyfill',
    );

    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function register(): void
    {
        add_filter('script_loader_tag', array($this, 'addDeferAttribute'), 10, 3);
    }

    public function addDeferAttribute(string $tag, string $handle, string $src): string
    {
        if (! $this->settings->getBool('defer_scripts') || is_admin()) {
            return $tag;
        }

        if (in_array($handle, self::EXCLUDED_HANDLES, true)) {
            return $tag;
        }

        if (str_contains($tag, ' defer')) {
            return $tag;
        }

        return str_replace('<script ', '<script defer ', $tag);
    }
}

