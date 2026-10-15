<?php
/**
 * Removes Composer's legacy "PHP < 5.6" guard from includes/Vendor/autoload.php.
 *
 * Composer generates that block unconditionally. It can never run here (the plugin
 * header requires PHP 8.2, which WordPress enforces), and it contains an `echo` of a
 * variable that the WP.org review flags under "escape output".
 *
 * Runs as part of the Strauss build step (see composer.json), since Strauss regenerates
 * includes/Vendor/ from scratch on every install/update. It is idempotent, and fails
 * loudly (exit 1) if a Composer/Strauss bump changes the generated file so the block
 * can no longer be found.
 */

$autoload_file = __DIR__ . '/../includes/Vendor/autoload.php';

if ( ! is_file( $autoload_file ) ) {
	fwrite( STDERR, "patch-vendor-autoload-php-check: {$autoload_file} not found.\n" );
	exit( 1 );
}

$contents = file_get_contents( $autoload_file );

if ( false === strpos( $contents, 'PHP_VERSION_ID < 50600' ) ) {
	echo "patch-vendor-autoload-php-check: nothing to remove.\n";
	exit( 0 );
}

$patched = preg_replace( '/if \(PHP_VERSION_ID < 50600\) \{.*?\n\}\n\n(?=require_once)/s', '', $contents, 1, $count );

if ( 1 !== $count || false !== strpos( $patched, 'PHP_VERSION_ID < 50600' ) ) {
	fwrite( STDERR, "patch-vendor-autoload-php-check: expected PHP < 5.6 guard not found in {$autoload_file} -- vendor output may have changed.\n" );
	exit( 1 );
}

file_put_contents( $autoload_file, $patched );
echo "patch-vendor-autoload-php-check: removed the PHP < 5.6 guard from autoload.php.\n";
