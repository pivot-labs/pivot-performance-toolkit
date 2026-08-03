@props([
    'icon' => 'circle-check',
    'color' => 'blue',
    'title' => '',
    'description' => '',
    'form' => '',
])

@php
    $palette = array(
        'blue' => array(
            'icon_bg' => 'bg-blue-50',
            'icon_text' => 'text-blue-600',
        ),
        'amber' => array(
            'icon_bg' => 'bg-amber-50',
            'icon_text' => 'text-amber-600',
        ),
        'red' => array(
            'icon_bg' => 'bg-red-50',
            'icon_text' => 'text-red-600',
        ),
        'green' => array(
            'icon_bg' => 'bg-green-50',
            'icon_text' => 'text-green-600',
        ),
        'indigo' => array(
            'icon_bg' => 'bg-indigo-50',
            'icon_text' => 'text-indigo-600',
        ),
        'slate' => array(
            'icon_bg' => 'bg-slate-100',
            'icon_text' => 'text-slate-600',
        ),
    );

    $variant = $palette[$color] ?? $palette['blue'];
    $icon_svg = \PivotPerformanceToolkit\Admin\LucideIcons::render((string) $icon);
    $icon_svg = str_replace('<svg ', '<svg class="h-5 w-5" ', $icon_svg);
@endphp

<div class="pivot-performance-toolkit-action-block flex min-h-44 gap-4 rounded-xl border border-slate-200 bg-white p-5">
    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg {{ $variant['icon_bg'] }} {{ $variant['icon_text'] }}">
        {!! $icon_svg !!}
    </div>

    <div class="flex min-w-0 flex-1 flex-col">
        <h3 class="m-0 text-base font-semibold leading-snug text-slate-900">
            {{ $title }}
        </h3>

        <p class="mt-2 text-sm leading-6 text-slate-500">
            {{ $description }}
        </p>

        <div class="mt-auto pt-5">
            {!! $form !!}
        </div>
    </div>
</div>

