<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props([
    'title' => '',
    'description' => '',
    'helper' => '',
    'noticeTone' => '',
    'noticeTitle' => '',
    'noticeText' => '',
])

@php
    $noticeClasses = array(
        'info' => 'border border-blue-200 bg-blue-50 text-blue-900',
        'danger' => 'border border-red-200 bg-red-50 text-red-900',
    );

    $noticeClass = $noticeTone !== '' ? ($noticeClasses[$noticeTone] ?? $noticeClasses['info']) : '';
@endphp

<section {{ $attributes->merge(array('class' => 'grid gap-6 py-6 first:pt-0 last:pb-0 md:grid-cols-[minmax(0,3fr)_minmax(280px,2fr)] md:gap-8')) }}>
    <div class="space-y-4">
        <div class="space-y-2">
            <h3 class="m-0 text-base font-semibold text-gray-900 md:text-lg" style="margin:0 !important;">{{ $title }}</h3>

            @if ($description !== '')
                <p class="text-sm leading-6 text-gray-500 md:text-base">
                    {{ $description }}
                </p>
            @endif
        </div>

        @if ($noticeText !== '')
            <div class="rounded-xl px-4 py-3 text-sm leading-6 {{ $noticeClass }}">
                @if ($noticeTitle !== '')
                    <p class="font-semibold">{{ $noticeTitle }}</p>
                @endif
                <p>{{ $noticeText }}</p>
            </div>
        @endif

        @if ($helper !== '')
            <p class="text-sm leading-6 text-gray-500">
                {{ $helper }}
            </p>
        @endif
    </div>

    @if (trim((string) $slot) !== '')
        <div class="space-y-4 rounded-xl border border-gray-100 bg-gray-50 p-4 md:p-5">
            {{ $slot }}
        </div>
    @endif
</section>


