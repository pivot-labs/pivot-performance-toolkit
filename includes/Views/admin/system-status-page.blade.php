<x-card :title="__('System Status', 'performance-toolkit')" id="ptk-system-status">

    @if (!$fs_writable)
        <div style="margin-bottom: 16px; padding: 12px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
            <p style="margin: 0 0 8px;">
                <strong>{{ __('⚠ Filesystem Warning', 'performance-toolkit') }}</strong>
            </p>
            <p style="margin: 0;">
                {{ __('The Performance Toolkit cache directory is not writable. Caching and minification are disabled. Contact your hosting provider to ensure the cache directory has write permissions.', 'performance-toolkit') }}
            </p>
        </div>
    @endif

    <table class="ptk-table-list ptk-status-table">
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <th scope="row">{{ $row['label'] }}</th>
                    <td>
                        @if (!empty($row['is_html']))
                            {!! wp_kses_post($row['value']) !!}
                        @else
                            {{ $row['value'] }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-card>

