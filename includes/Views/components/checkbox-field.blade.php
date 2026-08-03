@props(['label' => '', 'name' => '', 'description' => '', 'checked' => false])

<div class="pivot-performance-toolkit-field">
    <label>
        <input
            type="checkbox"
            name="{{ $name }}"
            value="1"
            {{ $checked ? 'checked' : '' }}
        />
        <span>{{ $label }}</span>
    </label>
    @if ($description)
        <p>{{ $description }}</p>
    @endif
</div>

