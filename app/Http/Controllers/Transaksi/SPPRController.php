<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pengaturan\HakAksesController;
use App\Models\Customer;
use App\Models\MarketingOffline;
use App\Models\SPPR;
use App\Services\SptbDocument;
use App\Traits\LogAktivitasTrait;
use Illuminate\Http\Request;
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
                    $sptbUrl = route('sppr.cetak-sptb', $row->id);
                    $editUrl = route('sppr.edit', $row->id);
                    $deleteUrl = route('sppr.destroy', $row->id);

                    $btn = '<div class="d-flex justify-content-center">';
                    if ($permissions['edit']) {
                        $btn .= '<button class="btn btn-info btn-sm mx-1 edit-button"
                                data-id="' . e($row->id) . '"
                                data-url="' . e($editUrl) . '">Edit</button>';
                    }
                    $btn .= '<a href="' . e($cetakUrl) . '" target="_blank" class="btn btn-dark btn-sm mx-1 text-nowrap" title="Cetak SPPR"><i class="fas fa-print mr-1" aria-hidden="true"></i>SPPR</a>';
                    $btn .= '<a href="' . e($sptbUrl) . '" target="_blank" class="btn btn-success btn-sm mx-1 text-nowrap" title="Cetak SPTB"><i class="fas fa-print mr-1" aria-hidden="true"></i>SPTB</a>';
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
                    'panjang_tanah' => $customer->kavling?->ukuranTanahSppr()['panjang_tanah'] ?? null,
                    'lebar_tanah' => $customer->kavling?->ukuranTanahSppr()['lebar_tanah'] ?? null,
                    'kode_kavling' => $customer->kavling->kode_kavling ?? '',
                    'blok' => $blok,
                    'no' => $no,
                    'harga_jual' => $customer->hrg_jual ?? 0,
                    'asumsi_plafon_kpr' => $customer->estimasi_plafon ?? 0,
                    'pekerjaan' => $customer->pekerjaan ?? '',
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

        // Nilai awal untuk form tambah.
        $request->mergeIfMissing([
            'tanggal_sppr' => now()->toDateString(),
            'harga_jual' => $customer->hrg_jual ?? 0,
            'asumsi_plafon_kpr' => $customer->estimasi_plafon ?? 0,
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
            'id_marketing' => 'nullable|integer',
            'penandatangan' => 'nullable',
            'keterangan' => 'nullable',
            'pekerjaan' => 'nullable',
            'nominal_dp' => 'nullable|numeric',
            'keterangan_dp' => 'nullable',
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
        ]);


        $sptb = $request->validate(SptbDocument::rules());
        $sppr = SPPR::create([
            'sptb_data' => SptbDocument::withKavlingDimensions($sptb['sptb_data'] ?? null, $customer->kavling),
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
            'id_marketing' => $request->id_marketing,
            'penandatangan' => $request->penandatangan,
            'keterangan' => $request->keterangan,
            'pekerjaan' => $request->pekerjaan,
            'nominal_dp' => $request->nominal_dp,
            'keterangan_dp' => $request->keterangan_dp,
        ]);

        $this->logCreate('SPPR', $sppr->id);

        return response()->json(['status' => 'success']);
    }

    public function edit($id)
    {
        $sppr = SPPR::with('customer.kavling')->findOrFail($id);
        $sppr->setAttribute('tanggal_sppr', ($sppr->tanggal_sppr ?? $sppr->created_at ?? now())->format('Y-m-d'));
        $sppr->setAttribute('kode_kavling', $sppr->customer?->kavling?->kode_kavling ?? '');
        $sppr->setAttribute('sptb_data', SptbDocument::withKavlingDimensions($sppr->sptb_data, $sppr->customer?->kavling));

        return response()->json([
            'status' => 'success',
            'data' => array_merge($sppr->toArray(), $sppr->luasUnit()),
        ]);
    }

    public function update(Request $request, $id)
    {
        $sppr = SPPR::findOrFail($id);

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
            'id_marketing' => 'nullable|integer',
            'penandatangan' => 'nullable',
            'keterangan' => 'nullable',
            'pekerjaan' => 'nullable',
            'nominal_dp' => 'nullable|numeric',
            'keterangan_dp' => 'nullable',
        ]);


        $sptb = $request->validate(SptbDocument::rules());
        $sppr->update([
            'sptb_data' => SptbDocument::withKavlingDimensions(
                array_key_exists('sptb_data', $sptb) ? $sptb['sptb_data'] : $sppr->sptb_data,
                Customer::findOrFail($request->id_customer)->kavling
            ),
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
            'id_marketing' => $request->id_marketing,
            'penandatangan' => $request->penandatangan,
            'keterangan' => $request->keterangan,
            'pekerjaan' => $request->pekerjaan,
            'nominal_dp' => $request->nominal_dp,
            'keterangan_dp' => $request->keterangan_dp,
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


    public function cetakSptb($id)
    {
        $sppr = SPPR::with(['customer.lokasi', 'customer.kavling', 'customer.marketing', 'marketing'])->findOrFail($id);
        abort_unless(file_exists(public_path('templates/template_sppr/sptb_wijaya_grande.docx')), 404, 'Template SPTB tidak ditemukan.');
        $path = app(SptbDocument::class)->generate($sppr);
        $name = \Illuminate\Support\Str::slug($sppr->nama) ?: $sppr->id;

        return response()->download($path, 'SPTB_'.$name.'.docx')->deleteFileAfterSend(true);
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


}
