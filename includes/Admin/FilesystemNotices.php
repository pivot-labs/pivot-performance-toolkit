<?php
/**
 * Admin notices for filesystem permission issues.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Contracts\ModuleInterface;
use PivotPerformanceToolkit\Utils\FilesystemCheck;
use PivotPerformanceToolkit\Views\BladeEngine;

final class FilesystemNotices implements ModuleInterface {

	private const DROPIN_INSTALL_NOTICE_TRANSIENT     = 'pivot_performance_toolkit_dropin_install_failure_notice';
	private const DEACTIVATE_CLEANUP_NOTICE_TRANSIENT = 'pivot_performance_toolkit_deactivate_cleanup_failure_notice';
	private const WP_CONFIG_NOTICE_TRANSIENT          = 'pivot_performance_toolkit_wp_config_failure_notice';

	public function register(): void {
		add_action( 'admin_notices', array( $this, 'displayDropinInstallNotice' ) );
		add_action( 'admin_notices', array( $this, 'displayDeactivateCleanupNotice' ) );
		add_action( 'admin_notices', array( $this, 'displayWpConfigNotice' ) );
		add_action( 'admin_notices', array( $this, 'displayFilesystemNotice' ) );
	}

	public function displayDropinInstallNotice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = get_transient( self::DROPIN_INSTALL_NOTICE_TRANSIENT );
		if ( ! is_array( $notice ) ) {
			return;
		}

		$reason = isset( $notice['reason'] ) && is_string( $notice['reason'] ) ? $notice['reason'] : '';
		$source = isset( $notice['source'] ) && is_string( $notice['source'] ) ? $notice['source'] : '';
		$dest   = isset( $notice['dest'] ) && is_string( $notice['dest'] ) ? $notice['dest'] : '';

		delete_transient( self::DROPIN_INSTALL_NOTICE_TRANSIENT );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- BladeEngine::view() returns HTML already escaped by Blade's {{ }} at render time; re-escaping here would break the markup.
		echo BladeEngine::view(
			'admin.dropin-install-notice',
			array(
				'reason' => $reason,
				'source' => $source,
				'dest'   => $dest,
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function displayDeactivateCleanupNotice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = get_transient( self::DEACTIVATE_CLEANUP_NOTICE_TRANSIENT );
		if ( ! is_array( $notice ) ) {
			return;
		}

		$errors      = isset( $notice['errors'] ) && is_array( $notice['errors'] ) ? $notice['errors'] : array();
		$cache_dir   = isset( $notice['cache_dir'] ) && is_string( $notice['cache_dir'] ) ? $notice['cache_dir'] : '';
		$config_file = isset( $notice['config_file'] ) && is_string( $notice['config_file'] ) ? $notice['config_file'] : '';

		$errors = array_values(
			array_filter(
				$errors,
				static function ( $value ): bool {
					return is_string( $value ) && '' !== $value;
				}
			)
		);

		if ( empty( $errors ) ) {
			delete_transient( self::DEACTIVATE_CLEANUP_NOTICE_TRANSIENT );
			return;
		}

		delete_transient( self::DEACTIVATE_CLEANUP_NOTICE_TRANSIENT );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- BladeEngine::view() returns HTML already escaped by Blade's {{ }} at render time; re-escaping here would break the markup.
		echo BladeEngine::view(
			'admin.deactivate-cleanup-notice',
			array(
				'errors'      => $errors,
				'cache_dir'   => $cache_dir,
				'config_file' => $config_file,
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function displayWpConfigNotice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = get_transient( self::WP_CONFIG_NOTICE_TRANSIENT );
		if ( ! is_array( $notice ) ) {
			return;
		}

		$message = isset( $notice['message'] ) && is_string( $notice['message'] ) ? $notice['message'] : '';

		if ( '' === $message ) {
			delete_transient( self::WP_CONFIG_NOTICE_TRANSIENT );
			return;
		}

		delete_transient( self::WP_CONFIG_NOTICE_TRANSIENT );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- BladeEngine::view() returns HTML already escaped by Blade's {{ }} at render time; re-escaping here would break the markup.
		echo BladeEngine::view(
			'admin.wp-config-notice',
			array(
				'message' => $message,
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function displayFilesystemNotice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = FilesystemCheck::getCachedStatus();

		if ( $status['writable'] ) {
			return;
		}

		$error_messages = $status['errors'] ?? array();

		if ( empty( $error_messages ) ) {
			return;
		}

		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Pivot Performance Toolkit – Filesystem Issue', 'pivot-performance-toolkit' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'The cache directory is not writable. Pivot Performance Toolkit will continue to work, but page caching and asset minification will be disabled until the issue is resolved.', 'pivot-performance-toolkit' ); ?>
			</p>
			<ul style="margin: 8px 0 8px 20px; list-style-type: disc;">
				<?php foreach ( $error_messages as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p>
				<?php
				printf(
					/* translators: %s: link to system status page */
					wp_kses_post( __( 'For more details, visit the <a href="%s">System Status page</a>.', 'pivot-performance-toolkit' ) ),
					esc_url(
						add_query_arg(
							array(
								'page'    => 'pivot-performance-toolkit',
								'section' => 'system',
							),
							admin_url( 'admin.php' )
						)
					)
				);
				?>
			</p>
			<p style="color: #666; font-size: 0.9em;">
				<?php echo wp_kses_post( __( '<strong>To fix:</strong> Ensure the <code>wp-content/cache/pivot-performance-toolkit</code> directory exists and is writable by the web server. Usually: <code>chmod 755 wp-content/cache/pivot-performance-toolkit</code> or contact your hosting provider.', 'pivot-performance-toolkit' ) ); ?>
			</p>
		</div>
		<?php
	}
}

