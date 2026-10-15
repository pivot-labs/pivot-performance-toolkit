<?php
/**
 * Blade compiler that turns the body of every component and slot into a closure.
 *
 * Illuminate compiles `<x-card> ... </x-card>` to a start call, the body, and an end call,
 * with an output buffer held open between the first and the last. Here the body becomes
 * the closure passed to ClosureViewFactory::renderComponent() / ::slot(), which runs it
 * inside a buffer that is opened and closed in one function (see ClosureComponents).
 *
 * The closure receives the template's variables (get_defined_vars()) and extracts them,
 * so the body reads the same loop variables, props and `$attributes` it did before. The one
 * difference from Illuminate: a variable the body *assigns* is local to the body.
 *
 * Only the `<x-...>` tag syntax is supported. The legacy @component / @slot directives are
 * compiled the same way, but @componentFirst and aliasComponent() are not, and throw.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Views;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Vendor\Illuminate\Support\Str;
use PivotPerformanceToolkit\Vendor\Illuminate\View\Compilers\BladeCompiler;

final class ClosureBladeCompiler extends BladeCompiler {

	/**
	 * Bump when the compiled output changes shape, so templates compiled by an
	 * earlier version are not reused.
	 */
	private const COMPILED_FORMAT = 'closure-components-1';

	private const BODY_OPEN = '<?php echo $__env->renderComponent(function (array $__scope) { extract($__scope); ?>';

	public function getCompiledPath( $path ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- cachePath, basePath and compiledExtension are properties of Illuminate's Compiler, not ours.
		return $this->cachePath . '/' . hash( 'xxh128', self::COMPILED_FORMAT . Str::after( $path, $this->basePath ) ) . '.' . $this->compiledExtension;
	}

	/**
	 * `<x-name>` with a class or anonymous component.
	 */
	public static function compileClassComponentOpening( string $component, string $alias, string $data, string $hash ) {
		return parent::compileClassComponentOpening( $component, $alias, $data, $hash ) . "\n" . self::BODY_OPEN;
	}

	/**
	 * `@component('view', $data)`; the class form is handled above.
	 *
	 * @param string $expression
	 */
	protected function compileComponent( $expression ) {
		$compiled = parent::compileComponent( $expression );

		// The class form already carries the body opener; the view-name form does not.
		return str_contains( $compiled, 'renderComponent(function' ) ? $compiled : $compiled . "\n" . self::BODY_OPEN;
	}

	protected function compileEndComponent() {
		return '<?php }, get_defined_vars()); ?>';
	}

	/**
	 * @param string $expression `('name', null, [])` as written by the component tag compiler.
	 */
	protected function compileSlot( $expression ) {
		$arguments = trim( $expression );
		$arguments = '(' === substr( $arguments, 0, 1 ) ? substr( $arguments, 1, -1 ) : $arguments;

		// Name, content, attributes: pad what @slot('name') leaves out.
		$count   = $this->topLevelArgumentCount( $arguments );
		$padding = 1 === $count ? ', null, []' : ( 2 === $count ? ', []' : '' );

		return '<?php $__env->slot(' . $arguments . $padding . ', function (array $__scope) { extract($__scope); ?>';
	}

	protected function compileEndSlot() {
		return '<?php }, get_defined_vars()); ?>';
	}

	protected function compileComponentFirst( $expression ) {
		throw new \LogicException( '@componentFirst is not supported by this build of the view library.' );
	}

	public function aliasComponent( $path, $alias = null ) {
		throw new \LogicException( 'Legacy component aliases are not supported by this build of the view library.' );
	}

	/**
	 * Number of comma-separated arguments, ignoring commas inside brackets and strings.
	 */
	private function topLevelArgumentCount( string $arguments ): int {
		if ( '' === trim( $arguments ) ) {
			return 0;
		}

		$count = 1;
		$depth = 0;
		$quote = '';

		for ( $i = 0, $length = strlen( $arguments ); $i < $length; $i++ ) {
			$char = $arguments[ $i ];

			if ( '' !== $quote ) {
				if ( '\\' === $char ) {
					++$i;
				} elseif ( $char === $quote ) {
					$quote = '';
				}
				continue;
			}

			if ( '"' === $char || "'" === $char ) {
				$quote = $char;
			} elseif ( false !== strpos( '([{', $char ) ) {
				++$depth;
			} elseif ( false !== strpos( ')]}', $char ) ) {
				--$depth;
			} elseif ( ',' === $char && 0 === $depth ) {
				++$count;
			}
		}

		return $count;
	}
}
