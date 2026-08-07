/**
 * Shared UI notifications for every Blade view and AJAX partial.
 * SweetAlert2 is the only user-facing alert implementation.
 */
(function (window, document) {
    'use strict';

    if (!window.Swal) {
        return;
    }

    const swalDefaults = window.Swal.mixin({
        customClass: {
            popup: 'inventory-swal',
            confirmButton: 'btn btn-primary px-4',
            cancelButton: 'btn btn-outline-secondary px-4 me-2'
        },
        buttonsStyling: false,
        reverseButtons: true,
        confirmButtonText: 'Mengerti',
        allowEscapeKey: true
    });

    function normalizeMessage(message) {
        if (message === undefined || message === null || message === '') {
            return 'Terjadi kesalahan. Silakan coba kembali.';
        }

        if (typeof message === 'object') {
            try {
                return JSON.stringify(message);
            } catch (error) {
                return String(message);
            }
        }

        return String(message);
    }

    window.AppAlert = {
        show(message, options) {
            return swalDefaults.fire(Object.assign({
                icon: 'info',
                title: 'Informasi',
                text: normalizeMessage(message)
            }, options || {}));
        },

        success(message, title) {
            return this.show(message, {
                icon: 'success',
                title: title || 'Berhasil'
            });
        },

        warning(message, title) {
            return this.show(message, {
                icon: 'warning',
                title: title || 'Perlu diperhatikan'
            });
        },

        error(message, title) {
            return this.show(message, {
                icon: 'error',
                title: title || 'Terjadi kesalahan'
            });
        },

        ajaxError(xhr, title) {
            const payload = xhr && xhr.responseJSON ? xhr.responseJSON : {};
            const errors = payload.errors || {};
            const lines = [];

            Object.keys(errors).forEach((field) => {
                const label = field.replace(/\.\d+\./g, ' — baris ').replace(/_/g, ' ');
                (Array.isArray(errors[field]) ? errors[field] : [errors[field]])
                    .forEach((message) => lines.push(`<li><strong>${escapeHtml(label)}</strong> — ${escapeHtml(message)}</li>`));
            });

            if (!lines.length) {
                lines.push(`<li>${escapeHtml(payload.message || payload.error || 'Terjadi kesalahan. Silakan periksa data dan coba kembali.')}</li>`);
            }

            return swalDefaults.fire({
                icon: 'error',
                title: title || 'Data belum dapat diproses',
                html: `<ul class="text-start mb-0">${lines.join('')}</ul>`,
                confirmButtonText: 'Periksa kembali'
            });
        },

        auto(message) {
            return this[classifyAlert(message)](message);
        },

        confirm(message, options) {
            return swalDefaults.fire(Object.assign({
                icon: 'question',
                title: 'Konfirmasi',
                text: normalizeMessage(message),
                showCancelButton: true,
                confirmButtonText: 'Ya, lanjutkan',
                cancelButtonText: 'Batal'
            }, options || {}));
        }
    };

    function escapeHtml(value) {
        const node = document.createElement('div');
        node.textContent = normalizeMessage(value);
        return node.innerHTML;
    }

    function classifyAlert(message) {
        const normalized = normalizeMessage(message).toLowerCase();

        if (/(berhasil|tersimpan|diperbarui|selesai)/.test(normalized)) {
            return 'success';
        }

        if (/(pilih|harus|harap|belum|tidak valid|dikunci)/.test(normalized)) {
            return 'warning';
        }

        if (/(gagal|error|kesalahan|terjadi masalah)/.test(normalized)) {
            return 'error';
        }

        return 'show';
    }

    // Safety net for third-party scripts; application code calls AppAlert directly.
    window.alert = function (message) {
        return window.AppAlert[classifyAlert(message)](message);
    };

    function migrateServerAlerts(root) {
        const scope = root && root.querySelectorAll ? root : document;
        const alerts = [];

        if (scope.matches && scope.matches('.alert:not([data-keep-alert])')) {
            alerts.push(scope);
        }

        scope.querySelectorAll('.alert:not([data-keep-alert])').forEach((element) => alerts.push(element));

        alerts.forEach((element) => {
            if (element.dataset.swalMigrated === 'true') {
                return;
            }

            element.dataset.swalMigrated = 'true';
            const message = element.textContent.replace(/\s+/g, ' ').trim();
            const type = element.classList.contains('alert-success') ? 'success'
                : element.classList.contains('alert-warning') ? 'warning'
                    : element.classList.contains('alert-info') ? 'show'
                        : 'error';

            element.remove();
            window.AppAlert[type](message);
        });
    }

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    ready(function () {
        migrateServerAlerts(document);

        new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        migrateServerAlerts(node);
                    }
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    });
})(window, document);
