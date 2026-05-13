@props(['title' => '', 'id' => ''])

<section id="{{ $id ?? '' }}" class="ptk-card">
    <h2>{{ $title }}</h2>
    {{ $slot }}
</section>

