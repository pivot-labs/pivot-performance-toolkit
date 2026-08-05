<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props([])

@php
    $svg = \PivotPerformanceToolkit\Admin\LucideIcons::render('css');
    $svg = str_replace('<svg ', '<svg class="h-7 w-7" ', $svg);
@endphp

<div {{ $attributes->merge(array('class' => 'flex items-center justify-center border border-blue-200 bg-blue-50 text-blue-600', 'style' => 'width: 38px; height: 38px; padding: 5px; box-sizing: border-box; border-radius: 5px;')) }}>
    {!! $svg !!}
</div>


