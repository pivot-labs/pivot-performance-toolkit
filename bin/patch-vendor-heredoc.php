<?php
/**
 * Replaces HEREDOC/NOWDOC blocks in specific bundled vendor files with plain
 * string literals, per the WP.org review's "Do not use HEREDOC syntax"
 * rule (their stated reasoning: it hides unescaped variables from their
 * scanner — a blanket rule with no stated vendor-code exception).
 *
 * Runs as part of the Strauss build step (see composer.json), since Strauss
 * regenerates includes/Vendor/ from scratch on every install/update — a
 * hand-edit here would otherwise be wiped on the next run.
 *
 * Each replacement string below was generated with var_export() from the
 * *actual* runtime value PHP evaluates the original heredoc/nowdoc block to
 * (see bin-dev/extract-heredoc*.php for the extraction approach — the
 * strings here were never hand-retyped), so each is guaranteed behaviorally
 * identical to the original, not just visually similar. The search text is
 * matched exactly; if a Strauss/Composer version bump changes the vendor
 * source, this script fails loudly (exit 1) rather than silently shipping
 * an unpatched file.
 */

$vendorDir = __DIR__ . '/../includes/Vendor';

if ( ! is_dir( $vendorDir ) ) {
	fwrite( STDERR, "patch-vendor-heredoc: {$vendorDir} not found, skipping.\n" );
	exit( 0 );
}

/**
 * @var array<int, array{file: string, search: string, replace: string}>
 */
$patches = array(
	// line 68 in original vendor source
	array(
		'file'    => $vendorDir . '/symfony/translation-contracts/TranslatorTrait.php',
		'search'  => '        $intervalRegexp = <<<\'EOF\'
            /^(?P<interval>
                ({\\s*
                    (\\-?\\d+(\\.\\d+)?[\\s*,\\s*\\-?\\d+(\\.\\d+)?]*)
                \\s*})

                    |

                (?P<left_delimiter>[\\[\\]])
                    \\s*
                    (?P<left>-Inf|\\-?\\d+(\\.\\d+)?)
                    \\s*,\\s*
                    (?P<right>\\+?Inf|\\-?\\d+(\\.\\d+)?)
                    \\s*
                (?P<right_delimiter>[\\[\\]])
            )\\s*(?P<message>.*?)$/xs
            EOF;',
		'replace' => '        $intervalRegexp = \'/^(?P<interval>
    ({\\\\s*
        (\\\\-?\\\\d+(\\\\.\\\\d+)?[\\\\s*,\\\\s*\\\\-?\\\\d+(\\\\.\\\\d+)?]*)
    \\\\s*})

        |

    (?P<left_delimiter>[\\\\[\\\\]])
        \\\\s*
        (?P<left>-Inf|\\\\-?\\\\d+(\\\\.\\\\d+)?)
        \\\\s*,\\\\s*
        (?P<right>\\\\+?Inf|\\\\-?\\\\d+(\\\\.\\\\d+)?)
        \\\\s*
    (?P<right_delimiter>[\\\\[\\\\]])
)\\\\s*(?P<message>.*?)$/xs\';',
	),
	// line 55 in original vendor source
	array(
		'file'    => $vendorDir . '/illuminate/view/DynamicComponent.php',
		'search'  => '        $template = <<<\'EOF\'
<?php extract((new \\PivotPerformanceToolkit\\Vendor\\Illuminate\\Support\\Collection($attributes->getAttributes()))->mapWithKeys(function ($value, $key) { return [PivotPerformanceToolkit\\Vendor\\Illuminate\\Support\\Str::camel(str_replace([\':\', \'.\'], \' \', $key)) => $value]; })->all(), EXTR_SKIP); ?>
{{ props }}
<x-{{ component }} {{ bindings }} {{ attributes }}>
{{ slots }}
{{ defaultSlot }}
</x-{{ component }}>
EOF;',
		'replace' => '        $template = \'<?php extract((new \\\\PivotPerformanceToolkit\\\\Vendor\\\\Illuminate\\\\Support\\\\Collection($attributes->getAttributes()))->mapWithKeys(function ($value, $key) { return [PivotPerformanceToolkit\\\\Vendor\\\\Illuminate\\\\Support\\\\Str::camel(str_replace([\\\':\\\', \\\'.\\\'], \\\' \\\', $key)) => $value]; })->all(), EXTR_SKIP); ?>
{{ props }}
<x-{{ component }} {{ bindings }} {{ attributes }}>
{{ slots }}
{{ defaultSlot }}
</x-{{ component }}>\';',
	),
	// line 313 in original vendor source
	array(
		'file'    => $vendorDir . '/symfony/translation/Translator.php',
		'search'  => '        $content = \\sprintf(<<<EOF
            <?php

            use PivotPerformanceToolkit\\Vendor\\Symfony\\Component\\Translation\\MessageCatalogue;

            \\$catalogue = new MessageCatalogue(\'%s\', %s);

            %s
            return \\$catalogue;

            EOF,',
		'replace' => '        $content = \\sprintf(\'<?php

use PivotPerformanceToolkit\\\\Vendor\\\\Symfony\\\\Component\\\\Translation\\\\MessageCatalogue;

$catalogue = new MessageCatalogue(\\\'%s\\\', %s);

%s
return $catalogue;
\',',
	),
	// line 343 in original vendor source
	array(
		'file'    => $vendorDir . '/symfony/translation/Translator.php',
		'search'  => '            $fallbackContent .= \\sprintf(<<<\'EOF\'
                $catalogue%s = new MessageCatalogue(\'%s\', %s);
                $catalogue%s->addFallbackCatalogue($catalogue%s);

                EOF,',
		'replace' => '            $fallbackContent .= \\sprintf(\'$catalogue%s = new MessageCatalogue(\\\'%s\\\', %s);
$catalogue%s->addFallbackCatalogue($catalogue%s);
\',',
	),
);

$patched = 0;
$errors  = array();

foreach ( $patches as $patch ) {
	$path = $patch['file'];

	if ( ! is_file( $path ) ) {
		$errors[] = "file not found: {$path}";
		continue;
	}

	$content = file_get_contents( $path );

	if ( false === $content ) {
		$errors[] = "could not read {$path}";
		continue;
	}

	if ( ! str_contains( $content, $patch['search'] ) ) {
		$errors[] = "expected heredoc block not found in {$path} -- vendor source may have changed; regenerate this script's needle/replacement.";
		continue;
	}

	$new_content = str_replace( $patch['search'], $patch['replace'], $content );

	if ( false === file_put_contents( $path, $new_content ) ) {
		$errors[] = "could not write {$path}";
		continue;
	}

	++$patched;
}

echo "patch-vendor-heredoc: patched {$patched} of " . count( $patches ) . " known heredoc/nowdoc blocks.\n";

if ( ! empty( $errors ) ) {
	fwrite( STDERR, implode( "\n", $errors ) . "\n" );
	exit( 1 );
}
