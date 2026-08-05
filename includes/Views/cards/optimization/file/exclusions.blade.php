<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<x-card :title="__('Minification Exclusions', 'pivot-performance-toolkit')" id="pivot-performance-toolkit-file-optimization-exclusions">

    @php
        $exclusionsOpen = !empty(trim((string) $options['minify_external_css_exclusions']))
                       || !empty(trim((string) $options['minify_external_js_exclusions']));
    @endphp

    <style>
        .pivot-performance-toolkit-exclusions-trigger {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #1f2937;
            width: 100%;
            text-align: left;
        }
        .pivot-performance-toolkit-exclusions-trigger:hover { color: #374151; }
        .pivot-performance-toolkit-exclusions-trigger::before {
            content: "▼";
            display: inline-block;
            transition: transform 200ms ease;
            font-size: 12px;
            width: 16px;
            text-align: center;
        }
        .pivot-performance-toolkit-exclusions-trigger[aria-expanded="false"]::before {
            transform: rotate(-90deg);
        }
        .pivot-performance-toolkit-card-content {
            max-height: 2000px;
            overflow: hidden;
            transition: max-height 250ms ease, opacity 250ms ease;
            opacity: 1;
        }
        .pivot-performance-toolkit-card-content[aria-hidden="true"] {
            max-height: 0;
            opacity: 0;
        }
    </style>

     <div class="pivot-performance-toolkit-http11-warning">
         <strong>{{ __('Warning:', 'pivot-performance-toolkit') }}</strong>
         <span>{{ __('Minification exclusions can break dependency order, plugin-specific assets, and conditional loading logic. Use only after testing key pages.', 'pivot-performance-toolkit') }}</span>
     </div>

    <button
        type="button"
        class="pivot-performance-toolkit-exclusions-trigger pivot-performance-toolkit-collapse-trigger"
        aria-expanded="{{ $exclusionsOpen ? 'true' : 'false' }}"
        aria-controls="pivot-performance-toolkit-exclusions-content"
    >
        {{ __('Exclusion Rules', 'pivot-performance-toolkit') }}
    </button>

    <div id="pivot-performance-toolkit-exclusions-content" class="pivot-performance-toolkit-card-content pivot-performance-toolkit-collapsible-section" aria-hidden="{{ $exclusionsOpen ? 'false' : 'true' }}">

        <form method="post" action="{{ esc_url(admin_url('options.php')) }}">
            @php
                settings_fields('pivot_performance_toolkit');
            @endphp

            <div class="pivot-performance-toolkit-field">
                <label for="pivot-performance-toolkit-external-css-exclusions" style="display:block;font-weight:600;">
                    {{ __('External CSS exclusions', 'pivot-performance-toolkit') }}
                </label>
                <p>{{ __('Prevent specific stylesheets from being minified by external CSS minification.', 'pivot-performance-toolkit') }}</p>
                <textarea
                    id="pivot-performance-toolkit-external-css-exclusions"
                    name="{{ $option_key }}[minify_external_css_exclusions]"
                    class="pivot-performance-toolkit-exclusions-textarea"
                    rows="6"
                    placeholder="{{ esc_attr("woocommerce-layout\nstyle.css\n/wp-content/themes/your-theme/css/*") }}"
                    spellcheck="false"
                >{{ esc_textarea((string) $options['minify_external_css_exclusions']) }}</textarea>
                <p class="pivot-performance-toolkit-exclusions-hint">
                    {!! wp_kses(
                        __('<strong>One rule per line.</strong> You can exclude by stylesheet handle, file name, full path, or wildcard pattern. Examples: <code>woocommerce-layout</code>, <code>style.css</code>, <code>/wp-content/themes/your-theme/css/*</code>.', 'pivot-performance-toolkit'),
                        array('strong' => array(), 'code' => array())
                    ) !!}
                </p>
            </div>

            <div class="pivot-performance-toolkit-field">
                <label for="pivot-performance-toolkit-external-js-exclusions" style="display:block;font-weight:600;">
                    {{ __('External JavaScript exclusions', 'pivot-performance-toolkit') }}
                </label>
                <p>{{ __('Prevent specific scripts from being minified by external JavaScript minification.', 'pivot-performance-toolkit') }}</p>
                <textarea
                    id="pivot-performance-toolkit-external-js-exclusions"
                    name="{{ $option_key }}[minify_external_js_exclusions]"
                    class="pivot-performance-toolkit-exclusions-textarea"
                    rows="6"
                    placeholder="{{ esc_attr("jquery-core\napp.js\n/wp-content/themes/your-theme/js/*") }}"
                    spellcheck="false"
                >{{ esc_textarea((string) $options['minify_external_js_exclusions']) }}</textarea>
                <p class="pivot-performance-toolkit-exclusions-hint">
                    {!! wp_kses(
                        __('<strong>One rule per line.</strong> You can exclude by script handle, file name, full path, or wildcard pattern. Examples: <code>jquery-core</code>, <code>app.js</code>, <code>/wp-content/themes/your-theme/js/*</code>.', 'pivot-performance-toolkit'),
                        array('strong' => array(), 'code' => array())
                    ) !!}
                </p>
            </div>

            @php
                submit_button(__('Save exclusions', 'pivot-performance-toolkit'));
            @endphp
        </form>

    </div>

    <script>
    (function () {
        const trigger = document.querySelector('.pivot-performance-toolkit-exclusions-trigger');
        const content = document.getElementById('pivot-performance-toolkit-exclusions-content');
        if (!trigger || !content) return;

        trigger.addEventListener('click', function () {
            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
            trigger.setAttribute('aria-expanded', String(!isExpanded));
            content.setAttribute('aria-hidden', String(isExpanded));
        });
    })();
    </script>

</x-card>

