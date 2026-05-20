@props([
    'action' => '',
    'nonce' => '',
    'checked' => false,
])

<x-toggles.toggle
    setting-key="defer_scripts"
    :checked="$checked"
    :action="$action"
    :nonce="$nonce"
    :label="__('Defer frontend scripts', 'performance-toolkit')"
    :description="__('Adds defer to non-critical scripts where possible.', 'performance-toolkit')"
>
    <x-slot name="icon">
        <x-icons.js
            class="shrink-0 border-gray-200 bg-gray-100 text-gray-600"
            style="width: 32px; height: 32px; padding: 4px; box-sizing: border-box; border-radius: 8px;"
        />
    </x-slot>
</x-toggles.toggle>


