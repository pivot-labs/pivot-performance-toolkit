<?php
/**
 * Documentation admin page.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class DocumentationPage extends BladeAdminPage {

	private const DOCS_URL = 'http://docs.wpperformancetoolkit.com';

	public function slug(): string {
		return 'performance-toolkit-documentation';
	}

	public function menuTitle(): string {
		return __( 'Documentation', 'performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Performance Toolkit Documentation', 'performance-toolkit' );
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
