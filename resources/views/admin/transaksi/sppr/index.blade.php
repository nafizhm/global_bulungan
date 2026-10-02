@extends('admin.layout_admin')
@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header p-3">
                                <div class="d-flex align-content-center justify-content-between">
                                    <h3 class="font-weight-bold text-lg">Data SPPR</h3>
                                    <div class="d-flex align-items-center">
                                        @if ($permissions['tambah'])
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal"
                                                data-target="#modalForm">
                                                <i class="fas fa-plus"></i> Tambah SPPR
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-striped data-table">
                                    <thead>
                                        <tr>
                                            <th width="50px">No</th>
                                            <th>Nama</th>
                                            <th>Lokasi Unit</th>
                                            <th>Alamat / No Telp</th>
                                            <th>Luas Tanah / Bangunan (m²)</th>
                                            <th>Marketing</th>
                                            <th width="260px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="modal fade" id="modalForm" tabindex="-1" role="dialog" data-focus="false"
            aria-labelledby="modalFormLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-indigo">
                        <h5 class="modal-title text-white font-weight-bold" id="modalFormLabel"></h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="formData">
                        @csrf
                        <input type="hidden" id="primary_id" name="primary_id">
                        <div class="modal-body">
                            <div class="form-group row">
                                <label for="id_customer" class="col-sm-3 col-form-label">Customer</label>
                                <div class="col-sm-8">
                                    <select name="id_customer" id="id_customer" class="form-control select-customer">
                                        <option value=""></option>
                                        @foreach ($customerList as $c)
                                            <option value="{{ $c->id }}">{{ $c->nama_lengkap }} ({{ $c->nik }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <hr>
                            <div class="form-group row">
                                <label for="no_sppr" class="col-sm-3 col-form-label">No. SPPR</label>
                                <div class="col-sm-8">
                                    <input type="text" name="no_sppr" id="no_sppr" class="form-control">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="tanggal_sppr" class="col-sm-3 col-form-label">Tanggal SPPR</label>
                                <div class="col-sm-8">
                                    <input type="date" name="tanggal_sppr" id="tanggal_sppr" class="form-control"
                                        value="{{ now()->toDateString() }}" required>
                                </div>
                            </div>

                            <hr>
                            <div class="form-group row">
                                <label for="nama" class="col-sm-3 col-form-label">Nama</label>
                                <div class="col-sm-8">
                                    <input type="text" name="nama" id="nama" class="form-control" readonly>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="alamat" class="col-sm-3 col-form-label">Alamat</label>
                                <div class="col-sm-8">
                                    <textarea name="alamat" id="alamat" class="form-control" readonly rows="2"></textarea>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="nik" class="col-sm-3 col-form-label">NIK</label>
                                <div class="col-sm-4">
                                    <input type="text" name="nik" id="nik" class="form-control" readonly>
                                </div>
                                <label for="no_telp" class="col-sm-1 col-form-label">Telp</label>
                                <div class="col-sm-3">
                                    <input type="text" name="no_telp" id="no_telp" class="form-control" readonly>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="pekerjaan" class="col-sm-3 col-form-label">Pekerjaan</label>
                                <div class="col-sm-8">
                                    <input type="text" name="pekerjaan" id="pekerjaan" class="form-control">
                                </div>
                            </div>

                            <hr>
                            <div class="form-group row">
                                <label for="kode_kavling" class="col-sm-3 col-form-label">Kode Kavling</label>
                                <div class="col-sm-8">
                                    <input type="text" id="kode_kavling" class="form-control" readonly>
                                </div>
                            </div>
                            <input type="hidden" name="blok" id="blok">
                            <input type="hidden" name="no" id="no">
                            <div class="form-group row">
                                <label for="luas_bangunan" class="col-sm-3 col-form-label">Luas Bangunan</label>
                                <div class="col-sm-2">
                                    <div class="input-group">
                                        <input type="text" name="luas_bangunan" id="luas_bangunan" class="form-control" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text">m&sup2;</span>
                                        </div>
                                    </div>
                                </div>
                                <label for="luas_tanah" class="col-sm-2 col-form-label">Luas Tanah</label>
                                <div class="col-sm-2">
                                    <div class="input-group">
                                        <input type="text" name="luas_tanah" id="luas_tanah" class="form-control" readonly>
                                        <div class="input-group-append">
                                            <span class="input-group-text">m&sup2;</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="tahun_bangunan" class="col-sm-3 col-form-label">Tahun Bangunan</label>
                                <div class="col-sm-3">
                                    <input type="number" name="tahun_bangunan" id="tahun_bangunan" class="form-control"
                                        min="1000" max="9999" step="1" placeholder="Contoh: 2026">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="harga_jual" class="col-sm-3 col-form-label">Harga Jual</label>
                                <div class="col-sm-3">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="text" name="harga_jual" id="harga_jual" class="form-control rupiah" inputmode="numeric" required>
                                    </div>
                                </div>
                                <label for="asumsi_plafon_kpr" class="col-sm-3 col-form-label">Plafon KPR</label>
                                <div class="col-sm-3">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="text" name="asumsi_plafon_kpr" id="asumsi_plafon_kpr" class="form-control rupiah" inputmode="numeric" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="nominal_dp" class="col-sm-3 col-form-label">DP</label>
                                <div class="col-sm-3">
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="text" name="nominal_dp" id="nominal_dp" class="form-control rupiah" inputmode="numeric" required>
                                    </div>
                                </div>
                                <div class="col-sm-5">
                                    <input type="text" name="keterangan_dp" id="keterangan_dp" class="form-control" placeholder="Keterangan DP">
                                </div>
                            </div>

                            <hr>
                            <div class="form-group row">
                                <label for="id_marketing" class="col-sm-3 col-form-label">Marketing</label>
                                <div class="col-sm-8">
                                    <select name="id_marketing" id="id_marketing" class="form-select select-marketing">
                                        <option value=""></option>
                                        @foreach ($marketingList as $m)
                                            <option value="{{ $m->id }}">{{ $m->nama_marketing }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="penandatangan" class="col-sm-3 col-form-label">Penandatangan / Manajer Pemasaran / Direktur</label>
                                <div class="col-sm-8">
                                    <input type="text" name="penandatangan" id="penandatangan" class="form-control">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="keterangan" class="col-sm-3 col-form-label">Keterangan</label>
                                <div class="col-sm-8">
                                    <textarea name="keterangan" id="keterangan" class="form-control" rows="2"></textarea>
                                </div>
                            </div>

                        </div>
                        <div class="px-3 pb-3">
                            <fieldset class="border rounded p-3" id="sptb-fields">
                                <legend class="w-auto px-2 h5 text-success">Surat Pesanan Tanah dan Bangunan</legend>
                                <p class="text-muted small">Identitas pemesan, unit, harga jual, DP, marketing, dan penandatangan menggunakan data di atas. Isi nomor SPTB lengkap. Nominal awal biaya mengikuti contoh surat dan dapat diubah. Sisa pembayaran serta jadwal pelunasan diisi sesuai kesepakatan.</p>
                                <div class="row">
                                    @foreach (\App\Services\SptbDocument::fields() as $key => $field)
                                        <div class="form-group col-md-6">
                                            <label for="sptb_{{ $key }}">{{ $field['label'] }}</label>
                                            <input id="sptb_{{ $key }}" name="sptb_data[{{ $key }}]"
                                                type="{{ $field['type'] === 'money' ? 'text' : $field['type'] }}"
                                                class="form-control sptb-input {{ $field['type'] === 'money' ? 'rupiah' : '' }}"
                                                data-key="{{ $key }}"
                                                @if (in_array($key, ['panjang_tanah', 'lebar_tanah'])) readonly @endif
                                                value="{{ $field['type'] === 'money' && isset($field['default']) ? number_format($field['default'], 0, ',', '.') : '' }}"
                                                @if ($field['type'] === 'money') inputmode="numeric" @endif
                                                @if ($field['type'] === 'number') min="0" max="999999.99" step="0.01" @endif
                                                @if ($field['type'] === 'text') maxlength="150" @endif>
                                        </div>
                                    @endforeach
                                    <div class="col-12 text-muted small mb-3">Panjang dan lebar otomatis dari panjang kanan dan lebar depan kavling (jika kosong, menggunakan panjang kiri dan lebar belakang).</div>
                                    <div class="form-group col-md-6">
                                        <label for="sptb_total">Total Harga, Biaya, dan Booking Fee (Rp)</label>
                                        <input id="sptb_total" class="form-control" readonly>
                                        <small class="text-muted">Harga jual + PPN + biaya surat + bea KPR + booking fee. Terbilang diisi otomatis saat cetak.</small>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary ms-1" id="submitBtn">
                                <span class="spinner-border spinner-border-sm mx-1 d-none" role="status"
                                    aria-hidden="true"></span>
                                <span class="button-text">Simpan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection
@push('scripts')
    <script>
        $(document).on('click', '[data-target="#modalForm"]', function() {
            $('#modalFormLabel').text('Tambah SPPR');
            $('#id_customer').val('').trigger('change').prop('disabled', false);
            $('#no_sppr').val('');
            $('#tanggal_sppr').val(tanggalHariIni());
            $('#sptb_tanggal').val(tanggalHariIni());
            updateSptbTotal();
        });

        function tanggalHariIni() {
            const parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit'
            }).formatToParts(new Date());
            const part = type => parts.find(item => item.type === type).value;
            return `${part('year')}-${part('month')}-${part('day')}`;
        }

        var audio = new Audio('{{ asset('audio/notification.ogg') }}');
        var permissions = @json($permissions);
        var showActionColumn = true;

        $(function() {
            $('.select-customer').select2({
                theme: "bootstrap4",
                width: '100%',
                placeholder: "Pilih Customer",
                // dropdownParent: $('#modalForm'),
            });

            $('.select-marketing').select2({
                theme: "bootstrap4",
                width: '100%',
                placeholder: "Pilih Marketing",
                // dropdownParent: $('#modalForm'),
            });

            var table = $('.data-table').DataTable({
                processing: false,
                serverSide: false,
                ordering: false,
                responsive: true,
                ajax: "{{ route('sppr.index') }}",
                columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                }, {
                    data: 'customer_nama',
                    name: 'customer_nama',
                    orderable: false,
                    searchable: true
                }, {
                    data: 'customer_lokasi',
                    name: 'customer_lokasi',
                    orderable: false,
                    searchable: true
                }, {
                    data: 'kontak',
                    name: 'kontak',
                    orderable: false,
                    searchable: true
                }, {
                    data: 'luas_tanah',
                    name: 'luas_tanah',
                    render: (data, type, row) => `${Number(data || 0)} / ${Number(row.luas_bangunan || 0)}`,
                    orderable: false,
                    searchable: true
                }, {
                    data: 'nama_marketing',
                    name: 'nama_marketing',
                    orderable: false,
                    searchable: true
                }, {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    visible: showActionColumn,
                    className: 'text-center'
                }],
                columnDefs: [{
                    targets: 0,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                }]
            });
        });

        $(document).on('change', '#id_customer', function() {
            let id = $(this).val();
            $('#sptb_panjang_tanah, #sptb_lebar_tanah').val('');
            if (!id) {
                $('#pekerjaan').val('');
                $('#id_marketing').val('').trigger('change');
                return;
            }

            const url = '{{ route('sppr.get-customer-detail', ':id') }}'.replace(':id', id);
            $.get(url, function(res) {
                if (String($('#id_customer').val()) !== String(id)) return;
                if (res.status === 'success') {
                    let d = res.data;
                    $('#nama').val(d.nama_lengkap || '');
                    $('#alamat').val(d.alamat || '');
                    $('#nik').val(d.nik || '');
                    $('#no_telp').val(d.no_telp || '');
                    $('#luas_bangunan').val(d.luas_bangunan || 0);
                    $('#luas_tanah').val(d.luas_tanah || 0);
                    $('#sptb_panjang_tanah').val(d.panjang_tanah ?? '');
                    $('#sptb_lebar_tanah').val(d.lebar_tanah ?? '');
                    $('#blok').val(d.blok || '');
                    $('#no').val(d.no || '');
                    $('#harga_jual').val(formatNumber(d.harga_jual) || '0');
                    $('#asumsi_plafon_kpr').val(formatNumber(d.asumsi_plafon_kpr) || '0');
                    $('#nominal_dp').val('0');
                    $('#pekerjaan').val(d.pekerjaan || '');
                    $('#kode_kavling').val(d.kode_kavling || '');
                    updateSptbTotal();
                    if (!$('#primary_id').val()) {
                        if (d.id_marketing) {
                            $('#id_marketing').val(d.id_marketing).trigger('change');
                        } else {
                            $('#id_marketing').val('').trigger('change');
                        }
                    }
                }
            });
        });

        $(document).on('input', '.rupiah', function() {
            let value = $(this).val().replace(/\D/g, '');
            $(this).val(value ? formatRupiah(value) : '');
            updateSptbTotal();
        });

        function updateSptbTotal() {
            const total = ['harga_jual', 'sptb_ppn', 'sptb_biaya_surat', 'sptb_biaya_kpr', 'sptb_booking_fee']
                .reduce((sum, key) => sum + unformatNumber($('#' + key).val()), 0);
            $('#sptb_total').val(formatNumber(total));
        }


        function formatRupiah(angka) {
            let number_string = angka.replace(/\D/g, ''),
                split = number_string.split(''),
                sisa = split.length % 3,
                rupiah = split.slice(0, sisa).join(''),
                ribuan = split.slice(sisa).join('').match(/\d{3}/g);

            if (ribuan) {
                let separator = sisa ? '.' : '';
                rupiah += separator + ribuan.join('.');
            }
            return rupiah;
        }

        function formatNumber(num) {
            if (!num && num !== 0) return '';
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function unformatNumber(str) {
            if (!str) return 0;
            return parseInt(str.replace(/\./g, '')) || 0;
        }

        $(document).on('click', '.edit-button', function() {
            var url = $(this).data('url');
            $.get(url, function(response) {
                if (response.status === 'success') {
                    $('#modalFormLabel').text('Edit SPPR');
                    let d = response.data;
                    $('#primary_id').val(d.id);
                    $('#id_customer').val(d.id_customer).prop('disabled', false).trigger('change.select2');
                    $('#no_sppr').val(d.no_sppr || '');
                    $('#tanggal_sppr').val(d.tanggal_sppr || tanggalHariIni());
                    $('#nama').val(d.nama);
                    $('#alamat').val(d.alamat);
                    $('#nik').val(d.nik);
                    $('#no_telp').val(d.no_telp);
                    $('#luas_bangunan').val(d.luas_bangunan);
                    $('#tahun_bangunan').val(d.tahun_bangunan ?? '');
                    $('#luas_tanah').val(d.luas_tanah);
                    $('#blok').val(d.blok);
                    $('#no').val(d.no);
                    $('#harga_jual').val(formatNumber(d.harga_jual));
                    $('#asumsi_plafon_kpr').val(formatNumber(d.asumsi_plafon_kpr));
                    $('#kode_kavling').val(d.kode_kavling || '');
                    $('#pekerjaan').val(d.pekerjaan || '');
                    $('#nominal_dp').val(formatNumber(d.nominal_dp || 0));
                    $('#keterangan_dp').val(d.keterangan_dp || '');
                    if (d.id_marketing) {
                        $('#id_marketing').val(d.id_marketing).trigger('change');
                    } else {
                        $('#id_marketing').val('').trigger('change');
                    }
                    $('#penandatangan').val(d.penandatangan || '');
                    $('#keterangan').val(d.keterangan || '');
                    $('.sptb-input').each(function() {
                        const value = (d.sptb_data || {})[$(this).data('key')] ?? '';
                        $(this).val($(this).hasClass('rupiah') && value !== '' ? formatNumber(value) : value);
                    });
                    updateSptbTotal();
                    $('#modalForm').modal('show');
                }
            });
        });

        $('#modalForm').on('hidden.bs.modal', function() {
            $('#formData')[0].reset();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('#primary_id').val('');
            $('#id_customer').val('').trigger('change').prop('disabled', false);
            $('#no_sppr').val('');
            $('#tanggal_sppr').val(tanggalHariIni());
            $('#id_marketing').val('').trigger('change');
            $('#penandatangan').val('');
            $('#keterangan').val('');
            $('#pekerjaan').val('');
            $('#nominal_dp, #keterangan_dp').val('');

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.addClass('d-none');
            btnText.text('Simpan');
            submitBtn.prop('disabled', false);
        });

        $('#formData').on('submit', function(e) {
            e.preventDefault();

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            let id = $('#primary_id').val();
            let url = id ? '{{ route('sppr.update', ['sppr' => ':id']) }}'.replace(':id', id) :
                '{{ route('sppr.store') }}';
            let method = id ? 'PUT' : 'POST';

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let formData = new FormData(this);

            if (id) {
                formData.set('id_customer', $('#id_customer').val());
            }

            const rupiahFields = ['harga_jual', 'asumsi_plafon_kpr', 'nominal_dp'];
            rupiahFields.forEach(function(field) {
                let input = $('#' + field);
                if (!input.length || input.is(':disabled')) return;
                let val = input.val();
                formData.set(field, unformatNumber(val));
            });
            $('.sptb-input.rupiah').each(function() {
                formData.set(this.name, this.value === '' ? '' : unformatNumber(this.value));
            });

            formData.append('_method', method);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#modalForm').modal('hide');
                    audio.play();
                    let msg = id ? "Data berhasil diupdate!" : "Data berhasil ditambahkan!";
                    toastr.success(msg, "BERHASIL", {
                        progressBar: true,
                        timeOut: 3500,
                        positionClass: "toast-bottom-right",
                    });
                    $('.data-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        audio.play();
                        toastr.error("Ada inputan yang salah!", "GAGAL!", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right",
                        });

                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, val) {
                            let input = $('#' + (key.startsWith('sptb_data.') ? 'sptb_' + key.substring(10) : key));
                            input.addClass('is-invalid');
                            input.parent().find('.invalid-feedback').remove();
                            input.parent().append(
                                '<span class="invalid-feedback" role="alert"><strong>' +
                                val[0] + '</strong></span>'
                            );
                        });

                        spinner.addClass('d-none');
                        btnText.text('Simpan');
                        submitBtn.prop('disabled', false);
                    }
                }
            });
        });

        $(document).on('click', '.delete-button', function(e) {
            e.preventDefault();

            const form = $(this).closest('form');

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<span class="swal-btn-text">Ya, Hapus</span>',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: false,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger mx-2',
                    cancelButton: 'btn btn-secondary'
                },
                preConfirm: () => {
                    return new Promise((resolve) => {
                        const confirmBtn = Swal.getConfirmButton();
                        const btnText = confirmBtn.querySelector('.swal-btn-text');

                        btnText.innerHTML =
                            '<span class="spinner-border spinner-border-sm mx-2" role="status" aria-hidden="true"></span> Menghapus...';
                        confirmBtn.disabled = true;

                        $.ajax({
                            url: form.attr('action'),
                            method: 'POST',
                            data: form.serialize(),
                            success: function() {
                                audio.play();
                                toastr.success("Data telah dihapus!", "BERHASIL", {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: "toast-bottom-right"
                                });
                                $('.data-table').DataTable().ajax.reload(null, false);
                                Swal.close();
                            },
                            error: function() {
                                audio.play();
                                toastr.error("Gagal menghapus data.", "GAGAL!", {
                                    progressBar: true,
                                    timeOut: 3500,
                                    positionClass: "toast-bottom-right"
                                });
                                btnText.innerHTML = 'Ya, Hapus';
                                confirmBtn.disabled = false;
                            }
                        });
                    });
                }
            });
        });
    </script>
@endpush
