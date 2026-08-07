/**
 * Bootstrap 5 searchable picker.
 *
 * Add `data-app-picker` to an existing select. The original select remains the
 * form source of truth, so controllers and validation do not need to change.
 * Optional option metadata: data-subtitle, data-meta, data-badge, data-search.
 */
(function (window, document) {
    'use strict';

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
            option.dataset.search
        ].filter(Boolean).join(' ').toLocaleLowerCase('id-ID');
    }

    class SmartPicker {
        constructor(select) {
            this.select = select;
            this.multiple = select.multiple;
            this.placeholder = select.dataset.placeholder ||
                select.querySelector('option[value=""]')?.textContent?.trim() ||
                'Cari dan pilih data';
            this.build();
            this.bind();
            this.render();
        }

        build() {
            this.wrapper = document.createElement('div');
            this.wrapper.className = 'app-smart-picker dropdown';
            this.wrapper.innerHTML = `
                <button type="button" class="form-select app-smart-picker__button text-start"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <span class="app-smart-picker__value"></span>
                </button>
                <div class="dropdown-menu app-smart-picker__menu w-100 p-2 shadow-lg">
                    <div class="app-smart-picker__search-wrap">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" class="form-control app-smart-picker__search"
                            placeholder="Ketik untuk mencari..." autocomplete="off">
                    </div>
                    <div class="app-smart-picker__results mt-2" role="listbox"></div>
                </div>`;

            this.select.insertAdjacentElement('afterend', this.wrapper);
            this.select.classList.add('app-picker-source');
            this.button = this.wrapper.querySelector('.app-smart-picker__button');
            this.value = this.wrapper.querySelector('.app-smart-picker__value');
            this.search = this.wrapper.querySelector('.app-smart-picker__search');
            this.results = this.wrapper.querySelector('.app-smart-picker__results');
            this.syncDisabled();
        }

        bind() {
            this.search.addEventListener('input', () => this.renderResults());
            this.wrapper.addEventListener('shown.bs.dropdown', () => {
                this.renderResults();
                window.setTimeout(() => this.search.focus(), 30);
            });
            this.results.addEventListener('click', (event) => {
                const choice = event.target.closest('[data-picker-value]');
                if (!choice || choice.classList.contains('disabled')) return;
                const option = Array.from(this.select.options)
                    .find(item => String(item.value) === String(choice.dataset.pickerValue));
                if (!option || option.disabled) return;

                if (this.multiple) {
                    option.selected = !option.selected;
                } else {
                    this.select.value = option.value;
                    window.bootstrap?.Dropdown.getOrCreateInstance(this.button).hide();
                }

                this.select.dispatchEvent(new Event('change', { bubbles: true }));
                this.render();
            });
            this.select.addEventListener('change', () => this.render());
            this.observer = new MutationObserver(() => this.render());
            this.observer.observe(this.select, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['disabled', 'selected', 'label', 'data-subtitle', 'data-meta']
            });
        }

        choices() {
            return Array.from(this.select.options).filter(option => option.value !== '');
        }

        selected() {
            return this.choices().filter(option => option.selected);
        }

        render() {
            this.syncDisabled();
            const selected = this.selected();
            if (!selected.length) {
                this.value.innerHTML = `<span class="text-muted">${escapeHtml(this.placeholder)}</span>`;
            } else if (this.multiple) {
                const preview = selected.slice(0, 2).map(option =>
                    `<span class="app-smart-picker__chip">${escapeHtml(optionText(option))}</span>`
                ).join('');
                const rest = selected.length > 2
                    ? `<span class="app-smart-picker__count">+${selected.length - 2}</span>`
                    : '';
                this.value.innerHTML = `<span class="app-smart-picker__chips">${preview}${rest}</span>`;
            } else {
                const option = selected[0];
                this.value.innerHTML = `
                    <span class="d-block text-truncate fw-semibold">${escapeHtml(optionText(option))}</span>
                    ${option.dataset.meta ? `<small class="d-block text-muted text-truncate">${escapeHtml(option.dataset.meta)}</small>` : ''}`;
            }
            this.renderResults();
        }

        renderResults() {
            const term = this.search.value.trim().toLocaleLowerCase('id-ID');
            const options = this.choices().filter(option => !term || searchText(option).includes(term));
            if (!options.length) {
                this.results.innerHTML =
                    '<div class="app-smart-picker__empty"><i class="fa-regular fa-folder-open"></i><span>Data tidak ditemukan</span></div>';
                return;
            }

            this.results.innerHTML = options.map(option => {
                const checked = option.selected;
                return `
                    <button type="button"
                        class="dropdown-item app-smart-picker__option ${checked ? 'is-selected' : ''} ${option.disabled ? 'disabled' : ''}"
                        data-picker-value="${escapeHtml(option.value)}" role="option" aria-selected="${checked}">
                        <span class="app-smart-picker__indicator">
                            <i class="fa-solid ${this.multiple ? (checked ? 'fa-square-check' : 'fa-square') : (checked ? 'fa-circle-check' : 'fa-circle')}"></i>
                        </span>
                        <span class="app-smart-picker__copy">
                            <span class="app-smart-picker__title">${escapeHtml(optionText(option))}</span>
                            ${option.dataset.subtitle ? `<span class="app-smart-picker__subtitle">${escapeHtml(option.dataset.subtitle)}</span>` : ''}
                            ${option.dataset.meta ? `<span class="app-smart-picker__meta">${escapeHtml(option.dataset.meta)}</span>` : ''}
                        </span>
                        ${option.dataset.badge ? `<span class="badge text-bg-primary-subtle text-primary-emphasis">${escapeHtml(option.dataset.badge)}</span>` : ''}
                    </button>`;
            }).join('');
        }

        syncDisabled() {
            this.button.disabled = this.select.disabled;
            this.wrapper.classList.toggle('is-disabled', this.select.disabled);
        }
    }

    function initialize(root) {
        const scope = root?.querySelectorAll ? root : document;
        const targets = [];
        if (scope.matches?.('select[data-app-picker]')) targets.push(scope);
        scope.querySelectorAll?.('select[data-app-picker]').forEach(select => targets.push(select));
        targets.forEach(select => {
            if (!instances.has(select)) instances.set(select, new SmartPicker(select));
        });
    }

    function ready(callback) {
        document.readyState === 'loading'
            ? document.addEventListener('DOMContentLoaded', callback, { once: true })
            : callback();
    }

    ready(() => {
        initialize(document);
        new MutationObserver(mutations => {
            mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE) initialize(node);
            }));
        }).observe(document.body, { childList: true, subtree: true });
    });

    window.AppSmartPicker = {
        init: initialize,
        refresh(select) {
            instances.get(select)?.render();
        }
    };
})(window, document);
