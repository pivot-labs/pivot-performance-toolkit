<?php
/**
 * Documentation admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DocumentationPage extends BladeAdminPage {

	private const DOCS_URL = 'https://docs.pivotlabs.dev/performance-toolkit';

	public function slug(): string {
		return 'pivot-performance-toolkit-documentation';
	}

	public function menuTitle(): string {
		return __( 'Documentation', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Documentation', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'book-open';
	}


	public function view(): string {
		return 'admin.documentation-page';
	}

	/**
	 * @return array<string, string>
	 */
	protected function buildViewData(): array {
		return array(
			'docs_url' => self::DOCS_URL,
		);
	}
}
