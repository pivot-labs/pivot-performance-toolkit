<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
@props([
    'action' => '',
    'nonce' => '',
    'settingKey' => '',
    'checked' => false,
    'label' => '',
    'description' => '',
])

<style>
    .pivot-performance-toolkit-toggle-input:checked + .pivot-performance-toolkit-toggle-knob {
        transform: translateX(1.25rem);
    }
</style>

<li class="h-full">
    <form
        method="post"
        action="{{ esc_url(admin_url('admin-ajax.php')) }}"
        class="h-full"
        data-ajax-action-form
        data-ajax-autosave-form
        data-ajax-success-label="{{ esc_attr__('Saved', 'pivot-performance-toolkit') }}"
    >
        <input type="hidden" name="action" value="{{ esc_attr((string) $action) }}" />
        <input type="hidden" name="_ajax_nonce" value="{{ esc_attr((string) $nonce) }}" />
        <input type="hidden" name="setting_key" value="{{ esc_attr((string) $settingKey) }}" />
        <input type="hidden" name="setting_value" value="0" />

        <div class="flex h-full items-start justify-between gap-x-6">
            <div class="flex min-w-0 items-start gap-x-4">
                {{ $icon ?? '' }}
                <div class="min-w-0">
                    <div class="text-sm/6 font-semibold text-gray-900">{{ $label }}</div>
                    @if ($description)
                        <div class="mt-3 text-xs/5 text-gray-500">{{ $description }}</div>
                    @endif
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-x-2">
                <span
                    class="pivot-performance-toolkit-toggle-status"
                    aria-live="polite"
                    aria-atomic="true"
                    style="display: inline-flex; width: 56px; height: 22px; margin-right: 4px; align-items: center; justify-content: center; border-radius: 9999px; font-size: 11px; font-weight: 600; line-height: 1; opacity: 0; transition: opacity 180ms ease-in-out; box-sizing: border-box;"
                ></span>
                <div class="group relative inline-flex w-11 shrink-0 rounded-full bg-gray-200 p-0.5 inset-ring inset-ring-gray-900/5 outline-offset-2 outline-indigo-600 transition-colors duration-200 ease-in-out has-checked:bg-indigo-600 has-focus-visible:outline-2">
                    <input
                        type="checkbox"
                        name="setting_value"
                        value="1"
                        aria-label="{{ esc_attr((string) $label) }}"
                        {{ $checked ? 'checked' : '' }}
                        class="pivot-performance-toolkit-toggle-input absolute inset-0 z-10 m-0 h-full w-full opacity-0 appearance-none border-0 shadow-none focus:outline-hidden"
                        style="position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer;"
                    />
                    <span class="pivot-performance-toolkit-toggle-knob size-5 rounded-full bg-white shadow-xs ring-1 ring-gray-900/5 transition-transform duration-200 ease-in-out"></span>
                </div>
            </div>
        </div>
    </form>
</li>


