@props(['items' => [], 'allowHtml' => false])

<ul class="ptk-why-list">
    @foreach ($items as $item)
        <li class="ptk-why-list-item">
            <span class="ptk-why-check" aria-hidden="true">
                {!! \PerformanceToolkit\Admin\LucideIcons::render('circle-check') !!}
            </span>
            <span>
                @if ($allowHtml)
                    {!! wp_kses_post((string) $item) !!}
                @else
                    {{ $item }}
                @endif
            </span>
        </li>
    @endforeach
</ul>

