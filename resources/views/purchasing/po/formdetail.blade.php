<div class="current-modal">
    <div class="modal fade" id="detailModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-custom-width">
            <div class="modal-content">
                <div class="modal-header" style="display: flex; justify-content: space-between;">
                    <div style="flex: 1;">
                        <h6 class="modal-title" id="staticBackdropLabel">{{ $title }}</h6>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-2" style="padding: 3mm">
                        <div class="table-responsive">

                            <table id="tabelshowdetail" class="table table-striped table-bordered" style="width: 100%;">

                                <thead>
                                    <tr>
                                        <th>Nama Bahan</th>
                                        <th>Keterangan</th>
                                        <th>Satuan</th>
                                        <th>Jumlah</th>
                                        <th>Diterima</th>
                                        <th>PO</th>
                                        <th>Tanggal</th>
                                        <th>Supplier</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Rows will be added here dynamically -->
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
    function loadTable() {
        $('#tabelshowdetail').DataTable({
            processing: true,
            serverSide: true,
            destroy: true, // Agar bisa di-reload tanpa duplikasi
            ajax: {
                url: "/showDetail", // Sesuaikan dengan route Laravel
                type: "GET",
                data: function(d) {
                    d.bulan = {{ $bulan }};
                    d.tahun = {{ $tahun }};
                    d.jenis = {{ $jenis }};
                }
            },
            columns: [{
                    data: 'nama',
                    name: 'nama',
                    title: 'Nama Bahan',
                    className: 'text-nowrap',
                    orderable: false,
                    searchable: true
                },
                {
                    data: 'keterangan_bahan',
                    name: 'keterangan_bahan',
                    title: 'Keterangan',
                    className: 'text-nowrap',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'satuan',
                    name: 'satuan',
                    className: 'text-nowrap',
                    title: 'Satuan',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'jumlah',
                    name: 'jumlah',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'diterima',
                    name: 'diterima',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'no_po',
                    name: 'no_po',
                    className: 'text-nowrap',
                    title: 'No. PO',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'tanggal',
                    name: 'tanggal',
                    className: 'text-nowrap',
                    title: 'Tanggal',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'nama_supplier',
                    name: 'nama_supplier',
                    className: 'text-nowrap',
                    title: 'Supplier',
                    orderable: false,
                    searchable: false
                },
            ],
            order: [
                [6, 'desc']
            ]
        });
    }

    $(document).ready(function() {


        // Load data pertama kali
        loadTable();

        // Event listener untuk filter
        $('#filter-form').on('submit', function(e) {
            e.preventDefault();

            loadTable();
        });
    });
</script>
