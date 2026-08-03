<?php
/**
 * Module contract interface.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ModuleInterface {

	public function register(): void;
}
