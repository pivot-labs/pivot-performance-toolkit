<?php
/**
 * Blade templating engine wrapper.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Views;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * Blade templating engine for the plugin.
 * Provides a clean, Laravel-like API for rendering views.
 */
final class BladeEngine {

	private static ?BladeEngine $instance = null;
	private Factory $view_factory;
	private Container $container;

	private function __construct() {
		$this->container = new Container();
		$this->setupViewFactory();
	}

	/**
	 * Get or create the singleton instance.
	 */
	public static function getInstance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup the Illuminate View Factory.
	 */
	private function setupViewFactory(): void {
		$filesystem = new Filesystem();

		// Define paths for views and cache
		$views_path = PERFORMANCE_TOOLKIT_PATH . 'includes/Views';
		$cache_path = WP_CONTENT_DIR . '/cache/performance-toolkit-blade';

		// Ensure cache directory exists
		if ( ! is_dir( $cache_path ) ) {
			wp_mkdir_p( $cache_path );
		}

		// Create the view finder
		$finder = new FileViewFinder( $filesystem, array( $views_path ) );

		// Create the engine resolver
		$resolver = new EngineResolver();

		// Register the Blade compiler
		$blade_compiler = new BladeCompiler( $filesystem, $cache_path );
		$resolver->register(
			'blade',
			function () use ( $blade_compiler ) {
				return new CompilerEngine( $blade_compiler );
			}
		);

		// Factory now requires an events dispatcher in recent Illuminate versions.
		$events             = new Dispatcher( $this->container );
		$this->view_factory = new Factory( $resolver, $finder, $events );
		$this->view_factory->setContainer( $this->container );

		// Blade component tag compilation resolves view contracts via the global container.
		$this->container->instance(
			Application::class,
			new class() {
				public function getNamespace(): string {
					return 'PerformanceToolkit\\';
				}
			}
		);
		$this->container->instance( 'view', $this->view_factory );
		$this->container->instance( ViewFactoryContract::class, $this->view_factory );
		$this->container->instance( Factory::class, $this->view_factory );
		Container::setInstance( $this->container );
	}

	/**
	 * Render a view with data.
	 *
	 * @param string $view    View name (e.g., 'admin.cache-page')
	 * @param array  $data    Data to pass to the view
	 * @return string         Rendered HTML
	 */
	public function render( string $view, array $data = array() ): string {
		return $this->view_factory->make( $view, $data )->render();
	}

	/**
	 * Helper function to render a Blade view globally.
	 */
	public static function view( string $view, array $data = array() ): string {
		return self::getInstance()->render( $view, $data );
	}
}
