<x-card :title="__('Minification Exclusions', 'performance-toolkit')" id="ptk-file-optimization-exclusions">

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
</x-card>

