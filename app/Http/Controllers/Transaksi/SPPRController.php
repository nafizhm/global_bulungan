<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Customer;
use App\Models\MarketingOffline;
use App\Models\SPPR;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class SPPRController extends Controller
{
    use LogAktivitasTrait;

    public function index(Request $request)
    {
        $permissions = HakAksesController::getUserPermissions();

        if ($request->ajax()) {
            $data = SPPR::with(['customer.kavling', 'customer.marketing', 'marketing'])->orderBy('id', 'desc');

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('luas_tanah', fn ($row) => $row->luasUnit()['luas_tanah'])
                ->editColumn('luas_bangunan', fn ($row) => $row->luasUnit()['luas_bangunan'])
                ->addColumn('customer_nama', function ($row) {
                    return $row->nama;
                })
                ->addColumn('customer_lokasi', function ($row) {
                    return $row->customer?->kavling?->kode_kavling ?? '-';
                })
                ->addColumn('kontak', function ($row) {
                    return e($row->alamat ?? '-') . '<br><span class="text-muted">Telp: ' . e($row->no_telp ?? '-') . '</span>';
                })
                ->addColumn('nama_marketing', function ($row) {
                    return $row->marketing?->nama_marketing ?? $row->customer?->marketing?->nama_marketing ?? '-';
                })
                ->addColumn('action', function ($row) use ($permissions) {
                    $cetakUrl = route('sppr.cetak', $row->id);
                    $editUrl = route('sppr.edit', $row->id);
                    $deleteUrl = route('sppr.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-info btn-sm mx-1 edit-button"
                                data-id="' . e($row->id) . '"
                                data-url="' . e($editUrl) . '">Edit</button>';
                    }
                    $btn .= '<a href="' . e($cetakUrl) . '" target="_blank" class="btn btn-dark btn-sm mx-1">Cetak</a>';
                    if ($permissions['hapus']) {
                        $btn .= '<form action="' . e($deleteUrl) . '" method="POST" style="display:inline;">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="delete-button btn btn-danger btn-sm mx-1">Hapus</button>
                        </form>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action', 'kontak'])
                ->make(true);
        }

        $customerList = Customer::orderBy('nama_lengkap')->get();
        $marketingList = MarketingOffline::orderBy('nama_marketing')->get();

        return view('admin.transaksi.sppr.index', compact('permissions', 'customerList', 'marketingList'));
    }

    public function getCustomerDetail($id)
    {
        try {
            $customer = Customer::with(['lokasi', 'kavling', 'pemasukans', 'marketing'])->findOrFail($id);

            $bookingFee = (int) $customer->pemasukans()
                ->where('id_kategori_transaksi', 1)
                ->sum('nominal');

            $blok = '';
            $no = '';
            if ($customer->kavling) {
                if ($customer->lokasi && $customer->lokasi->is_cluster) {
                    $blok = $customer->kavling->cluster ?? '';
                    $no = $customer->kavling->no ?? '';
                } else {
                    $kode = $customer->kavling->kode_kavling ?? '';
                    $parts = explode('-', $kode);
                    $blok = $parts[0] ?? $kode;
                    $no = $parts[1] ?? '';
                }
                $blok = filled($customer->kavling->blok) ? $customer->kavling->blok : $blok;
                $no = filled($customer->kavling->no) ? $customer->kavling->no : $no;
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'nama_lengkap' => $customer->nama_lengkap,
                    'alamat' => $customer->alamat_ktp ?? $customer->alamat_domisili ?? '',
                    'nik' => $customer->nik,
                    'no_telp' => $customer->no_telp,
                    'id_marketing' => $customer->id_marketing,
                    'nama_marketing' => $customer->marketing->nama_marketing ?? '',
                    'luas_bangunan' => $customer->kavling->luas_bangunan ?? 0,
                    'luas_tanah' => $customer->kavling->luas_tanah ?? 0,
                    'kode_kavling' => $customer->kavling->kode_kavling ?? '',
                    'blok' => $blok,
                    'no' => $no,
                    'harga_jual' => $customer->hrg_jual ?? 0,
                    'asumsi_plafon_kpr' => $customer->estimasi_plafon ?? 0,
                    'biaya_surat_surat' => $customer->biaya_surat ?? 0,
                    'peningkatan_mutu' => $customer->peningkatan_mutu ?? 0,
                    'jumlah_booking_fee' => $bookingFee,
                    'pekerjaan' => $customer->pekerjaan ?? '',
                    'agama' => $customer->agama ?? '',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Data customer tidak ditemukan.'], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate(['id_customer' => 'required|integer|exists:customer,id']);
        $customer = Customer::findOrFail($request->id_customer);

        // Lengkapi rincian biaya yang tidak ditampilkan pada form tambah.
        $request->mergeIfMissing([
            'tanggal_sppr' => now()->toDateString(),
            'harga_jual' => $customer->hrg_jual ?? 0,
            'asumsi_plafon_kpr' => $customer->estimasi_plafon ?? 0,
            'biaya_surat_surat' => $customer->biaya_surat ?? 0,
            'peningkatan_mutu' => $customer->peningkatan_mutu ?? 0,
            'jumlah_booking_fee' => (int) $customer->pemasukans()
                ->where('id_kategori_transaksi', 1)->sum('nominal'),
            'cicilan_per_bulan' => 0,
        ]);

        $request->validate([
            'id_customer' => 'required|integer|exists:customer,id',
            'no_sppr' => 'nullable',
            'tanggal_sppr' => 'required|date_format:Y-m-d',
            'nama' => 'required',
            'alamat' => 'required',
            'nik' => 'required',
            'no_telp' => 'required',
            'luas_bangunan' => 'required|numeric',
            'tahun_bangunan' => 'nullable|integer|digits:4|min:1000|max:9999',
            'luas_tanah' => 'required|numeric',
            'blok' => 'nullable|string|max:50',
            'no' => 'nullable|string|max:50',
            'harga_jual' => 'required|numeric',
            'asumsi_plafon_kpr' => 'required|numeric',
            'biaya_surat_surat' => 'required|numeric',
            'peningkatan_mutu' => 'required|numeric',
            'biaya_kelebihan_tanah' => 'nullable|numeric',
            'biaya_sudut' => 'nullable|numeric',
            'biaya_lain_lain' => 'nullable|numeric',
            'jumlah_booking_fee' => 'required|numeric',
            'cicilan_per_bulan' => 'required|numeric',
            'id_marketing' => 'nullable|integer',
            'penandatangan' => 'nullable',
            'keterangan' => 'nullable',
            'agama' => 'nullable',
            'pekerjaan' => 'nullable',
            'promo' => 'nullable',
            'perubahan_posisi' => 'nullable',
            'keterangan_booking' => 'nullable',
            'nominal_dp' => 'nullable|numeric',
            'keterangan_dp' => 'nullable',
            'nominal_biaya_posisi_unit' => 'nullable|numeric',
            'keterangan_posisi_unit' => 'nullable',
            'nominal_biaya_kpr' => 'nullable|numeric',
            'keterangan_kpr' => 'nullable',
            'nominal_blokir_angsuran' => 'nullable|numeric',
            'keterangan_blokir_angsuran' => 'nullable',
            'nominal_biaya_materai' => 'nullable|numeric',
            'keterangan_materai' => 'nullable',
            'nominal_biaya_buka_tabungan' => 'nullable|numeric',
            'keterangan_tabungan' => 'nullable',
            'keterangan_shm' => 'nullable',
        ], [
            'id_customer.required' => 'Customer wajib dipilih.',
            'nama.required' => 'Nama wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
            'nik.required' => 'NIK wajib diisi.',
            'no_telp.required' => 'No Telp wajib diisi.',
            'luas_bangunan.required' => 'Luas Bangunan wajib diisi.',
            'luas_tanah.required' => 'Luas Tanah wajib diisi.',
            'blok.required' => 'Blok wajib diisi.',
            'no.required' => 'No wajib diisi.',
            'harga_jual.required' => 'Harga Jual wajib diisi.',
            'asumsi_plafon_kpr.required' => 'Asumsi Plafon KPR wajib diisi.',
            'biaya_surat_surat.required' => 'Biaya Surat-surat wajib diisi.',
            'peningkatan_mutu.required' => 'Peningkatan Mutu wajib diisi.',
            'jumlah_booking_fee.required' => 'Jumlah Booking Fee wajib diisi.',
            'cicilan_per_bulan.required' => 'Cicilan per Bulan wajib diisi.',
        ]);

        $total = $this->hitungTotal($request);

        $sppr = SPPR::create([
            'id_customer' => $request->id_customer,
            'no_sppr' => $request->no_sppr,
            'tanggal_sppr' => $request->tanggal_sppr,
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'nik' => $request->nik,
            'no_telp' => $request->no_telp,
            'luas_bangunan' => $request->luas_bangunan,
            'tahun_bangunan' => $request->tahun_bangunan,
            'luas_tanah' => $request->luas_tanah,
            'blok' => $request->blok ?? '',
            'no' => $request->no ?? '',
            'harga_jual' => $request->harga_jual,
            'asumsi_plafon_kpr' => $request->asumsi_plafon_kpr,
            'biaya_surat_surat' => $request->biaya_surat_surat,
            'peningkatan_mutu' => $request->peningkatan_mutu,
            'biaya_kelebihan_tanah' => $request->biaya_kelebihan_tanah,
            'biaya_sudut' => $request->biaya_sudut,
            'biaya_lain_lain' => $request->biaya_lain_lain,
            'total_yang_harus_dibayar' => $total,
            'jumlah_booking_fee' => $request->jumlah_booking_fee,
            'cicilan_per_bulan' => $request->cicilan_per_bulan,
            'id_marketing' => $request->id_marketing,
            'penandatangan' => $request->penandatangan,
            'keterangan' => $request->keterangan,
            'agama' => $request->agama,
            'pekerjaan' => $request->pekerjaan,
            'promo' => $request->promo,
            'perubahan_posisi' => $request->perubahan_posisi,
            'keterangan_booking' => $request->keterangan_booking,
            'nominal_dp' => $request->nominal_dp,
            'keterangan_dp' => $request->keterangan_dp,
            'nominal_biaya_posisi_unit' => $request->nominal_biaya_posisi_unit,
            'keterangan_posisi_unit' => $request->keterangan_posisi_unit,
            'nominal_biaya_kpr' => $request->nominal_biaya_kpr,
            'keterangan_kpr' => $request->keterangan_kpr,
            'nominal_blokir_angsuran' => $request->nominal_blokir_angsuran,
            'keterangan_blokir_angsuran' => $request->keterangan_blokir_angsuran,
            'nominal_biaya_materai' => $request->nominal_biaya_materai,
            'keterangan_materai' => $request->keterangan_materai,
            'nominal_biaya_buka_tabungan' => $request->nominal_biaya_buka_tabungan,
            'keterangan_tabungan' => $request->keterangan_tabungan,
            'keterangan_shm' => $request->keterangan_shm,
        ]);

        $this->logCreate('SPPR', $sppr->id);

        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        $sppr = SPPR::with('customer.kavling')->findOrFail($id);
        $sppr->setAttribute('tanggal_sppr', ($sppr->tanggal_sppr ?? $sppr->created_at ?? now())->format('Y-m-d'));
        $sppr->setAttribute('kode_kavling', $sppr->customer?->kavling?->kode_kavling ?? '');

        return response()->json([
            'status' => 'success',
            'data' => array_merge($sppr->toArray(), $sppr->luasUnit()),
        ]);
    }

    public function update(Request $request, $id)
    {
        $sppr = SPPR::findOrFail($id);

        // Pertahankan rincian biaya yang tidak ditampilkan pada form tambah/edit.
        $request->mergeIfMissing($sppr->only([
            'biaya_kelebihan_tanah', 'biaya_sudut', 'biaya_lain_lain',
            'promo', 'perubahan_posisi', 'keterangan_booking',
            'nominal_biaya_posisi_unit', 'keterangan_posisi_unit',
            'nominal_biaya_kpr', 'keterangan_kpr',
            'nominal_blokir_angsuran', 'keterangan_blokir_angsuran',
            'nominal_biaya_materai', 'keterangan_materai',
            'nominal_biaya_buka_tabungan', 'keterangan_tabungan', 'keterangan_shm',
        ]));
        $request->mergeIfMissing([
            'biaya_surat_surat' => $sppr->biaya_surat_surat ?? 0,
            'peningkatan_mutu' => $sppr->peningkatan_mutu ?? 0,
            'jumlah_booking_fee' => $sppr->jumlah_booking_fee ?? 0,
            'cicilan_per_bulan' => $sppr->cicilan_per_bulan ?? 0,
        ]);

        $request->validate([
            'id_customer' => 'required|integer|exists:customer,id',
            'no_sppr' => 'nullable',
            'tanggal_sppr' => 'required|date_format:Y-m-d',
            'nama' => 'required',
            'alamat' => 'required',
            'nik' => 'required',
            'no_telp' => 'required',
            'luas_bangunan' => 'required|numeric',
            'luas_tanah' => 'required|numeric',
            'tahun_bangunan' => 'nullable|integer|digits:4|min:1000|max:9999',
            'blok' => 'nullable|string|max:50',
            'no' => 'nullable|string|max:50',
            'harga_jual' => 'required|numeric',
            'asumsi_plafon_kpr' => 'required|numeric',
            'biaya_surat_surat' => 'required|numeric',
            'peningkatan_mutu' => 'required|numeric',
            'biaya_kelebihan_tanah' => 'nullable|numeric',
            'biaya_sudut' => 'nullable|numeric',
            'biaya_lain_lain' => 'nullable|numeric',
            'jumlah_booking_fee' => 'required|numeric',
            'cicilan_per_bulan' => 'required|numeric',
            'id_marketing' => 'nullable|integer',
            'penandatangan' => 'nullable',
            'keterangan' => 'nullable',
            'agama' => 'nullable',
            'pekerjaan' => 'nullable',
            'promo' => 'nullable',
            'perubahan_posisi' => 'nullable',
            'keterangan_booking' => 'nullable',
            'nominal_dp' => 'nullable|numeric',
            'keterangan_dp' => 'nullable',
            'nominal_biaya_posisi_unit' => 'nullable|numeric',
            'keterangan_posisi_unit' => 'nullable',
            'nominal_biaya_kpr' => 'nullable|numeric',
            'keterangan_kpr' => 'nullable',
            'nominal_blokir_angsuran' => 'nullable|numeric',
            'keterangan_blokir_angsuran' => 'nullable',
            'nominal_biaya_materai' => 'nullable|numeric',
            'keterangan_materai' => 'nullable',
            'nominal_biaya_buka_tabungan' => 'nullable|numeric',
            'keterangan_tabungan' => 'nullable',
            'keterangan_shm' => 'nullable',
        ]);

        $total = $this->hitungTotal($request);

        $sppr->update([
            'id_customer' => $request->id_customer,
            'no_sppr' => $request->no_sppr,
            'tanggal_sppr' => $request->tanggal_sppr,
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'nik' => $request->nik,
            'no_telp' => $request->no_telp,
            'luas_bangunan' => $request->luas_bangunan,
            'luas_tanah' => $request->luas_tanah,
            'tahun_bangunan' => $request->tahun_bangunan,
            'blok' => $request->blok ?? '',
            'no' => $request->no ?? '',
            'harga_jual' => $request->harga_jual,
            'asumsi_plafon_kpr' => $request->asumsi_plafon_kpr,
            'biaya_surat_surat' => $request->biaya_surat_surat,
            'peningkatan_mutu' => $request->peningkatan_mutu,
            'biaya_kelebihan_tanah' => $request->biaya_kelebihan_tanah,
            'biaya_sudut' => $request->biaya_sudut,
            'biaya_lain_lain' => $request->biaya_lain_lain,
            'total_yang_harus_dibayar' => $total,
            'jumlah_booking_fee' => $request->jumlah_booking_fee,
            'cicilan_per_bulan' => $request->cicilan_per_bulan,
            'id_marketing' => $request->id_marketing,
            'penandatangan' => $request->penandatangan,
            'keterangan' => $request->keterangan,
            'agama' => $request->input('agama', $sppr->agama),
            'pekerjaan' => $request->pekerjaan,
            'promo' => $request->promo,
            'perubahan_posisi' => $request->perubahan_posisi,
            'keterangan_booking' => $request->keterangan_booking,
            'nominal_dp' => $request->nominal_dp,
            'keterangan_dp' => $request->keterangan_dp,
            'nominal_biaya_posisi_unit' => $request->nominal_biaya_posisi_unit,
            'keterangan_posisi_unit' => $request->keterangan_posisi_unit,
            'nominal_biaya_kpr' => $request->nominal_biaya_kpr,
            'keterangan_kpr' => $request->keterangan_kpr,
            'nominal_blokir_angsuran' => $request->nominal_blokir_angsuran,
            'keterangan_blokir_angsuran' => $request->keterangan_blokir_angsuran,
            'nominal_biaya_materai' => $request->nominal_biaya_materai,
            'keterangan_materai' => $request->keterangan_materai,
            'nominal_biaya_buka_tabungan' => $request->nominal_biaya_buka_tabungan,
            'keterangan_tabungan' => $request->keterangan_tabungan,
            'keterangan_shm' => $request->keterangan_shm,
        ]);

        $this->logEdit('SPPR', $sppr->id);

        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $sppr = SPPR::findOrFail($id);

        $this->logDelete('SPPR', $sppr->id);
        $sppr->delete();

        return response()->json(['status' => 'success']);
    }


    public function cetak($id)
    {
        $sppr = SPPR::with(['customer.lokasi', 'customer.kavling'])->findOrFail($id);
        $customer = $sppr->customer;
        $luas = $sppr->luasUnit();
        $templatePath = public_path('templates/template_sppr/spr_wijaya_grande.docx');

        if (!file_exists($templatePath)) {
            abort(404, 'Template SPR Wijaya Grande tidak ditemukan.');
        }

        $templateProcessor = new TemplateProcessor($templatePath);
        $tanggalDoc = $sppr->tanggal_sppr ?? $sppr->created_at ?? Carbon::now();
        $rupiah = fn ($value) => 'Rp ' . number_format((int) ($value ?? 0), 0, ',', '.');

        $values = [
            'tanggal_surat' => $tanggalDoc->locale('id')->isoFormat('D MMMM YYYY'),
            'no_sppr' => filled($sppr->no_sppr) ? $sppr->no_sppr : '-',
            'nama_lengkap' => $sppr->nama,
            'nik' => $sppr->nik,
            'tipe_bangunan' => (string) $luas['luas_bangunan'],
            'luas_bangunan' => (string) $luas['luas_bangunan'],
            'luas_tanah' => (string) $luas['luas_tanah'],
            'tahun_bangunan' => $sppr->tahun_bangunan ?? '-',
            'nama_perumahan' => $customer?->lokasi?->nama_kavling ?? '-',
            'kode_kavling' => $customer?->kavling?->kode_kavling ?? '-',
            'harga_jual' => $rupiah($sppr->harga_jual),
            'nominal_dp' => $rupiah($sppr->nominal_dp),
            'plafon_kpr' => $rupiah($sppr->asumsi_plafon_kpr),
        ];

        // Escape data customer agar karakter seperti & dan < tetap valid di XML Word.
        $templateProcessor->setValues(array_map(
            fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            $values
        ));

        $filename = 'SPPR_' . str_replace(' ', '_', $sppr->nama) . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'sppr_');
        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }


    private function hitungTotal(Request $request)
    {
        return (int) $request->jumlah_booking_fee
            + (int) ($request->nominal_dp ?? 0)
            + (int) ($request->nominal_biaya_posisi_unit ?? 0)
            + (int) ($request->nominal_biaya_kpr ?? 0)
            + (int) ($request->nominal_blokir_angsuran ?? 0)
            + (int) ($request->nominal_biaya_materai ?? 0)
            + (int) ($request->nominal_biaya_buka_tabungan ?? 0)
            + (int) $request->peningkatan_mutu
            + 4000000;
    }
}
