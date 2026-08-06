<?php
/**
 * Inserts a direct-access guard into every PHP file under includes/Vendor/.
 *
 * Runs as part of the Strauss build step (see composer.json), since Strauss
 * regenerates includes/Vendor/ from scratch on every install/update — any
 * guard added by hand here would otherwise be wiped on the next run.
 *
 * A namespace declaration must be the first statement in a file, so the
 * guard can't simply be prepended after `<?php`; it has to land after any
 * `declare()` and `namespace` statements. This uses PHP's own tokenizer to
 * find that point reliably rather than guessing with regex.
 */

$vendorDir = __DIR__ . '/../includes/Vendor';

if ( ! is_dir( $vendorDir ) ) {
	fwrite( STDERR, "add-abspath-guards: {$vendorDir} not found, skipping.\n" );
	exit( 0 );
}

$guard = "\nif ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n";

/**
 * Find the byte offset to insert the guard at: right after the last of any
 * leading `declare(...);` / `namespace ...;` statements, or right after the
 * opening `<?php` tag if neither is present.
 */
function ptk_find_insert_offset( string $content ): int {
	$tokens               = token_get_all( $content );
	$offset               = 0;
	$open_tag_end         = null;
	$insert_offset        = null;
	$in_leading_statement = false;

	foreach ( $tokens as $token ) {
		$is_array = is_array( $token );
		$id       = $is_array ? $token[0] : null;
		$text     = $is_array ? $token[1] : $token;

		if ( T_OPEN_TAG === $id && null === $open_tag_end ) {
			$open_tag_end = $offset + strlen( $text );
		}

		if ( T_DECLARE === $id || T_NAMESPACE === $id ) {
			$in_leading_statement = true;
		}

		$offset += strlen( $text );

		if ( $in_leading_statement && ';' === $text ) {
			$insert_offset        = $offset;
			$in_leading_statement = false;
		}
	}

	return $insert_offset ?? ( $open_tag_end ?? 5 );
}

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $vendorDir, FilesystemIterator::SKIP_DOTS )
);

$patched = 0;
$skipped = 0;
$total   = 0;
$errors  = array();

foreach ( $iterator as $file ) {
	if ( 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}

	++$total;
	$path    = $file->getPathname();
	$content = file_get_contents( $path );

	if ( false === $content ) {
		$errors[] = "could not read {$path}";
		continue;
	}

	if ( str_contains( $content, "defined( 'ABSPATH' )" ) ) {
		++$skipped;
		continue;
	}

	$insert_offset    = ptk_find_insert_offset( $content );
	$patched_content  = substr( $content, 0, $insert_offset ) . $guard . substr( $content, $insert_offset );

	if ( false === file_put_contents( $path, $patched_content ) ) {
		$errors[] = "could not write {$path}";
		continue;
	}

	++$patched;
}

echo "add-abspath-guards: patched {$patched}, already guarded {$skipped}, scanned {$total} PHP files under includes/Vendor/.\n";

if ( ! empty( $errors ) ) {
	fwrite( STDERR, implode( "\n", $errors ) . "\n" );
	exit( 1 );
}
