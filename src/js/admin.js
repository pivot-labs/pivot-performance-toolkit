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

    function bindQuickActionButtons() {
        var i18n = (typeof window.ptkAdmin === 'object' && window.ptkAdmin) ? window.ptkAdmin : {};
        var requestFailedMessage = i18n.requestFailed || '';
        var buttons = document.querySelectorAll('.pivot-performance-toolkit-action-btn[data-ajax-action]');

        buttons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                var action = button.getAttribute('data-ajax-action');
                var nonce = button.getAttribute('data-ajax-nonce') || '';
                var successMessage = button.getAttribute('data-success-message') || '';
                var list = button.closest('.pivot-performance-toolkit-action-list');

                if (!action || !list) {
                    return;
                }

                button.disabled = true;

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
                        button.disabled = false;
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
                var body = new URLSearchParams(new FormData(form));

                if (submitButton) {
                    submitButton.disabled = true;
                }

                controls.forEach(function (control) {
                    control.disabled = true;
                });

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

