<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props([
    'icon' => 'circle-check',
    'tone' => 'blue',
])

@php
    $tones = array(
        'blue' => array(
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-600',
        ),
        'indigo' => array(
            'bg' => 'bg-indigo-50',
            'text' => 'text-indigo-600',
        ),
        'emerald' => array(
            'bg' => 'bg-emerald-50',
            'text' => 'text-emerald-600',
        ),
        'amber' => array(
            'bg' => 'bg-amber-50',
            'text' => 'text-amber-600',
        ),
        'red' => array(
            'bg' => 'bg-red-50',
            'text' => 'text-red-600',
        ),
        'slate' => array(
            'bg' => 'bg-slate-100',
            'text' => 'text-slate-600',
        ),
    );

    $variant = $tones[$tone] ?? $tones['blue'];
    $iconSvg = \PivotPerformanceToolkit\Admin\LucideIcons::render((string) $icon);
    $iconSvg = str_replace('<svg ', '<svg class="h-5 w-5" ', $iconSvg);
@endphp

<div {{ $attributes->merge(array('class' => 'flex h-12 w-12 shrink-0 items-center justify-center rounded-full ' . $variant['bg'] . ' ' . $variant['text'])) }}>
    {!! $iconSvg !!}
</div>

