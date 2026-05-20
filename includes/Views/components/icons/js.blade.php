@props([])

@php
    $svg = \PerformanceToolkit\Admin\LucideIcons::render('js');
    $svg = str_replace('<svg ', '<svg class="h-7 w-7" ', $svg);
@endphp

<div {{ $attributes->merge(array('class' => 'flex items-center justify-center border border-amber-200 bg-amber-50 text-amber-700', 'style' => 'width: 38px; height: 38px; padding: 5px; box-sizing: border-box; border-radius: 5px;')) }}>
    {!! $svg !!}
</div>


