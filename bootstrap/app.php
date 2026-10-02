<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            if (!$request->routeIs('sppr.store', 'sppr.update')) {
                return null;
            }

            $sqlState = $e->errorInfo[0] ?? '';
            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            $message = match (true) {
                in_array($sqlState, ['42S22', '42S02']) => 'Struktur database SPPR belum lengkap. Minta admin menjalankan migration terbaru, termasuk kolom sptb_data.',
                $driverCode === 1062 => 'Data yang sama sudah tersimpan pada kolom yang harus unik. Periksa data SPPR sebelum menyimpan kembali.',
                in_array($driverCode, [1451, 1452]) => 'Data referensi tidak valid atau sudah dihapus. Pilih ulang customer dan marketing.',
                $sqlState === '22001' || $driverCode === 1406 => 'Ada isian yang melebihi kapasitas kolom database. Persingkat isian teks SPPR.',
                in_array($driverCode, [1048, 1364]) => 'Ada kolom wajib database yang belum terisi. Lengkapi form; jika tetap gagal, minta admin memeriksa struktur tabel SPPR.',
                $sqlState === '40001' || in_array($driverCode, [1205, 1213]) => 'Data sedang diproses oleh transaksi lain. Tunggu sebentar lalu coba kembali.',
                str_starts_with($sqlState, '08') || in_array($driverCode, [2002, 2006, 2013]) => 'Koneksi database bermasalah. Minta admin memeriksa layanan database dan cek daftar SPPR sebelum mencoba kembali.',
                default => 'Database gagal menyimpan SPPR. Minta admin memeriksa log aplikasi pada waktu penyimpanan ini.',
            };

            return response()->json(['save_error' => true, 'message' => $message], 500);
        });
    })->create();
