<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

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
        $htaccess_snippet = $this->generateHtaccessSnippet();
        $nginx_snippet = $this->generateNginxSnippet();
        $server_software = isset($_SERVER['SERVER_SOFTWARE']) ? (string) $_SERVER['SERVER_SOFTWARE'] : '';
        $is_apache = stripos($server_software, 'Apache') !== false;
        $home_url = home_url();
        ?>
        <!-- Browser Cache Test Section -->
        <section id="ptk-browser-cache-test" class="ptk-card">
            <h2><?php esc_html_e('Browser Cache Test', 'performance-toolkit'); ?></h2>
            <p>
                <?php esc_html_e('Test your site\'s browser cache headers and compression configuration. This checks your actual WordPress installation, not just the directives.', 'performance-toolkit'); ?>
            </p>

            <div style="margin: 16px 0;">
                <button type="button" class="button button-primary" id="ptk-run-cache-test">
                    <?php esc_html_e('Run Cache Test', 'performance-toolkit'); ?>
                </button>
                <span id="ptk-test-status" style="display: none; margin-left: 12px;">
                    <span class="spinner" style="float: none; margin: 0;"></span>
                    <?php esc_html_e('Testing...', 'performance-toolkit'); ?>
                </span>
            </div>

            <div id="ptk-test-results" style="display: none; margin-top: 16px;">
                <table class="ptk-table-list" style="width: 100%; margin-top: 12px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Asset', 'performance-toolkit'); ?></th>
                            <th><?php esc_html_e('Cache-Control', 'performance-toolkit'); ?></th>
                            <th><?php esc_html_e('Compression', 'performance-toolkit'); ?></th>
                            <th><?php esc_html_e('Status', 'performance-toolkit'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="ptk-test-results-body">
                    </tbody>
                </table>

                <div id="ptk-test-summary" style="margin-top: 16px; padding: 12px; background-color: #f0f6fc; border-left: 4px solid #0969da; line-height: 1.6;">
                    <p id="ptk-test-summary-text"></p>
                </div>
            </div>

            <div id="ptk-test-errors" style="display: none; margin-top: 16px; padding: 12px; background-color: #fff5f5; border-left: 4px solid #d63638;">
                <strong><?php esc_html_e('Test Error:', 'performance-toolkit'); ?></strong>
                <p id="ptk-test-error-text" style="margin: 8px 0 0;"></p>
            </div>
        </section>

        <section id="ptk-browser-cache" class="ptk-card">
            <h2><?php esc_html_e('Browser Cache & Compression Settings', 'performance-toolkit'); ?></h2>
            <p>
                <?php esc_html_e('Browser caching tells visitors\' browsers to store static assets locally. Gzip compression reduces file sizes during transfer. Both dramatically improve repeat-visitor performance.', 'performance-toolkit'); ?>
            </p>

            <div style="margin-top: 20px; padding: 12px; background-color: #e7f3ff; border-left: 4px solid #0969da;">
                <p style="margin: 0;">
                    <strong><?php esc_html_e('Server Detected:', 'performance-toolkit'); ?></strong>
                    <code><?php echo esc_html($server_software !== '' ? $server_software : 'Unknown'); ?></code>
                </p>
            </div>

            <div style="margin-top: 24px;">
                <div role="tablist" aria-label="<?php esc_html_e('Server configuration tabs', 'performance-toolkit'); ?>">
                    <!-- Apache / .htaccess Tab -->
                    <div role="tabpanel" aria-labelledby="ptk-tab-htaccess" id="ptk-panel-htaccess">
                        <h3><?php esc_html_e('Apache .htaccess Configuration', 'performance-toolkit'); ?></h3>
                        <p>
                            <?php esc_html_e('Add this to your .htaccess file in the WordPress root directory. Most shared hosting uses Apache.', 'performance-toolkit'); ?>
                        </p>

                        <div style="margin: 16px 0;">
                            <h4 style="margin: 0 0 8px;"><?php esc_html_e('What this does:', 'performance-toolkit'); ?></h4>
                            <ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
                                <li><?php esc_html_e('Sets browser cache expiration times (1 year for versioned assets, 1 month for images)', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Enables Gzip compression for HTML, CSS, and JavaScript', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Adds cache-control headers to improve hit rates', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Prevents MIME-type sniffing for security', 'performance-toolkit'); ?></li>
                            </ul>
                        </div>

                        <div style="margin: 16px 0; padding: 12px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <strong><?php esc_html_e('Copy-friendly snippet:', 'performance-toolkit'); ?></strong>
                                <button type="button" class="button button-secondary" id="ptk-copy-htaccess" data-text="<?php echo esc_attr($htaccess_snippet); ?>">
                                    <?php esc_html_e('Copy', 'performance-toolkit'); ?>
                                </button>
                            </div>
                            <pre style="margin: 0; padding: 12px; background: white; border: 1px solid #ccc; border-radius: 3px; overflow-x: auto; font-size: 12px; line-height: 1.4;"><code><?php echo esc_html($htaccess_snippet); ?></code></pre>
                        </div>

                        <div style="margin: 16px 0; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
                            <p style="margin: 0 0 8px;"><strong><?php esc_html_e('How to apply:', 'performance-toolkit'); ?></strong></p>
                            <ol style="margin: 8px 0 0 20px; list-style-type: decimal;">
                                <li><?php esc_html_e('Connect via FTP/SFTP to your server', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Navigate to your WordPress root (where wp-content, wp-admin, wp-includes are)', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Open .htaccess (may be hidden file)', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Add the above snippet (or replace entire file if new)', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Save and clear your site cache', 'performance-toolkit'); ?></li>
                            </ol>
                        </div>
                    </div>

                    <!-- Nginx Tab -->
                    <div role="tabpanel" aria-labelledby="ptk-tab-nginx" id="ptk-panel-nginx" style="margin-top: 32px;">
                        <h3><?php esc_html_e('Nginx Configuration', 'performance-toolkit'); ?></h3>
                        <p>
                            <?php esc_html_e('Ask your hosting provider to add this to your Nginx server block configuration. Nginx is common on managed hosting and higher-tier plans.', 'performance-toolkit'); ?>
                        </p>

                        <div style="margin: 16px 0;">
                            <h4 style="margin: 0 0 8px;"><?php esc_html_e('What this does:', 'performance-toolkit'); ?></h4>
                            <ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
                                <li><?php esc_html_e('Sets browser cache expiration times (1 year for versioned assets, 1 month for images)', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Enables Gzip compression for text-based files', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Adds cache-control and immutable headers', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Adds security headers to prevent MIME-sniffing', 'performance-toolkit'); ?></li>
                            </ul>
                        </div>

                        <div style="margin: 16px 0; padding: 12px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <strong><?php esc_html_e('Copy-friendly snippet:', 'performance-toolkit'); ?></strong>
                                <button type="button" class="button button-secondary" id="ptk-copy-nginx" data-text="<?php echo esc_attr($nginx_snippet); ?>">
                                    <?php esc_html_e('Copy', 'performance-toolkit'); ?>
                                </button>
                            </div>
                            <pre style="margin: 0; padding: 12px; background: white; border: 1px solid #ccc; border-radius: 3px; overflow-x: auto; font-size: 12px; line-height: 1.4;"><code><?php echo esc_html($nginx_snippet); ?></code></pre>
                        </div>

                        <div style="margin: 16px 0; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
                            <p style="margin: 0 0 8px;"><strong><?php esc_html_e('How to apply:', 'performance-toolkit'); ?></strong></p>
                            <ol style="margin: 8px 0 0 20px; list-style-type: decimal;">
                                <li><?php esc_html_e('Contact your hosting provider or VPS admin', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Share the above snippet and ask them to add it to your Nginx server block', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('They will reload/restart Nginx to apply changes', 'performance-toolkit'); ?></li>
                                <li><?php esc_html_e('Clear your site cache to see effects', 'performance-toolkit'); ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 32px; padding: 12px; background-color: #f0f6fc; border-left: 4px solid #0969da;">
                <h3 style="margin-top: 0;"><?php esc_html_e('Testing Your Configuration', 'performance-toolkit'); ?></h3>
                <p>
                    <?php esc_html_e('After applying these directives, test your headers using:', 'performance-toolkit'); ?>
                </p>
                <ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
                    <li>
                        <a href="https://www.webpagetest.org/" target="_blank" rel="noopener noreferrer">WebPageTest.org</a>
                        <?php esc_html_e('(detailed waterfall + caching info)', 'performance-toolkit'); ?>
                    </li>
                    <li>
                        <a href="https://gtmetrix.com/" target="_blank" rel="noopener noreferrer">GTmetrix</a>
                        <?php esc_html_e('(cache headers report)', 'performance-toolkit'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('Browser DevTools → Network tab → Response Headers', 'performance-toolkit'); ?>
                    </li>
                </ul>
            </div>

            <div style="margin-top: 24px; padding: 12px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
                <h3 style="margin-top: 0;"><?php esc_html_e('Cache-Busting Strategy', 'performance-toolkit'); ?></h3>
                <p>
                    <?php esc_html_e('With 1-year browser cache on versioned assets (like style.css?v=123), you need a way to invalidate old versions when you update. WordPress theme/plugin versioning handles this automatically through query strings.', 'performance-toolkit'); ?>
                </p>
                <p style="margin: 0;">
                    <?php esc_html_e('Always clear your server cache and CDN cache when updating themes, plugins, or custom code.', 'performance-toolkit'); ?>
                </p>
            </div>
        </section>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const copyButtons = document.querySelectorAll('[id^="ptk-copy-"]');
            copyButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const text = this.getAttribute('data-text');
                    navigator.clipboard.writeText(text).then(() => {
                        const original = this.textContent;
                        this.textContent = '<?php esc_html_e('Copied!', 'performance-toolkit'); ?>';
                        setTimeout(() => {
                            this.textContent = original;
                        }, 2000);
                    }).catch(() => {
                        alert('<?php esc_html_e('Failed to copy. Please try again.', 'performance-toolkit'); ?>');
                    });
                });
            });

            // Browser Cache Test
            const testButton = document.getElementById('ptk-run-cache-test');
            if (testButton) {
                testButton.addEventListener('click', runCacheTest);
            }

            async function runCacheTest() {
                const status = document.getElementById('ptk-test-status');
                const results = document.getElementById('ptk-test-results');
                const errors = document.getElementById('ptk-test-errors');
                const resultsBody = document.getElementById('ptk-test-results-body');
                const summaryText = document.getElementById('ptk-test-summary-text');

                status.style.display = 'inline';
                results.style.display = 'none';
                errors.style.display = 'none';
                resultsBody.innerHTML = '';

                try {
                    const assets = await getTestAssets();
                    const testResults = [];

                    for (const asset of assets) {
                        try {
                            const result = await testAsset(asset);
                            testResults.push(result);
                            addResultRow(resultsBody, result);
                        } catch (e) {
                            console.error('Error testing asset:', asset, e);
                        }
                    }

                    status.style.display = 'none';
                    results.style.display = 'block';
                    generateSummary(testResults, summaryText);
                } catch (error) {
                    status.style.display = 'none';
                    errors.style.display = 'block';
                    document.getElementById('ptk-test-error-text').textContent = error.message;
                }
            }

            async function getTestAssets() {
                const homeUrl = '<?php echo esc_js($home_url); ?>';

                try {
                    const response = await fetch(homeUrl);
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const assets = [];

                    // Get CSS files
                    doc.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
                        const href = link.getAttribute('href');
                        if (href && !href.includes('//fonts.')) {
                            assets.push({ url: href, type: 'CSS', contentType: 'text/css' });
                        }
                    });

                    // Get JS files
                    doc.querySelectorAll('script[src]').forEach(script => {
                        const src = script.getAttribute('src');
                        if (src && src.includes('.js') && !src.includes('//')) {
                            assets.push({ url: src, type: 'JavaScript', contentType: 'application/javascript' });
                        }
                    });

                    // Add a test image if available
                    const img = doc.querySelector('img');
                    if (img) {
                        const src = img.getAttribute('src');
                        if (src && !src.includes('//')) {
                            assets.push({ url: src, type: 'Image', contentType: 'image' });
                        }
                    }

                    return assets.slice(0, 5); // Test up to 5 assets
                } catch (e) {
                    console.error('Error fetching home page:', e);
                    return [];
                }
            }

            async function testAsset(asset) {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 5000);

                try {
                    const response = await fetch(asset.url, { signal: controller.signal });
                    clearTimeout(timeoutId);

                    const cacheControl = response.headers.get('Cache-Control') || 'Not set';
                    const contentEncoding = response.headers.get('Content-Encoding') || 'None';
                    const contentLength = response.headers.get('Content-Length') || 'Unknown';
                    const etag = response.headers.get('ETag');

                    return {
                        url: asset.url.split('/').pop(),
                        type: asset.type,
                        cacheControl: cacheControl,
                        encoding: contentEncoding,
                        size: contentLength,
                        etag: etag ? 'Yes' : 'No',
                        status: response.status
                    };
                } catch (e) {
                    clearTimeout(timeoutId);
                    return {
                        url: asset.url.split('/').pop(),
                        type: asset.type,
                        cacheControl: 'Error: ' + e.message,
                        encoding: 'N/A',
                        size: 'N/A',
                        etag: 'N/A',
                        status: 'Error'
                    };
                }
            }

            function addResultRow(tbody, result) {
                const row = tbody.insertRow();

                const cacheStatus = result.cacheControl !== 'Not set' && result.cacheControl !== 'Error' ? '✓ Good' : '⚠ Check';
                const compressionStatus = result.encoding !== 'None' && result.encoding !== 'N/A' ? '✓ ' + result.encoding : 'ℹ Not used';

                row.innerHTML = `
                    <td><strong>${escapeHtml(result.type)}</strong><br><small>${escapeHtml(result.url)}</small></td>
                    <td><small>${escapeHtml(result.cacheControl)}</small></td>
                    <td><small>${escapeHtml(compressionStatus)}</small></td>
                    <td>${cacheStatus}</td>
                `;
            }

            function generateSummary(results, summaryElement) {
                if (results.length === 0) {
                    summaryElement.textContent = '<?php esc_html_e('No testable assets found. Make sure your WordPress site is publicly accessible.', 'performance-toolkit'); ?>';
                    return;
                }

                const good = results.filter(r => r.cacheControl !== 'Not set' && r.cacheControl !== 'Error' && !r.cacheControl.includes('Error')).length;
                const compressed = results.filter(r => r.encoding !== 'None' && r.encoding !== 'N/A').length;

                let summary = `<strong><?php esc_html_e('Results:', 'performance-toolkit'); ?> ${good}/${results.length} <?php esc_html_e('assets have cache headers', 'performance-toolkit'); ?></strong><br>`;
                summary += `${compressed}/${results.length} <?php esc_html_e('assets are compressed', 'performance-toolkit'); ?>`;

                if (good === results.length && compressed >= Math.floor(results.length / 2)) {
                    summary += '<br><strong style="color: #28a745;">✓ <?php esc_html_e('Browser cache configuration looks good!', 'performance-toolkit'); ?></strong>';
                } else if (good < results.length / 2) {
                    summary += '<br><strong style="color: #ffc107;">⚠ <?php esc_html_e('Not all assets have cache headers. Apply the configuration below.', 'performance-toolkit'); ?></strong>';
                }

                summaryElement.innerHTML = summary;
            }

            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, m => map[m]);
            }
        });
        </script>
        <?php
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


