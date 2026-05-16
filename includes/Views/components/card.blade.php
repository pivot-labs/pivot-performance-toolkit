@props(['title' => '', 'id' => ''])

<section {{ $attributes->merge(array('id' => $id ?? '', 'class' => 'ptk-card')) }}>
    <h2 class="ptk-card-title">{{ $title }}</h2>
    {{ $slot }}
</section>

