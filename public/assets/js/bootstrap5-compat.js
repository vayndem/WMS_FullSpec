/**
 * Bootstrap 5 jQuery adapters for existing application scripts.
 *
 * All markup uses Bootstrap 5 data attributes. These thin adapters translate
 * existing jQuery component calls to Bootstrap 5's native component API.
 */
(function (window, document, $) {
    'use strict';

    if (!window.bootstrap) {
        return;
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        window.bootstrap.Tooltip.getOrCreateInstance(element);
    });

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((element) => {
        window.bootstrap.Popover.getOrCreateInstance(element);
    });

    if (!$ || !$.fn) {
        return;
    }

    function createBridge(name, Component) {
        $.fn[name] = function (actionOrOptions) {
            return this.each(function () {
                const options = typeof actionOrOptions === 'object' ? actionOrOptions : {};
                if (name === 'collapse' && typeof actionOrOptions === 'string') {
                    options.toggle = false;
                }
                const instance = Component.getOrCreateInstance(this, options);

                if (typeof actionOrOptions === 'string' && typeof instance[actionOrOptions] === 'function') {
                    instance[actionOrOptions]();
                }
            });
        };
    }

    createBridge('modal', window.bootstrap.Modal);
    createBridge('collapse', window.bootstrap.Collapse);
    createBridge('dropdown', window.bootstrap.Dropdown);
    createBridge('tab', window.bootstrap.Tab);
    createBridge('toast', window.bootstrap.Toast);
    createBridge('tooltip', window.bootstrap.Tooltip);
    createBridge('popover', window.bootstrap.Popover);
})(window, document, window.jQuery);
