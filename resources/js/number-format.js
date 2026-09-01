const bound = new WeakSet();
const rawValues = new WeakMap();

function parseNumber(value) {
    if (value === '' || value === null || value === undefined) return NaN;
    return parseFloat(String(value).trim());
}

function formatForDisplay(num) {
    if (!Number.isFinite(num)) return '';
    return num.toLocaleString('id-ID', { maximumFractionDigits: 6 });
}

function enhance(input) {
    if (bound.has(input)) return;
    bound.add(input);
    input.type = 'text';
    input.inputMode = 'decimal';
    input.autocomplete = 'off';

    const initial = parseNumber(input.value);
    rawValues.set(input, Number.isFinite(initial) ? initial : null);
    if (input.value !== '') input.value = formatForDisplay(initial);

    input.addEventListener('focus', () => {
        const raw = rawValues.get(input);
        input.value = raw === null || raw === undefined ? '' : String(raw);
    });

    input.addEventListener('input', () => {
        const num = parseNumber(input.value);
        rawValues.set(input, Number.isFinite(num) ? num : null);
    });

    input.addEventListener('blur', () => {
        input.value = formatForDisplay(rawValues.get(input));
    });
}

function initialize(root) {
    const scope = root?.querySelectorAll ? root : document;
    const targets = [];
    if (scope.matches?.('input[data-money-input]')) targets.push(scope);
    scope.querySelectorAll?.('input[data-money-input]').forEach((input) => targets.push(input));
    targets.forEach(enhance);
}

document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', () => initialize(document), { once: true })
    : initialize(document);

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) initialize(node);
    }));
}).observe(document.documentElement, { childList: true, subtree: true });

document.addEventListener('submit', (event) => {
    event.target.querySelectorAll?.('input[data-money-input]').forEach((input) => {
        const raw = rawValues.get(input);
        input.value = raw === null || raw === undefined ? '' : String(raw);
    });
}, true);
