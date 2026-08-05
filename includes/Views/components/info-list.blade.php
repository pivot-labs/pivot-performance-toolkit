<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props(['items' => [], 'allowHtml' => false])

<ul class="pivot-performance-toolkit-why-list">
    @foreach ($items as $item)
        <li class="pivot-performance-toolkit-why-list-item">
            <span class="pivot-performance-toolkit-why-check" aria-hidden="true">
                {!! \PivotPerformanceToolkit\Admin\LucideIcons::render('circle-check') !!}
            </span>
            <span>
                @if ($allowHtml)
                    {!! wp_kses_post((string) $item) !!}
                @else
                    {{ $item }}
                @endif
            </span>
        </li>
    @endforeach
</ul>

