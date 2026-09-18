import Swal from 'sweetalert2';

const swalDefaults = Swal.mixin({
    buttonsStyling: false,
    reverseButtons: true,
    confirmButtonText: 'Mengerti',
    allowEscapeKey: true,
    customClass: {
        popup: 'app-swal',
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-ghost',
        denyButton: 'btn btn-error',
    },
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

function escapeHtml(value) {
    const node = document.createElement('div');
    node.textContent = normalizeMessage(value);
    return node.innerHTML;
}

function classifyAlert(payload) {
    if (payload && typeof payload === 'object') {
        if (payload.success === true) return 'success';
        if (payload.success === false) return 'error';
        return classifyAlert(payload.message);
    }
    const normalized = normalizeMessage(payload).toLowerCase();
    if (/(berhasil|tersimpan|diperbarui|selesai)/.test(normalized)) return 'success';
    if (/(pilih|harus|harap|belum|tidak valid|dikunci)/.test(normalized)) return 'warning';
    if (/(gagal|error|kesalahan|terjadi masalah)/.test(normalized)) return 'error';
    return 'show';
}

const AppAlert = {
    show(message, options) {
        return swalDefaults.fire(Object.assign({
            icon: 'info',
            title: 'Informasi',
            text: normalizeMessage(message),
        }, options || {}));
    },
    success(message, title) {
        return this.show(message, { icon: 'success', title: title || 'Berhasil' });
    },
    warning(message, title) {
        return this.show(message, { icon: 'warning', title: title || 'Perlu diperhatikan' });
    },
    error(message, title) {
        return this.show(message, { icon: 'error', title: title || 'Terjadi kesalahan' });
    },
    ajaxError(response, title) {
        const payload = response || {};
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
            confirmButtonText: 'Periksa kembali',
        });
    },
    auto(payload) {
        const message = payload && typeof payload === 'object' ? payload.message : payload;
        return this[classifyAlert(payload)](message);
    },
    confirm(message, options) {
        return swalDefaults.fire(Object.assign({
            icon: 'question',
            title: 'Konfirmasi',
            text: normalizeMessage(message),
            showCancelButton: true,
            confirmButtonText: 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
        }, options || {}));
    },
};

window.Swal = Swal;
window.AppAlert = AppAlert;

function migrateServerAlerts(root) {
    const scope = root && root.querySelectorAll ? root : document;
    const alerts = [];

    if (scope.matches && scope.matches('.js-server-alert:not([data-keep-alert])')) {
        alerts.push(scope);
    }
    scope.querySelectorAll('.js-server-alert:not([data-keep-alert])').forEach((el) => alerts.push(el));

    alerts.forEach((element) => {
        if (element.dataset.swalMigrated === 'true') return;
        element.dataset.swalMigrated = 'true';
        const message = element.textContent.replace(/\s+/g, ' ').trim();
        const type = element.dataset.alertType || 'show';
        element.remove();
        AppAlert[type === 'show' ? 'show' : type](message);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    migrateServerAlerts(document);
    new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) migrateServerAlerts(node);
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
});

export { AppAlert };
