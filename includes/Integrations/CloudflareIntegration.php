<?php
/**
 * Cloudflare CDN integration.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Integrations;

use PerformanceToolkit\Contracts\ModuleInterface;
use PerformanceToolkit\Core\Settings;

final class CloudflareIntegration implements ModuleInterface {

	private const API_BASE = 'https://api.cloudflare.com/client/v4';

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'save_post', array( $this, 'handleContentChange' ) );
		add_action( 'deleted_post', array( $this, 'handleContentChange' ) );
		add_action( 'trashed_post', array( $this, 'handleContentChange' ) );
		add_action( 'untrashed_post', array( $this, 'handleContentChange' ) );
	}

	/**
	 * @return array{success: bool, message: string}
	 */
	public function testConnection(): array {
		if ( ! $this->hasCredentials() ) {
			return array(
				'success' => false,
				'message' => __( 'Cloudflare API token and Zone ID are required.', 'performance-toolkit' ),
			);
		}

		$result = $this->request( 'GET', sprintf( '/zones/%s', rawurlencode( $this->settings->getString( 'cloudflare_zone_id' ) ) ) );

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => '' !== $result['message'] ? $result['message'] : __( 'Cloudflare connection failed.', 'performance-toolkit' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Cloudflare connection successful.', 'performance-toolkit' ),
		);
	}

	/**
	 * @return array{success: bool, message: string}
	 */
	public function purgeCache(): array {
		if ( ! $this->hasCredentials() ) {
			return array(
				'success' => false,
				'message' => __( 'Cloudflare API token and Zone ID are required.', 'performance-toolkit' ),
			);
		}

		$result = $this->request(
			'POST',
			sprintf( '/zones/%s/purge_cache', rawurlencode( $this->settings->getString( 'cloudflare_zone_id' ) ) ),
			array( 'purge_everything' => true )
		);

		if ( ! $result['success'] ) {
			return array(
				'success' => false,
				'message' => '' !== $result['message'] ? $result['message'] : __( 'Cloudflare cache purge failed.', 'performance-toolkit' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Cloudflare cache purged.', 'performance-toolkit' ),
		);
	}

	public function handleContentChange( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! $this->shouldAutoPurge() ) {
			return;
		}

		$this->purgeCache();
	}

	private function shouldAutoPurge(): bool {
		if ( 'cloudflare' !== $this->settings->getString( 'cdn_provider' ) ) {
			return false;
		}

		if ( ! $this->settings->getBool( 'cloudflare_auto_purge' ) ) {
			return false;
		}

		return $this->hasCredentials();
	}

	private function hasCredentials(): bool {
		return '' !== $this->settings->getString( 'cloudflare_api_token' )
			&& '' !== $this->settings->getString( 'cloudflare_zone_id' );
	}

	/**
	 * @param array<string, mixed> $body
	 *
	 * @return array{success: bool, message: string}
	 */
	private function request( string $method, string $endpoint, array $body = array() ): array {
		$headers = array(
			'Authorization' => 'Bearer ' . $this->settings->getString( 'cloudflare_api_token' ),
			'Content-Type'  => 'application/json',
		);

		$args = array(
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 20,
		);

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::API_BASE . $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$decoded = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $decoded ) || ! array_key_exists( 'success', $decoded ) ) {
			return array(
				'success' => false,
				'message' => __( 'Unexpected response from Cloudflare API.', 'performance-toolkit' ),
			);
		}

		if ( ! empty( $decoded['success'] ) ) {
			return array(
				'success' => true,
				'message' => '',
			);
		}

		$message = '';

		if ( ! empty( $decoded['errors'][0]['message'] ) && is_string( $decoded['errors'][0]['message'] ) ) {
			$message = $decoded['errors'][0]['message'];
		}

		return array(
			'success' => false,
			'message' => $message,
		);
	}
}
