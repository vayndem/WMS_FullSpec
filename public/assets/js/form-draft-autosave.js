(function (window, document) {
    'use strict';

    function ready(callback) {
        document.readyState === 'loading'
            ? document.addEventListener('DOMContentLoaded', callback, { once: true })
            : callback();
    }

    ready(function () {
        document.querySelectorAll('form[data-autosave]').forEach(function (form) {
            const key = 'wms:draft:' + (form.dataset.autosaveKey || window.location.pathname);
            let dirty = false;
            let timer;
            const indicator = document.createElement('small');
            indicator.className = 'autosave-indicator text-muted d-block mt-2';
            form.prepend(indicator);

            function snapshot() {
                const values = {};
                new FormData(form).forEach(function (value, name) {
                    if (name === '_token' || name === '_method' || value instanceof File) return;
                    if (!values[name]) values[name] = [];
                    values[name].push(value);
                });
                localStorage.setItem(key, JSON.stringify({ savedAt: new Date().toISOString(), values: values }));
                dirty = false;
                indicator.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-1"></i>Draft tersimpan di browser';
            }

            try {
                const draft = JSON.parse(localStorage.getItem(key) || 'null');
                if (draft && draft.values) {
                    Object.keys(draft.values).forEach(function (name) {
                        const fields = form.querySelectorAll('[name="' + CSS.escape(name) + '"]');
                        fields.forEach(function (field, index) {
                            if (field.type === 'checkbox' || field.type === 'radio') {
                                field.checked = draft.values[name].includes(field.value);
                            } else if (draft.values[name][index] !== undefined) {
                                field.value = draft.values[name][index];
                                field.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                        });
                    });
                    indicator.innerHTML = '<i class="fa-solid fa-clock-rotate-left me-1"></i>Draft browser dipulihkan';
                }
            } catch (error) {
                localStorage.removeItem(key);
            }

            form.addEventListener('input', function () {
                dirty = true;
                indicator.innerHTML = '<i class="fa-solid fa-pen me-1"></i>Perubahan belum tersimpan';
                clearTimeout(timer);
                timer = setTimeout(snapshot, 700);
            });
            form.addEventListener('change', function () {
                dirty = true;
                clearTimeout(timer);
                timer = setTimeout(snapshot, 250);
            });
            form.addEventListener('wms:saved', function () {
                localStorage.removeItem(key);
                dirty = false;
            });
            form.addEventListener('submit', function () {
                clearTimeout(timer);
                snapshot();
            });
            window.addEventListener('beforeunload', function (event) {
                if (!dirty) return;
                event.preventDefault();
                event.returnValue = '';
            });

            if (window.jQuery) {
                window.jQuery(document).ajaxSuccess(function (_event, _xhr, settings) {
                    const action = new URL(form.action, window.location.origin).pathname;
                    const target = new URL(settings.url, window.location.origin).pathname;
                    if (action === target) form.dispatchEvent(new Event('wms:saved'));
                });
            }
        });
    });
})(window, document);
