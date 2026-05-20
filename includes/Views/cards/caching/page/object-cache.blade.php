<x-card :title="__('Object cache', 'performance-toolkit')" class="ptk-object-cache-card">
    <p class="ptk-object-cache-description">
        {{ __('Stores database query results and runtime objects in memory to reduce database load.', 'performance-toolkit') }}
    </p>

    <div class="ptk-object-cache-stats">
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('Status', 'performance-toolkit') }}</span>
            <strong class="{{ $object_cache['active'] ? 'ptk-object-cache-active' : 'ptk-object-cache-inactive' }}">
                {{ $object_cache['status_label'] }}
            </strong>
        </div>
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('Provider', 'performance-toolkit') }}</span>
            <strong>{{ $object_cache['provider'] }}</strong>
        </div>
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('Drop-in', 'performance-toolkit') }}</span>
            <strong>{{ $object_cache['dropin_label'] }}</strong>
        </div>
        <div class="ptk-stat">
            <span class="ptk-stat-label">{{ __('Size', 'performance-toolkit') }}</span>
            <strong>{{ $object_cache['size_formatted'] }}</strong>
        </div>
    </div>


    <p class="ptk-object-cache-note">
        {{ __('Future versions can add Redis/Memcached controls and metrics here.', 'performance-toolkit') }}
    </p>
</x-card>

