<x-card :title="__('Minification Exclusions', 'performance-toolkit')" id="ptk-file-optimization-exclusions">

    @php
        $exclusionsOpen = !empty(trim((string) $options['minify_external_css_exclusions']))
                       || !empty(trim((string) $options['minify_external_js_exclusions']));
    @endphp

    <style>
        .ptk-exclusions-trigger {
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
        .ptk-exclusions-trigger:hover { color: #374151; }
        .ptk-exclusions-trigger::before {
            content: "▼";
            display: inline-block;
            transition: transform 200ms ease;
            font-size: 12px;
            width: 16px;
            text-align: center;
        }
        .ptk-exclusions-trigger[aria-expanded="false"]::before {
            transform: rotate(-90deg);
        }
        .ptk-card-content {
            max-height: 2000px;
            overflow: hidden;
            transition: max-height 250ms ease, opacity 250ms ease;
            opacity: 1;
        }
        .ptk-card-content[aria-hidden="true"] {
            max-height: 0;
            opacity: 0;
        }
    </style>

     <div class="ptk-http11-warning">
         <strong>{{ __('Warning:', 'performance-toolkit') }}</strong>
         <span>{{ __('Minification exclusions can break dependency order, plugin-specific assets, and conditional loading logic. Use only after testing key pages.', 'performance-toolkit') }}</span>
     </div>

    <button
        type="button"
        class="ptk-exclusions-trigger ptk-collapse-trigger"
        aria-expanded="{{ $exclusionsOpen ? 'true' : 'false' }}"
        aria-controls="ptk-exclusions-content"
    >
        {{ __('Exclusion Rules', 'performance-toolkit') }}
    </button>

    <div id="ptk-exclusions-content" class="ptk-card-content ptk-collapsible-section" aria-hidden="{{ $exclusionsOpen ? 'false' : 'true' }}">

        <form method="post" action="{{ esc_url(admin_url('options.php')) }}">
            @php
                settings_fields('performance_toolkit');
            @endphp

            <div class="ptk-field">
                <label for="ptk-external-css-exclusions" style="display:block;font-weight:600;">
                    {{ __('External CSS exclusions', 'performance-toolkit') }}
                </label>
                <p>{{ __('Prevent specific stylesheets from being minified by external CSS minification.', 'performance-toolkit') }}</p>
                <textarea
                    id="ptk-external-css-exclusions"
                    name="{{ $option_key }}[minify_external_css_exclusions]"
                    class="ptk-exclusions-textarea"
                    rows="6"
                    placeholder="{{ esc_attr("woocommerce-layout\nstyle.css\n/wp-content/themes/your-theme/css/*") }}"
                    spellcheck="false"
                >{{ esc_textarea((string) $options['minify_external_css_exclusions']) }}</textarea>
                <p class="ptk-exclusions-hint">
                    {!! wp_kses(
                        __('<strong>One rule per line.</strong> You can exclude by stylesheet handle, file name, full path, or wildcard pattern. Examples: <code>woocommerce-layout</code>, <code>style.css</code>, <code>/wp-content/themes/your-theme/css/*</code>.', 'performance-toolkit'),
                        array('strong' => array(), 'code' => array())
                    ) !!}
                </p>
            </div>

            <div class="ptk-field">
                <label for="ptk-external-js-exclusions" style="display:block;font-weight:600;">
                    {{ __('External JavaScript exclusions', 'performance-toolkit') }}
                </label>
                <p>{{ __('Prevent specific scripts from being minified by external JavaScript minification.', 'performance-toolkit') }}</p>
                <textarea
                    id="ptk-external-js-exclusions"
                    name="{{ $option_key }}[minify_external_js_exclusions]"
                    class="ptk-exclusions-textarea"
                    rows="6"
                    placeholder="{{ esc_attr("jquery-core\napp.js\n/wp-content/themes/your-theme/js/*") }}"
                    spellcheck="false"
                >{{ esc_textarea((string) $options['minify_external_js_exclusions']) }}</textarea>
                <p class="ptk-exclusions-hint">
                    {!! wp_kses(
                        __('<strong>One rule per line.</strong> You can exclude by script handle, file name, full path, or wildcard pattern. Examples: <code>jquery-core</code>, <code>app.js</code>, <code>/wp-content/themes/your-theme/js/*</code>.', 'performance-toolkit'),
                        array('strong' => array(), 'code' => array())
                    ) !!}
                </p>
            </div>

            @php
                submit_button(__('Save exclusions', 'performance-toolkit'));
            @endphp
        </form>

    </div>

    <script>
    (function () {
        const trigger = document.querySelector('.ptk-exclusions-trigger');
        const content = document.getElementById('ptk-exclusions-content');
        if (!trigger || !content) return;

        trigger.addEventListener('click', function () {
            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
            trigger.setAttribute('aria-expanded', String(!isExpanded));
            content.setAttribute('aria-hidden', String(isExpanded));
        });
    })();
    </script>

</x-card>

