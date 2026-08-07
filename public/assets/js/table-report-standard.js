/**
 * Shared list-page standard:
 * - debounced search while typing / on change
 * - per-column filters
 * - PDF export that preserves the active filters
 */
(function (window, document, $) {
    'use strict';

    if (!$ || !$.fn || !$.fn.dataTable) {
        return;
    }

    // Keep controls fixed while only the table viewport scrolls on narrow screens.
    $.extend(true, $.fn.dataTable.defaults, {
        autoWidth: false,
        scrollX: true,
        pageLength: 10,
        lengthMenu: [
            [10, 20, 50, 100, -1],
            [10, 20, 50, 100, 'Semua']
        ],
        language: {
            search: '',
            searchPlaceholder: 'Cari data...',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
            infoEmpty: 'Belum ada data',
            zeroRecords: 'Data tidak ditemukan',
            emptyTable: 'Belum ada data',
            processing: 'Memuat data...',
            paginate: {
                previous: '<i class="fa-solid fa-chevron-left"></i>',
                next: '<i class="fa-solid fa-chevron-right"></i>'
            }
        }
    });

    function debounce(callback, delay) {
        let timeout;
        return function () {
            const context = this;
            const args = arguments;
            window.clearTimeout(timeout);
            timeout = window.setTimeout(function () {
                callback.apply(context, args);
            }, delay);
        };
    }

    function parseColumns(table) {
        const definition = table.dataset.filterColumns || '';

        return definition.split(',')
            .map(function (item) {
                const parts = item.trim().split(':');
                return {
                    index: Number(parts[0]),
                    key: parts[1] || ''
                };
            })
            .filter(function (item) {
                return Number.isInteger(item.index) && item.key;
            });
    }

    function addColumnFilters(api, table, columns) {
        const head = table.tHead;
        if (!head || !head.rows.length || head.querySelector('.inventory-column-filters')) {
            return;
        }

        const filterRow = document.createElement('tr');
        filterRow.className = 'inventory-column-filters';
        const mapped = new Map(columns.map(function (column) {
            return [column.index, column];
        }));

        Array.from(head.rows[0].cells).forEach(function (heading, index) {
            const cell = document.createElement('th');
            const column = mapped.get(index);

            if (column) {
                const optionMap = {
                    'table-request:status': [
                        ['pending', 'Pending'],
                        ['approved', 'Approved'],
                        ['rejected', 'Rejected']
                    ],
                    'table-pembelian:status': [
                        ['0', 'Open'],
                        ['2', 'Closed']
                    ],
                    'table-invoice-lpb:status_pembayaran': [
                        ['Belum Dibayar', 'Belum Dibayar'],
                        ['Proses Pembayaran', 'Proses Pembayaran'],
                        ['Lunas', 'Lunas']
                    ],
                    'table-npk:close': [
                        ['0', 'Draft'],
                        ['1', 'Keluar']
                    ],
                    'opname-table:status': [
                        ['DRAFT', 'Draft'],
                        ['SUBMITTED', 'Menunggu Approval'],
                        ['APPROVED', 'Disetujui'],
                        ['REJECTED', 'Perlu Perbaikan'],
                        ['POSTED', 'Terposting']
                    ]
                };
                const options = optionMap[table.id + ':' + column.key];
                const input = document.createElement(options ? 'select' : 'input');
                input.className = 'form-control form-control-sm';

                if (options) {
                    input.innerHTML = '<option value="">Semua</option>';
                    options.forEach(function (option) {
                        const element = document.createElement('option');
                        element.value = option[0];
                        element.textContent = option[1];
                        input.appendChild(element);
                    });
                } else {
                    input.type = 'search';
                    input.placeholder = 'Filter ' + heading.textContent.trim().replace(/\s+/g, ' ');
                    input.setAttribute('aria-label', input.placeholder);
                }

                const applySearch = debounce(function () {
                    if (api.column(index).search() !== input.value) {
                        api.column(index).search(input.value).draw();
                    }
                }, 350);

                input.addEventListener('input', applySearch);
                input.addEventListener('change', function () {
                    if (api.column(index).search() !== input.value) {
                        api.column(index).search(input.value).draw();
                    }
                });

                input.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
                cell.appendChild(input);
            }

            filterRow.appendChild(cell);
        });

        head.appendChild(filterRow);
        api.columns.adjust();
    }

    function bindGlobalSearch(api, wrapper) {
        const input = wrapper.querySelector('.dataTables_filter input[type="search"]');
        if (!input) {
            return;
        }

        $(input).off('.DT');

        const search = debounce(function () {
            if (api.search() !== input.value) {
                api.search(input.value).draw();
            }
        }, 350);

        input.addEventListener('input', search);
        input.addEventListener('change', function () {
            if (api.search() !== input.value) {
                api.search(input.value).draw();
            }
        });
    }

    function reportUrl(api, table, columns) {
        const url = new URL(table.dataset.reportUrl, window.location.origin);
        const search = api.search();

        if (search) {
            url.searchParams.set('search', search);
        }

        columns.forEach(function (column) {
            const value = api.column(column.index).search();
            if (value) {
                url.searchParams.set('filters[' + column.key + ']', value);
            }
        });

        document.querySelectorAll('[id^="filter_"]').forEach(function (control) {
            const isAllMonths = control.id === 'filter_bulan' && control.value === '0';
            if (control.value !== '' && !isAllMonths) {
                url.searchParams.set(control.id.replace(/^filter_/, ''), control.value);
            }
        });

        return url.toString();
    }

    function addPdfButton(api, table, wrapper, columns) {
        if (!table.dataset.reportUrl || wrapper.querySelector('.btn-report-pdf')) {
            return;
        }

        const button = document.createElement('a');
        button.href = table.dataset.reportUrl;
        button.target = '_blank';
        button.rel = 'noopener';
        button.className = 'btn btn-danger btn-sm btn-report-pdf';
        button.innerHTML = '<i class="fa-solid fa-file-pdf me-2"></i>PDF';
        button.addEventListener('click', function () {
            button.href = reportUrl(api, table, columns);
        });

        const filter = wrapper.querySelector('.dataTables_filter');
        if (filter) {
            filter.classList.add('inventory-table-actions');
            filter.prepend(button);
        }
    }

    function bindPageFilters(api) {
        document.querySelectorAll('[id^="filter_"]').forEach(function (control) {
            if (control.dataset.reportChangeBound === 'true') {
                return;
            }

            control.dataset.reportChangeBound = 'true';
            control.addEventListener('change', function () {
                if (api.ajax) {
                    api.ajax.reload();
                } else {
                    api.draw();
                }
            });
        });
    }

    function markNumberedDataTable(settings) {
        const start = settings._iDisplayStart || 0;
        const tables = settings.nTableWrapper
            ? settings.nTableWrapper.querySelectorAll('table')
            : [settings.nTable];

        tables.forEach(function (table) {
            table.classList.add('inventory-numbered-table');
            table.style.setProperty('--inventory-row-start', start);
        });
    }

    $(document).on('init.dt', function (event, settings) {
        const table = settings.nTable;
        if (!table) {
            return;
        }

        const api = new $.fn.dataTable.Api(settings);
        const wrapper = settings.nTableWrapper;

        // Row numbers are deliberately visual columns. DataTables therefore does
        // not send/order/search this column and existing server-side definitions
        // do not need to be shifted.
        markNumberedDataTable(settings);

        if (!table.dataset.reportUrl) {
            return;
        }

        const columns = parseColumns(table);

        addColumnFilters(api, table, columns);
        bindGlobalSearch(api, wrapper);
        addPdfButton(api, table, wrapper, columns);
        bindPageFilters(api);
    });

    $(document).on('draw.dt', function (event, settings) {
        if (settings.nTable) {
            markNumberedDataTable(settings);
        }
    });

    // Plain Blade tables receive the same numbering. A table can opt out with
    // data-row-numbers="false" (useful for signatures/layout-only tables).
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table.table:not([data-row-numbers="false"])').forEach(function (table) {
            table.classList.add('inventory-numbered-table');
            if (!table.style.getPropertyValue('--inventory-row-start')) {
                table.style.setProperty('--inventory-row-start', table.dataset.rowStart || 0);
            }
        });
    });
})(window, document, window.jQuery);
