<?php
/**
 * Illuminate's view factory, with component and slot rendering done by
 * ClosureComponents instead of the vendored ManagesComponents trait.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Views;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Vendor\Illuminate\View\Factory;

final class ClosureViewFactory extends Factory {

	use ClosureComponents;
}
