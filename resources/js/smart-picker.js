const instances = new WeakMap();

function escapeHtml(value) {
    const node = document.createElement('div');
    node.textContent = value == null ? '' : String(value);
    return node.innerHTML;
}

function optionText(option) {
    return (option.dataset.label || option.textContent || '').trim();
}

function searchText(option) {
    return [
        optionText(option),
        option.dataset.subtitle,
        option.dataset.meta,
        option.dataset.badge,
        option.dataset.search,
    ].filter(Boolean).join(' ').toLocaleLowerCase('id-ID');
}

class SmartPicker {
    constructor(select) {
        this.select = select;
        this.multiple = select.multiple;
        this.open = false;
        this.placeholder = select.dataset.placeholder ||
            select.querySelector('option[value=""]')?.textContent?.trim() ||
            'Cari dan pilih data';
        this.build();
        this.bind();
        this.render();
    }

    build() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'app-smart-picker relative';
        this.wrapper.innerHTML = `
            <button type="button" class="select select-bordered w-full text-left app-smart-picker__button" aria-expanded="false">
                <span class="app-smart-picker__value truncate"></span>
            </button>
            <div class="app-smart-picker__menu dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-300 w-full p-2 absolute z-30 mt-1 hidden">
                <div class="app-smart-picker__search-wrap relative mb-2">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-base-content/40 text-sm"></i>
                    <input type="search" class="input input-bordered input-sm w-full pl-8 app-smart-picker__search"
                        placeholder="Ketik untuk mencari..." autocomplete="off">
                </div>
                <div class="app-smart-picker__results max-h-60 overflow-y-auto" role="listbox"></div>
            </div>`;

        this.select.insertAdjacentElement('afterend', this.wrapper);
        this.select.classList.add('hidden');
        if (this.select.required) {
            this.select.required = false;
            this.wrapper.querySelector('.app-smart-picker__button').setAttribute('aria-required', 'true');
        }
        this.button = this.wrapper.querySelector('.app-smart-picker__button');
        this.menu = this.wrapper.querySelector('.app-smart-picker__menu');
        this.value = this.wrapper.querySelector('.app-smart-picker__value');
        this.search = this.wrapper.querySelector('.app-smart-picker__search');
        this.results = this.wrapper.querySelector('.app-smart-picker__results');
        this.syncDisabled();
    }

    bind() {
        this.button.addEventListener('click', () => this.toggle());
        this.search.addEventListener('input', () => this.renderResults());
        this.results.addEventListener('click', (event) => {
            const choice = event.target.closest('[data-picker-value]');
            if (!choice || choice.classList.contains('opacity-40')) return;
            const option = Array.from(this.select.options)
                .find((item) => String(item.value) === String(choice.dataset.pickerValue));
            if (!option || option.disabled) return;

            if (this.multiple) {
                option.selected = !option.selected;
            } else {
                this.select.value = option.value;
                this.close();
            }

            this.select.dispatchEvent(new Event('change', { bubbles: true }));
            this.render();
        });
        this.select.addEventListener('change', () => this.render());
        document.addEventListener('click', (event) => {
            if (this.open && !this.wrapper.contains(event.target)) this.close();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.open) this.close();
        });
        this.observer = new MutationObserver(() => this.render());
        this.observer.observe(this.select, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['disabled', 'selected', 'label', 'data-subtitle', 'data-meta'],
        });
    }

    toggle() {
        this.open ? this.close() : this.openMenu();
    }

    openMenu() {
        if (this.button.disabled) return;
        this.open = true;
        this.menu.classList.remove('hidden');
        this.button.setAttribute('aria-expanded', 'true');
        this.renderResults();
        setTimeout(() => this.search.focus(), 30);
    }

    close() {
        this.open = false;
        this.menu.classList.add('hidden');
        this.button.setAttribute('aria-expanded', 'false');
    }

    choices() {
        return Array.from(this.select.options).filter((option) => option.value !== '');
    }

    selected() {
        return this.choices().filter((option) => option.selected);
    }

    render() {
        this.syncDisabled();
        const selected = this.selected();
        if (!selected.length) {
            this.value.innerHTML = `<span class="text-base-content/50">${escapeHtml(this.placeholder)}</span>`;
        } else if (this.multiple) {
            const preview = selected.slice(0, 2).map((option) =>
                `<span class="badge badge-ghost mr-1">${escapeHtml(optionText(option))}</span>`).join('');
            const rest = selected.length > 2
                ? `<span class="badge badge-ghost">+${selected.length - 2}</span>`
                : '';
            this.value.innerHTML = `${preview}${rest}`;
        } else {
            const option = selected[0];
            this.value.innerHTML = `
                <span class="block truncate font-semibold">${escapeHtml(optionText(option))}</span>
                ${option.dataset.meta ? `<span class="block text-xs text-base-content/50 truncate">${escapeHtml(option.dataset.meta)}</span>` : ''}`;
        }
        this.renderResults();
    }

    renderResults() {
        const term = this.search.value.trim().toLocaleLowerCase('id-ID');
        const options = this.choices().filter((option) => !term || searchText(option).includes(term));
        if (!options.length) {
            this.results.innerHTML =
                '<div class="flex items-center gap-2 p-3 text-sm text-base-content/50"><i class="fa-regular fa-folder-open"></i><span>Data tidak ditemukan</span></div>';
            return;
        }

        this.results.innerHTML = options.map((option) => {
            const checked = option.selected;
            return `
                <button type="button"
                    class="flex w-full items-start gap-2 rounded-lg px-2 py-2 text-left text-sm hover:bg-base-200 ${checked ? 'bg-primary/10' : ''} ${option.disabled ? 'opacity-40 pointer-events-none' : ''}"
                    data-picker-value="${escapeHtml(option.value)}" role="option" aria-selected="${checked}">
                    <i class="fa-solid ${this.multiple ? (checked ? 'fa-square-check text-primary' : 'fa-square text-base-content/30') : (checked ? 'fa-circle-check text-primary' : 'fa-circle text-base-content/30')} mt-0.5"></i>
                    <span class="flex-1 min-w-0">
                        <span class="block truncate font-medium">${escapeHtml(optionText(option))}</span>
                        ${option.dataset.subtitle ? `<span class="block truncate text-xs text-base-content/50">${escapeHtml(option.dataset.subtitle)}</span>` : ''}
                        ${option.dataset.meta ? `<span class="block truncate text-xs text-base-content/50">${escapeHtml(option.dataset.meta)}</span>` : ''}
                    </span>
                    ${option.dataset.badge ? `<span class="badge badge-primary badge-sm">${escapeHtml(option.dataset.badge)}</span>` : ''}
                </button>`;
        }).join('');
    }

    syncDisabled() {
        this.button.disabled = this.select.disabled;
        this.wrapper.classList.toggle('opacity-60', this.select.disabled);
    }
}

function initialize(root) {
    const scope = root?.querySelectorAll ? root : document;
    const targets = [];
    if (scope.matches?.('select[data-app-picker]')) targets.push(scope);
    scope.querySelectorAll?.('select[data-app-picker]').forEach((select) => targets.push(select));
    targets.forEach((select) => {
        if (!instances.has(select)) instances.set(select, new SmartPicker(select));
    });
}

document.readyState === 'loading'
    ? document.addEventListener('DOMContentLoaded', () => initialize(document), { once: true })
    : initialize(document);

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) initialize(node);
    }));
}).observe(document.documentElement, { childList: true, subtree: true });

window.AppSmartPicker = {
    init: initialize,
    refresh(select) {
        instances.get(select)?.render();
    },
};
