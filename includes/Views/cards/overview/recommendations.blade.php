<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Recommendations', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-dashboard-recommendations">
    @php
        $severity_styles = array(
            'warning'   => array('border' => 'border-amber-300', 'bg' => 'bg-amber-50', 'text' => 'text-amber-700'),
            'suggested' => array('border' => 'border-emerald-300', 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700'),
            'info'      => array('border' => 'border-gray-300', 'bg' => 'bg-gray-50', 'text' => 'text-gray-700'),
        );
    @endphp

    @if (empty($recommendations))
        <p>{{ __('Nothing to recommend right now — your setup looks good.', 'pivot-performance-toolkit') }}</p>
    @else
        @foreach ($recommendations as $index => $recommendation)
            @php
                $style = $severity_styles[$recommendation['severity']] ?? $severity_styles['info'];
            @endphp
            <div class="rounded-md border {{ $style['border'] }} p-2.5 {{ $style['bg'] }} {{ $index > 0 ? 'mt-3' : '' }}">
                <div class="flex items-center justify-between gap-3">
                    <p class="font-bold {{ $style['text'] }}">{{ $recommendation['title'] }}</p>
                    @if (!empty($recommendation['action']))
                        <a href="{!! esc_url($recommendation['action']['url']) !!}" class="shrink-0 text-sm font-semibold {{ $style['text'] }} underline">
                            {{ $recommendation['action']['label'] }}
                        </a>
                    @endif
                </div>
                <p class="{{ $style['text'] }}">{{ $recommendation['description'] }}</p>
            </div>
        @endforeach
    @endif
</x-card>
