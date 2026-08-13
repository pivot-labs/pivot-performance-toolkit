<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props([
    'score' => 0,
    'size' => 180,
    'has_score' => true,
])

@php
    $normalizedScore = max(0, min(100, (int) round((float) $score)));

    if (!$has_score) {
        $band = array(
            'label' => __('No data yet', 'pivot-performance-toolkit'),
            'color' => '#9ca3af',
            'glow' => 'transparent',
        );
        $normalizedScore = 0;
    } elseif ($normalizedScore >= 90) {
        $band = array(
            'label' => 'Excellent',
            'color' => '#10b981',
            'glow' => 'rgba(16, 185, 129, .35)',
        );
    } elseif ($normalizedScore >= 75) {
        $band = array(
            'label' => 'Good',
            'color' => '#2563eb',
            'glow' => 'rgba(37, 99, 235, .35)',
        );
    } elseif ($normalizedScore >= 50) {
        $band = array(
            'label' => 'Needs Improvement',
            'color' => '#d97706',
            'glow' => 'rgba(217, 119, 6, .35)',
        );
    } else {
        $band = array(
            'label' => 'Poor',
            'color' => '#dc2626',
            'glow' => 'rgba(220, 38, 38, .35)',
        );
    }

    $sizePx = max(96, (int) $size);
@endphp

<div
    {{ $attributes->merge(array('class' => 'pivot-performance-toolkit-score-donut')) }}
    style="--score:{{ $normalizedScore }}; --size:{{ $sizePx }}px; --donut-color:{{ $band['color'] }}; --donut-glow:{{ $band['glow'] }};"
>
    <svg viewBox="0 0 120 120" aria-hidden="true">
        <circle class="track" cx="60" cy="60" r="52"></circle>
        <circle class="progress" cx="60" cy="60" r="52"></circle>
    </svg>
    <div class="label">
        <strong>{{ $has_score ? $normalizedScore : '–' }}</strong>
        <span>{{ $band['label'] }}</span>
    </div>
</div>


