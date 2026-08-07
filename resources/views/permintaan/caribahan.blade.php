<div class="current-modal">
    <div class="modal fade" id="pilihmodalbahan" name="pilihmodalbahan" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="pilihmodalbahanLabel">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="tutup()"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="tabelDataPencarian" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Keterangan</th>
                                    <th>Satuan</th>
                                    <th style="text-align: center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data akan dimuat dari server-side -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="tambahModal" tabindex="-1" role="dialog" aria-labelledby="tambahModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tambahModalLabel">Tambah Permintaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formTambah">
                <div class="modal-body">

                    @csrf
                    <div class="mb-3">
                        <label for="bahan">Bahan</label>
                        <input type="text" id="nama_bahan" class="form-control">
                        <input type="hidden" id="id_bahan" name="id_bahan">
                    </div>
                    <div class="mb-3">
                        <label for="jumlah_order">Jumlah Order</label>
                        <input type="number" class="form-control" id="jumlah_order" name="jumlah_order" step="0.01">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary" form="formTambah">Simpan</button>
                </div>
            </form>

        </div>
    </div>
</div>
<script>
    function pilihbahan(id, nama) {
        $('#tambahModal').modal('show');

        $('#id_bahan').val(id); // Pastikan ada elemen input dengan id 'bahan_id' di dalam modal
        $('#nama_bahan').val(nama);
        //jumlah_order
        $('#jumlah_order').val('');

    }

    function tutup() {
        $('#tambahModal').modal('hide');

    }
    $(document).ready(function() {

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#tabelDataPencarian').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/permintaan/" + jenis,
                type: "GET",

                dataType: "json",
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },

                {
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'keterangan_bahan',
                    name: 'keterangan_bahan',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'satuan',
                    name: 'satuan',
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "action",
                    orderable: false,
                    searchable: false
                }
            ]
        });
        $('#formTambah').on('submit', function(e) {
            e.preventDefault(); // Mencegah pengiriman form secara default

            // Menonaktifkan tombol submit
            var submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).html(
                '<i class="fa fa-spin fa-spinner"></i> Menyimpan...');

            // Mengambil data form
            var formData = new FormData(this);

            // Melakukan AJAX request
            $.ajax({
                // url: "{{ secure_url('permintaan') }}",
                url: "/permintaan",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#tambahModal').modal('hide');
                        $('#formTambah')[0].reset();
                        reload();
                    } else {
                        AppAlert.auto('Terjadi kesalahan: ' + response.message);
                    }
                },
                error: function(xhr, ajaxOptions, thrownError) {
                    AppAlert.auto('Error: ' + thrownError);
                },
                complete: function() {
                    // Aktifkan kembali tombol setelah semua selesai
                    submitButton.prop('disabled', false).html('Simpan');
                }
            });
        });


    });
</script>
