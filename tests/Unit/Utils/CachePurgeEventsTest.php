<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Utils;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Tests\Unit\TestCase;
use PivotPerformanceToolkit\Utils\CachePurgeEvents;

final class CachePurgeEventsTest extends TestCase {

	public function test_is_real_post_change_false_for_revision(): void {
		Functions\when( 'wp_is_post_revision' )->justReturn( true );
		Functions\when( 'wp_is_post_autosave' )->justReturn( false );

		self::assertFalse( CachePurgeEvents::isRealPostChange( 123 ) );
	}

	public function test_is_real_post_change_false_for_autosave(): void {
		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( true );

		self::assertFalse( CachePurgeEvents::isRealPostChange( 123 ) );
	}

	public function test_is_real_post_change_true_for_a_genuine_edit(): void {
		Functions\when( 'wp_is_post_revision' )->justReturn( false );
		Functions\when( 'wp_is_post_autosave' )->justReturn( false );

		self::assertTrue( CachePurgeEvents::isRealPostChange( 123 ) );
	}

	/**
	 * The whole point of the shared list: PageCache and CloudflareIntegration
	 * must invalidate on exactly the same events, or one cache layer can
	 * silently outlive the other after a content change.
	 */
	public function test_post_hooks_and_generic_hooks_do_not_overlap(): void {
		self::assertSame(
			array(),
			array_intersect( CachePurgeEvents::POST_HOOKS, CachePurgeEvents::GENERIC_HOOKS )
		);
	}

	public function test_post_hooks_covers_the_full_post_lifecycle(): void {
		self::assertSame(
			array( 'save_post', 'deleted_post', 'trashed_post', 'untrashed_post' ),
			CachePurgeEvents::POST_HOOKS
		);
	}
}
