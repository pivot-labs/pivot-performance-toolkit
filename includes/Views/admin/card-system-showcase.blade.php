<div class="space-y-8">
    <x-card-standard
        :title="__('Reset Performance Profile', 'pivot-performance-toolkit')"
        :description="__('Restore the plugin to a safe baseline when you want to roll back aggressive optimization changes or start a fresh round of testing.', 'pivot-performance-toolkit')"
        icon="trash-2"
        tone="red"
    >
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm leading-6 text-red-900">
            <p class="font-semibold">{{ __('Destructive action', 'pivot-performance-toolkit') }}</p>
            <p>{{ __('This resets cache, optimization, and media settings to recommended defaults. Export your configuration first if you may want to restore it later.', 'pivot-performance-toolkit') }}</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500">
                {{ __('Last configuration export: 2 days ago · Safe defaults profile ready to apply.', 'pivot-performance-toolkit') }}
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Export current settings', 'pivot-performance-toolkit') }}
                </button>
                <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                    {{ __('Reset to defaults', 'pivot-performance-toolkit') }}
                </button>
            </div>
        </div>
    </x-card-standard>

    <x-card-split
        :title="__('Cache Actions', 'pivot-performance-toolkit')"
        :description="__('Run common cache operations without leaving the page. This layout is ideal for utility actions paired with contextual status information.', 'pivot-performance-toolkit')"
        :helper="__('Use this pattern for actions like clearing page cache, rebuilding preloads, or updating edge cache providers.', 'pivot-performance-toolkit')"
        icon="rocket"
        tone="blue"
    >
        <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-6 text-gray-600">
            <p class="font-semibold text-gray-900">{{ __('Current status', 'pivot-performance-toolkit') }}</p>
            <p>{{ __('Page cache is enabled and serving 87% of anonymous requests from disk cache. Last cache clear was 14 minutes ago.', 'pivot-performance-toolkit') }}</p>
        </div>

        <x-slot:actions>
            <div class="space-y-3">
                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    {{ __('Recommended: clear the cache after changing minification, defer, or CDN settings.', 'pivot-performance-toolkit') }}
                </div>

                <button type="button" class="inline-flex w-full min-h-10 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    {{ __('Clear page cache', 'pivot-performance-toolkit') }}
                </button>

                <button type="button" class="inline-flex w-full min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Preload homepage cache', 'pivot-performance-toolkit') }}
                </button>

                <p class="text-xs leading-5 text-gray-500">
                    {{ __('Buttons should expand full-width on mobile and remain visually grouped in the lighter right column on larger screens.', 'pivot-performance-toolkit') }}
                </p>
            </div>
        </x-slot:actions>
    </x-card-split>

    <x-card-sectioned-split
        :title="__('CDN Import / Export Workflow', 'pivot-performance-toolkit')"
        :description="__('Use the sectioned split card for multi-step admin workflows where each area needs its own explanation, notice, and set of controls.', 'pivot-performance-toolkit')"
        icon="dashicons-admin-settings"
        tone="indigo"
    >
        <x-card-section
            :title="__('Export live configuration', 'pivot-performance-toolkit')"
            :description="__('Download the current plugin settings as a portable JSON bundle before changing environments or trying a more aggressive performance profile.', 'pivot-performance-toolkit')"
            noticeTone="info"
            :noticeTitle="__('Includes metadata', 'pivot-performance-toolkit')"
            :noticeText="__('Exports can include schema version, plugin version, and the export timestamp so support teams can review what was deployed.', 'pivot-performance-toolkit')"
        >
            <div class="space-y-4">
                <label class="flex items-start gap-3 text-sm text-gray-700">
                    <input type="checkbox" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                    <span>{{ __('Include API tokens and secret integration keys', 'pivot-performance-toolkit') }}</span>
                </label>

                <button type="button" class="inline-flex w-full min-h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    {{ __('Export settings JSON', 'pivot-performance-toolkit') }}
                </button>
            </div>
        </x-card-section>

        <x-card-section
            :title="__('Import configuration package', 'pivot-performance-toolkit')"
            :description="__('Apply a previously exported configuration file during migrations or when restoring a known-good setup on staging or production.', 'pivot-performance-toolkit')"
            noticeTone="danger"
            :noticeTitle="__('Validate before import', 'pivot-performance-toolkit')"
            :noticeText="__('A malformed import can overwrite current settings. Always review the incoming environment and confirm it matches the current site before applying it.', 'pivot-performance-toolkit')"
        >
            <div class="space-y-4">
                <input type="file" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200" />

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="button" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        {{ __('Validate import', 'pivot-performance-toolkit') }}
                    </button>
                    <button type="button" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        {{ __('Import settings', 'pivot-performance-toolkit') }}
                    </button>
                </div>
            </div>
        </x-card-section>
    </x-card-sectioned-split>
</div>

