@props(['title' => '', 'id' => ''])

<section id="{{ $id ?? '' }}" class="ptk-card ptk-info-card">
    <h2 class="ptk-card-title ptk-info-card-title">{{ $title }}</h2>
    {{ $slot }}
</section>

