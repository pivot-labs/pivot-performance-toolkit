<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

final class ToolsPage implements AdminPageInterface
{
    private const CLEAR_MINIFIED_ACTION = 'performance_toolkit_clear_minified_assets';
    private const EXPORT_SETTINGS_ACTION = 'performance_toolkit_export_settings';
    private const IMPORT_SETTINGS_ACTION = 'performance_toolkit_import_settings';
    private const SET_UNINSTALL_POLICY_ACTION = 'performance_toolkit_set_uninstall_policy';
    private const RESET_TO_DEFAULTS_ACTION = 'performance_toolkit_reset_to_defaults';

    private const MINIFIED_ASSETS_DIR = WP_CONTENT_DIR . '/cache/performance-toolkit/minified-assets';
    private const UNINSTALL_POLICY_OPTION = 'performance_toolkit_remove_data_on_uninstall';
    private const REDACTED_VALUE = '[redacted]';

    /**
     * @var string[]
     */
    private const SECRET_KEYS = array(
        'cloudflare_api_token',
    );

    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;

        add_action('admin_post_' . self::CLEAR_MINIFIED_ACTION, array($this, 'handleClearMinifiedAssets'));
        add_action('admin_post_' . self::EXPORT_SETTINGS_ACTION, array($this, 'handleExportSettings'));
        add_action('admin_post_' . self::IMPORT_SETTINGS_ACTION, array($this, 'handleImportSettings'));
        add_action('admin_post_' . self::SET_UNINSTALL_POLICY_ACTION, array($this, 'handleSetUninstallPolicy'));
        add_action('admin_post_' . self::RESET_TO_DEFAULTS_ACTION, array($this, 'handleResetToDefaults'));
    }

    public function slug(): string
    {
        return 'performance-toolkit-tools';
    }

    public function menuTitle(): string
    {
        return __('Tools', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Tools', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'wrench';
    }

    public function renderContent(): void
    {
        $cleared       = isset($_GET['ptk_minified_cleared']) && (string) $_GET['ptk_minified_cleared'] === '1';
        $removed_files = isset($_GET['ptk_minified_removed']) ? max(0, (int) $_GET['ptk_minified_removed']) : 0;
        $tools_notice  = isset($_GET['ptk_tools_notice']) ? sanitize_key((string) wp_unslash($_GET['ptk_tools_notice'])) : '';
        $tools_message = isset($_GET['ptk_tools_message']) ? sanitize_text_field((string) wp_unslash($_GET['ptk_tools_message'])) : '';
        $stats         = $this->getMinifiedAssetStats();
        $cleanup_on_uninstall = (bool) get_option(self::UNINSTALL_POLICY_OPTION, false);
        ?>
        <section id="ptk-tools" class="ptk-card">
            <h2><?php esc_html_e('Tools', 'performance-toolkit'); ?></h2>

            <?php if ($cleared) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        printf(
                            /* translators: %d: number of deleted files */
                            esc_html__('Cleared %d minified asset file(s).', 'performance-toolkit'),
                            esc_html((string) $removed_files)
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($tools_notice !== '' && $tools_message !== '') : ?>
                <div class="notice <?php echo $tools_notice === 'success' ? 'notice-success' : 'notice-error'; ?> is-dismissible">
                    <p><?php echo esc_html($tools_message); ?></p>
                </div>
            <?php endif; ?>

            <div class="ptk-field">
                <h3 style="margin:0 0 8px;"><?php esc_html_e('Minified CSS/JS cache', 'performance-toolkit'); ?></h3>
                <p>
                    <?php
                    printf(
                        /* translators: 1: file count, 2: formatted size */
                        esc_html__('%1$d file(s), %2$s total.', 'performance-toolkit'),
                        esc_html((string) $stats['count']),
                        esc_html(self::formatBytes($stats['bytes']))
                    );
                    ?>
                </p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="<?php echo esc_attr(self::CLEAR_MINIFIED_ACTION); ?>" />
                    <?php wp_nonce_field('ptk_clear_minified_assets'); ?>
                    <?php submit_button(__('Clear minified CSS/JS cache', 'performance-toolkit'), 'secondary', 'submit', false); ?>
                </form>
            </div>

            <div class="ptk-field" style="margin-top:18px;">
                <h3 style="margin:0 0 8px;"><?php esc_html_e('Export settings', 'performance-toolkit'); ?></h3>
                <p><?php esc_html_e('Download current Performance Toolkit settings as a JSON file.', 'performance-toolkit'); ?></p>
                <p style="margin:8px 0 12px;padding:8px 12px;background-color:#f0f6fc;border-left:3px solid #0969da;color:#24292f;">
                    <?php esc_html_e('Includes all settings: page cache, bypass cookies, assets optimization, CDN configuration, and more.', 'performance-toolkit'); ?>
                </p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="<?php echo esc_attr(self::EXPORT_SETTINGS_ACTION); ?>" />
                    <?php wp_nonce_field('ptk_export_settings'); ?>
                    <label style="display:block;margin:6px 0 10px;">
                        <input type="checkbox" name="ptk_include_secrets" value="1" />
                        <span><?php esc_html_e('Include secret API keys in export', 'performance-toolkit'); ?></span>
                    </label>
                    <p style="margin-top:-4px;color:#b32d2e;">
                        <?php esc_html_e('Warning: exported files with secrets should be stored securely and never committed to version control.', 'performance-toolkit'); ?>
                    </p>
                    <?php submit_button(__('Export settings', 'performance-toolkit'), 'secondary', 'submit', false); ?>
                </form>
            </div>

            <div class="ptk-field" style="margin-top:18px;">
                <h3 style="margin:0 0 8px;"><?php esc_html_e('Import settings', 'performance-toolkit'); ?></h3>
                <p><?php esc_html_e('Import settings from a previously exported JSON file.', 'performance-toolkit'); ?></p>
                <p style="margin:8px 0 12px;padding:8px 12px;background-color:#f0f6fc;border-left:3px solid #0969da;color:#24292f;">
                    <?php esc_html_e('This will import all cached settings including cache bypass cookies configuration, which helps with WooCommerce and other plugins that rely on cookies for personalization.', 'performance-toolkit'); ?>
                </p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo esc_attr(self::IMPORT_SETTINGS_ACTION); ?>" />
                    <?php wp_nonce_field('ptk_import_settings'); ?>
                    <input type="file" name="ptk_settings_import_file" accept=".json,application/json" required />
                    <div style="margin-top:10px;">
                        <?php submit_button(__('Import settings', 'performance-toolkit'), 'secondary', 'submit', false); ?>
                    </div>
                </form>
            </div>

            <div class="ptk-field" style="margin-top:18px;">
                <h3 style="margin:0 0 8px;"><?php esc_html_e('Uninstall cleanup policy', 'performance-toolkit'); ?></h3>
                <p><?php esc_html_e('Choose whether plugin settings and cache data should be removed when the plugin is deleted from WordPress.', 'performance-toolkit'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="<?php echo esc_attr(self::SET_UNINSTALL_POLICY_ACTION); ?>" />
                    <?php wp_nonce_field('ptk_set_uninstall_policy'); ?>
                    <label style="display:block;margin:6px 0 10px;">
                        <input type="checkbox" name="ptk_remove_data_on_uninstall" value="1" <?php checked($cleanup_on_uninstall); ?> />
                        <span><?php esc_html_e('Remove all Performance Toolkit data on uninstall', 'performance-toolkit'); ?></span>
                    </label>
                    <p style="margin-top:-4px;color:#646970;">
                        <?php esc_html_e('If enabled, deleting the plugin removes its settings and cache files. If disabled, data is preserved for reinstall.', 'performance-toolkit'); ?>
                    </p>
                    <?php submit_button(__('Save uninstall policy', 'performance-toolkit'), 'secondary', 'submit', false); ?>
                </form>
            </div>

            <div class="ptk-field" style="margin-top:18px; padding:12px; background-color:#fef5f5; border-left:4px solid #d63638;">
                <h3 style="margin:0 0 8px; color:#d63638;"><?php esc_html_e('Reset to safe defaults', 'performance-toolkit'); ?></h3>
                <p><?php esc_html_e('Reset all Performance Toolkit settings to their recommended safe defaults. This action cannot be undone.', 'performance-toolkit'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="<?php echo esc_attr(self::RESET_TO_DEFAULTS_ACTION); ?>" />
                    <?php wp_nonce_field('ptk_reset_to_defaults'); ?>
                    <label style="display:block;margin:6px 0 10px;">
                        <input type="checkbox" name="ptk_confirm_reset" value="1" required />
                        <span><?php esc_html_e('I understand this will reset all settings and cannot be undone', 'performance-toolkit'); ?></span>
                    </label>
                    <?php submit_button(__('Reset to safe defaults', 'performance-toolkit'), 'delete', 'submit', false); ?>
                </form>
            </div>
        </section>
        <?php
    }

    public function handleClearMinifiedAssets(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_clear_minified_assets');

        $removed = 0;

        foreach (glob(self::MINIFIED_ASSETS_DIR . '/*.min.css') ?: array() as $file_path) {
            if (@unlink($file_path)) {
                ++$removed;
            }
        }

        foreach (glob(self::MINIFIED_ASSETS_DIR . '/*.min.js') ?: array() as $file_path) {
            if (@unlink($file_path)) {
                ++$removed;
            }
        }

        $redirect_url = add_query_arg(
            array(
                'page'                 => $this->slug(),
                'ptk_minified_cleared' => '1',
                'ptk_minified_removed' => (string) $removed,
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    public function handleExportSettings(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_export_settings');

        $include_secrets = isset($_POST['ptk_include_secrets']) && ! empty($_POST['ptk_include_secrets']);
        $settings        = $this->settings->all();

        if (! $include_secrets) {
            foreach (self::SECRET_KEYS as $secret_key) {
                if (array_key_exists($secret_key, $settings)) {
                    $settings[$secret_key] = self::REDACTED_VALUE;
                }
            }
        }

        $payload = array(
            'plugin'          => 'performance-toolkit',
            'exported_at_gmt' => gmdate('c'),
            'include_secrets' => $include_secrets,
            'settings'        => $settings,
        );

        $json = wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (! is_string($json) || $json === '') {
            $this->redirectWithNotice(false, __('Could not generate export file.', 'performance-toolkit'));
        }

        $filename = 'performance-toolkit-settings-' . gmdate('Ymd-His') . '.json';

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo $json;
        exit;
    }

    public function handleImportSettings(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_import_settings');

        if (! isset($_FILES['ptk_settings_import_file']) || ! is_array($_FILES['ptk_settings_import_file'])) {
            $this->redirectWithNotice(false, __('No import file was uploaded.', 'performance-toolkit'));
        }

        $file = $_FILES['ptk_settings_import_file'];

        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->redirectWithNotice(false, __('Upload failed. Please try again with a valid JSON file.', 'performance-toolkit'));
        }

        $tmp_name = (string) ($file['tmp_name'] ?? '');

        if ($tmp_name === '' || ! is_uploaded_file($tmp_name)) {
            $this->redirectWithNotice(false, __('Invalid uploaded file.', 'performance-toolkit'));
        }

        $raw = file_get_contents($tmp_name);

        if (! is_string($raw) || trim($raw) === '') {
            $this->redirectWithNotice(false, __('Import file is empty.', 'performance-toolkit'));
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            $this->redirectWithNotice(false, __('Import file is not valid JSON.', 'performance-toolkit'));
        }

        $incoming = isset($decoded['settings']) && is_array($decoded['settings']) ? $decoded['settings'] : $decoded;

        if (! is_array($incoming)) {
            $this->redirectWithNotice(false, __('No settings payload found in import file.', 'performance-toolkit'));
        }

        foreach (self::SECRET_KEYS as $secret_key) {
            if (! array_key_exists($secret_key, $incoming)) {
                continue;
            }

            $value = (string) $incoming[$secret_key];

            if ($value === '' || $value === self::REDACTED_VALUE) {
                unset($incoming[$secret_key]);
            }
        }

        $sanitized = $this->settings->sanitize($incoming);
        update_option($this->settings->optionKey(), $sanitized);

        $import_count = count(array_filter($incoming, static fn($key): bool => array_key_exists($key, $this->settings->defaults()), ARRAY_FILTER_USE_KEY));
        $message = sprintf(
            /* translators: %d: number of settings imported */
            esc_html__('Settings imported successfully. %d setting(s) imported, including cache bypass cookies.', 'performance-toolkit'),
            $import_count
        );

        $this->redirectWithNotice(true, $message);
    }

    public function handleSetUninstallPolicy(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_set_uninstall_policy');

        $remove_data = isset($_POST['ptk_remove_data_on_uninstall']) && ! empty($_POST['ptk_remove_data_on_uninstall']);

        update_option(self::UNINSTALL_POLICY_OPTION, $remove_data ? '1' : '0');

        $this->redirectWithNotice(true, __('Uninstall cleanup policy saved.', 'performance-toolkit'));
    }

    public function handleResetToDefaults(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'performance-toolkit'));
        }

        check_admin_referer('ptk_reset_to_defaults');

        $confirm = isset($_POST['ptk_confirm_reset']) && ! empty($_POST['ptk_confirm_reset']);

        if (! $confirm) {
            $this->redirectWithNotice(false, __('Reset confirmation was not checked.', 'performance-toolkit'));
        }

        $defaults = $this->settings->defaults();
        update_option($this->settings->optionKey(), $defaults);

        $this->redirectWithNotice(true, __('All settings have been reset to safe defaults.', 'performance-toolkit'));
    }

    private function redirectWithNotice(bool $success, string $message): void
    {
        $redirect_url = add_query_arg(
            array(
                'page'              => $this->slug(),
                'ptk_tools_notice'  => $success ? 'success' : 'error',
                'ptk_tools_message' => $message,
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * @return array{count:int,bytes:int}
     */
    private function getMinifiedAssetStats(): array
    {
        $count = 0;
        $bytes = 0;

        foreach (glob(self::MINIFIED_ASSETS_DIR . '/*.min.css') ?: array() as $file_path) {
            ++$count;
            $bytes += (int) @filesize($file_path);
        }

        foreach (glob(self::MINIFIED_ASSETS_DIR . '/*.min.js') ?: array() as $file_path) {
            ++$count;
            $bytes += (int) @filesize($file_path);
        }

        return array(
            'count' => $count,
            'bytes' => $bytes,
        );
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
