(function (window, document) {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    ready(function () {
        const body = document.body;
        const sidebar = document.querySelector('.mm-sidebar');
        const toggles = document.querySelectorAll('.wrapper-menu');
        const darkMode = document.getElementById('dark-mode');
        const fullscreen = document.getElementById('btnFullscreen');
        let overlay = document.querySelector('.sidebar-overlay');

        if (sidebar && !overlay) {
            overlay = document.createElement('button');
            overlay.type = 'button';
            overlay.className = 'sidebar-overlay';
            overlay.setAttribute('aria-label', 'Tutup menu');
            document.body.appendChild(overlay);
        }

        function toggleSidebar() {
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                body.classList.toggle('sidebar-open');
            } else {
                body.classList.toggle('sidebar-collapsed');
                window.localStorage.setItem('inventory-sidebar-collapsed', body.classList.contains('sidebar-collapsed') ? '1' : '0');
            }
        }

        if (window.localStorage.getItem('inventory-sidebar-collapsed') === '1' &&
            !window.matchMedia('(max-width: 991.98px)').matches) {
            body.classList.add('sidebar-collapsed');
        }

        toggles.forEach(function (toggle) {
            toggle.setAttribute('role', 'button');
            toggle.setAttribute('tabindex', '0');
            toggle.addEventListener('click', toggleSidebar);
            toggle.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    toggleSidebar();
                }
            });
        });

        if (overlay) {
            overlay.addEventListener('click', function () {
                body.classList.remove('sidebar-open');
            });
        }

        if (darkMode) {
            const savedTheme = window.localStorage.getItem('inventory-theme') ||
                document.documentElement.getAttribute('data-bs-theme');
            darkMode.checked = savedTheme === 'dark';
            document.documentElement.setAttribute('data-bs-theme', darkMode.checked ? 'dark' : 'light');
            darkMode.addEventListener('change', function () {
                const theme = darkMode.checked ? 'dark' : 'light';
                document.documentElement.setAttribute('data-bs-theme', theme);
                window.localStorage.setItem('inventory-theme', theme);
                window.dispatchEvent(new CustomEvent('inventory:theme-changed', { detail: { theme } }));
            });
        }

        if (fullscreen) {
            fullscreen.addEventListener('click', function (event) {
                event.preventDefault();
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen?.();
                } else {
                    document.exitFullscreen?.();
                }
            });
        }

        const loader = document.getElementById('loading');
        if (loader) {
            loader.remove();
        }
    });
})(window, document);
