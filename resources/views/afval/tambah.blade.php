<div class="modal fade" id="modalTambah" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-custom-width">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="staticBackdropLabel">{{ $title }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-sm-3 d-flex align-items-center">
                        <div class="input-group">
                            <input type="hidden" name="id_suplier" id="id_suplier">
                            <input type="text" class="form-control uppercase" name="nama_suplier" id="nama_suplier"
                                placeholder="Nama Pembeli"
                                style="background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9; box-shadow: 0 0 5px rgba(0, 123, 255, 0.1);">
                        </div>
                    </div>
                    <div class="col-sm-3 d-flex align-items-center">
                        <div class="input-group">
                            <input type="text" class="form-control uppercase" name="alamat" id="alamat"
                                placeholder="Alamat Tujuan"
                                style="background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9; box-shadow: 0 0 5px rgba(0, 123, 255, 0.1);">
                        </div>
                    </div>
                    <div class="col-sm-2 d-flex align-items-center">
                        <label for="tanggal" class="me-2" style="text-align: right;">Tanggal</label>
                        <input type="date" class="form-control" id="tanggal" name="tanggal"
                            value="{{ \Carbon\Carbon::now()->toDateString() }}"
                            style="background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9; box-shadow: 0 0 5px rgba(0, 123, 255, 0.1);">
                    </div>
                    <div class="col-sm-2 d-flex align-items-center">
                        <label for="status_faktur" class="me-2" style="text-align: right;">Status Faktur</label>
                        <input type="text" class="form-control" name="status_faktur" id="status_faktur"
                            value="waiting" readonly
                            style="background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9; box-shadow: 0 0 5px rgba(0, 123, 255, 0.1);" />
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-sm-3 d-flex align-items-center">
                        <select class="form-control" name="nama_bahan" id="nama_bahan"
                            style="background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9; box-shadow: 0 0 5px rgba(0, 123, 255, 0.1);">
                            <option value="">-- Pilih Afval --</option>
                            <option value="Afval Kertas">Afval Kertas</option>
                            <option value="Afval Laminasi">Afval Laminasi</option>
                            <option value="Afval Campuran">Afval Kertas Coklat</option>
                            <option value="Afval Campuran">Afval Kertas Kardus</option>
                            <option value="Afval Campuran">Afval Campuran</option>
                        </select>
                    </div>
                    <div class="col-sm-2 d-flex align-items-center">
                        <label for="jumlah" class="me-2" style="width: 80px; text-align: right;">Berat (KG)</label>
                        <input type="number" step="1" class="form-control" id="jumlah" name="jumlah"
                            style="text-align: right; background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9; box-shadow: 0 0 5px rgba(0, 123, 255, 0.1);">
                    </div>
                    <div class="col-sm-2 d-flex align-items-center">
                        <label for="harga" class="me-2" style="width: 80px; text-align: right;">Harga</label>
                        <input type="number" step="0.0001" class="form-control" id="harga" name="harga"
                            style="text-align: right; background-color: #e3f2fd; color: #0d6efd; border: 1px solid #90caf9; box-shadow: 0 0 5px rgba(0, 123, 255, 0.1);">
                    </div>
                    <div class="col-sm-1 d-flex align-items-center">
                        <button class="btn btn-success ms-2" id="submitformtambah">Submit</button>
                    </div>
                </div>

                <div class="row mb-2">
                    <table id="tabeldetail" class="table table-sm table-striped table-hover custom-table-border mt-2;">
                        <thead class="table-dark">
                            <tr>
                                <th colspan="2">Nama Bahan</th>
                                <th style="text-align: right">Harga</th>
                                <th style="text-align: right">Qty</th>
                                <th style="text-align: right">Total</th>
                                <th style="text-align: center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" rowspan="6" style="position: relative;"> <label for="notes"
                                        style="position: absolute; top: 5px; left: 10px; background: white; padding: 0 5px;">Notes:</label>
                                    <textarea name="notes" id="notes"
                                        style="width: 100%; height: calc(6 * 1.5em + 50px); padding-top: 20px; resize: none;"> - </textarea>
                                </td>
                                <td style="text-align: right"><b>Total:</b></td>
                                <td style="text-align: right; font-weight: bold;" id="SumTotalExclude">0.00</td>
                            </tr>
                            <tr>
                                <td style="text-align: right"><b>Total Berat:</b></td>
                                <td style="text-align: right; font-weight: bold;" id="SumTotalBerat">0.00</td>
                            </tr>
                            <tr>
                                <td style="text-align: right">Diskon</td>
                                <td style="text-align: right"><input type="text" id="diskon" name="diskon"
                                        value="0" style="text-align: right; border: none; outline: none;"></td>
                            </tr>
                            <tr>
                                <td style="text-align: right"><input type="text" id="inputlabel"
                                        name="inputlabel" value="Freight Handling"
                                        style="text-align:right; border:none; outline:none;"></td>
                                <td style="text-align: right"><input type="text" id="ongkir" name="ongkir"
                                        value="0" style="text-align: right; border: none; outline: none;"></td>
                            </tr>
                            <tr>
                                <td style="text-align: right; font-size: 20px; font-weight: bold; font-style: italic;">
                                    Grand Total</td>
                                <td style="text-align: right">
                                    <input type="text" id="GrandTotalPembelian" name="GrandTotalPembelian"
                                        value="0"
                                        style="text-align: right; font-size: 20px; font-weight: bold; font-style: italic; border: none; outline: none;"
                                        readonly>
                                </td>
                                <td style="text-align: center;">
                                    <button class="btn btn-info btn-sm simpansemua">Simpan</button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>