@props(['title' => '', 'id' => ''])

<x-card :title="$title" :id="$id" {{ $attributes->merge(array('class' => 'pivot-performance-toolkit-info-card')) }}>
    {{ $slot }}
</x-card>

