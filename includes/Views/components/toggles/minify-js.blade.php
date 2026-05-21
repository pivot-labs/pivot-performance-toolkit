@props([
    'action' => '',
    'nonce' => '',
    'checked' => false,
])

<x-toggles.toggle
    setting-key="minify_js"
    :checked="$checked"
    :action="$action"
    :nonce="$nonce"
    :label="__('Minify inline JavaScript', 'performance-toolkit')"
    :description="__('Minifies inline script blocks in frontend HTML output.', 'performance-toolkit')"
>
    <x-slot name="icon">
        <x-icons.js
            class="shrink-0"
            style="width: 32px; height: 32px; padding: 4px; box-sizing: border-box; border-radius: 8px;"
        />
    </x-slot>
</x-toggles.toggle>

