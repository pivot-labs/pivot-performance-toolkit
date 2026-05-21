<?php
/**
 * Admin page contract interface.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

interface AdminPageInterface {

	public function slug(): string;

	public function menuTitle(): string;

	public function pageTitle(): string;

	public function iconKey(): string;

	/**
	 * Legacy rendering entry point.
	 *
	 * New pages should also implement AdminPageViewInterface so AdminShell can
	 * render them without output buffering.
	 */
	public function renderContent(): void;
}
