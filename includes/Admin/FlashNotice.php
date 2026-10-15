<?php
/**
 * One-time, per-user admin notices passed across a post-redirect-get cycle.
 *
 * The nonce-verified admin-post handlers store the outcome here server-side
 * and redirect to a clean URL; the page then reads (and consumes) it. Nothing
 * request-supplied is ever read back to build a notice, so a crafted link
 * cannot display arbitrary text in the admin.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FlashNotice {

	private const TTL = 120;

	/**
	 * Notices already read during this request, so several views can show the
	 * same notice even though the stored copy is consumed on first read.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $loaded = array();

	/**
	 * @param array<string, mixed> $data
	 */
	public static function set( string $scope, array $data ): void {
		set_transient( self::key( $scope ), $data, self::TTL );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get( string $scope ): array {
		if ( ! isset( self::$loaded[ $scope ] ) ) {
			$key    = self::key( $scope );
			$stored = get_transient( $key );

			if ( false !== $stored ) {
				delete_transient( $key );
			}

			self::$loaded[ $scope ] = is_array( $stored ) ? $stored : array();
		}

		return self::$loaded[ $scope ];
	}

	/**
	 * Whether WordPress's Settings API just saved this request's settings form.
	 *
	 * options.php records a "settings_updated" entry and redirects with
	 * ?settings-updated=true; reading it through get_settings_errors() lets core
	 * handle that query arg instead of this plugin reading $_GET directly.
	 */
	public static function settingsSaved(): bool {
		foreach ( get_settings_errors() as $error ) {
			if ( 'settings_updated' === ( $error['code'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	private static function key( string $scope ): string {
		return 'pivot_performance_toolkit_flash_' . get_current_user_id() . '_' . $scope;
	}
}
