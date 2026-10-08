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

    function track(promise) {
        if (!activeForm) {
            return;
        }
        var list = pending.get(activeForm) || [];
        list.push(promise);
        pending.set(activeForm, list);
    }

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
            ));
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
        if (activeForm) {
            var xhr = this;
            var write = isWrite(xhr._submitGuardMethod);
            track(new Promise(function (resolve) {
                xhr.addEventListener('loadend', function () {
                    resolve({ ok: xhr.status >= 200 && xhr.status < 400, write: write });
                });
            }));
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
    });
})();
