function initAutosave(form) {
    if (form.dataset.autosaveBound === 'true') return;
    form.dataset.autosaveBound = 'true';

    const key = 'wms:draft:' + (form.dataset.autosaveKey || window.location.pathname);
    let dirty = false;
    let timer;
    const indicator = document.createElement('small');
    indicator.className = 'autosave-indicator block mt-2 text-base-content/50';
    form.prepend(indicator);

    function snapshot() {
        const values = {};
        new FormData(form).forEach((value, name) => {
            if (name === '_token' || name === '_method' || value instanceof File) return;
            if (!values[name]) values[name] = [];
            values[name].push(value);
        });
        localStorage.setItem(key, JSON.stringify({ savedAt: new Date().toISOString(), values }));
        dirty = false;
        indicator.innerHTML = '<i class="fa-solid fa-cloud-arrow-up mr-1"></i>Draft tersimpan di browser';
    }

    try {
        const draft = JSON.parse(localStorage.getItem(key) || 'null');
        if (draft && draft.values) {
            Object.keys(draft.values).forEach((name) => {
                const fields = form.querySelectorAll(`[name="${CSS.escape(name)}"]`);
                fields.forEach((field, index) => {
                    if (field.type === 'checkbox' || field.type === 'radio') {
                        field.checked = draft.values[name].includes(field.value);
                    } else if (draft.values[name][index] !== undefined) {
                        field.value = draft.values[name][index];
                        field.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });
            indicator.innerHTML = '<i class="fa-solid fa-clock-rotate-left mr-1"></i>Draft browser dipulihkan';
        }
    } catch (error) {
        localStorage.removeItem(key);
    }

    form.addEventListener('input', () => {
        dirty = true;
        indicator.innerHTML = '<i class="fa-solid fa-pen mr-1"></i>Perubahan belum tersimpan';
        clearTimeout(timer);
        timer = setTimeout(snapshot, 700);
    });
    form.addEventListener('change', () => {
        dirty = true;
        clearTimeout(timer);
        timer = setTimeout(snapshot, 250);
    });
    form.addEventListener('wms:saved', () => {
        localStorage.removeItem(key);
        dirty = false;
    });
    form.addEventListener('submit', () => {
        clearTimeout(timer);
        snapshot();
    });
    window.addEventListener('beforeunload', (event) => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });
}

function initialize(root) {
    const scope = root?.querySelectorAll ? root : document;
    if (scope.matches?.('form[data-autosave]')) initAutosave(scope);
    scope.querySelectorAll?.('form[data-autosave]').forEach(initAutosave);
}

document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', () => initialize(document), { once: true })
    : initialize(document);

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) initialize(node);
    }));
}).observe(document.documentElement, { childList: true, subtree: true });
