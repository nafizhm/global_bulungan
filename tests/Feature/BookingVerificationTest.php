<?php

namespace Tests\Feature;

use App\Http\Controllers\GenerateNumberController;
use App\Http\Controllers\PengajuanHoldController;
use App\Models\{Bank, Customer, KavlingPeta, LokasiKavling, MarketingOffline, MetodeBayar, Pemasukan, PengajuanHold, PersyaratanLegal, Piutang, UploudFile};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, File, Route, Schema};
use Tests\TestCase;

class BookingVerificationTest extends TestCase
{
    private string $testPublicPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPublicPath = storage_path('framework/testing/booking-' . uniqid());
        $this->app->usePublicPath($this->testPublicPath);
        foreach ([new PengajuanHold, new KavlingPeta, new LokasiKavling, new MarketingOffline, new Bank, new MetodeBayar, new Customer, new PersyaratanLegal, new Piutang, new Pemasukan, new UploudFile] as $model) {
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
        DB::table('lokasi_kavling')->insert(['id' => 1, 'nama_kavling' => 'Lokasi Uji', 'nama_singkat' => 'UJI']);
        DB::table('kavling_peta')->insert([
            ['id' => 1, 'id_lokasi' => 1, 'kode_kavling' => 'A1', 'status' => 1, 'rincian_biaya' => '[]'],
            ['id' => 2, 'id_lokasi' => 1, 'kode_kavling' => 'A2', 'status' => 0, 'rincian_biaya' => '[{"nama":"Harga Rumah","nilai":200000000}]'],
        ]);
        PengajuanHold::create(array_merge($this->payload(), ['id_kavling' => 1, 'tgl_booking' => '2026-09-28', 'nama_lengkap' => 'Nama Lama', 'foto_npwp' => 'lama.png']));
        Route::post('/test-booking/{id}', [PengajuanHoldController::class, 'simpanVerifikasi']);
        Route::post('/test-booking/{id}/delete-file', [PengajuanHoldController::class, 'deleteFile']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testPublicPath);
        parent::tearDown();
    }

    private function payload(array $changes = []): array
    {
        return array_merge([
            'nama_lengkap' => 'Nama Baru', 'nik' => '1234567890123456',
            'tempat_lahir' => 'Bulungan', 'tgl_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Laki-laki', 'no_telp' => '081234567890',
            'alamat_ktp' => 'Alamat KTP Baru', 'alamat_domisili' => 'Alamat Domisili Baru',
            'id_lokasi' => 1, 'id_kavling' => 2, 'id_marketing' => 0,
            'jenis_perumahan' => 'Subsidi', 'jenis_pembelian' => 'KPR',
            'booking_fee' => '1.000.000', 'total_harga' => '200.000.000', 'stt_reg' => 1,
        ], $changes);
    }

    public function test_pending_saves_edits_replaces_files_and_removes_selected_attachment(): void
    {
        $this->postJson('/test-booking/1', $this->payload([
            'foto_ktp' => UploadedFile::fake()->image('ktp.png'),
            'hapus_lampiran' => ['foto_npwp'],
        ]))->assertOk()->assertJson(['status' => 'success']);

        $booking = PengajuanHold::findOrFail(1);
        $this->assertSame('Nama Baru', $booking->nama_lengkap);
        $this->assertEquals(1000000, $booking->booking_fee);
        $this->assertNull($booking->foto_npwp);
        $this->assertFileExists(public_path('assets/booking/' . $booking->foto_ktp));
        $this->assertEquals(0, KavlingPeta::find(1)->status);
        $this->assertEquals(1, KavlingPeta::find(2)->status);
        $this->assertSame(0, Customer::count());
    }

    public function test_invalid_attachment_does_not_save_edits(): void
    {
        $this->postJson('/test-booking/1', $this->payload([
            'foto_ktp' => UploadedFile::fake()->create('script.txt', 1, 'text/plain'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('foto_ktp');
        $this->assertSame('Nama Lama', PengajuanHold::find(1)->nama_lengkap);
    }

    public function test_attachment_is_deleted_immediately_and_stays_deleted_after_reopening(): void
    {
        File::ensureDirectoryExists(public_path('assets/booking'));
        File::put(public_path('assets/booking/lama.png'), 'test file');
        $this->postJson('/test-booking/1/delete-file', ['field' => 'foto_npwp'])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertFileDoesNotExist(public_path('assets/booking/lama.png'));
        $view = app(PengajuanHoldController::class)->verifikasi(1, request());
        $this->assertNull($view->getData()['data']->foto_npwp);
        $this->assertSame('Nama Lama', $view->getData()['data']->nama_lengkap);
        $this->postJson('/test-booking/1', $this->payload())->assertOk();
        $this->assertNull(PengajuanHold::find(1)->foto_npwp);
    }

    public function test_approved_booking_attachment_cannot_be_deleted(): void
    {
        PengajuanHold::whereKey(1)->update(['stt_reg' => 2]);
        $this->postJson('/test-booking/1/delete-file', ['field' => 'foto_npwp'])->assertForbidden();
        $this->assertSame('lama.png', PengajuanHold::find(1)->foto_npwp);
    }

    public function test_saving_without_upload_keeps_existing_attachment(): void
    {
        $this->postJson('/test-booking/1', $this->payload())->assertOk();
        $this->assertSame('lama.png', PengajuanHold::find(1)->foto_npwp);
    }

    public function test_pending_payment_details_and_personal_data_survive_reopening(): void
    {
        DB::table('bank')->insert(['id' => 1]);
        DB::table('metode_bayar')->insert(['id' => 1]);
        $details = [
            'id_bank' => 1, 'id_metode_bayar' => 1,
            'jenis_pembelian' => 'Cash Bertahap', 'termin_x_cash_b' => 36,
            'an_surat_cash' => 'Nama Surat', 'email' => 'uji@example.com',
            'npwp' => '12345', 'pekerjaan' => 'Pegawai', 'no_bpjs_kes' => '67890',
            'status_pernikahan' => 'Menikah', 'nama_p' => 'Pasangan Uji', 'nik_p' => '98765',
            'nama_saudara' => 'Saudara Uji', 'no_telp_saudara' => '0811111111',
        ];
        $this->postJson('/test-booking/1', $this->payload(array_merge($details, [
            'foto_ktp' => UploadedFile::fake()->image('ktp.png'),
        ])))->assertOk();

        $view = app(PengajuanHoldController::class)->verifikasi(1, request());
        $saved = $view->getData()['data'];
        foreach ($details as $field => $value) {
            $this->assertEquals($value, $saved->$field, $field);
        }
        $html = view('admin.pengajuan_hold.form-verifikasi', $view->getData())->render();
        $this->assertStringContainsString('value="Pasangan Uji"', $html);
        $this->assertStringContainsString($saved->foto_ktp, $html);
        $this->assertFileExists(public_path('assets/booking/' . $saved->foto_ktp));
    }

    public function test_form_renders_existing_values_and_all_attachment_inputs(): void
    {
        $html = view('admin.pengajuan_hold.form-verifikasi', [
            'data' => PengajuanHold::find(1),
            'marketing' => collect(),
            'lokasi' => LokasiKavling::all(),
            'kavlingList' => KavlingPeta::all(),
        ])->render();
        $this->assertStringContainsString('value="Nama Lama"', $html);
        foreach (['foto_pemohon', 'foto_ktp', 'foto_npwp', 'foto_kk', 'foto_bpjs', 'foto_ktp_p', 'file_bukti'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html);
        }
    }

    public function test_occupied_kavling_is_rejected_without_saving(): void
    {
        KavlingPeta::whereKey(2)->update(['status' => 2]);
        $this->postJson('/test-booking/1', $this->payload())
            ->assertUnprocessable()->assertJsonValidationErrors('id_kavling');
        $this->assertSame('Nama Lama', PengajuanHold::find(1)->nama_lengkap);
        $this->assertEquals(1, KavlingPeta::find(1)->status);
    }

    public function test_rejection_saves_edits_without_payment_details(): void
    {
        $this->postJson('/test-booking/1', $this->payload(['stt_reg' => 3, 'jenis_pembelian' => null]))->assertOk();
        $this->assertEquals(3, PengajuanHold::find(1)->stt_reg);
        $this->assertEquals(0, KavlingPeta::find(2)->status);
    }

    public function test_approval_uses_edited_data_and_new_attachment_and_cannot_repeat(): void
    {
        DB::table('bank')->insert(['id' => 1]);
        DB::table('metode_bayar')->insert(['id' => 1]);
        $this->mock(GenerateNumberController::class, function ($mock) {
            $mock->shouldReceive('generateNomorDokumen')->once()->andReturn('UJI-001');
        });
        $this->postJson('/test-booking/1', $this->payload([
            'stt_reg' => 2, 'id_bank' => 1, 'id_metode_bayar' => 1,
            'file_bukti' => UploadedFile::fake()->image('bukti.png'),
        ]))->assertOk();

        $customer = Customer::firstOrFail();
        $this->assertSame('Nama Baru', $customer->nama_lengkap);
        $this->assertEquals(2, $customer->id_kavling);
        $this->assertEquals(1000000, Pemasukan::firstOrFail()->nominal);
        $this->assertEquals(200000000, Piutang::firstOrFail()->nominal);
        $attachment = UploudFile::firstOrFail()->lampiran;
        $this->assertFileExists(public_path('assets/customer/' . $attachment));
        $this->assertFileExists(public_path('assets/keuangan/pemasukan/' . $attachment));
        $this->postJson('/test-booking/1', $this->payload(['stt_reg' => 2]))->assertForbidden();
        $this->assertSame(1, Customer::count());
    }
}
