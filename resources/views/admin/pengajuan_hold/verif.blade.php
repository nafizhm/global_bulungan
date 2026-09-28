@extends('admin.layout_admin')
@section('content')
    <style>
        .booking-verification .card { border-radius: 12px; overflow: hidden; }
        .booking-verification .card-body { padding: 24px; }
        .booking-verification .section-title { font-size: 1.05rem; font-weight: 700; color: #263a52; margin-bottom: 8px; }
        .booking-verification .booking-data { padding-right: 28px; }
        .booking-verification .booking-data .form-group { display: grid; grid-template-columns: minmax(140px, 34%) minmax(0, 1fr); gap: 8px 16px; align-items: start; margin-bottom: 0; padding-top: 12px; padding-bottom: 12px; border-bottom: 1px solid #edf0f4; }
        .booking-verification .booking-data label { padding-top: 8px; margin-bottom: 0; font-size: .9rem; color: #475569; font-weight: 600; }
        .booking-verification .form-control { border-color: #d9e1ea; border-radius: 6px; }
        .booking-verification .form-control:focus { border-color: #648cce; box-shadow: 0 0 0 3px rgba(63,106,180,.1); }
        .booking-verification .booking-data .invalid-feedback { grid-column: 2; }
        .booking-verification #rincian-biaya { background: #f7f9fc; border-radius: 8px; padding: 14px; margin-top: 16px; font-size: .9rem; line-height: 1.9; }
        .booking-verification .booking-documents { border-left: 1px solid #e8edf3; padding-left: 24px; }
        .booking-verification .attachment-field { border: 1px solid #e0e6ed; border-radius: 10px; padding: 16px; margin-bottom: 14px; background: #fff; }
        .booking-verification .attachment-heading { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 12px; font-size: .9rem; }
        .booking-verification .attachment-status { white-space: normal; text-align: right; font-weight: 500; }
        .booking-verification .attachment-body { display: flex; gap: 16px; align-items: center; }
        .booking-verification .attachment-preview { width: 112px; min-height: 100px; flex-shrink: 0; border: 1px solid #e5eaf0; background: #f7f9fc; border-radius: 8px; display: flex; align-items: center; justify-content: center; padding: 8px; text-align: center; font-size: .75rem; overflow-wrap: anywhere; }
        .booking-verification .attachment-preview img { max-height: 112px !important; object-fit: contain; }
        .booking-verification .attachment-controls { min-width: 0; }
        .booking-verification .attachment-filename { overflow-wrap: anywhere; }
        .booking-verification .attachment-preview.pending-removal { opacity: .3; }
        .booking-verification .attachment-field:focus-within { border-color: #648cce; }
        .booking-verification .remove-attachment + label { font-size: .8rem; color: #a24040; font-weight: 400; }
        .booking-verification .verification-actions { display: flex; align-items: center; gap: 8px; padding: 20px 0 0; margin-top: 24px; border-top: 1px solid #e5eaf0; }
        @media (max-width: 991px) {
            .booking-verification .booking-data { padding-right: 15px; }
            .booking-verification .booking-documents { border-left: 0; padding-left: 15px; margin-top: 28px; }
        }
        @media (max-width: 575px) {
            .booking-verification .card-body { padding: 16px; }
            .booking-verification .booking-data .form-group { grid-template-columns: 1fr; gap: 6px; }
            .booking-verification .booking-data .invalid-feedback { grid-column: 1; }
            .booking-verification .verification-actions { flex-wrap: wrap; }
            .booking-verification .verification-actions > span { display: none; }
            .booking-verification .attachment-preview { width: 88px; }
        }
    </style>
    <div class="content-wrapper booking-verification">
        <section class="content-header">
            <div class="container-fluid">
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header p-3 bg-indigo text-white">
                                <div class="d-flex align-content-center justify-content-between">
                                    <h3 class="font-weight-bold text-lg">Verifikasi Data Booking</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <form id="formData" enctype="multipart/form-data">
                                    @csrf
                                    @include('admin.pengajuan_hold.form-verifikasi')
                                    <h5 class="section-title">Keputusan &amp; Pembayaran</h5>
                                    <p class="text-muted small">Pilih Pending untuk menyimpan perubahan dan melanjutkan pemeriksaan nanti.</p>

                                <div id="verificationError" class="alert alert-danger d-none" role="alert">
                                    <strong>Verifikasi gagal.</strong>
                                    <div class="mt-1" id="verificationErrorMessage"></div>
                                </div>

                                    <input type="hidden" id="primary_id" name="primary_id"
                                        value="{{ $data->id }}">

                                    <div class="form-group row">
                                        <label for="stt_reg" class="col-sm-2 col-form-label">Status Verifikasi</label>
                                        <div class="col-sm-3">
                                            <select name="stt_reg" id="stt_reg" class="form-control select-status">
                                                <option value=""></option>
                                                <option value="1" {{ $data->stt_reg == 1 ? 'selected' : '' }}>Pending
                                                </option>
                                                <option value="2" {{ $data->stt_reg == 2 ? 'selected' : '' }}>
                                                    Disetujui
                                                </option>
                                                <option value="3" {{ $data->stt_reg == 3 ? 'selected' : '' }}>Ditolak
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-2 col-form-label">Metode Bayar</label>
                                        <div class="col-sm-3">
                                            <select class="form-select select-metode-bayar" name="id_metode_bayar"
                                                id="id_metode_bayar">
                                                <option value=""></option>
                                                @foreach ($metodeBayarList as $item)
                                                    <option value="{{ $item->id }}" @selected($data->id_metode_bayar == $item->id)>{{ $item->jenis_bayar }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="id_bank" class="col-sm-2 col-form-label">Rekening Pembayaran</label>
                                        <div class="col-sm-3">
                                            <select class="form-select select-bank" name="id_bank" id="id_bank">
                                                <option value=""></option>
                                                @foreach ($bankList as $item)
                                                    <option value="{{ $item->id }}" @selected($data->id_bank == $item->id)>{{ $item->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="jenis_pembelian" class="col-sm-2 col-form-label">Jenis
                                            Pembelian</label>
                                        <div class="col-sm-3">
                                            <select name="jenis_pembelian" id="jenis_pembelian"
                                                class="form-control select-pembelian">
                                                <option value=""></option>
                                                <option value="Pembelian Cash"
                                                    {{ $data->jenis_pembelian == 'Pembelian Cash' ? 'selected' : '' }}>
                                                    Pembelian Cash</option>
                                                <option value="Cash Bertahap"
                                                    {{ $data->jenis_pembelian == 'Cash Bertahap' ? 'selected' : '' }}>Cash
                                                    Bertahap</option>
                                                <option value="KPR"
                                                    {{ $data->jenis_pembelian == 'KPR' ? 'selected' : '' }}>KPR</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- CASH ==================================> -->
                                    <hr class="hr-transaksi" style="display: none;">
                                    <div id="trx_cash" style="display: none;">
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Atas Nama Surat</label>
                                            <div class="col-sm-3">
                                                <input name="an_surat_cash" id="an_surat_cash" class="form-control"
                                                    type="text"
                                                    value="{{ $data->an_surat_cash ?? $data->nama_lengkap }}">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CASH BERTAHAP ==================================> -->
                                    <hr class="hr-transaksi" style="display: none;">
                                    <div id="trx_cash_bertahap" style="display: none;">
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label">Termin (x)</label>
                                            <div class="col-sm-2">
                                                <div class="input-group">
                                                    <input name="termin_x_cash_b" id="termin_x_cash_b"
                                                        class="form-control format-number"
                                                        value="{{ isset($data->termin_x_cash_b) && $data->termin_x_cash_b != 0 ? number_format($data->termin_x_cash_b, 0, ',', '.') : 60 }}">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">Bulan</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="verification-actions">
                                        <a href="{{ route('pengajuan-hold.index') }}" class="btn btn-outline-secondary">Kembali</a>

                                        <span class="text-muted small mr-auto ml-3">Data dan lampiran disimpan bersama.</span>
                                        <button type="submit" class="btn btn-primary ms-1" id="submitBtn"
                                            @if (isset($data) && $data->stt_reg == 2) readonly @endif>
                                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                                aria-hidden="true"></span>
                                            <span class="button-text">Simpan</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <div class="modal fade" id="attachmentModal" tabindex="-1" role="dialog" aria-labelledby="attachmentModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="attachmentModalTitle">Detail Lampiran</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body text-center" id="attachmentModalBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select-status').select2({
                theme: "bootstrap4",
                minimumResultsForSearch: Infinity,
            });

            $('.select-jenis').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Jenis Pembelian',
                minimumResultsForSearch: Infinity,
            });

            $('.select-bank').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Bank',
                minimumResultsForSearch: Infinity,
                width: '100%'
            });

            $('.select-metode-bayar').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Metode Bayar',
                minimumResultsForSearch: Infinity,
                width: '100%'
            });

            $('.select-pembelian').select2({
                theme: "bootstrap4",
                placeholder: 'Pilih Jenis Pembelian',
                minimumResultsForSearch: Infinity,
            });
        });

        $(document).ready(function() {
            function hideAllTransactionForms() {
                $('#trx_cash').hide();
                $('#trx_cash_bertahap').hide();
                $('.hr-transaksi').hide();
            }

            $('#jenis_pembelian').on('change', function() {
                let selected = $(this).val();
                hideAllTransactionForms();

                switch (selected) {
                    case 'Pembelian Cash':
                        $('#trx_cash').prev('.hr-transaksi').show();
                        $('#trx_cash').show();
                        break;
                    case 'Cash Bertahap':
                        $('#trx_cash_bertahap').prev('.hr-transaksi').show();
                        $('#trx_cash_bertahap').show();
                        break;
                }
            });

            $('#jenis_pembelian').trigger('change');
        });


        @php
            $bookingKavling = $kavlingList->map->only(['id', 'id_lokasi', 'kode_kavling', 'rincian_biaya', 'total_harga'])->values();
        @endphp
        const bookingKavling = @json($bookingKavling);
        $('#id_lokasi').on('change', function() {
            const locationId = this.value;
            const select = $('#id_kavling').empty().append(new Option('Pilih Kavling', ''));
            bookingKavling.filter(item => String(item.id_lokasi) === locationId)
                .forEach(item => select.append(new Option(item.kode_kavling, item.id)));
            select.trigger('change');
        });
        $('#id_kavling').on('change', function() {
            const item = bookingKavling.find(item => String(item.id) === this.value);
            const container = $('#rincian-biaya').empty();
            (item?.rincian_biaya || []).forEach(cost => {
                $('<div>').text((cost.nama || '-') + ': Rp ' + Number(cost.nilai || 0).toLocaleString('id-ID')).appendTo(container);
            });
            $('#total_harga').val(item ? Number(item.total_harga).toLocaleString('id-ID') : '');
        });
        const emptyPreview = '<span class="text-muted"><i class="far fa-image fa-2x d-block mb-2" aria-hidden="true"></i>Belum ada file</span>';
        $('.attachment-field').each(function() {
            const group = $(this);
            group.data('savedPreview', group.find('.attachment-preview').html());
            group.data('savedUrl', group.find('.detail-action').attr('data-url') || '');
            group.data('savedPdf', group.find('.detail-action').attr('data-pdf'));
        });
        $('.booking-attachment').on('change', function() {
            const group = $(this).closest('.attachment-field');
            const preview = group.find('.attachment-preview');
            const detail = group.find('.detail-action');
            if (group.data('objectUrl')) URL.revokeObjectURL(group.data('objectUrl'));
            group.removeData('objectUrl');
            const file = this.files[0];
            if (file && file.size > 10 * 1024 * 1024) {
                this.value = '';
                $(this).trigger('change');
                toastr.error('Ukuran file maksimal 10 MB.');
                return;
            }
            group.find('.attachment-filename').text(file ? file.name : 'Belum ada file baru dipilih');
            group.find('.cancel-upload').toggleClass('d-none', !file);
            group.find('.attachment-status').text(file ? 'File baru - belum disimpan' : group.find('.attachment-status').data('original'));
            preview.html(group.data('savedPreview'));
            detail.attr('data-url', group.data('savedUrl')).attr('data-pdf', group.data('savedPdf')).toggleClass('d-none', !group.data('savedUrl'));
            if (!file) return;
            const url = URL.createObjectURL(file);
            const isPdf = file.type === 'application/pdf';
            group.data('objectUrl', url);
            detail.attr('data-url', url).attr('data-pdf', isPdf ? '1' : '0').removeClass('d-none');
            preview.empty();
            const button = $('<button>', {type: 'button', class: 'btn btn-link p-0 attachment-detail'})
                .attr({'data-url': url, 'data-pdf': isPdf ? '1' : '0', 'data-title': detail.attr('data-title')}).appendTo(preview);
            if (file.type.startsWith('image/')) {
                $('<img>', {src: url, alt: file.name}).css('max-width', '100%').appendTo(button);
            } else {
                button.text(isPdf ? 'Lihat PDF' : file.name);
            }
        });
        $('.cancel-upload').on('click', function() {
            $(this).closest('.attachment-field').find('.booking-attachment').val('').trigger('change');
        });
        $(document).on('click', '.attachment-detail', function() {
            const url = $(this).attr('data-url');
            if (!url) return;
            const title = $(this).attr('data-title') || 'Detail Lampiran';
            $('#attachmentModalTitle').text(title);
            const body = $('#attachmentModalBody').empty();
            if ($(this).attr('data-pdf') === '1') {
                $('<iframe>', {src: url, title: title}).css({width: '100%', height: '72vh', border: 0}).appendTo(body);
            } else {
                $('<img>', {src: url, alt: title}).css({'max-width': '100%', 'max-height': '72vh', 'object-fit': 'contain'}).appendTo(body);
            }
            $('#attachmentModal').modal('show');
        });
        $('#attachmentModal').on('hidden.bs.modal', function() { $('#attachmentModalBody').empty(); });
        let deletingAttachment = false;
        $('.delete-attachment').on('click', function() {
            if (deletingAttachment || $('#submitBtn').prop('disabled')) return;
            deletingAttachment = true;
            const button = $(this);
            const group = button.closest('.attachment-field');
            $('.delete-attachment, #submitBtn').prop('disabled', true);
            button.text('Menghapus...');
            $.ajax({
                url: @json(route('pengajuan-hold.delete-file', $data->id)),
                method: 'POST',
                data: {_token: $('#formData input[name="_token"]').val(), field: button.data('field')},
                success: function(response) {
                    if (!response.success) { toastr.error('Lampiran gagal dihapus.'); return; }
                    group.data('savedPreview', emptyPreview).data('savedUrl', '').data('savedPdf', '0');
                    group.find('.attachment-status').data('original', 'Belum diunggah');
                    group.find('.booking-attachment').val('').trigger('change');
                    group.find('label.btn').html('<i class="fas fa-upload mr-1" aria-hidden="true"></i>Pilih file');
                    button.remove();
                    toastr.success('Lampiran sudah dihapus.');
                },
                error: function(xhr) { toastr.error(xhr.responseJSON?.message || 'Lampiran gagal dihapus. Coba lagi.'); },
                complete: function() {
                    deletingAttachment = false;
                    $('.delete-attachment, #submitBtn').prop('disabled', false);
                    button.html('<i class="far fa-trash-alt mr-1" aria-hidden="true"></i>Hapus');
                }
            });
        });

        var audio = new Audio('{{ asset('audio/notification.ogg') }}');

        $('#formData').on('submit', function(e) {
            e.preventDefault();
            if (deletingAttachment || $('#submitBtn').prop('disabled')) return;

            let submitBtn = $('#submitBtn');
            let spinner = submitBtn.find('.spinner-border');
            let btnText = submitBtn.find('.button-text');

            spinner.removeClass('d-none');
            btnText.text('Menyimpan...');
            submitBtn.prop('disabled', true);

            let id = '{{ $data->id }}';
            let url = '{{ route('pengajuan-hold.verifikasi.simpan', ['id' => ':id']) }}'.replace(':id', id);
            let method = 'POST';

            $('#verificationError').addClass('d-none');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let formData = new FormData(this);
            formData.append('_method', method);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    sessionStorage.setItem('success', 'Verifikasi Booking Berhasil!');
                    window.location.href = "{{ route('pengajuan-hold.index') }}";
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        audio.play();
                        toastr.error("Ada inputan yang salah!", "GAGAL!", {
                            progressBar: true,
                            timeOut: 3500,
                            positionClass: "toast-bottom-right",
                        });

                        let errors = xhr.responseJSON.errors || {};
                        $('#verificationErrorMessage').html(Object.values(errors).flat().join('<br>'));
                        $('#verificationError').removeClass('d-none');
                        $.each(errors, function(key, val) {
                            let input = $('#' + key);
                            input.addClass('is-invalid');
                            input.parent().find('.invalid-feedback').remove();
                            input.parent().append(
                                '<span class="invalid-feedback" role="alert"><strong>' +
                                val[0] + '</strong></span>'
                            );
                        });
                    } else {
                        audio.play();
                        const response = xhr.responseJSON || {};
                        const serverErrors = response.errors || {};
                        const messages = [];

                        Object.keys(serverErrors).forEach(function(key) {
                            (Array.isArray(serverErrors[key]) ? serverErrors[key] : [serverErrors[key]])
                                .forEach(function(message) {
                                    messages.push(message);
                                    const input = $('#' + key);
                                    if (input.length) {
                                        input.addClass('is-invalid');
                                        input.closest('.form-group, .input-group, .col-sm-3, .col-sm-2')
                                            .find('.invalid-feedback').remove();
                                        input.closest('.form-group, .input-group, .col-sm-3, .col-sm-2')
                                            .append('<span class="invalid-feedback d-block"><strong>' + message + '</strong></span>');
                                    }
                                });
                        });

                        const message = messages.join('<br>') || response.error || response.message || 'Terjadi kesalahan pada server.';
                        $('#verificationErrorMessage').html(message);
                        $('#verificationError').removeClass('d-none');
                        toastr.error(message, "GAGAL!", {
                            progressBar: true,
                            timeOut: 6000,
                            positionClass: "toast-bottom-right",
                        });
                    }
                    document.getElementById('verificationError').scrollIntoView({behavior: 'smooth', block: 'center'});
                    spinner.addClass('d-none');
                    btnText.text('Simpan');
                    submitBtn.prop('disabled', false);
                }
            });
        });
    </script>
@endpush
