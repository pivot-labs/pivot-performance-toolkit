<?php
/**
 * Renames the Composer-generated autoloader classes in includes/Vendor/ so
 * they carry this plugin's prefix instead of the generic, hash-suffixed
 * "ComposerAutoloaderInit<hash>" / "ComposerStaticInit<hash>" names.
 *
 * Strauss prefixes namespaced classes but leaves the global-scope
 * ComposerAutoloaderInit<hash> class alone, and the WP.org review flags any
 * global class without a plugin-specific prefix.
 *
 * Runs as part of the Strauss build step (see composer.json), since Strauss
 * regenerates includes/Vendor/ from scratch on every install/update. It is
 * idempotent: if the files are already renamed it does nothing, and it fails
 * loudly (exit 1) if a Composer/Strauss bump changes the generated output so
 * the expected names can't be found.
 */

$vendorDir = __DIR__ . '/../includes/Vendor';

if ( ! is_dir( $vendorDir ) ) {
	fwrite( STDERR, "rename-vendor-autoloader: {$vendorDir} not found, skipping.\n" );
	exit( 0 );
}

$files = array(
	$vendorDir . '/autoload.php',
	$vendorDir . '/composer/autoload_real.php',
	$vendorDir . '/composer/autoload_static.php',
);

$renames = array(
	'/ComposerAutoloaderInit[0-9a-f]{32}/' => 'PivotPerformanceToolkit_Autoloader_Init',
	'/ComposerStaticInit[0-9a-f]{32}/'     => 'PivotPerformanceToolkit_Autoloader_Static',
);

foreach ( $files as $file ) {
	if ( ! is_file( $file ) ) {
		fwrite( STDERR, "rename-vendor-autoloader: {$file} not found.\n" );
		exit( 1 );
	}

	$contents = file_get_contents( $file );
	$updated  = $contents;

	foreach ( $renames as $pattern => $replacement ) {
		$updated = preg_replace( $pattern, $replacement, $updated );
	}

	if ( $updated === $contents ) {
		if ( false !== strpos( $contents, 'PivotPerformanceToolkit_Autoloader_' ) ) {
			continue;
		}

		fwrite( STDERR, "rename-vendor-autoloader: no autoloader class names found in {$file}.\n" );
		exit( 1 );
	}

	file_put_contents( $file, $updated );
	echo 'Renamed autoloader classes in ' . basename( $file ) . "\n";
}
