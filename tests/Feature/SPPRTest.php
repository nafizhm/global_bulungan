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
