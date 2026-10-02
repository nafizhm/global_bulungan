<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SPPR extends Model
{
    protected $table = 'sppr';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_customer',
        'no_sppr',
        'tanggal_sppr',
        'nama',
        'alamat',
        'nik',
        'no_telp',
        'luas_bangunan',
        'tahun_bangunan',
        'luas_tanah',
        'blok',
        'no',
        'harga_jual',
        'asumsi_plafon_kpr',
        'id_marketing',
        'penandatangan',
        'keterangan',
        'pekerjaan',
        'nominal_dp',
        'keterangan_dp',
    ];

    protected $casts = [
        'tanggal_sppr' => 'date:Y-m-d',
        'luas_bangunan' => 'integer',
        'tahun_bangunan' => 'integer',
        'luas_tanah' => 'integer',
        'harga_jual' => 'integer',
        'asumsi_plafon_kpr' => 'integer',
        'id_marketing' => 'integer',
        'nominal_dp' => 'integer',
    ];

    public function luasUnit(): array
    {
        $kavling = $this->customer?->kavling;

        return [
            'luas_tanah' => ($kavling?->luas_tanah ?? $this->luas_tanah ?? 0) + 0,
            'luas_bangunan' => ($kavling?->luas_bangunan ?? $this->luas_bangunan ?? 0) + 0,
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function marketing()
    {
        return $this->belongsTo(MarketingOffline::class, 'id_marketing');
    }
}
