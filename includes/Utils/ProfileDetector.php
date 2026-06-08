<?php
/**
 * Website profile recommendation detector.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Utils;

final class ProfileDetector {

	/**
	 * Detect a recommended website profile from active plugins/runtime signals.
	 *
	 * @return array{profile:string,label:string,signals:array<int,string>}
	 */
	public static function detectRecommendation(): array {
		$signals = self::detectPageBuilderSignals();

		if ( array() !== $signals ) {
			return array(
				'profile' => 'page-builder-heavy',
				'label'   => __( 'Page Builder Heavy', 'performance-toolkit' ),
				'signals' => $signals,
			);
		}

		$signals = self::detectMembershipLmsSignals();

		if ( array() !== $signals ) {
			return array(
				'profile' => 'membership-lms',
				'label'   => __( 'Membership / LMS', 'performance-toolkit' ),
				'signals' => $signals,
			);
		}

		$signals = self::detectWooCommerceSignals();

		if ( array() !== $signals ) {
			return array(
				'profile' => 'woocommerce',
				'label'   => __( 'WooCommerce', 'performance-toolkit' ),
				'signals' => $signals,
			);
		}

		return array(
			'profile' => 'standard',
			'label'   => __( 'Standard', 'performance-toolkit' ),
			'signals' => array(),
		);
	}

	/**
	 * @return string[]
	 */
	private static function detectPageBuilderSignals(): array {
		$matches = array();
		$active  = self::activePluginFiles();

		$plugin_map = array(
			'elementor/elementor.php'       => 'Elementor',
			'bb-plugin/fl-builder.php'      => 'Beaver Builder',
			'js_composer/js_composer.php'   => 'WPBakery',
			'oxygen/functions.php'          => 'Oxygen',
			'bricks/bricks.php'             => 'Bricks',
			'et-core-plugin/et-core-plugin.php' => 'Divi',
		);

		foreach ( $plugin_map as $plugin_file => $label ) {
			if ( isset( $active[ $plugin_file ] ) ) {
				$matches[] = $label;
			}
		}

		if ( did_action( 'elementor/loaded' ) > 0 && ! in_array( 'Elementor', $matches, true ) ) {
			$matches[] = 'Elementor';
		}

		if ( class_exists( 'FLBuilder' ) && ! in_array( 'Beaver Builder', $matches, true ) ) {
			$matches[] = 'Beaver Builder';
		}

		if ( defined( 'BRICKS_VERSION' ) && ! in_array( 'Bricks', $matches, true ) ) {
			$matches[] = 'Bricks';
		}

		sort( $matches, SORT_NATURAL | SORT_FLAG_CASE );

		return $matches;
	}

	/**
	 * @return string[]
	 */
	private static function detectWooCommerceSignals(): array {
		$matches = array();
		$active  = self::activePluginFiles();

		if ( isset( $active['woocommerce/woocommerce.php'] ) ) {
			$matches[] = 'WooCommerce';
		}

		if ( class_exists( 'WooCommerce' ) && ! in_array( 'WooCommerce', $matches, true ) ) {
			$matches[] = 'WooCommerce';
		}

		return $matches;
	}

	/**
	 * @return string[]
	 */
	private static function detectMembershipLmsSignals(): array {
		$matches = array();
		$active  = self::activePluginFiles();

		$plugin_map = array(
			'sfwd-lms/sfwd_lms.php'                                   => 'LearnDash',
			'lifterlms/lifterlms.php'                                 => 'LifterLMS',
			'tutor/tutor.php'                                         => 'Tutor LMS',
			'sensei-lms/sensei-lms.php'                               => 'Sensei',
			'memberpress/memberpress.php'                             => 'MemberPress',
			'paid-memberships-pro/paid-memberships-pro.php'           => 'Paid Memberships Pro',
			'restrict-content-pro/restrict-content-pro.php'           => 'Restrict Content Pro',
			'wishlist-member/wlmapi.php'                              => 'WishList Member',
		);

		foreach ( $plugin_map as $plugin_file => $label ) {
			if ( isset( $active[ $plugin_file ] ) ) {
				$matches[] = $label;
			}
		}

		// Runtime fallbacks.
		if ( defined( 'LEARNDASH_VERSION' ) && ! in_array( 'LearnDash', $matches, true ) ) {
			$matches[] = 'LearnDash';
		}

		if ( defined( 'LLMS_VERSION' ) && ! in_array( 'LifterLMS', $matches, true ) ) {
			$matches[] = 'LifterLMS';
		}

		if ( defined( 'TUTOR_VERSION' ) && ! in_array( 'Tutor LMS', $matches, true ) ) {
			$matches[] = 'Tutor LMS';
		}

		if ( defined( 'SENSEI_LMS_VERSION' ) && ! in_array( 'Sensei', $matches, true ) ) {
			$matches[] = 'Sensei';
		}

		if ( defined( 'MEMBERPRESS_VERSION' ) && ! in_array( 'MemberPress', $matches, true ) ) {
			$matches[] = 'MemberPress';
		}

		if ( defined( 'PMPRO_VERSION' ) && ! in_array( 'Paid Memberships Pro', $matches, true ) ) {
			$matches[] = 'Paid Memberships Pro';
		}

		if ( defined( 'RCP_PLUGIN_VERSION' ) && ! in_array( 'Restrict Content Pro', $matches, true ) ) {
			$matches[] = 'Restrict Content Pro';
		}

		if ( defined( 'WISHLISTMEMBER' ) && ! in_array( 'WishList Member', $matches, true ) ) {
			$matches[] = 'WishList Member';
		}

		sort( $matches, SORT_NATURAL | SORT_FLAG_CASE );

		return $matches;
	}

	/**
	 * @return array<string, true>
	 */
	private static function activePluginFiles(): array {
		$active_plugins = get_option( 'active_plugins', array() );
		$active_plugins = is_array( $active_plugins ) ? $active_plugins : array();

		if ( is_multisite() ) {
			$network_active = get_site_option( 'active_sitewide_plugins', array() );
			if ( is_array( $network_active ) ) {
				$active_plugins = array_merge( $active_plugins, array_keys( $network_active ) );
			}
		}

		$lookup = array();
		foreach ( $active_plugins as $plugin_file ) {
			if ( is_string( $plugin_file ) && '' !== $plugin_file ) {
				$lookup[ $plugin_file ] = true;
			}
		}

		return $lookup;
	}
}






