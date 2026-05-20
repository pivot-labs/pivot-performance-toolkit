@props([
    'title' => '',
    'description' => '',
    'icon' => 'dashicons-admin-settings',
    'tone' => 'indigo',
    'id' => '',
    'titleClass' => '',
])

<section {{ $attributes->merge(array('id' => $id, 'class' => 'w-full max-w-5xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm md:p-8')) }}>
    <div class="space-y-6">
        <header class="flex items-start gap-4">
            <x-card-icon :icon="$icon" :tone="$tone" />

            <div class="min-w-0 space-y-2">
                <h3 class="{{ trim('m-0 text-lg font-semibold tracking-tight text-gray-900 md:text-xl ' . $titleClass) }}" style="margin:0 !important;">{{ $title }}</h3>

                @if ($description !== '')
                    <p class="max-w-3xl text-sm leading-6 text-gray-500 md:text-base">
                        {{ $description }}
                    </p>
                @endif
            </div>
        </header>

        <div class="divide-y divide-gray-200">
            {{ $slot }}
        </div>
    </div>
</section>

