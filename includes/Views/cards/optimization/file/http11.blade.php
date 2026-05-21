<x-card :title="__('HTTP/1.1 File Combination', 'performance-toolkit')" id="ptk-file-optimization-http11">
    <form method="post" action="{{ esc_url(admin_url('options.php')) }}">
        @php
            settings_fields('performance_toolkit');
        @endphp

            <p>
                {{ __('Combining CSS/JS files is usually only beneficial on HTTP/1.1 servers. On HTTP/2 and HTTP/3, it often reduces cache efficiency and may hurt real-world performance.', 'performance-toolkit') }}
            </p>

            <div class="ptk-http11-warning">
                <strong>{{ __('Warning:', 'performance-toolkit') }}</strong>
                <span>{{ __('File combination can break dependency order, plugin-specific assets, and conditional loading logic. Use only after testing key pages.', 'performance-toolkit') }}</span>
            </div>

            <p class="text-xs text-gray-500 mt-3">
                {{ sprintf(__('Detected protocol: HTTP/%s', 'performance-toolkit'), !empty($http_protocol_version) ? (string) $http_protocol_version : __('unknown', 'performance-toolkit')) }}
            </p>

     <style>
         .ptk-http11-trigger {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 0;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: #1f2937;
            width: 100%;
            text-align: left;
        }

        .ptk-http11-trigger:hover {
            color: #374151;
        }

        .ptk-http11-trigger::before {
            content: "▼";
            display: inline-block;
            transition: transform 200ms ease;
            font-size: 12px;
            width: 16px;
            text-align: center;
        }

        .ptk-http11-trigger[aria-expanded="false"]::before {
            transform: rotate(-90deg);
        }

        .ptk-http11-content {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 200ms ease, opacity 200ms ease;
            opacity: 1;
        }

        .ptk-http11-content[aria-hidden="true"] {
            max-height: 0;
            opacity: 0;
        }
    </style>

    <button type="button" class="ptk-http11-trigger ptk-collapse-trigger" aria-expanded="true" aria-controls="ptk-http11-content">
        {{ __('Advanced Combination Settings', 'performance-toolkit') }}
    </button>

    <div id="ptk-http11-content" class="ptk-http11-content ptk-http11-only ptk-collapsible-section" aria-hidden="false">


            <div class="ptk-field" style="margin-top:14px;">
                <input type="hidden" name="{{ $option_key }}[combine_css]" value="0" />
                <label>
                    <input type="checkbox" name="{{ $option_key }}[combine_css]" value="1" {{ !empty($options['combine_css']) ? 'checked' : '' }} />
                    <span>{{ __('Combine CSS files', 'performance-toolkit') }}</span>
                </label>
                <p>{{ __('Merge eligible CSS files into fewer requests. Recommended only for HTTP/1.1 environments.', 'performance-toolkit') }}</p>

                <label for="ptk-combine-css-exclusions" style="display:block;margin-top:10px;font-weight:600;">
                    {{ __('CSS combine exclusions', 'performance-toolkit') }}
                </label>
                <textarea
                    id="ptk-combine-css-exclusions"
                    name="{{ $option_key }}[combine_css_exclusions]"
                    class="ptk-exclusions-textarea"
                    rows="5"
                    placeholder="{{ esc_attr("woocommerce-layout
style.css
/wp-content/themes/your-theme/css/*") }}"
                    spellcheck="false"
                >{{ esc_textarea((string) $options['combine_css_exclusions']) }}</textarea>
            </div>

            <div class="ptk-field">
                <input type="hidden" name="{{ $option_key }}[combine_js]" value="0" />
                <label>
                    <input type="checkbox" name="{{ $option_key }}[combine_js]" value="1" {{ !empty($options['combine_js']) ? 'checked' : '' }} />
                    <span>{{ __('Combine JavaScript files', 'performance-toolkit') }}</span>
                </label>
                <p>{{ __('Merge eligible JS files into fewer requests. Recommended only for HTTP/1.1 environments.', 'performance-toolkit') }}</p>

                <label for="ptk-combine-js-exclusions" style="display:block;margin-top:10px;font-weight:600;">
                    {{ __('JS combine exclusions', 'performance-toolkit') }}
                </label>
                <textarea
                    id="ptk-combine-js-exclusions"
                    name="{{ $option_key }}[combine_js_exclusions]"
                    class="ptk-exclusions-textarea"
                    rows="5"
                    placeholder="{{ esc_attr("jquery-core
app.js
/wp-content/themes/your-theme/js/*") }}"
                    spellcheck="false"
                >{{ esc_textarea((string) $options['combine_js_exclusions']) }}</textarea>
            </div>

             <p class="ptk-http11-note">
                 {{ __('Recommendation: keep minification enabled and only enable file combination when your origin truly serves HTTP/1.1 traffic.', 'performance-toolkit') }}
             </p>

             @php
                 submit_button(__('Save HTTP/1.1 settings', 'performance-toolkit'));
             @endphp
         </div>
     </form>
</x-card>

<script>
(function() {
    const trigger = document.querySelector('.ptk-http11-trigger');
    const content = document.getElementById('ptk-http11-content');
    const combineCssCheckbox = document.querySelector('input[name$="[combine_css]"][type="checkbox"]');
    const combineJsCheckbox = document.querySelector('input[name$="[combine_js]"][type="checkbox"]');

    // Determine initial state: open if any checkbox is checked
    function updateCollapsibleState() {
        const anyChecked = (combineCssCheckbox && combineCssCheckbox.checked) ||
                          (combineJsCheckbox && combineJsCheckbox.checked);

        if (trigger && content) {
            if (anyChecked) {
                // Open state
                trigger.setAttribute('aria-expanded', 'true');
                content.setAttribute('aria-hidden', 'false');
            } else {
                // Closed state
                trigger.setAttribute('aria-expanded', 'false');
                content.setAttribute('aria-hidden', 'true');
            }
        }
    }

    // Set initial state on page load
    updateCollapsibleState();

    // Toggle collapsible on button click
    if (trigger) {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
            trigger.setAttribute('aria-expanded', !isExpanded);
            content.setAttribute('aria-hidden', isExpanded);
        });
    }

    // Update state when checkboxes change
    if (combineCssCheckbox) {
        combineCssCheckbox.addEventListener('change', updateCollapsibleState);
    }
    if (combineJsCheckbox) {
        combineJsCheckbox.addEventListener('change', updateCollapsibleState);
    }
})();
</script>
