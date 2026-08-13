/* Pivot Performance Toolkit – admin sidebar toggle */
(function () {
    'use strict';

    function getAjaxUrl() {
        if (typeof window.ajaxurl === 'string' && window.ajaxurl !== '') {
            return window.ajaxurl;
        }

        return '/wp-admin/admin-ajax.php';
    }

    function showQuickActionNotice(container, type, message) {
        if (!container || !message) {
            return;
        }

        var existing = container.querySelector('.pivot-performance-toolkit-quick-action-notice');

        if (existing) {
            if (existing._ptkDismissTimer) {
                window.clearTimeout(existing._ptkDismissTimer);
            }
            existing.remove();
        }

        var notice = document.createElement('div');
        notice.className = 'notice ' + (type === 'error' ? 'notice-error' : 'notice-success') + ' pivot-performance-toolkit-quick-action-notice';

        var p = document.createElement('p');
        p.textContent = message;
        notice.appendChild(p);

        container.insertBefore(notice, container.firstChild);

        // Auto-dismiss success notices; errors stay until replaced.
        if (type !== 'error') {
            notice._ptkDismissTimer = window.setTimeout(function () {
                notice.style.transition = 'opacity 600ms ease-in-out';
                notice.style.opacity = '0';
                window.setTimeout(function () {
                    if (notice.parentNode) {
                        notice.parentNode.removeChild(notice);
                    }
                }, 620);
            }, 4000);
        }
    }

    function showToggleAutosaveStatus(form, type, message) {
        if (!form) {
            return false;
        }

        var status = form.querySelector('.pivot-performance-toolkit-toggle-status');

        if (!status) {
            return false;
        }

        if (form._ptkToggleStatusTimer) {
            window.clearTimeout(form._ptkToggleStatusTimer);
        }

        status.textContent = message;
        status.style.color = type === 'error' ? '#b91c1c' : '#15803d';
        status.style.backgroundColor = type === 'error' ? '#fee2e2' : '#dcfce7';
        status.style.transition = 'opacity 180ms ease-in-out';
        status.style.opacity = '1';

        form._ptkToggleStatusTimer = window.setTimeout(function () {
            status.style.transition = 'opacity 900ms ease-in-out';
            status.style.opacity = '0';
        }, 3000);

        return true;
    }

    function updateCacheUsage(usage) {
        if (!usage || typeof usage.usage_pct === 'undefined') {
            return;
        }

        var pct = parseInt(String(usage.usage_pct), 10);

        if (Number.isNaN(pct)) {
            return;
        }

        pct = Math.max(0, Math.min(100, pct));

        document.querySelectorAll('.pivot-performance-toolkit-cache-usage').forEach(function (container) {
            var fill = container.querySelector('.pivot-performance-toolkit-cache-usage-fill');
            var label = container.querySelector('.pivot-performance-toolkit-cache-usage-label');

            if (fill) {
                fill.style.width = pct + '%';
                fill.classList.toggle('is-critical', pct >= 90);
                fill.classList.toggle('is-warning', pct >= 70 && pct < 90);
            }

            if (label && usage.cache_usage_label) {
                label.textContent = String(usage.cache_usage_label);
            }
        });
    }

    function setQuickActionBusy(button, busy) {
        // <button> supports .disabled natively; <a role="button"> does not,
        // so aria-disabled + a guard in the click handler covers both.
        if (button.tagName === 'BUTTON') {
            button.disabled = busy;
        }

        button.setAttribute('aria-disabled', busy ? 'true' : 'false');
        button.classList.toggle('pivot-performance-toolkit-action-btn--busy', busy);

        var spinner = button.querySelector('.spinner');
        if (spinner) {
            spinner.classList.toggle('is-active', busy);
        }
    }

    function bindQuickActionButtons() {
        var i18n = (typeof window.ptkAdmin === 'object' && window.ptkAdmin) ? window.ptkAdmin : {};
        var requestFailedMessage = i18n.requestFailed || '';
        var buttons = document.querySelectorAll('.pivot-performance-toolkit-action-btn[data-ajax-action]');

        buttons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                if (button.getAttribute('aria-disabled') === 'true') {
                    return;
                }

                var action = button.getAttribute('data-ajax-action');
                var nonce = button.getAttribute('data-ajax-nonce') || '';
                var successMessage = button.getAttribute('data-success-message') || '';
                var list = button.closest('.pivot-performance-toolkit-action-list');

                if (!action || !list) {
                    return;
                }

                setQuickActionBusy(button, true);

                var body = new URLSearchParams();
                body.set('action', action);
                body.set('_ajax_nonce', nonce);

                fetch(getAjaxUrl(), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString()
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        var message = successMessage;
                        var usage = payload && payload.data ? payload.data.usage : null;

                        updateCacheUsage(usage);

                        if (payload && payload.data && payload.data.message) {
                            message = payload.data.message;
                        }

                        if (payload && payload.success) {
                            showQuickActionNotice(list, 'success', message);
                            return;
                        }

                        showQuickActionNotice(list, 'error', message || requestFailedMessage);
                    })
                    .catch(function () {
                        showQuickActionNotice(list, 'error', requestFailedMessage);
                    })
                    .finally(function () {
                        setQuickActionBusy(button, false);
                    });
            });
        });
    }

    function bindAjaxActionForms() {
        var i18n = (typeof window.ptkAdmin === 'object' && window.ptkAdmin) ? window.ptkAdmin : {};
        var requestFailedMessage = i18n.requestFailed || '';
        var savedMessage = i18n.saved || '';
        var errorMessage = i18n.error || '';

        document.querySelectorAll('form[data-ajax-action-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
                var controls = form.querySelectorAll('button, input, select, textarea');
                var isAutosaveForm = form.hasAttribute('data-ajax-autosave-form');
                var targetSelector = form.getAttribute('data-ajax-notice-target') || '';
                var container = targetSelector !== ''
                    ? document.querySelector(targetSelector)
                    : (form.closest('.pivot-performance-toolkit-action-block') || form);
                var spinner = form.querySelector('.spinner');
                var body = new URLSearchParams(new FormData(form));

                if (submitButton) {
                    submitButton.disabled = true;
                }

                controls.forEach(function (control) {
                    control.disabled = true;
                });

                if (spinner) {
                    spinner.classList.add('is-active');
                }

                fetch(getAjaxUrl(), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString()
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        var message = requestFailedMessage;

                        if (payload && payload.data && payload.data.message) {
                            message = payload.data.message;
                        }

                        if (payload && payload.data && payload.data.usage) {
                            updateCacheUsage(payload.data.usage);
                        }

                        if (payload && payload.success) {
                            if (isAutosaveForm && showToggleAutosaveStatus(form, 'success', form.getAttribute('data-ajax-success-label') || savedMessage)) {
                                return;
                            }

                            showQuickActionNotice(container, 'success', message);
                            return;
                        }

                        if (isAutosaveForm && showToggleAutosaveStatus(form, 'error', errorMessage)) {
                            return;
                        }

                        showQuickActionNotice(container, 'error', message);
                    })
                    .catch(function () {
                        if (isAutosaveForm && showToggleAutosaveStatus(form, 'error', errorMessage)) {
                            return;
                        }

                        showQuickActionNotice(container, 'error', requestFailedMessage);
                    })
                    .finally(function () {
                        controls.forEach(function (control) {
                            control.disabled = false;
                        });

                        if (submitButton) {
                            submitButton.disabled = false;
                        }

                        if (spinner) {
                            spinner.classList.remove('is-active');
                        }
                    });
            });
        });
    }

    function bindAjaxAutosaveForms() {
        document.querySelectorAll('form[data-ajax-autosave-form]').forEach(function (form) {
            form.addEventListener('change', function (event) {
                var target = event.target;

                if (!target || !target.matches('input, select, textarea')) {
                    return;
                }

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                    return;
                }

                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            });
        });
    }

    function bindSnippetCopyButtons() {
        var i18n = (typeof window.ptkSnippet === 'object' && window.ptkSnippet) ? window.ptkSnippet : {};
        var copiedLabel     = i18n.copied || '';
        var copyFailedLabel = i18n.copyFailed || '';

        document.querySelectorAll('.pivot-performance-toolkit-copy-snippet').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var text = btn.getAttribute('data-text');

                if (!navigator.clipboard) {
                    alert(copyFailedLabel);
                    return;
                }

                navigator.clipboard.writeText(text).then(function () {
                    var original = btn.textContent;
                    btn.textContent = copiedLabel;
                    setTimeout(function () {
                        btn.textContent = original;
                    }, 2000);
                }).catch(function () {
                    alert(copyFailedLabel);
                });
            });
        });
    }

    function bindSnippetToggles() {
        var i18n = (typeof window.ptkSnippet === 'object' && window.ptkSnippet) ? window.ptkSnippet : {};
        var expandLabel   = i18n.expand || '';
        var collapseLabel = i18n.collapse || '';

        document.querySelectorAll('.pivot-performance-toolkit-snippet-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var targetId = btn.getAttribute('data-target');
                var wrapper  = targetId ? document.getElementById(targetId) : null;

                if (!wrapper) {
                    return;
                }

                var expanded = wrapper.classList.toggle('pivot-performance-toolkit-snippet--expanded');
                btn.textContent = expanded ? collapseLabel : expandLabel;
            });
        });
    }

    /*
     * Generic collapse/expand trigger — powers the file-optimization
     * exclusions card and the HTTP/1.1 combine-settings card. Both markup
     * shapes match: a button with aria-expanded + aria-controls pointing at
     * the id of the content it toggles.
     */
    function bindCollapsibleTriggers() {
        document.querySelectorAll('.pivot-performance-toolkit-collapse-trigger').forEach(function (trigger) {
            var targetId = trigger.getAttribute('aria-controls');
            var content = targetId ? document.getElementById(targetId) : null;

            if (!content) {
                return;
            }

            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                var isExpanded = trigger.getAttribute('aria-expanded') === 'true';
                trigger.setAttribute('aria-expanded', String(!isExpanded));
                content.setAttribute('aria-hidden', String(isExpanded));
            });
        });
    }

    /*
     * HTTP/1.1 combine-settings card: auto-expand/collapse based on whether
     * either combine checkbox is checked, independent of the manual
     * click-to-toggle behavior bindCollapsibleTriggers() already provides.
     */
    function bindHttp11AutoExpand() {
        var trigger = document.querySelector('.pivot-performance-toolkit-http11-trigger');
        var content = document.getElementById('pivot-performance-toolkit-http11-content');
        var combineCssCheckbox = document.querySelector('input[name$="[combine_css]"][type="checkbox"]');
        var combineJsCheckbox = document.querySelector('input[name$="[combine_js]"][type="checkbox"]');

        if (!trigger || !content) {
            return;
        }

        function updateCollapsibleState() {
            var anyChecked = (combineCssCheckbox && combineCssCheckbox.checked) ||
                              (combineJsCheckbox && combineJsCheckbox.checked);

            trigger.setAttribute('aria-expanded', anyChecked ? 'true' : 'false');
            content.setAttribute('aria-hidden', anyChecked ? 'false' : 'true');
        }

        updateCollapsibleState();

        if (combineCssCheckbox) {
            combineCssCheckbox.addEventListener('change', updateCollapsibleState);
        }
        if (combineJsCheckbox) {
            combineJsCheckbox.addEventListener('change', updateCollapsibleState);
        }
    }

    function bindHtaccessToggle() {
        var i18n = (typeof window.ptkAdmin === 'object' && window.ptkAdmin) ? window.ptkAdmin : {};
        var requestFailedMessage = i18n.requestFailed || '';

        document.querySelectorAll('[data-htaccess-toggle]').forEach(function (container) {
            var button = container.querySelector('[data-htaccess-submit]');
            var title = container.querySelector('[data-htaccess-title]');
            var description = container.querySelector('[data-htaccess-description]');
            var noticeContainer = container.closest('#pivot-performance-toolkit-panel-htaccess')
                ? container.closest('#pivot-performance-toolkit-panel-htaccess').querySelector('.pivot-performance-toolkit-card-notices')
                : null;

            if (!button || !title || !description) {
                return;
            }

            button.addEventListener('click', function () {
                var applied = container.getAttribute('data-state') === 'applied';
                var action = applied ? container.getAttribute('data-remove-action') : container.getAttribute('data-apply-action');
                var nonce = applied ? container.getAttribute('data-remove-nonce') : container.getAttribute('data-apply-nonce');

                if (!action) {
                    return;
                }

                button.disabled = true;

                var body = new URLSearchParams();
                body.set('action', action);
                body.set('_ajax_nonce', nonce || '');

                fetch(getAjaxUrl(), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString()
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (payload) {
                        var message = requestFailedMessage;

                        if (payload && payload.data && payload.data.message) {
                            message = payload.data.message;
                        }

                        if (!payload || !payload.success) {
                            showQuickActionNotice(noticeContainer, 'error', message);
                            return;
                        }

                        var nowApplied = !!(payload.data && payload.data.applied);

                        container.setAttribute('data-state', nowApplied ? 'applied' : 'not-applied');
                        container.classList.toggle('border-green-200', nowApplied);
                        container.classList.toggle('bg-green-50', nowApplied);
                        container.classList.toggle('border-slate-200', !nowApplied);
                        container.classList.toggle('bg-slate-50', !nowApplied);

                        title.textContent = nowApplied
                            ? container.getAttribute('data-label-applied')
                            : container.getAttribute('data-label-not-applied');
                        title.classList.toggle('text-green-800', nowApplied);
                        title.classList.toggle('text-slate-900', !nowApplied);

                        description.textContent = nowApplied
                            ? container.getAttribute('data-description-applied')
                            : container.getAttribute('data-description-not-applied');
                        description.classList.toggle('text-green-700', nowApplied);
                        description.classList.toggle('text-slate-500', !nowApplied);

                        button.textContent = nowApplied
                            ? container.getAttribute('data-button-label-remove')
                            : container.getAttribute('data-button-label-apply');
                        button.classList.toggle('border-slate-300', nowApplied);
                        button.classList.toggle('bg-white', nowApplied);
                        button.classList.toggle('text-slate-700', nowApplied);
                        button.classList.toggle('hover:bg-slate-50', nowApplied);
                        button.classList.toggle('border-blue-600', !nowApplied);
                        button.classList.toggle('bg-blue-600', !nowApplied);
                        button.classList.toggle('text-white', !nowApplied);
                        button.classList.toggle('hover:bg-blue-700', !nowApplied);

                        showQuickActionNotice(noticeContainer, 'success', message);
                    })
                    .catch(function () {
                        showQuickActionNotice(noticeContainer, 'error', requestFailedMessage);
                    })
                    .finally(function () {
                        button.disabled = false;
                    });
            });
        });
    }

    function bindIconSelects() {
        document.querySelectorAll('[data-icon-select]').forEach(function (container) {
            var input    = container.querySelector('input[type="hidden"]');
            var trigger  = container.querySelector('.pivot-performance-toolkit-icon-select__trigger');
            var options  = container.querySelector('.pivot-performance-toolkit-icon-select__options');
            var optItems = container.querySelectorAll('.pivot-performance-toolkit-icon-select__option');

            if (!input || !trigger || !options) {
                return;
            }

            function getSelected() {
                return container.querySelector('.pivot-performance-toolkit-icon-select__option[aria-selected="true"]');
            }

            function getFocused() {
                return container.querySelector('.pivot-performance-toolkit-icon-select__option.is-focused');
            }

            function setFocus(item) {
                var prev = getFocused();
                if (prev) {
                    prev.classList.remove('is-focused');
                }
                if (item) {
                    item.classList.add('is-focused');
                    item.scrollIntoView({ block: 'nearest' });
                }
            }

            function selectOption(item) {
                if (!item) {
                    return;
                }
                var value = item.getAttribute('data-value') || '';
                var label = item.getAttribute('data-label') || '';
                var logo  = item.getAttribute('data-logo') || '';

                // Update hidden input
                input.value = value;
                input.dispatchEvent(new Event('change', { bubbles: true }));

                // Update trigger display
                var imgEl   = trigger.querySelector('.pivot-performance-toolkit-icon-select__logo');
                var phEl    = trigger.querySelector('.pivot-performance-toolkit-icon-select__logo-placeholder');
                var labelEl = trigger.querySelector('.pivot-performance-toolkit-icon-select__label');

                var safeLogo = '';
                if (logo !== '') {
                    try {
                        var parsedLogo = new URL(logo, window.location.origin);
                        if (parsedLogo.protocol === 'http:' || parsedLogo.protocol === 'https:') {
                            safeLogo = parsedLogo.href;
                        }
                    } catch (e) {
                        safeLogo = '';
                    }
                }

                if (safeLogo !== '') {
                    if (!imgEl) {
                        imgEl = document.createElement('img');
                        imgEl.className = 'pivot-performance-toolkit-icon-select__logo';
                        imgEl.setAttribute('aria-hidden', 'true');
                        imgEl.setAttribute('alt', '');
                        if (phEl) {
                            phEl.parentNode.replaceChild(imgEl, phEl);
                        }
                    }
                    imgEl.src = safeLogo;
                } else {
                    if (imgEl) {
                        var newPh = document.createElement('span');
                        newPh.className = 'pivot-performance-toolkit-icon-select__logo-placeholder';
                        imgEl.parentNode.replaceChild(newPh, imgEl);
                    }
                }

                if (labelEl) {
                    labelEl.textContent = label;
                }

                // Update aria-selected + checkmarks
                optItems.forEach(function (opt) {
                    var isThis = opt === item;
                    opt.setAttribute('aria-selected', isThis ? 'true' : 'false');
                    var check = opt.querySelector('.pivot-performance-toolkit-icon-select__check');
                    if (check) {
                        check.classList.toggle('hidden', !isThis);
                    }
                });

                close();
                trigger.focus();
            }

            function open() {
                document.querySelectorAll('[data-icon-select].is-open').forEach(function (openContainer) {
                    if (openContainer !== container) {
                        openContainer.classList.remove('is-open');
                        var openTrigger = openContainer.querySelector('.pivot-performance-toolkit-icon-select__trigger');
                        var openOptions = openContainer.querySelector('.pivot-performance-toolkit-icon-select__options');

                        if (openTrigger) {
                            openTrigger.setAttribute('aria-expanded', 'false');
                        }

                        if (openOptions) {
                            openOptions.hidden = true;
                        }

                        openContainer.querySelectorAll('.pivot-performance-toolkit-icon-select__option.is-focused').forEach(function (focusedOption) {
                            focusedOption.classList.remove('is-focused');
                        });
                    }
                });

                container.classList.add('is-open');
                options.hidden = false;
                trigger.setAttribute('aria-expanded', 'true');
                var sel = getSelected();
                setFocus(sel || optItems[0]);
            }

            function close() {
                container.classList.remove('is-open');
                options.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
                setFocus(null);
            }

            function isOpen() {
                return !options.hidden;
            }

            // Trigger click
            trigger.addEventListener('click', function () {
                isOpen() ? close() : open();
            });

            // Option click
            optItems.forEach(function (opt) {
                opt.addEventListener('click', function () {
                    selectOption(opt);
                });
                opt.addEventListener('mouseenter', function () {
                    setFocus(opt);
                });
            });

            // Keyboard navigation
            trigger.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (!isOpen()) {
                        open();
                        return;
                    }
                }
                if (!isOpen()) {
                    return;
                }
                var focused = getFocused();
                var items   = Array.from(optItems);
                var idx     = focused ? items.indexOf(focused) : -1;

                if (e.key === 'ArrowDown') {
                    setFocus(items[Math.min(idx + 1, items.length - 1)]);
                } else if (e.key === 'ArrowUp') {
                    setFocus(idx <= 0 ? items[0] : items[idx - 1]);
                } else if (e.key === 'Home') {
                    setFocus(items[0]);
                } else if (e.key === 'End') {
                    setFocus(items[items.length - 1]);
                } else if (e.key === 'Enter' || e.key === ' ') {
                    selectOption(focused);
                } else if (e.key === 'Escape' || e.key === 'Tab') {
                    close();
                }
            });

            // Close on outside click
            document.addEventListener('click', function (e) {
                if (!container.contains(e.target)) {
                    close();
                }
            });
        });
    }

    // provider select legacy helper removed; icon-select component is used instead

    function bindDisableOnSubmitForms() {
        document.querySelectorAll('form[data-disable-on-submit]').forEach(function (form) {
            var submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            var spinner = form.querySelector('.spinner');

            if (!submitButton) {
                return;
            }

            form.addEventListener('submit', function () {
                submitButton.disabled = true;

                if (spinner) {
                    spinner.classList.add('is-active');
                }
            });

            // If the user navigates back to this page from the browser's
            // back/forward cache, the DOM (including the disabled button
            // and active spinner from the previous submit) can be restored
            // exactly as it was left — reset it since no request is pending.
            window.addEventListener('pageshow', function () {
                submitButton.disabled = false;

                if (spinner) {
                    spinner.classList.remove('is-active');
                }
            });
        });
    }

    function bindWooExclusions() {
        var addButton = document.getElementById('pivot-performance-toolkit-add-woo-exclusions');
        var textarea = document.getElementById('pivot-performance-toolkit-excluded-urls');
        var data = (typeof window.ptkAdvancedRules === 'object' && window.ptkAdvancedRules) ? window.ptkAdvancedRules : {};
        var defaults = data.wooDefaults;

        if (!addButton || !textarea || !Array.isArray(defaults)) {
            return;
        }

        addButton.addEventListener('click', function () {
            var existing = textarea.value
                .split('\n')
                .map(function (line) {
                    return line.trim();
                })
                .filter(function (line) {
                    return line !== '';
                });

            var normalized = new Set(existing.map(function (line) {
                return line.toLowerCase();
            }));

            defaults.forEach(function (rule) {
                if (typeof rule !== 'string') {
                    return;
                }

                if (!normalized.has(rule.toLowerCase())) {
                    existing.push(rule);
                    normalized.add(rule.toLowerCase());
                }
            });

            textarea.value = existing.join('\n');
            textarea.focus();
        });
    }

    function bindBrowserCacheTest() {
        var testButton = document.getElementById('pivot-performance-toolkit-run-cache-test');

        if (!testButton) {
            return;
        }

        var data = (typeof window.ptkBrowserCacheTest === 'object' && window.ptkBrowserCacheTest) ? window.ptkBrowserCacheTest : {};
        var homeUrl = data.homeUrl || '/';
        var i18n = (typeof data.i18n === 'object' && data.i18n) ? data.i18n : {};

        var noAssetsLabel = i18n.noAssets || '';
        var resultsLabel = i18n.results || '';
        var assetsHaveHeadersLabel = i18n.assetsHaveHeaders || '';
        var assetsCompressedLabel = i18n.assetsCompressed || '';
        var configGoodLabel = i18n.configGood || '';
        var applyConfigLabel = i18n.applyConfig || '';
        var cacheNotSetLabel = i18n.notSet || '';
        var cacheNoneLabel = i18n.none || '';
        var cacheUnknownLabel = i18n.unknown || '';
        var cacheYesLabel = i18n.yes || '';
        var cacheErrorPrefixLabel = i18n.errorPrefix || '';
        var cacheNaLabel = i18n.na || '';
        var cacheGoodLabel = i18n.good || '';
        var cacheCheckLabel = i18n.check || '';
        var cacheCompressionOkLabel = i18n.compressionOk || '';
        var cacheNotUsedLabel = i18n.notUsed || '';
        var cssLabel = i18n.css || '';
        var jsLabel = i18n.js || '';
        var imageLabel = i18n.image || '';
        var errorLabel = i18n.error || '';

        testButton.addEventListener('click', runCacheTest);

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };

            return String(text).replace(/[&<>"']/g, function (m) {
                return map[m];
            });
        }

        function getTestAssets() {
            return fetch(homeUrl)
                .then(function (response) {
                    return response.text();
                })
                .then(function (html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    var assets = [];

                    doc.querySelectorAll('link[rel="stylesheet"]').forEach(function (link) {
                        var href = link.getAttribute('href');
                        if (href && !href.includes('//fonts.')) {
                            assets.push({ url: href, type: cssLabel, contentType: 'text/css' });
                        }
                    });

                    doc.querySelectorAll('script[src]').forEach(function (script) {
                        var src = script.getAttribute('src');
                        if (src && src.includes('.js') && !src.includes('//')) {
                            assets.push({ url: src, type: jsLabel, contentType: 'application/javascript' });
                        }
                    });

                    var img = doc.querySelector('img');
                    if (img) {
                        var src2 = img.getAttribute('src');
                        if (src2 && !src2.includes('//')) {
                            assets.push({ url: src2, type: imageLabel, contentType: 'image' });
                        }
                    }

                    return assets.slice(0, 5);
                })
                .catch(function (e) {
                    console.error('Error fetching home page:', e);
                    return [];
                });
        }

        function testAsset(asset) {
            var controller = new AbortController();
            var timeoutId = setTimeout(function () {
                controller.abort();
            }, 5000);

            return fetch(asset.url, { signal: controller.signal })
                .then(function (response) {
                    clearTimeout(timeoutId);

                    var cacheControl = response.headers.get('Cache-Control') || cacheNotSetLabel;
                    var contentEncoding = response.headers.get('Content-Encoding') || cacheNoneLabel;
                    var contentLength = response.headers.get('Content-Length') || cacheUnknownLabel;
                    var etag = response.headers.get('ETag');

                    return {
                        url: asset.url.split('/').pop(),
                        type: asset.type,
                        cacheControl: cacheControl,
                        encoding: contentEncoding,
                        size: contentLength,
                        etag: etag ? cacheYesLabel : cacheNoneLabel,
                        status: response.status
                    };
                })
                .catch(function (e) {
                    clearTimeout(timeoutId);
                    return {
                        url: asset.url.split('/').pop(),
                        type: asset.type,
                        cacheControl: cacheErrorPrefixLabel + ' ' + e.message,
                        encoding: cacheNaLabel,
                        size: cacheNaLabel,
                        etag: cacheNaLabel,
                        status: errorLabel
                    };
                });
        }

        function addResultRow(tbody, result) {
            var row = tbody.insertRow();
            var cacheStatus = result.cacheControl !== cacheNotSetLabel && result.cacheControl !== errorLabel ? cacheGoodLabel : cacheCheckLabel;
            var compressionStatus = result.encoding !== cacheNoneLabel && result.encoding !== cacheNaLabel ? cacheCompressionOkLabel + ' ' + result.encoding : cacheNotUsedLabel;

            row.innerHTML =
                '<td><strong>' + escapeHtml(result.type) + '</strong><br><small>' + escapeHtml(result.url) + '</small></td>' +
                '<td><small>' + escapeHtml(result.cacheControl) + '</small></td>' +
                '<td><small>' + escapeHtml(compressionStatus) + '</small></td>' +
                '<td>' + cacheStatus + '</td>';
        }

        function generateSummary(results, summaryElement) {
            if (results.length === 0) {
                summaryElement.textContent = noAssetsLabel;
                return;
            }

            var good = results.filter(function (r) {
                return r.cacheControl !== cacheNotSetLabel && r.cacheControl !== errorLabel && !r.cacheControl.includes(cacheErrorPrefixLabel);
            }).length;
            var compressed = results.filter(function (r) {
                return r.encoding !== cacheNoneLabel && r.encoding !== cacheNaLabel;
            }).length;

            var summary = '<strong>' + resultsLabel + ' ' + good + '/' + results.length + ' ' + assetsHaveHeadersLabel + '</strong><br>';
            summary += compressed + '/' + results.length + ' ' + assetsCompressedLabel;

            if (good === results.length && compressed >= Math.floor(results.length / 2)) {
                summary += '<br><strong style="color: #28a745;">' + configGoodLabel + '</strong>';
            } else if (good < results.length / 2) {
                summary += '<br><strong style="color: #ffc107;">' + applyConfigLabel + '</strong>';
            }

            summaryElement.innerHTML = summary;
        }

        function runCacheTest() {
            var status = document.getElementById('pivot-performance-toolkit-test-status');
            var results = document.getElementById('pivot-performance-toolkit-test-results');
            var errors = document.getElementById('pivot-performance-toolkit-test-errors');
            var resultsBody = document.getElementById('pivot-performance-toolkit-test-results-body');
            var summaryText = document.getElementById('pivot-performance-toolkit-test-summary-text');

            status.style.display = 'inline';
            results.style.display = 'none';
            errors.style.display = 'none';
            resultsBody.innerHTML = '';

            getTestAssets()
                .then(function (assets) {
                    var testResults = [];

                    return assets.reduce(function (chain, asset) {
                        return chain.then(function () {
                            return testAsset(asset).then(function (result) {
                                testResults.push(result);
                                addResultRow(resultsBody, result);
                            }).catch(function (e) {
                                console.error('Error testing asset:', asset, e);
                            });
                        });
                    }, Promise.resolve()).then(function () {
                        status.style.display = 'none';
                        results.style.display = 'block';
                        generateSummary(testResults, summaryText);
                    });
                })
                .catch(function (error) {
                    status.style.display = 'none';
                    errors.style.display = 'block';
                    document.getElementById('pivot-performance-toolkit-test-error-text').textContent = error.message;
                });
        }
    }

    function bindAssetsDetector() {
        var root = document.querySelector('[data-pivot-performance-toolkit-assets-detector]');
        if (!root) {
            return;
        }

        var select    = root.querySelector('[data-pivot-performance-toolkit-assets-select]');
        var runBtn    = root.querySelector('[data-pivot-performance-toolkit-assets-run]');
        var status    = root.querySelector('[data-pivot-performance-toolkit-assets-status]');
        var summary   = root.querySelector('[data-pivot-performance-toolkit-assets-summary]');
        var table     = root.querySelector('[data-pivot-performance-toolkit-assets-table]');
        var rowsWrap  = root.querySelector('[data-pivot-performance-toolkit-assets-rows]');
        var filtersBar = root.querySelector('[data-pivot-performance-toolkit-assets-filters]');

        if (!select || !runBtn || !status || !summary || !table || !rowsWrap) {
            return;
        }

        var data = (typeof window.ptkAssetsDetector === 'object' && window.ptkAssetsDetector) ? window.ptkAssetsDetector : {};
        var i18n = (typeof data.i18n === 'object' && data.i18n) ? data.i18n : {};

        var activeFilter = 'all';
        var typeCounts = { css: 0, javascript: 0, fonts: 0, images: 0, other: 0 };
        var summaryLabels = {
            css: i18n.css || '',
            javascript: i18n.javascript || '',
            fonts: i18n.fonts || '',
            images: i18n.images || '',
            other: i18n.other || ''
        };
        var detectedLabel = i18n.detected || '';

        function updateSummaryDisplay() {
            var parts = [];
            if (typeCounts.css > 0) parts.push(typeCounts.css + ' ' + summaryLabels.css);
            if (typeCounts.javascript > 0) parts.push(typeCounts.javascript + ' ' + summaryLabels.javascript);
            if (typeCounts.fonts > 0) parts.push(typeCounts.fonts + ' ' + summaryLabels.fonts);
            if (typeCounts.images > 0) parts.push(typeCounts.images + ' ' + summaryLabels.images);
            if (typeCounts.other > 0) parts.push(typeCounts.other + ' ' + summaryLabels.other);
            summary.textContent = parts.length > 0 ? detectedLabel + ' ' + parts.join(', ') : '';
        }

        function setStatus(message, isError) {
            status.textContent = message;
            status.style.color = isError ? '#b91c1c' : '#374151';
        }

        function clearRows() {
            rowsWrap.innerHTML = '';
            table.style.display = 'none';
            if (filtersBar) { filtersBar.style.display = 'none'; }
            summary.textContent = '';
            activeFilter = 'all';
            typeCounts = { css: 0, javascript: 0, fonts: 0, images: 0, other: 0 };
            if (filtersBar) {
                filtersBar.querySelectorAll('[data-pivot-performance-toolkit-filter]').forEach(function (btn) {
                    var isAll = btn.getAttribute('data-pivot-performance-toolkit-filter') === 'all';
                    btn.setAttribute('aria-pressed', isAll ? 'true' : 'false');
                    btn.classList.toggle('button-primary', isAll);
                });
            }
        }

        function applyFilter(filter) {
            activeFilter = filter;
            rowsWrap.querySelectorAll('tr[data-pivot-performance-toolkit-category]').forEach(function (tr) {
                var cat = tr.getAttribute('data-pivot-performance-toolkit-category') || '';
                tr.style.display = (filter === 'all' || cat === filter) ? '' : 'none';
            });
            if (filtersBar) {
                filtersBar.querySelectorAll('[data-pivot-performance-toolkit-filter]').forEach(function (btn) {
                    var isActive = btn.getAttribute('data-pivot-performance-toolkit-filter') === filter;
                    btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    btn.classList.toggle('button-primary', isActive);
                });
            }
        }

        function addRow(type, url, category) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-pivot-performance-toolkit-category', category);

            if (activeFilter !== 'all' && category !== activeFilter) {
                tr.style.display = 'none';
            }

            var typeKey = type.toLowerCase();
            if (typeKey === 'css' || typeKey === 'stylesheet') {
                typeCounts.css++;
            } else if (typeKey === 'javascript' || typeKey === 'script' || typeKey === 'js') {
                typeCounts.javascript++;
            } else if (typeKey === 'font' || typeKey === 'fonts') {
                typeCounts.fonts++;
            } else if (typeKey === 'image' || typeKey === 'img' || typeKey === 'svg') {
                typeCounts.images++;
            } else {
                typeCounts.other++;
            }

            var typeCell = document.createElement('td');
            typeCell.textContent = type;
            tr.appendChild(typeCell);

            var urlCell = document.createElement('td');
            urlCell.style.wordBreak = 'break-all';
            urlCell.style.overflowWrap = 'break-word';
            urlCell.style.maxWidth = '0';
            var link = document.createElement('a');
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = url;
            urlCell.appendChild(link);
            tr.appendChild(urlCell);

            var categoryCell = document.createElement('td');
            categoryCell.textContent = category;
            tr.appendChild(categoryCell);

            rowsWrap.appendChild(tr);
        }

        if (filtersBar) {
            filtersBar.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-pivot-performance-toolkit-filter]');
                if (!btn) { return; }
                applyFilter(btn.getAttribute('data-pivot-performance-toolkit-filter') || 'all');
            });
        }

        runBtn.addEventListener('click', function () {
            var action    = root.getAttribute('data-ajax-action') || '';
            var nonce     = root.getAttribute('data-ajax-nonce') || '';
            var targetUrl = select.value || '';
            var ajaxUrl   = getAjaxUrl();

            if (!targetUrl) {
                setStatus(i18n.selectUrl || '', true);
                return;
            }

            runBtn.disabled = true;
            clearRows();
            setStatus(i18n.detecting || '', false);

            var body = new URLSearchParams();
            body.set('action', action);
            body.set('_ajax_nonce', nonce);
            body.set('target_url', targetUrl);

            fetch(ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            })
                .then(function (r) { return r.json(); })
                .then(function (payload) {
                    if (!payload || !payload.success) {
                        var msg = payload && payload.data && payload.data.message
                            ? String(payload.data.message)
                            : (i18n.detectionFailed || '');
                        throw new Error(msg);
                    }

                    var rows = payload.data && Array.isArray(payload.data.rows) ? payload.data.rows : [];
                    var summaryText = payload.data && payload.data.summary_text ? String(payload.data.summary_text) : '';

                    if (rows.length === 0) {
                        setStatus(i18n.noAssetsFound || '', false);
                        summary.textContent = summaryText;
                        return;
                    }

                    rows.forEach(function (row) {
                        if (!row || typeof row !== 'object') { return; }
                        addRow(String(row.type || '-'), String(row.url || '-'), String(row.category || '-'));
                    });

                    table.style.display = '';
                    if (filtersBar) { filtersBar.style.display = 'flex'; }
                    updateSummaryDisplay();
                    setStatus(i18n.detectionComplete || '', false);
                })
                .catch(function (error) {
                    setStatus(error && error.message ? error.message : (i18n.detectionFailed || ''), true);
                })
                .finally(function () {
                    runBtn.disabled = false;
                });
        });
    }

    function onDomReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function bindObjectCacheButtons() {
        var i18n = (typeof window.ptkAdmin === 'object' && window.ptkAdmin) ? window.ptkAdmin : {};
        var requestFailedMessage = i18n.requestFailed || '';

        document.querySelectorAll('.pivot-performance-toolkit-oc-btn[data-oc-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var action  = btn.getAttribute('data-oc-action');
                var nonce   = btn.getAttribute('data-oc-nonce') || '';
                var reload  = btn.getAttribute('data-oc-reload') === '1';
                var card    = btn.closest('#pivot-performance-toolkit-object-cache-card');
                var notice  = card ? card.querySelector('.pivot-performance-toolkit-oc-notice') : null;

                if (!action) {
                    return;
                }

                card && card.querySelectorAll('.pivot-performance-toolkit-oc-btn').forEach(function (b) { b.disabled = true; });

                var body = new URLSearchParams();
                body.set('action', action);
                body.set('_ajax_nonce', nonce);

                fetch(getAjaxUrl(), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString()
                })
                    .then(function (r) { return r.json(); })
                    .then(function (payload) {
                        var message = (payload && payload.data && payload.data.message)
                            ? payload.data.message
                            : (payload && payload.success ? '' : requestFailedMessage);

                        if (payload && payload.success) {
                            if (reload) {
                                window.location.reload();
                                return;
                            }
                            if (notice) {
                                showObjectCacheNotice(notice, 'success', message);
                            }
                        } else {
                            if (notice) {
                                showObjectCacheNotice(notice, 'error', message);
                            }
                            card && card.querySelectorAll('.pivot-performance-toolkit-oc-btn').forEach(function (b) { b.disabled = false; });
                        }
                    })
                    .catch(function () {
                        if (notice) {
                            showObjectCacheNotice(notice, 'error', requestFailedMessage);
                        }
                        card && card.querySelectorAll('.pivot-performance-toolkit-oc-btn').forEach(function (b) { b.disabled = false; });
                    });
            });
        });
    }

    function showObjectCacheNotice(el, type, message) {
        el.textContent = message;
        el.className   = 'pivot-performance-toolkit-oc-notice notice notice-' + (type === 'success' ? 'success' : 'error') + ' inline mt-3';
        el.style.display = '';
    }

    onDomReady(function () {
        var toggle = document.getElementById('pivot-performance-toolkit-sidebar-toggle');
        var shell  = document.querySelector('.pivot-performance-toolkit-shell');

        bindQuickActionButtons();
        bindAjaxActionForms();
        bindAjaxAutosaveForms();
        bindSnippetCopyButtons();
        bindSnippetToggles();
        bindHtaccessToggle();
        bindIconSelects();
        bindObjectCacheButtons();
        bindDisableOnSubmitForms();
        bindCollapsibleTriggers();
        bindHttp11AutoExpand();
        bindWooExclusions();
        bindBrowserCacheTest();
        bindAssetsDetector();

        if (toggle && shell) {
            toggle.addEventListener('click', function () {
                var isOpen = shell.classList.toggle('pivot-performance-toolkit-sidebar-open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });

            // Close sidebar when a nav link is clicked on narrow screens
            document.querySelectorAll('.pivot-performance-toolkit-nav-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth <= 960) {
                        shell.classList.remove('pivot-performance-toolkit-sidebar-open');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
            });
        }
    });
}());

