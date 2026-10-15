<?php
/**
 * Removes the Blade features this plugin never uses from the bundled
 * illuminate/view, because each of them buffers output with an ob_start() in
 * one method and closes it with ob_get_clean() in another (@section/@endsection,
 * @push/@endpush, @fragment, @lang). WordPress.org's review rejects an
 * ob_start() that is not closed in the same function.
 *
 * None of the plugin's 92 templates use those directives
 * (tests/Unit/Build/VendorViewTrimTest.php fails the build if one starts to).
 * What remains is <x-component> / slot support, whose buffer necessarily spans
 * the opening and closing tag of the component in a template.
 *
 * Runs after Strauss (see composer.json) because Strauss regenerates
 * includes/Vendor/ from scratch. Fails loudly (exit 1) if the vendor source
 * changes shape, rather than shipping an unpatched file.
 */

$vendor_dir = __DIR__ . '/../includes/Vendor';

if ( ! is_dir( $vendor_dir ) ) {
	fwrite( STDERR, "patch-vendor-view-trim: {$vendor_dir} not found, skipping.\n" );
	exit( 0 );
}

$traits  = array( 'ManagesComponents', 'ManagesFragments', 'ManagesLayouts', 'ManagesStacks', 'ManagesTranslations' );
$factory = $vendor_dir . '/illuminate/view/Factory.php';
$errors  = array();

$code = is_file( $factory ) ? file_get_contents( $factory ) : false;

if ( false === $code ) {
	fwrite( STDERR, "patch-vendor-view-trim: cannot read {$factory}\n" );
	exit( 1 );
}

foreach ( $traits as $trait ) {
	$pattern = '/,\s*\\\\PivotPerformanceToolkit\\\\Vendor\\\\Illuminate\\\\View\\\\Concerns\\\\' . $trait . '\b/';

	if ( preg_match( $pattern, $code ) ) {
		$code = (string) preg_replace( $pattern, '', $code, 1 );
	} elseif ( str_contains( $code, $trait ) ) {
		$errors[] = "Factory.php mentions {$trait} in an unexpected form.";
	}
}

foreach ( array( 'flushSections', 'flushStacks', 'flushFragments' ) as $call ) {
	$line = '        $this->' . $call . "();\n";

	if ( str_contains( $code, $line ) ) {
		$code = str_replace( $line, '', $code );
	} elseif ( str_contains( $code, '$this->' . $call . '(' ) ) {
		$errors[] = "Factory.php calls {$call}() in an unexpected form.";
	}
}

file_put_contents( $factory, $code );

$removed = 0;

foreach ( $traits as $trait ) {
	$file = $vendor_dir . '/illuminate/view/Concerns/' . $trait . '.php';

	if ( is_file( $file ) ) {
		unlink( $file );
		++$removed;
	}

	// Keep Composer's class maps from pointing at the deleted files.
	foreach ( array( 'autoload_classmap.php', 'autoload_static.php' ) as $map ) {
		$map_file = $vendor_dir . '/composer/' . $map;

		if ( ! is_file( $map_file ) ) {
			continue;
		}

		$lines = file( $map_file );
		$lines = array_filter( (array) $lines, static fn( string $l ): bool => ! str_contains( $l, "Concerns\\\\{$trait}'" ) );
		file_put_contents( $map_file, implode( '', $lines ) );
	}
}

echo "patch-vendor-view-trim: removed {$removed} unused view traits.\n";

if ( ! empty( $errors ) ) {
	fwrite( STDERR, implode( "\n", $errors ) . "\n" );
	exit( 1 );
}
