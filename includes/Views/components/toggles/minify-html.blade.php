@props([
    'action' => '',
    'nonce' => '',
    'checked' => false,
])

<x-toggles.toggle
    setting-key="minify_html"
    :checked="$checked"
    :action="$action"
    :nonce="$nonce"
    :label="__('Minify HTML output', 'performance-toolkit')"
    :description="__('Removes non-essential whitespace and safe HTML comments from frontend output.', 'performance-toolkit')"
>
    <x-slot name="icon">
        <x-icons.html
            class="shrink-0 border-gray-200 bg-gray-100 text-gray-600"
            style="width: 32px; height: 32px; padding: 4px; box-sizing: border-box; border-radius: 8px;"
        />
    </x-slot>
</x-toggles.toggle>

