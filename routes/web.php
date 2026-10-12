<?php

use App\Http\Controllers\DocenteController;
use App\Http\Controllers\ExcepcionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiesgoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'rol:docente'])
    ->prefix('docente')
    ->name('docente.')
    ->group(function () {
        Route::get('/clases', [DocenteController::class, 'clases'])->name('clases');
        Route::get('/clases/crear', [DocenteController::class, 'crear'])->name('crear');
        Route::post('/clases', [DocenteController::class, 'guardar'])->name('guardar');
        Route::get('/clases/{clase}/qr', [DocenteController::class, 'qr'])->name('qr');
        Route::post('/clases/{clase}/cerrar', [DocenteController::class, 'cerrar'])->name('cerrar');
    });

Route::middleware(['auth', 'rol:admin,docente'])->group(function () {
    Route::get('/riesgo', [RiesgoController::class, 'index'])->name('riesgo.index');
    Route::post('/riesgo/entrenar', [RiesgoController::class, 'entrenar'])->name('riesgo.entrenar');

    Route::get('/excepciones', [ExcepcionController::class, 'index'])->name('excepciones.index');
    Route::post('/excepciones', [ExcepcionController::class, 'store'])->name('excepciones.store');
    Route::delete('/excepciones/{excepcion}', [ExcepcionController::class, 'destroy'])->name('excepciones.destroy');

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';