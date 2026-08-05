<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props([
    'title' => '',
    'description' => '',
    'helper' => '',
    'icon' => 'rocket',
    'tone' => 'blue',
    'id' => '',
])

<section {{ $attributes->merge(array('id' => $id, 'class' => 'w-full max-w-5xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm md:p-8')) }}>
    <div class="grid gap-6 md:grid-cols-[minmax(0,1.8fr)_minmax(0,1fr)] md:items-center md:gap-8">
        <div class="space-y-4">
            <header class="flex items-start gap-4">
                <x-card-icon :icon="$icon" :tone="$tone" />

                <div class="min-w-0 space-y-2">
                    <h3 class="m-0 text-lg font-semibold tracking-tight text-gray-900 md:text-xl" style="margin:0 !important;">{{ $title }}</h3>

                    @if ($description !== '')
                        <p class="text-sm leading-6 text-gray-500 md:text-base">
                            {{ $description }}
                        </p>
                    @endif
                </div>
            </header>

            @if ($helper !== '')
                <p class="text-sm leading-6 text-gray-500">
                    {{ $helper }}
                </p>
            @endif

            @if (trim((string) $slot) !== '')
                <div class="space-y-4">
                    {{ $slot }}
                </div>
            @endif
        </div>

        @if (isset($actions) && trim((string) $actions) !== '')
            <aside class="space-y-4 rounded-xl border border-gray-100 bg-gray-50 p-4 md:p-5">
                {{ $actions }}
            </aside>
        @endif
    </div>
</section>

