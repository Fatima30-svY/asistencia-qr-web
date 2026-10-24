<?php

use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\AsistenteController;
use App\Http\Controllers\AdministracionController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\DocenteController;
use App\Http\Controllers\ExcepcionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiesgoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth', 'throttle:asistente'])->prefix('asistente')->name('asistente.')->group(function () {
    Route::post('/preguntar', [AsistenteController::class, 'responder'])->name('preguntar');
    Route::post('/descripcion', [AsistenteController::class, 'descripcion'])->middleware('rol:admin')->name('descripcion');
});

Route::get('/dashboard', fn () => view('dashboard'))
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'rol:admin'])->prefix('administracion')->name('admin.')->group(function () {
    Route::get('/', [AdministracionController::class, 'index'])->name('index');
    Route::get('/docentes', [AdministracionController::class, 'index'])->defaults('seccion', 'docentes')->name('docentes.index');
    Route::get('/alumnos', [AdministracionController::class, 'index'])->defaults('seccion', 'alumnos')->name('alumnos.index');
    Route::get('/materias', [AdministracionController::class, 'index'])->defaults('seccion', 'materias')->name('materias.index');
    Route::get('/grupos', [AdministracionController::class, 'index'])->defaults('seccion', 'grupos')->name('grupos.index');
    Route::get('/clases', [AdministracionController::class, 'index'])->defaults('seccion', 'clases')->name('clases.index');
    Route::get('/materias/crear', [AdministracionController::class, 'nuevaMateria'])->name('materias.crear');
    Route::get('/materias/{materia}/editar', [AdministracionController::class, 'editarMateria'])->name('materias.editar');
    Route::get('/grupos/{grupo}/editar', [AdministracionController::class, 'editarGrupo'])->name('grupo.editar');
    Route::patch('/grupos/{grupo}/alumnos', [AdministracionController::class, 'alumnosGrupo'])->name('grupo.alumnos');
    Route::patch('/grupos/{grupo}/materias', [AdministracionController::class, 'materiasGrupo'])->name('grupo.materias');
    Route::post('/grupos/{grupo}/materias', [AdministracionController::class, 'crearMateriaGrupo'])->name('grupo.materia.crear');
    Route::get('/grupos/{grupo}/eliminar', [AdministracionController::class, 'confirmarEliminarGrupo'])->name('grupo.confirmar-eliminacion');
    Route::patch('/usuarios/{usuario}', [AdministracionController::class, 'usuario'])->name('usuario');
    Route::post('/catalogos/{tipo}', [AdministracionController::class, 'guardar'])->name('crear');
    Route::put('/catalogos/{tipo}/{id}', [AdministracionController::class, 'guardar'])->name('editar');
    Route::delete('/catalogos/{tipo}/{id}', [AdministracionController::class, 'eliminar'])->name('eliminar');
    Route::put('/clases/{clase}', [AdministracionController::class, 'actualizarClase'])->name('clase.editar');
    Route::delete('/clases/{clase}', [AdministracionController::class, 'eliminarClase'])->name('clase.eliminar');
});

Route::middleware(['auth', 'rol:alumno'])->prefix('alumno')->name('alumno.')->group(function () {
    Route::get('/', [AlumnoController::class, 'index'])->name('index');
    Route::get('/asistencia/{clase}', [AlumnoController::class, 'confirmar'])->name('confirmar');
    Route::post('/asistencia', [AlumnoController::class, 'registrar'])->middleware('throttle:30,1')->name('registrar');
});

Route::middleware(['auth', 'aprobado', 'rol:admin,docente'])
    ->prefix('docente')
    ->name('docente.')
    ->group(function () {
        Route::get('/clases', [DocenteController::class, 'clases'])->name('clases');
        Route::get('/clases/crear', [DocenteController::class, 'crear'])->middleware('rol:admin')->name('crear');
        Route::post('/clases', [DocenteController::class, 'guardar'])->middleware('rol:admin')->name('guardar');
        Route::get('/clases/{clase}/qr', [DocenteController::class, 'qr'])->name('qr');
        Route::post('/clases/{clase}/cerrar', [DocenteController::class, 'cerrar'])->name('cerrar');
    });

Route::middleware(['auth', 'rol:admin'])->group(function () {
    Route::post('/catalogos/materias', [CatalogoController::class, 'guardarMateria'])->name('catalogos.materias.guardar');
    Route::post('/catalogos/grupos', [CatalogoController::class, 'guardarGrupo'])->name('catalogos.grupos.guardar');
});

Route::middleware(['auth', 'aprobado', 'rol:admin,docente'])->group(function () {
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
