<?php
/**
 * Regenerate languages/pivot-performance-toolkit.pot.
 *
 * `wp i18n make-pot` parses source files as plain PHP, so it handles this
 * plugin's .php class files correctly (including plural/context forms). It
 * does not handle .blade.php templates the same way: Blade's directive and
 * component-tag syntax (`{{ __('...') }}`, `<x-card :title="__('...')">`)
 * is not valid standalone PHP, so its tokenizer loses the rest of a file
 * once it hits a construct it can't parse — silently dropping every
 * translatable string after that point.
 *
 * Fix: regex-extract every __()/_e()/esc_html__()/esc_html_e()/
 * esc_attr__()/esc_attr_e() call with a literal string argument from every
 * .blade.php file, re-emit them as plain `__()` calls in a throwaway stub
 * .php file (so wp-cli's normal parser picks them up and merges/dedupes
 * them against the native scan the usual way), run make-pot once, then
 * patch the stub's #: source references back to the real blade file:line
 * each string actually came from.
 *
 * Blade templates only ever use __/_e/esc_html__/esc_html_e/esc_attr__/
 * esc_attr_e with literal string arguments in this codebase (no _n/_x/
 * _nx/_ex — those are plain-.php-only and already handled natively), so
 * this script only needs to cover that simpler surface.
 */

declare(strict_types=1);

$root_dir = dirname( __DIR__ );
chdir( $root_dir );

$views_dir = $root_dir . '/includes/Views';
$pot_file  = 'languages/pivot-performance-toolkit.pot';
$stub_path = 'includes/pot-blade-stub.php';

/**
 * @return array<int, array{file: string, line: int, string: string}>
 */
function find_blade_files( string $dir ): array {
	$files = array();
	$rii   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );

	foreach ( $rii as $file ) {
		if ( $file->isFile() && str_ends_with( $file->getFilename(), '.blade.php' ) ) {
			$files[] = $file->getPathname();
		}
	}

	sort( $files );

	return $files;
}

function unescape_php_string( string $raw, string $quote ): string {
	if ( "'" === $quote ) {
		return str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $raw );
	}

	$map = array(
		'\\\\' => '\\',
		'\\"'  => '"',
		'\\n'  => "\n",
		'\\t'  => "\t",
		'\\r'  => "\r",
		'\\$'  => '$',
	);

	return strtr( $raw, $map );
}

$call_pattern = '/\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*(\'|")((?:\\\\.|(?!\1).)*)\1\s*,\s*\'pivot-performance-toolkit\'\s*\)/s';

$extracted = array();

foreach ( find_blade_files( $views_dir ) as $path ) {
	$relative = ltrim( str_replace( $root_dir, '', $path ), '/' );
	$content  = (string) file_get_contents( $path );

	if ( ! preg_match_all( $call_pattern, $content, $matches, PREG_OFFSET_CAPTURE ) ) {
		continue;
	}

	foreach ( $matches[0] as $i => $whole_match ) {
		$offset = $whole_match[1];
		$quote  = $matches[1][ $i ][0];
		$raw    = $matches[2][ $i ][0];
		$string = unescape_php_string( $raw, $quote );
		$line   = substr_count( $content, "\n", 0, $offset ) + 1;

		$extracted[] = array(
			'file'   => $relative,
			'line'   => $line,
			'string' => $string,
		);
	}
}

fwrite( STDERR, 'Extracted ' . count( $extracted ) . " translatable string occurrences from .blade.php templates.\n" );

$stub_lines = array( '<?php' );
$line_map   = array(); // stub line number => ['file' => ..., 'line' => ...]

foreach ( $extracted as $item ) {
	$stub_lines[] = '__(' . var_export( $item['string'], true ) . ", 'pivot-performance-toolkit' );";
	$stub_line_no = count( $stub_lines );
	$line_map[ $stub_line_no ] = array(
		'file' => $item['file'],
		'line' => $item['line'],
	);
}

file_put_contents( $stub_path, implode( "\n", $stub_lines ) . "\n" );

$wp_cli_bin = file_exists( $root_dir . '/vendor/bin/wp' ) ? 'vendor/bin/wp' : 'wp';

$exclude = 'includes/Vendor,vendor,node_modules,tests,bin,dist-builds,.wordpress-org';
$cmd     = sprintf(
	'%s i18n make-pot %s %s --slug=pivot-performance-toolkit --exclude=%s --allow-root 2>&1',
	$wp_cli_bin,
	escapeshellarg( '.' ),
	escapeshellarg( $pot_file ),
	escapeshellarg( $exclude )
);

exec( $cmd, $output, $exit_code );
fwrite( STDERR, implode( "\n", $output ) . "\n" );

unlink( $stub_path );

if ( 0 !== $exit_code ) {
	fwrite( STDERR, "wp i18n make-pot failed.\n" );
	exit( 1 );
}

// Patch the stub's source references back to the real blade file:line, then
// de-duplicate reference comments within each entry (the stub-derived ref
// and a native partial-parse ref can occasionally point at the same spot).
$pot = (string) file_get_contents( $pot_file );

$pot = (string) preg_replace_callback(
	'/^#: includes\/pot-blade-stub\.php:(\d+)$/m',
	static function ( array $m ) use ( $line_map ): string {
		$stub_line = (int) $m[1];

		if ( ! isset( $line_map[ $stub_line ] ) ) {
			return $m[0];
		}

		return '#: ' . $line_map[ $stub_line ]['file'] . ':' . $line_map[ $stub_line ]['line'];
	},
	$pot
);

$entries = explode( "\n\n", $pot );

foreach ( $entries as &$entry ) {
	$lines      = explode( "\n", $entry );
	$seen_refs  = array();
	$new_lines  = array();

	foreach ( $lines as $line ) {
		if ( str_starts_with( $line, '#: ' ) ) {
			if ( isset( $seen_refs[ $line ] ) ) {
				continue;
			}

			$seen_refs[ $line ] = true;
		}

		$new_lines[] = $line;
	}

	$entry = implode( "\n", $new_lines );
}
unset( $entry );

file_put_contents( $pot_file, implode( "\n\n", $entries ) );

$final_count = substr_count( (string) file_get_contents( $pot_file ), "\nmsgid \"" ) + 1;
fwrite( STDERR, "Done. languages/pivot-performance-toolkit.pot now has approximately {$final_count} entries.\n" );
