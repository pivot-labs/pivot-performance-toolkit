/**
 * Pivot Performance Toolkit - delayed JavaScript runtime.
 *
 * Front-end script for the "Delay JavaScript execution" option. The plugin rewrites
 * eligible <script> tags to a placeholder type so the browser does not run them; this
 * file puts them back, in their original order, on the first user interaction
 * (mouse, scroll, key, touch, click) or after a fallback timeout, whichever is first.
 *
 * Settings arrive from PHP through wp_localize_script() as
 * window.pivotPerformanceToolkitDelayedJs:
 *   placeholderType - the temporary script type the plugin gave the delayed tags
 *   timeoutMs       - how long to wait before restoring them without an interaction
 */
(function () {
    var config = window.pivotPerformanceToolkitDelayedJs || {};
    var placeholderType = typeof config.placeholderType === 'string' ? config.placeholderType : '';
    var timeoutMs = Number(config.timeoutMs) > 0 ? Number(config.timeoutMs) : 8000;

    if (!placeholderType) {
        return;
    }

    var triggered = false;
    var events = ['mousemove', 'scroll', 'keydown', 'touchstart', 'click'];

    function restore() {
        if (triggered) return;
        triggered = true;

        events.forEach(function (evt) {
            window.removeEventListener(evt, restore, { passive: true });
        });
        clearTimeout(timer);

        var delayed = document.querySelectorAll('script[type="' + placeholderType + '"]');
        delayed.forEach(function (oldScript) {
            var newScript = document.createElement('script');
            // Dynamically-inserted external scripts execute as soon as they
            // finish fetching (not in insertion order) unless async is
            // explicitly disabled — required to preserve relative order
            // between e.g. wp-i18n and its inline "after" companion script.
            newScript.async = false;
            for (var i = 0; i < oldScript.attributes.length; i++) {
                var attr = oldScript.attributes[i];
                if (attr.name === 'type') continue;
                if (attr.name === 'data-pivot-delayed-src') {
                    newScript.src = attr.value;
                    continue;
                }
                newScript.setAttribute(attr.name, attr.value);
            }
            if (!newScript.src) {
                newScript.text = oldScript.text;
            }
            oldScript.replaceWith(newScript);
        });
    }

    var timer = setTimeout(restore, timeoutMs);
    events.forEach(function (evt) {
        window.addEventListener(evt, restore, { passive: true, once: true });
    });
})();
