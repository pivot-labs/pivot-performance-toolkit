@props([
    'action' => '',
    'nonce' => '',
    'checked' => false,
])

<x-toggles.toggle
    setting-key="minify_external_css"
    :checked="$checked"
    :action="$action"
    :nonce="$nonce"
    :label="__('Minify external CSS files', 'performance-toolkit')"
    :description="__('Creates cached minified copies of local enqueued stylesheet files and rewrites their URLs.', 'performance-toolkit')"
>
    <x-slot name="icon">
        <x-icons.css
            class="shrink-0 border-gray-200 bg-gray-100 text-gray-600"
            style="width: 32px; height: 32px; padding: 4px; box-sizing: border-box; border-radius: 8px;"
        />
    </x-slot>
</x-toggles.toggle>

