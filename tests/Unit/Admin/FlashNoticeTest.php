<?php
/**
 * Coverage for FlashNotice: one-time, per-user notices that replace reading
 * notice text back out of redirect query strings.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Admin\FlashNotice;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class FlashNoticeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		// The per-request read cache is static; start every test clean.
		$property = new \ReflectionProperty( FlashNotice::class, 'loaded' );
		$property->setValue( null, array() );

		Functions\when( 'get_current_user_id' )->justReturn( 7 );
	}

	public function test_set_stores_a_short_lived_transient_keyed_by_user_and_scope(): void {
		Functions\expect( 'set_transient' )
			->once()
			->with( 'pivot_performance_toolkit_flash_7_tools', array( 'notice' => 'success' ), 120 );

		FlashNotice::set( 'tools', array( 'notice' => 'success' ) );

		self::addToAssertionCount( 1 );
	}

	public function test_get_returns_the_stored_notice_and_consumes_it(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'pivot_performance_toolkit_flash_7_tools' )
			->andReturn( array( 'notice' => 'error', 'message' => 'Nope' ) );
		Functions\expect( 'delete_transient' )
			->once()
			->with( 'pivot_performance_toolkit_flash_7_tools' );

		self::assertSame( array( 'notice' => 'error', 'message' => 'Nope' ), FlashNotice::get( 'tools' ) );
	}

	public function test_get_serves_the_same_notice_to_every_view_in_one_request(): void {
		Functions\expect( 'get_transient' )->once()->andReturn( array( 'cleared' => true ) );
		Functions\expect( 'delete_transient' )->once();

		self::assertSame( array( 'cleared' => true ), FlashNotice::get( 'tools' ) );
		self::assertSame( array( 'cleared' => true ), FlashNotice::get( 'tools' ) );
	}

	public function test_get_returns_an_empty_array_and_deletes_nothing_when_no_notice_is_stored(): void {
		Functions\expect( 'get_transient' )->once()->andReturn( false );
		Functions\expect( 'delete_transient' )->never();

		self::assertSame( array(), FlashNotice::get( 'database' ) );
	}

	public function test_get_ignores_a_stored_value_that_is_not_an_array(): void {
		Functions\when( 'get_transient' )->justReturn( 'unexpected' );
		Functions\when( 'delete_transient' )->justReturn( true );

		self::assertSame( array(), FlashNotice::get( 'cloudflare' ) );
	}

	public function test_scopes_are_independent(): void {
		Functions\when( 'get_transient' )->alias(
			static fn( string $key ) => 'pivot_performance_toolkit_flash_7_htaccess' === $key ? array( 'notice' => 'success' ) : false
		);
		Functions\when( 'delete_transient' )->justReturn( true );

		self::assertSame( array( 'notice' => 'success' ), FlashNotice::get( 'htaccess' ) );
		self::assertSame( array(), FlashNotice::get( 'cloudflare' ) );
	}

	public function test_settings_saved_is_true_only_for_the_core_settings_updated_entry(): void {
		Functions\when( 'get_settings_errors' )->justReturn(
			array(
				array( 'setting' => 'general', 'code' => 'settings_updated', 'message' => 'Settings saved.', 'type' => 'success' ),
			)
		);

		self::assertTrue( FlashNotice::settingsSaved() );
	}

	public function test_settings_saved_is_false_for_other_or_no_settings_errors(): void {
		Functions\when( 'get_settings_errors' )->justReturn(
			array(
				array( 'setting' => 'other', 'code' => 'some_error', 'message' => 'Bad value.', 'type' => 'error' ),
			)
		);

		self::assertFalse( FlashNotice::settingsSaved() );

		Functions\when( 'get_settings_errors' )->justReturn( array() );

		self::assertFalse( FlashNotice::settingsSaved() );
	}
}
