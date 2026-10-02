<?php

namespace App\Services;

use App\Models\SPPR;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

class SptbDocument
{
    public static function fields(): array
    {
        return [
            'nomor' => ['label' => 'Nomor SPTB (lengkap)', 'type' => 'text'],
            'tanggal' => ['label' => 'Tanggal SPTB', 'type' => 'date'],
            'panjang_tanah' => ['label' => 'Panjang Tanah (m)', 'type' => 'number'],
            'lebar_tanah' => ['label' => 'Lebar Tanah (m)', 'type' => 'number'],
            'ppn' => ['label' => 'PPN (Rp)', 'type' => 'money', 'default' => 0],
            'biaya_surat' => ['label' => 'Biaya PPJB/AJB/Sertifikat/BBN (Rp)', 'type' => 'money', 'default' => 4000000],
            'biaya_kpr' => ['label' => 'Bea KPR dan Angsuran KPR-1 (Rp)', 'type' => 'money', 'default' => 4000000],
            'booking_fee' => ['label' => 'Booking Fee (Rp)', 'type' => 'money', 'default' => 2000000],
            'tanggal_booking' => ['label' => 'Tanggal Pembayaran Booking Fee', 'type' => 'date'],
            'tanggal_dp' => ['label' => 'Tanggal Pelunasan Uang Muka', 'type' => 'date'],
            'sisa_pembayaran' => ['label' => 'Sisa Pembayaran (Rp)', 'type' => 'money'],
            'tanggal_angsuran_1' => ['label' => 'Tanggal Pembayaran 1', 'type' => 'date'],
            'nominal_angsuran_1' => ['label' => 'Nominal Pembayaran 1 (Rp)', 'type' => 'money'],
            'tanggal_angsuran_2' => ['label' => 'Tanggal Pembayaran 2', 'type' => 'date'],
            'nominal_angsuran_2' => ['label' => 'Nominal Pembayaran 2 (Rp)', 'type' => 'money'],
        ];
    }

    public static function rules(): array
    {
        $rules = ['sptb_data' => 'nullable|array:'.implode(',', array_keys(self::fields()))];
        foreach (self::fields() as $key => $field) {
            $rules['sptb_data.'.$key] = match ($field['type']) {
                'date' => 'nullable|date_format:Y-m-d',
                'number' => 'nullable|numeric|min:0|max:999999.99',
                'money' => 'nullable|integer|min:0|max:999999999999',
                default => 'nullable|string|max:150',
            };
        }

        return $rules;
    }

    public static function withKavlingDimensions(?array $data, ?\App\Models\KavlingPeta $kavling): array
    {
        return array_merge($data ?? [], array_filter(
            $kavling?->ukuranTanahSppr() ?? [],
            fn ($value) => $value !== null
        ));
    }

    public function generate(SPPR $sppr): string
    {
        $template = new TemplateProcessor(public_path('templates/template_sppr/sptb_wijaya_grande.docx'));
        $data = self::withKavlingDimensions($sppr->sptb_data, $sppr->customer?->kavling);
        $luas = $sppr->luasUnit();
        $money = fn ($value) => $value === null ? '-' : number_format((int) $value, 0, ',', '.');
        $words = fn ($value) => $value === null ? '-' : DocumentDataContext::terbilang($value);
        $date = fn ($value) => filled($value) ? Carbon::parse($value)->locale('id')->isoFormat('D MMMM YYYY') : '-';
        $numberWords = fn ($value) => ucwords((new \NumberFormatter('id', \NumberFormatter::SPELLOUT))->format($value));
        $total = (int) $sppr->harga_jual;
        foreach (['ppn', 'biaya_surat', 'biaya_kpr', 'booking_fee'] as $key) {
            $total += (int) ($data[$key] ?? 0);
        }
        $values = [
            'nama' => $sppr->nama, 'alamat' => $sppr->alamat, 'nik' => $sppr->nik, 'no_telp' => $sppr->no_telp,
            'nama_perumahan' => $sppr->customer?->lokasi?->nama_kavling ?? '-',
            'tipe' => $sppr->customer?->kavling?->tipe_bangunan ?: $luas['luas_bangunan'],
            'luas_tanah' => $luas['luas_tanah'], 'luas_bangunan' => $luas['luas_bangunan'],
            'luas_tanah_terbilang' => $numberWords($luas['luas_tanah']).' Meter Persegi',
            'luas_bangunan_terbilang' => $numberWords($luas['luas_bangunan']).' Meter Persegi',
            'blok' => $sppr->blok ?: '-', 'no' => $sppr->no ?: '-',
            'kode_kavling' => $sppr->customer?->kavling?->kode_kavling ?? '-',
            'harga_jual' => $money($sppr->harga_jual), 'nominal_dp' => $money($sppr->nominal_dp),
            'dp_terbilang' => $words($sppr->nominal_dp),
            'total' => $money($total), 'total_terbilang' => $words($total),
            'marketing' => $sppr->marketing?->nama_marketing ?? $sppr->customer?->marketing?->nama_marketing ?? '-',
            'penandatangan' => $sppr->penandatangan ?: '-',
        ];
        foreach (self::fields() as $key => $field) {
            $value = $data[$key] ?? null;
            $values[$key] = match ($field['type']) {
                'date' => $date($value),
                'money' => $money($value),
                default => filled($value) ? $value : '-',
            };
        }
        $values['booking_terbilang'] = $words($data['booking_fee'] ?? null);
        $values['sisa_terbilang'] = $words($data['sisa_pembayaran'] ?? null);
        $template->setValues(array_map(
            fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            $values
        ));
        $path = tempnam(sys_get_temp_dir(), 'sptb_');
        $template->saveAs($path);

        return $path;
    }
}
