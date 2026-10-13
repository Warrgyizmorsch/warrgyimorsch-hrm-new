/**
 * Global double-submit guard for every <form> on the site.
 *
 * - Native POST forms: submit buttons are disabled once the browser starts submitting,
 *   until the next page loads (or a safety timeout, e.g. when the response is a download).
 * - AJAX forms (submit handler calls preventDefault() and then fetch / $.ajax / XHR),
 *   whatever their method attribute: the form stays locked while the request started
 *   by that submit is in flight. After a successful write (POST/PUT/PATCH/DELETE) it
 *   stays locked a little longer, since pages often reload after a toast; on failure
 *   it unlocks immediately so the user can correct and resubmit.
 * - While locked, further submits of the same form are swallowed before any
 *   page-level handler runs.
 * - Native GET forms (filters, search, exports) are not locked.
 * - Opt out per form with <form data-allow-resubmit>.
 * - Buttons outside forms (approve / delete / status buttons that call fetch or $.ajax in
 *   their click handler): when a click starts a write request, that button is locked until
 *   the request finishes (+ a short hold on success). Opt out with data-allow-repeat.
 * - The server refuses identical repeats as well (PreventDuplicateSubmissions middleware).
 */
(function () {
    'use strict';

    var NATIVE_UNLOCK_MS = 10000;
    var AJAX_SUCCESS_HOLD_MS = 2000;

    var activeForm = null;      // form whose submit handlers are currently running
    var pending = new WeakMap(); // form -> array of promises resolving to {ok, write}

    function isGuarded(form) {
        return !!form && form.nodeName === 'FORM' && !form.hasAttribute('data-allow-resubmit');
    }

    function isNativeGet(form) {
        return (form.getAttribute('method') || 'get').toLowerCase() === 'get';
    }

    function isWrite(method) {
        return ['GET', 'HEAD', 'OPTIONS'].indexOf(String(method || 'GET').toUpperCase()) === -1;
    }

    function submitButtons(form) {
        return Array.prototype.filter.call(form.elements, function (el) {
            var type = (el.getAttribute('type') || (el.nodeName === 'BUTTON' ? 'submit' : '')).toLowerCase();
            return type === 'submit' || type === 'image';
        });
    }

    function lock(form) {
        form.dataset.submitLocked = '1';
        submitButtons(form).forEach(function (btn) {
            // Leave alone buttons a page script already disabled itself.
            if (btn.disabled) {
                return;
            }
            btn.disabled = true;
            btn.dataset.submitGuardDisabled = '1';
            btn.classList.add('is-submitting');
            btn.setAttribute('aria-busy', 'true');
        });
    }

    function unlock(form) {
        delete form.dataset.submitLocked;
        submitButtons(form).forEach(function (btn) {
            if (btn.dataset.submitGuardDisabled !== '1') {
                return;
            }
            btn.disabled = false;
            delete btn.dataset.submitGuardDisabled;
            btn.classList.remove('is-submitting');
            btn.removeAttribute('aria-busy');
        });
    }

    var activeClick = null; // {el, requests[]} while a click's handlers start requests

    function track(promise, write) {
        if (activeClick && !activeForm) {
            activeClick.requests.push({ promise: promise, write: write });
        }
        if (!activeForm) {
            return;
        }
        var list = pending.get(activeForm) || [];
        list.push(promise);
        pending.set(activeForm, list);
    }

    // --- Buttons outside forms ---

    var CLICK_SELECTOR = 'button, a.btn, a.zoho-btn-primary, a.zoho-btn-outline, [role="button"], .zoho-icon-btn';

    function lockClick(el) {
        el.dataset.clickLocked = '1';
        el.classList.add('is-submitting');
        el.setAttribute('aria-busy', 'true');
        if ('disabled' in el && !el.disabled) {
            el.disabled = true;
            el.dataset.clickGuardDisabled = '1';
        }
    }

    function unlockClick(el) {
        delete el.dataset.clickLocked;
        el.classList.remove('is-submitting');
        el.removeAttribute('aria-busy');
        if (el.dataset.clickGuardDisabled === '1') {
            el.disabled = false;
            delete el.dataset.clickGuardDisabled;
        }
    }

    document.addEventListener('click', function (e) {
        var el = e.target && e.target.closest ? e.target.closest(CLICK_SELECTOR) : null;
        if (!el || el.hasAttribute('data-allow-repeat')) {
            return;
        }
        // Submit buttons of a form are handled by the form guard.
        var type = (el.getAttribute('type') || (el.nodeName === 'BUTTON' ? 'submit' : '')).toLowerCase();
        if (el.nodeName === 'BUTTON' && type === 'submit' && el.form) {
            return;
        }
        if (el.dataset.clickLocked === '1') {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }
        var ctx = { el: el, requests: [] };
        activeClick = ctx;
        // Requests started by this click's handlers (incl. promise callbacks) are collected
        // until the next task; then decide whether to lock the button.
        setTimeout(function () {
            if (activeClick === ctx) {
                activeClick = null;
            }
            var writes = ctx.requests.filter(function (r) { return r.write; });
            if (!writes.length) {
                return;
            }
            lockClick(el);
            Promise.all(writes.map(function (r) { return r.promise; })).then(function (results) {
                var allOk = results.every(function (r) { return r.ok; });
                setTimeout(function () { unlockClick(el); }, allOk ? AJAX_SUCCESS_HOLD_MS : 0);
            });
        }, 0);
    }, true);

    // --- Capture requests started synchronously inside a submit handler ---

    if (window.fetch) {
        var originalFetch = window.fetch;
        window.fetch = function () {
            var input = arguments[0];
            var init = arguments[1] || {};
            var write = isWrite(init.method || (input && input.method));
            var result = originalFetch.apply(this, arguments);
            track(result.then(
                function (res) { return { ok: res.ok, write: write }; },
                function () { return { ok: false, write: write }; }
            ), write);
            return result;
        };
    }

    var originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (method) {
        this._submitGuardMethod = method;
        return originalOpen.apply(this, arguments);
    };

    var originalSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function () {
        if (activeForm || activeClick) {
            var xhr = this;
            var write = isWrite(xhr._submitGuardMethod);
            track(new Promise(function (resolve) {
                xhr.addEventListener('loadend', function () {
                    resolve({ ok: xhr.status >= 200 && xhr.status < 400, write: write });
                });
            }), write);
        }
        return originalSend.apply(this, arguments);
    };

    // --- Submit lifecycle ---

    // Capture on document: runs before any handler bound to the form itself.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!isGuarded(form)) {
            return;
        }
        if (form.dataset.submitLocked === '1') {
            e.preventDefault();
            e.stopImmediatePropagation();
            return;
        }
        activeForm = form;
        pending.delete(form);
    }, true);

    // Bubble on window: runs after form-level and document-delegated (jQuery) handlers.
    window.addEventListener('submit', function (e) {
        var form = e.target;
        if (!isGuarded(form) || activeForm !== form) {
            return;
        }
        activeForm = null;

        if (!e.defaultPrevented) {
            if (isNativeGet(form)) {
                return;
            }
            // Native submission. Disable on the next tick so the clicked button's
            // name/value is still included in the request.
            form.dataset.submitLocked = '1';
            setTimeout(function () { lock(form); }, 0);
            setTimeout(function () { unlock(form); }, NATIVE_UNLOCK_MS);
            return;
        }

        var requests = pending.get(form);
        pending.delete(form);
        if (!requests || !requests.length) {
            // Handler stopped the submit without sending anything (client-side validation).
            return;
        }

        lock(form);
        Promise.all(requests).then(function (results) {
            var allOk = results.every(function (r) { return r.ok; });
            var wrote = results.some(function (r) { return r.write; });
            setTimeout(function () { unlock(form); }, allOk && wrote ? AJAX_SUCCESS_HOLD_MS : 0);
        });
    });

    // Back/forward cache restores the page with buttons still disabled.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) {
            return;
        }
        Array.prototype.forEach.call(document.querySelectorAll('form[data-submit-locked]'), unlock);
        Array.prototype.forEach.call(document.querySelectorAll('[data-click-locked]'), unlockClick);
    });
})();
