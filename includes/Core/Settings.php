<?php

declare(strict_types=1);

namespace PerformanceToolkit\Core;

final class Settings
{
    private const OPTION_KEY = 'performance_toolkit_settings';

    /**
     * @return array<string, bool|int|string>
     */
    public function defaults(): array
    {
        return array(
            'enable_page_cache'    => true,
            'cache_ttl'            => 600,
            'max_cache_size_mb'    => 50,
            'cache_excluded_urls'  => '',
            'minify_html'          => false,
            'minify_css'           => false,
            'minify_external_css'  => false,
            'minify_external_css_exclusions' => '',
            'minify_external_js'   => false,
            'minify_external_js_exclusions' => '',
            'minify_js'            => false,
            'defer_scripts'        => true,
            'lazy_load_images'     => true,
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
     * @return array<string, bool|int|string>
     */
    public function sanitize($raw): array
    {
        $defaults = $this->defaults();
        $raw = is_array($raw) ? $raw : array();

        return array(
            'enable_page_cache'   => ! empty($raw['enable_page_cache']),
            'cache_ttl'           => max(60, (int) ($raw['cache_ttl'] ?? $defaults['cache_ttl'])),
            'max_cache_size_mb'   => max(1, (int) ($raw['max_cache_size_mb'] ?? $defaults['max_cache_size_mb'])),
            'cache_excluded_urls' => sanitize_textarea_field((string) ($raw['cache_excluded_urls'] ?? '')),
            'minify_html'         => ! empty($raw['minify_html']),
            'minify_css'          => ! empty($raw['minify_css']),
            'minify_external_css' => ! empty($raw['minify_external_css']),
            'minify_external_css_exclusions' => sanitize_textarea_field((string) ($raw['minify_external_css_exclusions'] ?? '')),
            'minify_external_js'  => ! empty($raw['minify_external_js']),
            'minify_external_js_exclusions' => sanitize_textarea_field((string) ($raw['minify_external_js_exclusions'] ?? '')),
            'minify_js'           => ! empty($raw['minify_js']),
            'defer_scripts'       => ! empty($raw['defer_scripts']),
            'lazy_load_images'    => ! empty($raw['lazy_load_images']),
        );
    }

    /**
     * @return array<string, bool|int|string>
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

    public function getString(string $key): string
    {
        return (string) ($this->all()[$key] ?? '');
    }

    /**
     * Returns a setting stored as newline-delimited text as a trimmed, non-empty array of lines.
     *
     * @return string[]
     */
    public function getLines(string $key): array
    {
        $raw   = $this->getString($key);
        $lines = array_filter(
            array_map('trim', explode("\n", $raw)),
            static fn(string $line): bool => $line !== ''
        );

        return array_values($lines);
    }

    public function optionKey(): string
    {
        return self::OPTION_KEY;
    }
}

