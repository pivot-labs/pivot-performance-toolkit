<?php

declare(strict_types=1);

namespace PerformanceToolkit\Core;

final class Settings
{
    private const OPTION_KEY = 'performance_toolkit_settings';

    /**
     * @return array<string, bool|int>
     */
    public function defaults(): array
    {
        return array(
            'enable_page_cache' => true,
            'cache_ttl' => 600,
            'defer_scripts' => true,
            'lazy_load_images' => true,
        );
    }

    public function register(): void
    {
        register_setting(
            'performance_toolkit',
            self::OPTION_KEY,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize'),
                'default' => $this->defaults(),
            )
        );
    }

    /**
     * @param mixed $raw
     *
     * @return array<string, bool|int>
     */
    public function sanitize($raw): array
    {
        $defaults = $this->defaults();
        $raw = is_array($raw) ? $raw : array();

        return array(
            'enable_page_cache' => ! empty($raw['enable_page_cache']),
            'cache_ttl' => max(60, (int) ($raw['cache_ttl'] ?? $defaults['cache_ttl'])),
            'defer_scripts' => ! empty($raw['defer_scripts']),
            'lazy_load_images' => ! empty($raw['lazy_load_images']),
        );
    }

    /**
     * @return array<string, bool|int>
     */
    public function all(): array
    {
        $saved = get_option(self::OPTION_KEY, array());

        if (! is_array($saved)) {
            $saved = array();
        }

        return wp_parse_args($saved, $this->defaults());
    }

    public function getBool(string $key): bool
    {
        return ! empty($this->all()[$key]);
    }

    public function getInt(string $key): int
    {
        return (int) ($this->all()[$key] ?? 0);
    }

    public function optionKey(): string
    {
        return self::OPTION_KEY;
    }
}

