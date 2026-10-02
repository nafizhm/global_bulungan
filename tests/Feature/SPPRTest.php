<?php

namespace Tests\Feature;

use App\Http\Controllers\Transaksi\SPPRController;
use App\Models\{Customer, KavlingPeta, LokasiKavling, MarketingOffline, Menu, Pemasukan, SPPR};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Route, Schema};
use Tests\TestCase;
use ZipArchive;

class SPPRTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([new SPPR, new Customer, new KavlingPeta, new LokasiKavling, new MarketingOffline, new Menu, new Pemasukan] as $model) {
            Schema::create($model->getTable(), function (Blueprint $table) use ($model) {
                $table->id();
                foreach (array_unique($model->getFillable()) as $field) {
                    if (!in_array($field, ['id', 'created_at', 'updated_at'])) {
                        $table->text($field)->nullable();
                    }
                }
                $table->timestamps();
            });
        }

        $lokasi = LokasiKavling::create(['nama_kavling' => 'Wijaya Grande']);
        $unit = KavlingPeta::create(['kode_kavling' => 'WG-A-007', 'tipe_bangunan' => '45', 'luas_tanah' => 90.5, 'luas_bangunan' => 40]);
        $marketing = MarketingOffline::create(['nama_marketing' => 'Marketing Customer']);
        $customer = Customer::create([
            'nama_lengkap' => 'Nama Customer', 'id_kavling' => $unit->id,
            'id_lokasi' => $lokasi->id, 'id_marketing' => $marketing->id,
        ]);
        SPPR::create([
            'id_customer' => $customer->id, 'nama' => 'Budi & Siti <Uji>',
            'no_sppr' => '007/WIJAYA/SPR/X/2026', 'nik' => '1234567890123456',
            'alamat' => 'Jl. Uji & Contoh', 'no_telp' => '081234567890',
            'blok' => 'LAMA', 'no' => '99', 'luas_bangunan' => 36, 'luas_tanah' => 72,
            'harga_jual' => 200000000, 'nominal_dp' => 20000000, 'asumsi_plafon_kpr' => 180000000,
        ]);

        Route::post('/test-sppr', [SPPRController::class, 'store']);
        Route::put('/test-sppr/{id}', [SPPRController::class, 'update']);
        Route::get('/test-sppr/{id}/edit', [SPPRController::class, 'edit']);
        Route::get('/test-sppr', [SPPRController::class, 'index'])->name('test.sppr');
    }

    public function test_land_dimensions_follow_kavling_on_customer_selection_save_and_edit(): void
    {
        Route::get('/test-sppr-customer/{id}', [SPPRController::class, 'getCustomerDetail']);
        $unit = KavlingPeta::first();
        $unit->update(['panjang_kanan' => '12,5', 'panjang_kiri' => 13, 'lebar_depan' => 6, 'lebar_belakang' => 7]);
        $this->getJson('/test-sppr-customer/'.Customer::first()->id)->assertOk()
            ->assertJsonPath('data.panjang_tanah', 12.5)
            ->assertJsonPath('data.lebar_tanah', 6);

        $payload = SPPR::first()->only((new SPPR)->getFillable());
        $payload['tanggal_sppr'] = '2026-10-02';
        $payload['sptb_data'] = ['panjang_tanah' => 99, 'lebar_tanah' => 99, 'nomor' => 'TEST'];
        $this->postJson('/test-sppr', $payload)->assertOk();
        $record = SPPR::orderByDesc('id')->first();
        $this->assertSame(['panjang_tanah' => 12.5, 'lebar_tanah' => 6, 'nomor' => 'TEST'], $record->sptb_data);

        $unit->update(['panjang_kanan' => null, 'lebar_depan' => '']);
        $this->getJson('/test-sppr/'.$record->id.'/edit')->assertOk()
            ->assertJsonPath('data.sptb_data.panjang_tanah', 13)
            ->assertJsonPath('data.sptb_data.lebar_tanah', 7);
        $this->putJson('/test-sppr/'.$record->id, $payload)->assertOk();
        $this->assertSame(13, $record->fresh()->sptb_data['panjang_tanah']);
        $this->assertSame(7, $record->fresh()->sptb_data['lebar_tanah']);

        Customer::first()->update(['id_kavling' => null]);
        $this->getJson('/test-sppr-customer/'.Customer::first()->id)->assertOk()
            ->assertJsonPath('data.panjang_tanah', null)
            ->assertJsonPath('data.lebar_tanah', null);
    }

    public function test_list_shows_unit_code_contacts_and_marketing_with_customer_fallback(): void
    {
        $response = $this->getJson('/test-sppr', ['X-Requested-With' => 'XMLHttpRequest']);
        $response->assertOk()->assertJsonPath('data.0.customer_lokasi', 'WG-A-007')
            ->assertJsonPath('data.0.alamat', 'Jl. Uji &amp; Contoh')
            ->assertJsonPath('data.0.no_telp', '081234567890')
            ->assertJsonPath('data.0.kontak', 'Jl. Uji &amp; Contoh<br><span class="text-muted">Telp: 081234567890</span>')
            ->assertJsonPath('data.0.luas_tanah', 90.5)
            ->assertJsonPath('data.0.luas_bangunan', 40)
            ->assertJsonPath('data.0.nama_marketing', 'Marketing Customer');

        $marketing = MarketingOffline::create(['nama_marketing' => 'Marketing SPPR']);
        SPPR::first()->update(['id_marketing' => $marketing->id]);
        $this->getJson('/test-sppr', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.nama_marketing', 'Marketing SPPR');

        SPPR::first()->update(['id_customer' => null, 'id_marketing' => null]);
        $this->getJson('/test-sppr', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.customer_lokasi', '-')
            ->assertJsonPath('data.0.nama_marketing', '-')
            ->assertJsonPath('data.0.luas_tanah', 72)
            ->assertJsonPath('data.0.luas_bangunan', 36);
    }

    public function test_edit_and_list_follow_changes_to_the_linked_kavling(): void
    {
        $id = SPPR::first()->id;
        $this->getJson('/test-sppr/'.$id.'/edit')->assertOk()
            ->assertJsonPath('data.luas_tanah', 90.5)
            ->assertJsonPath('data.luas_bangunan', 40);

        KavlingPeta::first()->update(['luas_tanah' => 105.75, 'luas_bangunan' => 50]);
        $this->getJson('/test-sppr/'.$id.'/edit')->assertOk()
            ->assertJsonPath('data.luas_tanah', 105.75)
            ->assertJsonPath('data.luas_bangunan', 50);
        $this->getJson('/test-sppr', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()
            ->assertJsonPath('data.0.luas_tanah', 105.75)
            ->assertJsonPath('data.0.luas_bangunan', 50);
    }

    public function test_create_and_edit_preserve_money_and_selected_date(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-02 10:00:00', 'Asia/Jakarta'));
        $payload = SPPR::first()->only((new SPPR)->getFillable());
        unset($payload['tanggal_sppr']);
        $payload['harga_jual'] = 250000000;
        $payload['nominal_dp'] = 30000000;
        $payload['tahun_bangunan'] = 2024;
        $payload['asumsi_plafon_kpr'] = 220000000;
        $this->postJson('/test-sppr', $payload)->assertOk();
        $sppr = SPPR::orderByDesc('id')->first();
        $this->assertSame('2026-10-02', $sppr->tanggal_sppr->format('Y-m-d'));
        $this->assertSame(250000000, $sppr->harga_jual);
        $this->assertSame(30000000, $sppr->nominal_dp);
        $this->assertSame(2024, $sppr->tahun_bangunan);
        $this->assertSame(220000000, $sppr->asumsi_plafon_kpr);

        $payload['tanggal_sppr'] = '2026-09-20';
        $payload['tahun_bangunan'] = 2025;
        $this->putJson('/test-sppr/'.$sppr->id, $payload)->assertOk();
        $this->getJson('/test-sppr/'.$sppr->id.'/edit')->assertOk()
            ->assertJsonPath('data.tanggal_sppr', '2026-09-20')
            ->assertJsonPath('data.tahun_bangunan', 2025);
        $payload['tanggal_sppr'] = 'bukan-tanggal';
        $this->postJson('/test-sppr', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors('tanggal_sppr');
    }

    public function test_edit_accepts_active_fields_and_validates_building_year(): void
    {
        $sppr = SPPR::first();
        $payload = $sppr->only((new SPPR)->getFillable());
        $payload['tanggal_sppr'] = '2026-10-02';
        $payload['tahun_bangunan'] = 2026;
        $payload['nominal_dp'] = 25000000;
        $this->putJson('/test-sppr/'.$sppr->id, $payload)->assertOk();
        $sppr->refresh();
        $this->assertSame(25000000, $sppr->nominal_dp);
        $this->assertSame(2026, $sppr->tahun_bangunan);

        foreach (['abc', 999, 10000, 2026.5] as $invalidYear) {
            $payload['tahun_bangunan'] = $invalidYear;
            $this->postJson('/test-sppr', $payload)->assertUnprocessable()
                ->assertJsonValidationErrors('tahun_bangunan');
            $this->putJson('/test-sppr/'.$sppr->id, $payload)->assertUnprocessable()
                ->assertJsonValidationErrors('tahun_bangunan');
        }
        $payload['tahun_bangunan'] = null;
        $this->putJson('/test-sppr/'.$sppr->id, $payload)->assertOk();
        $this->assertNull($sppr->fresh()->tahun_bangunan);
    }

    public function test_sptb_fields_round_trip_and_invalid_values_are_rejected(): void
    {
        $payload = SPPR::first()->only((new SPPR)->getFillable());
        $payload['tanggal_sppr'] = '2026-10-02';
        $payload['sptb_data'] = [
            'nomor' => '001/SPTB/GKA-TJS/WIJAYA/2026', 'tanggal' => '2026-10-03',
            'panjang_tanah' => 12.5, 'lebar_tanah' => 6,
            'ppn' => 0, 'biaya_surat' => 4000000, 'biaya_kpr' => 4000000,
            'booking_fee' => 2000000, 'tanggal_booking' => '2026-10-04',
            'tanggal_dp' => '2026-10-05', 'sisa_pembayaran' => 190000000,
            'tanggal_angsuran_1' => '2026-11-05', 'nominal_angsuran_1' => 90000000,
            'tanggal_angsuran_2' => '2026-12-05', 'nominal_angsuran_2' => 100000000,
        ];
        $this->postJson('/test-sppr', $payload)->assertOk();
        $record = SPPR::orderByDesc('id')->first();
        $this->assertSame($payload['sptb_data'], $record->sptb_data);
        $payload['sptb_data']['nomor'] = '002/SPTB/GKA-TJS/WIJAYA/2026';
        $payload['sptb_data']['booking_fee'] = 0;
        $this->putJson('/test-sppr/'.$record->id, $payload)->assertOk();
        $this->getJson('/test-sppr/'.$record->id.'/edit')->assertOk()
            ->assertJsonPath('data.sptb_data.nomor', $payload['sptb_data']['nomor'])
            ->assertJsonPath('data.sptb_data.booking_fee', 0);

        foreach (['tanggal' => '2026-02-30', 'ppn' => -1, 'nominal_angsuran_1' => 'abc',
            'lebar_tanah' => -5, 'nomor' => str_repeat('x', 151), 'unknown' => 'x'] as $key => $value) {
            $invalid = $payload;
            $invalid['sptb_data'][$key] = $value;
            $error = $key === 'unknown' ? 'sptb_data' : 'sptb_data.'.$key;
            $this->postJson('/test-sppr', $invalid)->assertUnprocessable()->assertJsonValidationErrors($error);
            $this->putJson('/test-sppr/'.$record->id, $invalid)->assertUnprocessable()->assertJsonValidationErrors($error);
        }
        $this->assertSame($payload['sptb_data'], $record->fresh()->sptb_data);
    }

    public function test_sptb_print_fills_values_calculates_total_and_preserves_attachments(): void
    {
        $record = SPPR::first();
        $record->update(['penandatangan' => 'Manajer & Direktur', 'sptb_data' => [
            'nomor' => '017/SPTB/GKA-TJS/WIJAYA/2026', 'tanggal' => '2026-10-03',
            'panjang_tanah' => 12.5, 'lebar_tanah' => 6, 'ppn' => 0,
            'biaya_surat' => 4000000, 'biaya_kpr' => 4000000, 'booking_fee' => 2000000,
            'tanggal_booking' => '2026-10-04', 'tanggal_dp' => '2026-10-05',
            'sisa_pembayaran' => 190000000,
            'tanggal_angsuran_1' => '2026-11-05', 'nominal_angsuran_1' => 90000000,
            'tanggal_angsuran_2' => '2026-12-05', 'nominal_angsuran_2' => 100000000,
        ]]);
        $response = app(SPPRController::class)->cetakSptb($record->id);
        $path = $response->getFile()->getPathname();
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path));
            $xml = $zip->getFromName('word/document.xml');
            $dom = new \DOMDocument;
            $this->assertTrue($dom->loadXML($xml));
            foreach (['SURAT PEMESANAN TANAH DAN/ATAU BANGUNAN', '017/SPTB/GKA-TJS/WIJAYA/2026',
                'Budi & Siti <Uji>', 'Jl. Uji & Contoh', '3 Oktober 2026', '4 Oktober 2026',
                '5 Oktober 2026', '5 November 2026', '5 Desember 2026', '12.5 m x 6 m',
                '90.5 M2', '40 M²', '210.000.000', 'Dua Ratus Sepuluh Juta Rupiah',
                '190.000.000', '90.000.000', '100.000.000', 'Manajer & Direktur', 'Marketing Customer',
                'LAMPIRAN SURAT PEMESANAN', 'KETENTUAN PINDAH BLOK/KAVLING',
                'KETENTUAN PEMBELIAN TIPE RUMAH SUBSIDI'] as $value) {
                $this->assertStringContainsString($value, $dom->textContent);
            }
            $this->assertStringNotContainsString('${', $xml);
            $original = new ZipArchive;
            $this->assertTrue($original->open(storage_path('app/templates/sptb_original.docx')));
            $originalDom = new \DOMDocument;
            $originalDom->loadXML($original->getFromName('word/document.xml'));
            $original->close();
            $originalParagraphs = $originalDom->getElementsByTagName('p');
            $generatedParagraphs = $dom->getElementsByTagName('p');
            $this->assertSame($originalParagraphs->length, $generatedParagraphs->length);
            foreach ($originalParagraphs as $index => $paragraph) {
                if ($paragraph->getElementsByTagName('tab')->length === 0) {
                    continue;
                }
                $expected = $paragraph->cloneNode(true);
                $actual = $generatedParagraphs->item($index)->cloneNode(true);
                foreach ([$expected, $actual] as $node) {
                    foreach ($node->getElementsByTagName('t') as $textNode) {
                        $textNode->nodeValue = '';
                    }
                }
                $this->assertSame($originalDom->saveXML($expected), $dom->saveXML($actual), 'Original tab/run layout at paragraph '.$index);
            }
            $template = new ZipArchive;
            $template->open(public_path('templates/template_sppr/sptb_wijaya_grande.docx'));
            for ($i = 0; $i < $template->numFiles; $i++) {
                $name = $template->getNameIndex($i);
                if (str_starts_with($name, 'word/media/') || $name === 'word/numbering.xml') {
                    $this->assertSame($template->getFromName($name), $zip->getFromName($name));
                }
            }
            $template->close();
            $zip->close();
        } finally {
            unlink($path);
        }

        $record->update(['sptb_data' => null]);
        $legacy = app(SPPRController::class)->cetakSptb($record->id);
        $zip = new ZipArchive;
        $zip->open($legacy->getFile()->getPathname());
        $this->assertStringNotContainsString('${', $zip->getFromName('word/document.xml'));
        $zip->close();
        unlink($legacy->getFile()->getPathname());
    }

    public function test_print_buttons_have_distinct_colors_and_printer_icons(): void
    {
        $response = $this->getJson('/test-sppr', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $action = $response->json('data.0.action');
        $this->assertStringContainsString('btn-dark', $action);
        $this->assertStringContainsString('btn-success', $action);
        $this->assertStringContainsString('>SPPR</a>', $action);
        $this->assertStringContainsString('>SPTB</a>', $action);
        $this->assertSame(2, substr_count($action, 'fa-print'));
        $this->assertStringContainsString(route('sppr.cetak-sptb', SPPR::first()->id), $action);
    }

    public function test_print_fills_the_attached_offer_template_and_preserves_letterhead(): void
    {
        SPPR::first()->update(['tanggal_sppr' => '2026-09-15', 'tahun_bangunan' => 2023]);
        $response = app(SPPRController::class)->cetak(SPPR::first()->id);
        $path = $response->getFile()->getPathname();

        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($path));
            $xml = $zip->getFromName('word/document.xml');
            $dom = new \DOMDocument;
            $this->assertTrue($dom->loadXML($xml));
            $text = $dom->textContent;
            foreach (['Surat Penawaran', 'Budi & Siti <Uji>', '007/WIJAYA/SPR/X/2026',
                'WG-A-007', 'Rp 200.000.000', 'Rp 20.000.000', 'Rp 180.000.000', 'Henny'] as $value) {
                $this->assertStringContainsString($value, $text);
            }
            $this->assertStringContainsString('15 September 2026', $text);
            $this->assertStringContainsString('2023', $text);
            $this->assertStringContainsString('Type: 40', $text);
            $this->assertStringContainsString('Luas Bangunan: 40', $text);
            $this->assertStringContainsString('Luas Tanah: 90.5', $text);
            $this->assertStringNotContainsString('${', $xml);
            $template = new ZipArchive;
            $template->open(public_path('templates/template_sppr/spr_wijaya_grande.docx'));
            $this->assertSame($template->getFromName('word/media/image1.jpeg'), $zip->getFromName('word/media/image1.jpeg'));
            $template->close();
            $zip->close();
        } finally {
            unlink($path);
        }
    }
}
