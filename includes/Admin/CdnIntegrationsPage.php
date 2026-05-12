<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Integrations\CloudflareIntegration;

final class CdnIntegrationsPage implements AdminPageInterface
{
    private const TEST_ACTION = 'performance_toolkit_cloudflare_test';
    private const PURGE_ACTION = 'performance_toolkit_cloudflare_purge';

    private Settings $settings;

    private CloudflareIntegration $cloudflare;

    public function __construct(Settings $settings, CloudflareIntegration $cloudflare)
    {
        $this->settings   = $settings;
        $this->cloudflare = $cloudflare;

        add_action('admin_post_' . self::TEST_ACTION, array($this, 'handleTestConnection'));
        add_action('admin_post_' . self::PURGE_ACTION, array($this, 'handlePurgeCache'));
    }

    public function slug(): string
    {
        return 'performance-toolkit-cdn-integrations';
    }

    public function menuTitle(): string
    {
        return __('CDN & Integrations', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit CDN & Integrations', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'dashicons-admin-site-alt3';
    }

    public function renderContent(): void
    {
        $options = $this->settings->all();
        $key     = $this->settings->optionKey();
        $notice  = isset($_GET['ptk_cf_notice']) ? sanitize_key(wp_unslash((string) $_GET['ptk_cf_notice'])) : '';
        $message = isset($_GET['ptk_cf_message']) ? sanitize_text_field(wp_unslash((string) $_GET['ptk_cf_message'])) : '';

        ?>
        <section id="ptk-cdn-integrations" class="ptk-card">
            <h2><?php esc_html_e('CDN & Integrations', 'performance-toolkit'); ?></h2>

            <?php if ($notice !== '') : ?>
                <div class="notice <?php echo $notice === 'success' ? 'notice-success' : 'notice-error'; ?> is-dismissible"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
                <?php settings_fields('performance_toolkit'); ?>

                <div class="ptk-field">
                    <label for="ptk-cdn-provider"><?php esc_html_e('Provider', 'performance-toolkit'); ?></label>
                    <select id="ptk-cdn-provider" name="<?php echo esc_attr($key); ?>[cdn_provider]">
                        <option value=""><?php esc_html_e('None', 'performance-toolkit'); ?></option>
                        <option value="cloudflare" <?php selected((string) $options['cdn_provider'], 'cloudflare'); ?>><?php esc_html_e('Cloudflare', 'performance-toolkit'); ?></option>
                    </select>
                </div>

                <div class="ptk-field">
                    <label for="ptk-cloudflare-api-token"><?php esc_html_e('API token', 'performance-toolkit'); ?></label>
                    <input
                        id="ptk-cloudflare-api-token"
                        type="password"
                        class="regular-text"
                        autocomplete="new-password"
                        name="<?php echo esc_attr($key); ?>[cloudflare_api_token]"
                        value="<?php echo esc_attr((string) $options['cloudflare_api_token']); ?>"
                    />
                </div>

                <div class="ptk-field">
                    <label for="ptk-cloudflare-zone-id"><?php esc_html_e('Zone ID', 'performance-toolkit'); ?></label>
                    <input
                        id="ptk-cloudflare-zone-id"
                        type="text"
                        class="regular-text"
                        name="<?php echo esc_attr($key); ?>[cloudflare_zone_id]"
                        value="<?php echo esc_attr((string) $options['cloudflare_zone_id']); ?>"
                    />
                </div>

                <div class="ptk-field">
                    <input type="hidden" name="<?php echo esc_attr($key); ?>[cloudflare_auto_purge]" value="0" />
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr($key); ?>[cloudflare_auto_purge]" value="1" <?php checked((bool) $options['cloudflare_auto_purge']); ?> />
                        <span><?php esc_html_e('Auto-purge on content update', 'performance-toolkit'); ?></span>
                    </label>
                </div>

                <?php submit_button(__('Save changes', 'performance-toolkit')); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:10px;">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::TEST_ACTION); ?>" />
                <?php wp_nonce_field('ptk_cloudflare_test'); ?>
                <?php submit_button(__('Test connection', 'performance-toolkit'), 'secondary', 'submit', false); ?>
            </form>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:10px;">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::PURGE_ACTION); ?>" />
                <?php wp_nonce_field('ptk_cloudflare_purge'); ?>
                <?php submit_button(__('Purge cache', 'performance-toolkit'), 'secondary', 'submit', false); ?>
            </form>
        </section>
        <?php
    }

    public function handleTestConnection(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_cloudflare_test');

        $result = $this->cloudflare->testConnection();

        $this->redirectWithNotice($result['success'], $result['message']);
    }

    public function handlePurgeCache(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_cloudflare_purge');

        $result = $this->cloudflare->purgeCache();

        $this->redirectWithNotice($result['success'], $result['message']);
    }

    private function redirectWithNotice(bool $success, string $message): void
    {
        $redirect_url = add_query_arg(
            array(
                'page'           => $this->slug(),
                'ptk_cf_notice'  => $success ? 'success' : 'error',
                'ptk_cf_message' => $message,
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }
}

