<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('dashboard/login');
});

// Download file pengajuan (proposal) — dipakai kolom File Pengajuan di Draft tabel
Route::get('/proposal-file/{file}', function ($file) {
    $file = basename($file);

    $candidates = [
        storage_path('app/proposal/' . $file),
        '/home/u101763413/domains/pengajuandanaagt.my.id/pengajuan_dana_agt_v2/storage/app/proposal/' . $file,
        '/home/u101763413/domains/pengajuandanaagt.my.id/pengajuan_dana_agt/storage/app/proposal/' . $file,
    ];

    foreach ($candidates as $path) {
        if (file_exists($path)) {
            return response()->download($path);
        }
    }

    abort(404, 'File tidak ditemukan');
})->middleware('auth')->name('proposal.file.download');

// Preview validasi formulir (QR diarahkan ke sini)
Route::get('/proposalformvalidation/{id}', function ($id) {
    return \App\Services\ProposalHelper::proposalformvalidation(\Illuminate\Http\Request::create('/proposalformvalidation/' . $id, 'GET', ['id' => $id]));
})->middleware('auth')->name('proposalformvalidation');

// Cetak formulir PDF
Route::get('/print-formulir/{id}', function ($id) {
    return \App\Services\ProposalHelper::printFormulir(\Illuminate\Http\Request::create('/print-formulir/' . $id, 'GET', ['id' => $id]));
})->middleware('auth')->name('print.formulir');

// Download bukti transfer
Route::get('/bukti-tf/{file}', function ($file) {
    $file = basename($file);

    $candidates = [
        storage_path('app/bukti_tf/' . $file),
        '/home/u101763413/domains/pengajuandanaagt.my.id/pengajuan_dana_agt_v2/storage/app/bukti_tf/' . $file,
        '/home/u101763413/domains/pengajuandanaagt.my.id/pengajuan_dana_agt/storage/app/bukti_tf/' . $file,
    ];

    foreach ($candidates as $path) {
        if (file_exists($path)) {
            return response()->file($path);
        }
    }

    abort(404, 'Bukti transfer tidak ditemukan');
})->middleware('auth')->name('bukti_tf.download');
