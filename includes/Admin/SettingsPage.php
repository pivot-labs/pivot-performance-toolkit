<?php
/**
 * Settings admin page.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Utils\ProfileDetector;

final class SettingsPage extends BladeAdminPage {

	private const SET_UNINSTALL_POLICY_ACTION = 'performance_toolkit_set_uninstall_policy';
	private const SET_WEBSITE_PROFILE_ACTION  = 'performance_toolkit_set_website_profile';

	private const TOOLS_NOTICE_QUERY_KEY = 'ptk_tools_notice';

	private const TOOLS_MESSAGE_QUERY_KEY = 'ptk_tools_message';

	private const SETTINGS_NOTICE_QUERY_KEY = 'ptk_settings_notice';

	private const SETTINGS_MESSAGE_QUERY_KEY = 'ptk_settings_message';

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		add_action( 'wp_ajax_' . self::SET_WEBSITE_PROFILE_ACTION, array( $this, 'handleSetWebsiteProfile' ) );
	}

	public function slug(): string {
		return 'performance-toolkit-settings';
	}

	public function menuTitle(): string {
		return __( 'Settings', 'performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Performance Toolkit Settings', 'performance-toolkit' );
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
		$tools_notice     = isset( $_GET[ self::TOOLS_NOTICE_QUERY_KEY ] ) ? sanitize_key( (string) wp_unslash( $_GET[ self::TOOLS_NOTICE_QUERY_KEY ] ) ) : '';
		$tools_message    = isset( $_GET[ self::TOOLS_MESSAGE_QUERY_KEY ] ) ? sanitize_text_field( (string) wp_unslash( $_GET[ self::TOOLS_MESSAGE_QUERY_KEY ] ) ) : '';
		$settings_notice  = isset( $_GET[ self::SETTINGS_NOTICE_QUERY_KEY ] ) ? sanitize_key( (string) wp_unslash( $_GET[ self::SETTINGS_NOTICE_QUERY_KEY ] ) ) : '';
		$settings_message = isset( $_GET[ self::SETTINGS_MESSAGE_QUERY_KEY ] ) ? sanitize_text_field( (string) wp_unslash( $_GET[ self::SETTINGS_MESSAGE_QUERY_KEY ] ) ) : '';
		$current_settings = $this->settings->all();
		$recommendation   = ProfileDetector::detectRecommendation();

		return array(
			'tools_notice'                   => $tools_notice,
			'tools_message'                  => $tools_message,
			'settings_notice'                => $settings_notice,
			'settings_message'               => $settings_message,
			'cleanup_on_uninstall'           => (bool) get_option( 'performance_toolkit_remove_data_on_uninstall', false ),
			'set_uninstall_policy_action'    => self::SET_UNINSTALL_POLICY_ACTION,
			'website_profile'                => (string) ( $current_settings['website_profile'] ?? 'standard' ),
			'website_profile_action'         => self::SET_WEBSITE_PROFILE_ACTION,
			'website_profile_options'        => self::websiteProfileOptions(),
			'website_profile_recommendation' => $recommendation,
		);
	}

	public function handleSetWebsiteProfile(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to perform this action.', 'performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'ptk_set_website_profile' );

		$profile = isset( $_POST['ptk_website_profile'] ) ? sanitize_key( (string) wp_unslash( $_POST['ptk_website_profile'] ) ) : '';

		$settings                    = $this->settings->all();
		$settings['website_profile'] = $profile;

		update_option( $this->settings->optionKey(), $this->settings->sanitize( $settings ) );

		$options = self::websiteProfileOptions();
		$label   = isset( $options[ $profile ] ) ? $options[ $profile ] : $profile;

		wp_send_json_success(
			array(
				'message' => sprintf(
				/* translators: %s: profile label e.g. "Standard" */
					__( 'Website profile changed to %s.', 'performance-toolkit' ),
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
			'standard'           => __( 'Standard', 'performance-toolkit' ),
			'woocommerce'        => __( 'WooCommerce', 'performance-toolkit' ),
			'membership-lms'     => __( 'Membership / LMS', 'performance-toolkit' ),
			'page-builder-heavy' => __( 'Page Builder Heavy', 'performance-toolkit' ),
		);
	}
}
