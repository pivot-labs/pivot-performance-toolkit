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

@include('cards.optimization.file.info.about')

@include('cards.optimization.file.info.recommendations')





</div>
