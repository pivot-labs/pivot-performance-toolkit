@props([
    'name'        => '',
    'id'          => '',
    'value'       => '',
    'options'     => [],   // [['value'=>'', 'label'=>'', 'logo'=>null], ...]
    'placeholder' => 'Select…',
])

@php
    $selected = collect($options)->firstWhere('value', $value) ?? null;
@endphp

<div
    class="ptk-icon-select"
    data-icon-select
    id="{{ $id }}-container"
>
    {{-- Hidden input carries the real form value --}}
    <input type="hidden" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" />

    {{-- Trigger button --}}
    <button
        type="button"
        id="{{ $id }}-button"
        class="ptk-icon-select__trigger"
        aria-haspopup="listbox"
        aria-expanded="false"
        aria-controls="{{ $id }}-options"
    >
        <span class="ptk-icon-select__selected">
            @if ($selected && !empty($selected['logo']))
                <img src="{{ $selected['logo'] }}" alt="" class="ptk-icon-select__logo" aria-hidden="true" />
            @else
                <span class="ptk-icon-select__logo-placeholder"></span>
            @endif
            <span class="ptk-icon-select__label">
                {{ $selected ? $selected['label'] : $placeholder }}
            </span>
        </span>
        {{-- Chevron --}}
        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="ptk-icon-select__chevron">
            <path d="M5.22 10.22a.75.75 0 0 1 1.06 0L8 11.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-2.25 2.25a.75.75 0 0 1-1.06 0l-2.25-2.25a.75.75 0 0 1 0-1.06ZM10.78 5.78a.75.75 0 0 1-1.06 0L8 4.06 6.28 5.78a.75.75 0 0 1-1.06-1.06l2.25-2.25a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06Z" />
        </svg>
    </button>

    {{-- Options panel --}}
    <ul
        id="{{ $id }}-options"
        class="ptk-icon-select__options"
        role="listbox"
        aria-label="{{ $placeholder }}"
        hidden
    >
        @foreach ($options as $index => $opt)
            <li
                class="ptk-icon-select__option"
                role="option"
                data-value="{{ $opt['value'] }}"
                data-label="{{ $opt['label'] }}"
                data-logo="{{ $opt['logo'] ?? '' }}"
                data-index="{{ $index }}"
                aria-selected="{{ $opt['value'] === $value ? 'true' : 'false' }}"
                tabindex="-1"
            >
                <span class="ptk-icon-select__option-content">
                    @if (!empty($opt['logo']))
                        <img src="{{ $opt['logo'] }}" alt="" class="ptk-icon-select__logo" aria-hidden="true" />
                    @else
                        <span class="ptk-icon-select__logo-placeholder"></span>
                    @endif
                    <span class="ptk-icon-select__option-label">
                        {{ $opt['label'] }}
                    </span>
                </span>
                {{-- Selected checkmark --}}
                <span class="ptk-icon-select__check{{ $opt['value'] === $value ? '' : ' hidden' }}" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="currentColor">
                        <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" />
                    </svg>
                </span>
            </li>
        @endforeach
    </ul>
</div>

