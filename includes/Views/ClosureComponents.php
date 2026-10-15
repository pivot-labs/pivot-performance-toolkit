<?php
/**
 * Blade component and slot rendering that never leaves an output buffer open
 * across function calls.
 *
 * Illuminate's own implementation (Concerns\ManagesComponents) calls ob_start() when a
 * component or slot starts and ob_get_clean() in a different method when it ends.
 * WordPress.org requires every ob_start() to be closed in the same function, so
 * BladeEngine uses this trait instead, together with ClosureBladeCompiler, which
 * compiles the body of every component and slot into a closure. The closure is run
 * by capture(), which opens and closes the buffer in one function inside try/finally,
 * so a template that throws cannot leave a buffer open either.
 *
 * Behaviour is otherwise a copy of Illuminate 11's: the stacks, the data passed to a
 * component, and how its default and named slots are collected are unchanged.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Views;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Vendor\Illuminate\Contracts\Support\Htmlable;
use PivotPerformanceToolkit\Vendor\Illuminate\Contracts\View\View;
use PivotPerformanceToolkit\Vendor\Illuminate\Support\Arr;
use PivotPerformanceToolkit\Vendor\Illuminate\View\ComponentSlot;

trait ClosureComponents {

	/**
	 * The components being rendered.
	 *
	 * @var array<int, mixed>
	 */
	protected $component_stack = array();

	/**
	 * The original data passed to each component.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $component_data = array();

	/**
	 * The component data for the component that is currently being rendered.
	 *
	 * @var array<string, mixed>
	 */
	protected $current_component_data = array();

	/**
	 * The named slot contents for each component.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected $slots = array();

	/**
	 * Register a component whose body is about to run. Pairs with renderComponent().
	 *
	 * @param mixed                $view
	 * @param array<string, mixed> $data
	 */
	public function startComponent( $view, array $data = array() ): void {
		$this->component_stack[] = $view;

		$this->component_data[ $this->currentComponent() ] = $data;

		$this->slots[ $this->currentComponent() ] = array();
	}

	/**
	 * @param array<int, string>   $names
	 * @param array<string, mixed> $data
	 */
	public function startComponentFirst( array $names, array $data = array() ): void {
		$name = Arr::first(
			$names,
			function ( $item ) {
				return $this->exists( $item );
			}
		);

		$this->startComponent( $name, $data );
	}

	/**
	 * Run the component's body, then render the component with what it produced.
	 *
	 * @param \Closure             $body  Compiled body: the default slot and any named slots.
	 * @param array<string, mixed> $scope Variables of the calling template, for the body.
	 */
	public function renderComponent( \Closure $body, array $scope ): string {
		$default_slot = new ComponentSlot( trim( $this->capture( $body, $scope ) ) );
		$view         = array_pop( $this->component_stack );

		$previous_data                = $this->current_component_data;
		$data                         = $this->componentDataFor( $default_slot );
		$this->current_component_data = array_merge( $previous_data, $data );

		try {
			if ( $view instanceof \Closure ) {
				$view = $view( $data );
			}

			if ( $view instanceof View ) {
				return $view->with( $data )->render();
			}

			if ( $view instanceof Htmlable ) {
				return $view->toHtml();
			}

			return $this->make( $view, $data )->render();
		} finally {
			$this->current_component_data = $previous_data;
		}
	}

	/**
	 * Run a named slot's body and store what it produced on the current component.
	 *
	 * @param string               $name
	 * @param mixed                $content    A ready-made value, instead of a body.
	 * @param array<string, mixed> $attributes
	 * @param \Closure             $body
	 * @param array<string, mixed> $scope
	 */
	public function slot( $name, $content = null, $attributes = array(), ?\Closure $body = null, array $scope = array() ): void {
		if ( null !== $content || null === $body ) {
			$this->slots[ $this->currentComponent() ][ $name ] = $content;
			return;
		}

		$this->slots[ $this->currentComponent() ][ $name ] = new ComponentSlot( trim( $this->capture( $body, $scope ) ), $attributes );
	}

	/**
	 * Get an item from the data of the component being rendered, or one above it.
	 *
	 * @param string $key
	 * @param mixed  $fallback
	 * @return mixed
	 */
	public function getConsumableComponentData( $key, $fallback = null ) {
		if ( array_key_exists( $key, $this->current_component_data ) ) {
			return $this->current_component_data[ $key ];
		}

		for ( $i = count( $this->component_stack ) - 1; $i >= 0; $i-- ) {
			$data = $this->component_data[ $i ] ?? array();

			if ( array_key_exists( $key, $data ) ) {
				return $data[ $key ];
			}
		}

		return $fallback instanceof \Closure ? $fallback() : $fallback;
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function componentDataFor( ComponentSlot $default_slot ): array {
		$index = count( $this->component_stack );

		$slots = array_merge( array( '__default' => $default_slot ), $this->slots[ $index ] );

		return array_merge(
			$this->component_data[ $index ],
			array( 'slot' => $default_slot ),
			$this->slots[ $index ],
			array( '__laravel_slots' => $slots )
		);
	}

	protected function currentComponent(): int {
		return count( $this->component_stack ) - 1;
	}

	protected function flushComponents(): void {
		$this->component_stack        = array();
		$this->component_data         = array();
		$this->current_component_data = array();
	}

	/**
	 * Run $body and return what it printed. The buffer is opened and closed here, in
	 * one function, and closed even if the body throws.
	 *
	 * @param array<string, mixed> $scope
	 */
	private function capture( \Closure $body, array $scope ): string {
		ob_start();

		try {
			$body( $scope );
		} finally {
			$output = (string) ob_get_clean();
		}

		return $output;
	}
}
