<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class LucideIcons
{
    /**
     * @var array<string, string>
     */
    private const ICON_PATHS = array(

        // dashboard
        'layout-dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect>',

        // cache
        'rocket' => '<path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"></path><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09"></path><path d="M9 12a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.4 22.4 0 0 1-4 2z"></path><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 .05 5 .05"></path>',

        // database
        'database-zap' => '<ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5V19A9 3 0 0 0 15 21.84"></path><path d="M21 5V8"></path><path d="M21 12L18 17H22L19 22"></path><path d="M3 12A9 3 0 0 0 14.59 14.87"></path>',
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"></path><path d="M3 12c0 1.7 4 3 9 3s9-1.3 9-3"></path>',

        // Media Optimizatio
        'image' => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect><circle cx="9" cy="9" r="2"></circle><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>',

        // Advanced Rules
        'sliders-horizontal' => '<line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line>',

        // system stats
        'activity' => '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"></path>',

         // docs
        'book-open' => '<path d="M12 7v14"></path><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>',

        // tools
        'wrench' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.106-3.105c.32-.322.863-.22.983.218a6 6 0 0 1-8.259 7.057l-7.91 7.91a1 1 0 0 1-2.999-3l7.91-7.91a6 6 0 0 1 7.057-8.259c.438.12.54.662.219.984z"></path>',

        // browser cache
        'monitor-cog' => '<path d="M12 17v4"></path><path d="m14.305 7.53.923-.382"></path><path d="m15.228 4.852-.923-.383"></path><path d="m16.852 3.228-.383-.924"></path><path d="m16.852 8.772-.383.923"></path><path d="m19.148 3.228.383-.924"></path><path d="m19.53 9.696-.382-.924"></path><path d="m20.772 4.852.924-.383"></path><path d="m20.772 7.148.924.383"></path><path d="M22 13v2a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7"></path><path d="M8 21h8"></path><circle cx="18" cy="6" r="3"></circle>',

        'dashicons-admin-settings' => '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.2a1.7 1.7 0 0 0 1 1.5h.1a1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1z"></path>',
        'dashicons-admin-tools' => '<path d="m14.7 6.3 3 3"></path><path d="m8 12 9.3-9.3a2.1 2.1 0 1 1 3 3L11 15"></path><path d="M9 7 4 12"></path><path d="m5 8 3 3"></path><path d="m2 17 3 3"></path><path d="m4 19 4-4"></path><path d="m7 22 7-7"></path>',
        'dashicons-media-document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 13h8"></path><path d="M8 17h8"></path>',
        'dashicons-heart' => '<path d="M19.5 13.6 12 21l-7.5-7.4a4.8 4.8 0 0 1 6.7-6.9L12 7.5l.8-.8a4.8 4.8 0 1 1 6.7 6.9z"></path>',
        'dashicons-format-image' => '<rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="9" cy="9" r="1.5"></circle><path d="m21 15-5-5L5 21"></path>',
        'dashicons-database-view' => '<ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5v8c0 1.7 4 3 9 3s9-1.3 9-3V5"></path><path d="M8 21h8"></path>',
        'dashicons-admin-site-alt3' => '<path d="M3 12a9 9 0 1 0 18 0A9 9 0 1 0 3 12"></path><path d="M3 12h18"></path><path d="M12 3a15 15 0 0 1 0 18"></path><path d="M12 3a15 15 0 0 0 0 18"></path>',
        'dashicons-media-code' => '<path d="m16 18 6-6-6-6"></path><path d="m8 6-6 6 6 6"></path>',
        'dashicons-database' => '<ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"></path><path d="M3 12c0 1.7 4 3 9 3s9-1.3 9-3"></path>',
        'dashicons-chart-area' => '<path d="M3 3v18h18"></path><path d="m19 9-5 5-4-4-3 3"></path>',
        'dashboard' => '<path d="M3 3h7v7H3z"></path><path d="M14 3h7v5h-7z"></path><path d="M14 12h7v9h-7z"></path><path d="M3 14h7v7H3z"></path>',

    );

    public static function render(string $icon_key): string
    {
        $paths = self::ICON_PATHS[$icon_key] ?? self::ICON_PATHS['dashboard'];
        $svg = sprintf(
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
            $paths
        );

        return (string) wp_kses($svg, self::allowedHtml());
    }

    /**
     * @return array<string, array<string, bool>>
     */
    private static function allowedHtml(): array
    {
        return array(
            'svg' => array(
                'viewBox' => true,
                'viewbox' => true,
                'fill' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
                'aria-hidden' => true,
            ),
            'path' => array(
                'd' => true,
            ),
            'circle' => array(
                'cx' => true,
                'cy' => true,
                'r' => true,
            ),
            'ellipse' => array(
                'cx' => true,
                'cy' => true,
                'rx' => true,
                'ry' => true,
            ),
            'rect' => array(
                'x' => true,
                'y' => true,
                'width' => true,
                'height' => true,
                'rx' => true,
                'ry' => true,
            ),
            'polyline' => array(
                'points' => true,
            ),
            'line' => array(
                'x1' => true,
                'y1' => true,
                'x2' => true,
                'y2' => true,
            ),
        );
    }
}

