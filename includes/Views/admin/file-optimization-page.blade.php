<div class="ptk-col ptk-col--main">
    @if ($settings_updated && !isset($_GET['ptk_notice']))
        <div class="notice notice-success is-dismissible">
            <p>{{ __('Settings saved successfully.', 'performance-toolkit') }}</p>
        </div>
    @endif

    @include('cards.optimization.file.quick')
    @include('cards.optimization.file.exclusions')
    @include('cards.optimization.file.http11')
</div>


<div class="ptk-col ptk-col--sidebar">
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="mb-3 text-sm font-semibold text-gray-900">{{ __('Optimization Targets', 'performance-toolkit') }}</p>
        <div class="grid grid-cols-3 gap-3">
            <x-icons.css />
            <x-icons.html />
            <x-icons.js />
        </div>
    </div>
</div>
