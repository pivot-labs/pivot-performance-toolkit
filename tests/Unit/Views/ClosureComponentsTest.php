<?php
/**
 * Renders real Blade components through ClosureBladeCompiler / ClosureViewFactory, the
 * replacement for Illuminate's cross-function output buffering.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Views;

use PivotPerformanceToolkit\Tests\Unit\TestCase;
use PivotPerformanceToolkit\Vendor\Illuminate\Container\Container;
use PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Foundation\Application;
use PivotPerformanceToolkit\Vendor\Illuminate\Events\Dispatcher;
use PivotPerformanceToolkit\Vendor\Illuminate\Filesystem\Filesystem;
use PivotPerformanceToolkit\Vendor\Illuminate\View\Engines\CompilerEngine;
use PivotPerformanceToolkit\Vendor\Illuminate\View\Engines\EngineResolver;
use PivotPerformanceToolkit\Vendor\Illuminate\View\FileViewFinder;
use PivotPerformanceToolkit\Views\ClosureBladeCompiler;
use PivotPerformanceToolkit\Views\ClosureViewFactory;

final class ClosureComponentsTest extends TestCase {

	private string $dir;
	private ClosureViewFactory $factory;

	protected function setUp(): void {
		parent::setUp();

		require_once dirname( __DIR__, 3 ) . '/includes/Vendor/autoload.php';

		$this->dir = sys_get_temp_dir() . '/ptk-closure-components-' . uniqid();
		mkdir( $this->dir . '/views/components', 0777, true );
		mkdir( $this->dir . '/cache', 0777, true );

		$write = fn( string $name, string $body ) => file_put_contents( $this->dir . '/views/' . $name . '.blade.php', $body );

		$write( 'components/card', "@props(['title' => ''])\n<section data-title=\"{{ \$title }}\">[{{ \$slot }}]@isset(\$actions)<aside>{{ \$actions }}</aside>@endisset</section>" );
		$write( 'components/outer', "<div class=\"outer\">{{ \$slot }}</div>" );
		$write( 'page', "@php \$items = ['a', 'b']; \$suffix = '!'; @endphp\n<x-card title=\"T\"><x-slot:actions>act-{{ \$suffix }}</x-slot:actions>\nbody-{{ \$suffix }}</x-card>\n@foreach (\$items as \$item)<x-outer><x-card title=\"{{ \$item }}\">{{ \$item }}{{ \$loop->iteration }}</x-card></x-outer>@endforeach" );
		$write( 'failing', "<x-card>@php throw new \\RuntimeException('boom'); @endphp</x-card>" );

		$container = new Container();
		$container->instance(
			Application::class,
			new class() {
				public function getNamespace(): string {
					return 'PivotPerformanceToolkit\\';
				}
			}
		);

		$compiler = new ClosureBladeCompiler( new Filesystem(), $this->dir . '/cache' );
		$compiler->setEchoFormat( 'pivotperformancetoolkit_vendor_e(%s)' );
		$resolver = new EngineResolver();
		$resolver->register( 'blade', static fn() => new CompilerEngine( $compiler ) );

		$this->factory = new ClosureViewFactory( $resolver, new FileViewFinder( new Filesystem(), array( $this->dir . '/views' ) ), new Dispatcher( $container ) );
		$this->factory->setContainer( $container );
		$container->instance( 'view', $this->factory );
		$container->instance( \PivotPerformanceToolkit\Vendor\Illuminate\Contracts\View\Factory::class, $this->factory );
		$container->instance( \PivotPerformanceToolkit\Vendor\Illuminate\View\Factory::class, $this->factory );
		Container::setInstance( $container );
	}

	protected function tearDown(): void {
		( new Filesystem() )->deleteDirectory( $this->dir );

		parent::tearDown();
	}

	public function test_components_slots_and_loop_variables_render_like_illuminate(): void {
		$html = preg_replace( '/\s+/', '', $this->factory->make( 'page' )->render() );

		// A component with a default slot and a named slot.
		self::assertStringContainsString( '<sectiondata-title="T">[body-!]<aside>act-!</aside></section>', $html );

		// Nested components inside a loop keep seeing the loop's own variables.
		self::assertStringContainsString( '<divclass="outer"><sectiondata-title="a">[a1]</section></div>', $html );
		self::assertStringContainsString( '<divclass="outer"><sectiondata-title="b">[b2]</section></div>', $html );
	}

	public function test_a_template_that_throws_leaves_no_output_buffer_open(): void {
		$level = ob_get_level();

		try {
			$this->factory->make( 'failing' )->render();
			self::fail( 'The template should have thrown.' );
		} catch ( \Throwable $e ) {
			self::assertStringContainsString( 'boom', $e->getMessage() );
		}

		self::assertSame( $level, ob_get_level() );
	}

	public function test_compiled_templates_use_closures_not_split_buffers(): void {
		$this->factory->make( 'page' )->render();

		$compiled = '';

		foreach ( glob( $this->dir . '/cache/*.php' ) ?: array() as $file ) {
			$compiled .= (string) file_get_contents( $file );
		}

		self::assertStringContainsString( 'renderComponent(function (array $__scope)', $compiled );
		self::assertStringNotContainsString( 'endSlot()', $compiled );
		self::assertStringNotContainsString( 'echo $__env->renderComponent();', $compiled );
	}
}
