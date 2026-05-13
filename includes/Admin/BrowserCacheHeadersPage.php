<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Views\BladeEngine;

final class BrowserCacheHeadersPage implements AdminPageInterface
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function slug(): string
    {
        return 'performance-toolkit-browser-cache';
    }

    public function menuTitle(): string
    {
        return __('Browser Cache', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit – Browser Cache & Compression', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'monitor-cog';
    }

    public function renderContent(): void
    {
        echo BladeEngine::view('admin.browser-cache-headers-page', $this->getViewData());
    }

    /**
     * @return array<string, string>
     */
    private function getViewData(): array
    {
        $server_software = isset($_SERVER['SERVER_SOFTWARE']) ? (string) $_SERVER['SERVER_SOFTWARE'] : '';

        return array(
            'htaccess_snippet' => $this->generateHtaccessSnippet(),
            'nginx_snippet'    => $this->generateNginxSnippet(),
            'server_software'  => $server_software,
            'home_url'         => home_url(),
            'option_key'       => $this->settings->optionKey(),
        );
    }

    private function generateHtaccessSnippet(): string
    {
        return '# BEGIN Performance Toolkit - Browser Cache & Compression
<IfModule mod_expires.c>
    ExpiresActive On

    # Cache HTML for 1 hour
    ExpiresByType text/html "access plus 1 hour"

    # Cache CSS/JS for 1 year (versioned assets like style.css?v=123)
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType text/javascript "access plus 1 year"

    # Cache images for 1 month
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType image/webp "access plus 1 month"

    # Cache fonts for 1 year
    ExpiresByType font/ttf "access plus 1 year"
    ExpiresByType font/otf "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType application/font-woff "access plus 1 year"

    # Default expiration
    ExpiresDefault "access plus 2 days"
</IfModule>

<IfModule mod_deflate.c>
    # Gzip compression for text-based files
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/x-httpd-php
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/atom+xml
    AddOutputFilterByType DEFLATE image/svg+xml

    # Disable for broken browsers
    BrowserMatch ^Mozilla/4 gzip-only-text/html
    BrowserMatch ^Mozilla/4\.0[678] no-gzip
    BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
    Header append Vary User-Agent
</IfModule>

<IfModule mod_headers.c>
    # Cache control headers for versioned assets
    <FilesMatch "\.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|otf)$">
        Header set Cache-Control "max-age=31536000, immutable"
    </FilesMatch>

    # Cache control for HTML (revalidate frequently)
    <FilesMatch "\.html$">
        Header set Cache-Control "max-age=3600, must-revalidate"
    </FilesMatch>

    # Security headers
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
</IfModule>
# END Performance Toolkit - Browser Cache & Compression';
    }

    private function generateNginxSnippet(): string
    {
        return '# BEGIN Performance Toolkit - Browser Cache & Compression

# Gzip compression
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/x-font-ttf image/svg+xml;

# Cache expiration map
map $sent_http_content_type $expires {
    default                    off;
    text/html                  1h;
    text/css                   1y;
    application/javascript     1y;
    text/javascript            1y;
    image/jpeg                 1M;
    image/gif                  1M;
    image/png                  1M;
    image/svg+xml              1M;
    image/webp                 1M;
    font/ttf                   1y;
    font/otf                   1y;
    font/woff                  1y;
    font/woff2                 1y;
    application/font-woff      1y;
}

expires $expires;

# Cache control headers for versioned assets
location ~ \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|otf)$ {
    add_header Cache-Control "max-age=31536000, immutable";
    access_log off;
}

# Cache control for HTML (revalidate frequently)
location ~ \.html$ {
    add_header Cache-Control "max-age=3600, must-revalidate";
}

# Security headers
add_header X-Content-Type-Options "nosniff";
add_header X-Frame-Options "SAMEORIGIN";

# END Performance Toolkit - Browser Cache & Compression

# Note: Add this configuration inside your server {} block in nginx.conf
# Contact your hosting provider to apply these settings for you';
    }
}


