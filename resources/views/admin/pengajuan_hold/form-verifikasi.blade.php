<div class="row booking-columns">
    <div class="col-lg-7 booking-data">
        <h5 class="section-title">Data Booking</h5><p class="text-muted small">Periksa dan lengkapi data pemohon sebelum menyimpan verifikasi.</p>
        <div class="form-group">
            <label>Tanggal Booking</label>
            <input class="form-control" value="{{ $data->tgl_booking_formatted }}" readonly>
        </div>
        <div class="row">
            @foreach ([
                'nama_lengkap' => 'Nama Lengkap', 'nik' => 'NIK',
                'tempat_lahir' => 'Tempat Lahir', 'tgl_lahir' => 'Tanggal Lahir',
                'no_telp' => 'No. Telp / WA', 'email' => 'Email',
                'npwp' => 'NPWP', 'pekerjaan' => 'Pekerjaan', 'no_bpjs_kes' => 'No. BPJS Kes',
                'nama_p' => 'Nama Pasangan', 'nik_p' => 'NIK Pasangan',
                'nama_saudara' => 'Nama Saudara', 'no_telp_saudara' => 'No. Telp Saudara',
            ] as $field => $label)
                <div class="form-group col-12">
                    <label for="{{ $field }}">{{ $label }}</label>
                    <input class="form-control" name="{{ $field }}" id="{{ $field }}"
                        type="{{ $field === 'tgl_lahir' ? 'date' : ($field === 'email' ? 'email' : 'text') }}"
                        value="{{ $data->$field }}">
                </div>
            @endforeach
            @foreach (['jenis_kelamin' => ['Jenis Kelamin', ['Laki-laki', 'Perempuan']], 'status_pernikahan' => ['Status Pernikahan', ['Belum Menikah', 'Menikah']], 'jenis_perumahan' => ['Jenis Perumahan', ['Subsidi', 'Komersil']]] as $field => [$label, $options])
                <div class="form-group col-12">
                    <label for="{{ $field }}">{{ $label }}</label>
                    <select class="form-control" name="{{ $field }}" id="{{ $field }}">
                        <option value="">Pilih {{ $label }}</option>
                        @foreach ($options as $option)
                            <option value="{{ $option }}" @selected($data->$field === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
        @foreach (['alamat_ktp' => 'Alamat KTP', 'alamat_domisili' => 'Alamat Domisili'] as $field => $label)
            <div class="form-group">
                <label for="{{ $field }}">{{ $label }}</label>
                <textarea class="form-control" name="{{ $field }}" id="{{ $field }}" rows="2">{{ $data->$field }}</textarea>
            </div>
        @endforeach
        <div class="row">
            <div class="form-group col-12">
                <label for="id_marketing">Marketing</label>
                <select class="form-control" name="id_marketing" id="id_marketing">
                    <option value="0" @selected((int) $data->id_marketing === 0)>Non Marketing</option>
                    @foreach ($marketing as $item)
                        <option value="{{ $item->id }}" @selected($data->id_marketing == $item->id)>{{ $item->nama_marketing }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-12">
                <label for="id_lokasi">Lokasi Perumahan</label>
                <select class="form-control" name="id_lokasi" id="id_lokasi">
                    @foreach ($lokasi as $item)
                        <option value="{{ $item->id }}" @selected($data->id_lokasi == $item->id)>{{ $item->nama_kavling }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-12">
                <label for="id_kavling">Blok / Kavling</label>
                <select class="form-control" name="id_kavling" id="id_kavling" required>
                    @foreach ($kavlingList->where('id_lokasi', $data->id_lokasi) as $item)
                        <option value="{{ $item->id }}" @selected($data->id_kavling == $item->id)>{{ $item->kode_kavling }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-12">
                <label for="booking_fee">Booking Fee (Rp)</label>
                <input class="form-control format-number" name="booking_fee" id="booking_fee" value="{{ number_format((float) $data->booking_fee, 0, ',', '.') }}">
            </div>
        </div>
        <div id="rincian-biaya" class="mb-3">
            @foreach ($data->rincian_biaya as $item)
                <div>{{ $item['nama'] ?? '-' }}: Rp {{ number_format((float) ($item['nilai'] ?? 0), 0, ',', '.') }}</div>
            @endforeach
        </div>
        <div class="form-group">
            <label for="total_harga">Total Harga (Rp)</label>
            <input class="form-control format-number" name="total_harga" id="total_harga" value="{{ number_format((float) $data->total_harga, 0, ',', '.') }}">
        </div>
    </div>
    <div class="col-lg-5 booking-documents">
        <h5 class="section-title">Lampiran Booking</h5>
        <p class="text-muted">Pilih file untuk mengganti lampiran. File baru disimpan saat menekan Simpan. Tombol Hapus langsung menghapus lampiran tersimpan. Maksimal 10 MB per file.</p>
        @foreach (['foto_pemohon' => 'Foto Pemohon', 'foto_ktp' => 'Foto KTP', 'foto_npwp' => 'Foto NPWP', 'foto_kk' => 'Foto KK', 'foto_bpjs' => 'Foto BPJS', 'foto_ktp_p' => 'Foto KTP Pasangan', 'file_bukti' => 'Bukti Transfer'] as $field => $label)
            <div class="form-group attachment-field">
                <div class="attachment-heading"><strong>{{ $label }}</strong>
                    <span class="attachment-status badge {{ $data->$field ? 'badge-light text-success' : 'badge-light text-muted' }}" data-original="{{ $data->$field ? 'Tersimpan' : 'Belum diunggah' }}">{{ $data->$field ? 'Tersimpan' : 'Belum diunggah' }}</span>
                </div>
                <div class="attachment-body">
                <div class="attachment-preview">
                    @if ($data->$field)
                        <button type="button" class="btn btn-link p-0 attachment-detail" data-url="{{ asset('assets/booking/' . $data->$field) }}" data-pdf="{{ strtolower(pathinfo($data->$field, PATHINFO_EXTENSION)) === 'pdf' ? 1 : 0 }}" data-title="{{ $label }}">
                            @if (strtolower(pathinfo($data->$field, PATHINFO_EXTENSION)) === 'pdf')
                                <i class="far fa-file-pdf fa-2x d-block mb-2" aria-hidden="true"></i>Lihat PDF
                            @else
                                <img src="{{ asset('assets/booking/' . $data->$field) }}" alt="{{ $label }}" style="max-width:100%;max-height:160px">
                            @endif
                        </button>
                    @else
                        <span class="text-muted"><i class="far fa-image fa-2x d-block mb-2" aria-hidden="true"></i>Belum ada file</span>
                    @endif
                </div>
                <div class="attachment-controls">
                <small class="d-block text-muted mb-2">{{ $field === 'file_bukti' ? 'JPG, PNG, atau PDF' : 'JPG atau PNG' }} &middot; Maks. 10 MB</small>
                <label for="{{ $field }}" class="btn btn-outline-primary btn-sm mb-2"><i class="fas fa-upload mr-1" aria-hidden="true"></i>{{ $data->$field ? 'Ganti file' : 'Pilih file' }}</label>
                <input type="file" class="sr-only booking-attachment" name="{{ $field }}" id="{{ $field }}" accept="{{ $field === 'file_bukti' ? '.jpg,.jpeg,.png,.pdf' : '.jpg,.jpeg,.png' }}">
                <span class="attachment-filename small d-block text-muted" aria-live="polite">Belum ada file baru dipilih</span>
                <button type="button" class="btn btn-link btn-sm p-0 mt-1 cancel-upload d-none">Batalkan file baru</button>
                <div class="mt-2 attachment-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm attachment-detail detail-action {{ $data->$field ? '' : 'd-none' }}"
                        data-url="{{ $data->$field ? asset('assets/booking/' . $data->$field) : '' }}"
                        data-pdf="{{ strtolower(pathinfo($data->$field ?? '', PATHINFO_EXTENSION)) === 'pdf' ? 1 : 0 }}" data-title="{{ $label }}">Detail</button>
                    @if ($data->$field)
                        <button type="button" class="btn btn-outline-danger btn-sm delete-attachment" data-field="{{ $field }}"><i class="far fa-trash-alt mr-1" aria-hidden="true"></i>Hapus</button>
                    @endif
                </div>
                </div></div>
            </div>
        @endforeach
    </div>
</div>
<hr>
