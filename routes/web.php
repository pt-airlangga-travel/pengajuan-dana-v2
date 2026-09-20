<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('dashboard/login');
});

// Download file pengajuan (proposal) — dipakai kolom File Pengajuan di Draft tabel
// Sama seperti v2 ProposalDraftController@download: baca storage_path('app/proposal/{file}')
Route::get('/proposal-file/{file}', function ($file) {
    $file = basename($file);
    $legacy = storage_path('app/proposal/' . $file);
    if (file_exists($legacy)) {
        return response()->download($legacy);
    }
    $path = 'proposal/' . $file;
    if (Storage::disk('public')->exists($path)) {
        return Storage::disk('public')->download($path);
    }
    if (Storage::disk('local')->exists($path)) {
        return Storage::disk('local')->download($path);
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

// Download bukti transfer — sama seperti v2 BankController@download: baca storage_path('app/bukti_tf/{file}')
Route::get('/bukti-tf/{file}', function ($file) {
    $file = basename($file);
    $legacy = storage_path('app/bukti_tf/' . $file);
    if (file_exists($legacy)) {
        return response()->download($legacy);
    }
    $path = 'bukti_tf/' . $file;
    if (Storage::disk('public')->exists($path)) {
        return Storage::disk('public')->download($path);
    }
    if (Storage::disk('local')->exists($path)) {
        return Storage::disk('local')->download($path);
    }
    abort(404, 'Bukti transfer tidak ditemukan');
})->middleware('auth')->name('bukti_tf.download');
