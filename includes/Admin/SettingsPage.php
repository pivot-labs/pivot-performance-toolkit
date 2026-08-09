<?php
/**
 * Settings admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Utils\Recommendations;

final class SettingsPage extends BladeAdminPage {

	private const SET_UNINSTALL_POLICY_ACTION = 'pivot_performance_toolkit_set_uninstall_policy';
	private const SET_WEBSITE_PROFILE_ACTION  = 'pivot_performance_toolkit_set_website_profile';

	private const TOOLS_NOTICE_QUERY_KEY = 'pivot_performance_toolkit_tools_notice';

	private const TOOLS_MESSAGE_QUERY_KEY = 'pivot_performance_toolkit_tools_message';

	private const SETTINGS_NOTICE_QUERY_KEY = 'pivot_performance_toolkit_settings_notice';

	private const SETTINGS_MESSAGE_QUERY_KEY = 'pivot_performance_toolkit_settings_message';

	private Settings $settings;

	private Recommendations $recommendations;

	public function __construct( Settings $settings, Recommendations $recommendations ) {
		$this->settings        = $settings;
		$this->recommendations = $recommendations;

		add_action( 'wp_ajax_' . self::SET_WEBSITE_PROFILE_ACTION, array( $this, 'handleSetWebsiteProfile' ) );
	}

	public function slug(): string {
		return 'pivot-performance-toolkit-settings';
	}

	public function menuTitle(): string {
		return __( 'Settings', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Settings', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'dashicons-admin-settings';
	}

	public function view(): string {
		return 'admin.settings-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- these are read-only display values from this page's own post-redirect notice (already produced by a nonce-verified admin-post handler), not a new state-changing action.
		$tools_notice     = isset( $_GET[ self::TOOLS_NOTICE_QUERY_KEY ] ) ? sanitize_key( (string) wp_unslash( $_GET[ self::TOOLS_NOTICE_QUERY_KEY ] ) ) : '';
		$tools_message    = isset( $_GET[ self::TOOLS_MESSAGE_QUERY_KEY ] ) ? sanitize_text_field( (string) wp_unslash( $_GET[ self::TOOLS_MESSAGE_QUERY_KEY ] ) ) : '';
		$settings_notice  = isset( $_GET[ self::SETTINGS_NOTICE_QUERY_KEY ] ) ? sanitize_key( (string) wp_unslash( $_GET[ self::SETTINGS_NOTICE_QUERY_KEY ] ) ) : '';
		$settings_message = isset( $_GET[ self::SETTINGS_MESSAGE_QUERY_KEY ] ) ? sanitize_text_field( (string) wp_unslash( $_GET[ self::SETTINGS_MESSAGE_QUERY_KEY ] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$current_settings = $this->settings->all();
		$recommendation   = $this->recommendations->collect( array( 'profile' ) )[0] ?? null;

		return array(
			'tools_notice'                   => $tools_notice,
			'tools_message'                  => $tools_message,
			'settings_notice'                => $settings_notice,
			'settings_message'               => $settings_message,
			'cleanup_on_uninstall'           => (bool) get_option( 'pivot_performance_toolkit_remove_data_on_uninstall', false ),
			'set_uninstall_policy_action'    => self::SET_UNINSTALL_POLICY_ACTION,
			'website_profile'                => (string) ( $current_settings['website_profile'] ?? 'standard' ),
			'website_profile_action'         => self::SET_WEBSITE_PROFILE_ACTION,
			'website_profile_options'        => self::websiteProfileOptions(),
			'website_profile_recommendation' => $recommendation,
		);
	}

	public function handleSetWebsiteProfile(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'pivot_performance_toolkit_set_website_profile' );

		$profile = isset( $_POST['pivot_performance_toolkit_website_profile'] ) ? sanitize_key( (string) wp_unslash( $_POST['pivot_performance_toolkit_website_profile'] ) ) : '';

		$settings                    = $this->settings->all();
		$settings['website_profile'] = $profile;

		update_option( $this->settings->optionKey(), $this->settings->sanitize( $settings ) );

		$options = self::websiteProfileOptions();
		$label   = isset( $options[ $profile ] ) ? $options[ $profile ] : $profile;

		wp_send_json_success(
			array(
				'message' => sprintf(
				/* translators: %s: profile label e.g. "Standard" */
					__( 'Website profile changed to %s.', 'pivot-performance-toolkit' ),
					$label
				),
			)
		);
	}

	/**
	 * @return array<string, string>
	 */
	private static function websiteProfileOptions(): array {
		return array(
			'standard'           => __( 'Standard', 'pivot-performance-toolkit' ),
			'woocommerce'        => __( 'WooCommerce', 'pivot-performance-toolkit' ),
			'membership-lms'     => __( 'Membership / LMS', 'pivot-performance-toolkit' ),
			'page-builder-heavy' => __( 'Page Builder Heavy', 'pivot-performance-toolkit' ),
		);
	}
}
