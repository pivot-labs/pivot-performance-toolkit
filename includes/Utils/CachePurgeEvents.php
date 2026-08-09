<?php
/**
 * Canonical list of WordPress events that should invalidate cached pages.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CachePurgeEvents {

	/**
	 * Hooks that pass a post ID as their first argument. Revisions and
	 * autosaves fire these too — WordPress autosaves roughly every 60
	 * seconds while a post is open in the editor — so callers must check
	 * isRealPostChange() before purging, or every purge trigger ends up
	 * wiping the entire cache on every autosave tick, not just real edits.
	 *
	 * @var string[]
	 */
	public const POST_HOOKS = array(
		'save_post',
		'deleted_post',
		'trashed_post',
		'untrashed_post',
	);

	/**
	 * Hooks that should trigger a full purge but don't concern a single
	 * post, so the revision/autosave check above doesn't apply. Any of
	 * these can change output on pages that have nothing to do with the
	 * specific post/term/etc. involved (a menu or widget change can affect
	 * every page on the site), so a full purge — not a targeted one — is
	 * the only safe response to all of them.
	 *
	 * @var string[]
	 */
	public const GENERIC_HOOKS = array(
		'created_term',
		'edited_term',
		'delete_term',
		'wp_update_nav_menu',
		'customize_save_after',
		'transition_comment_status',
		'upgrader_process_complete',
	);

	/**
	 * Whether a POST_HOOKS callback's $post_id represents a real content
	 * change worth purging for, as opposed to an autosave or revision.
	 */
	public static function isRealPostChange( int $post_id ): bool {
		return ! wp_is_post_revision( $post_id ) && ! wp_is_post_autosave( $post_id );
	}
}
