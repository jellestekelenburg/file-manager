<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\UserStorage;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('api/storage', UserStorage::class)->middleware('throttle:20,1')->name('api.storage');

Route::controller(FileController::class)
    ->middleware(['auth', 'verified'])->group(function () {
        Route::get('/my-files/{folder?}', 'myFiles')
            ->where('folder', '(.*)')
            ->name('myFiles');
        Route::post('/folder/create', 'createFolder')->name('folder.create');
        // file upload
        Route::post('/file', 'store')->name('file.store');
        // folder upload
        Route::delete('file', 'destroy')->name('file.delete');
        Route::get('file/download', 'download')->name('file.download');
    });

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
