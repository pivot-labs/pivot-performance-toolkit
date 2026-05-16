@props(['title' => '', 'id' => ''])

<x-card :title="$title" :id="$id" {{ $attributes->merge(array('class' => 'ptk-info-card')) }}>
    {{ $slot }}
</x-card>

