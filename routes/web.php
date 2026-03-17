<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConvocatoriasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\EquiposController;
use App\Http\Controllers\FinancieroController;
use App\Http\Controllers\FormulariosController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\OfertasController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PollProgressController;
use App\Http\Controllers\ProyectosController;
use App\Http\Controllers\RubrosController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

// Auth (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// All app routes — require login
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Convocatorias
    Route::get('/convocatorias', [ConvocatoriasController::class, 'index'])->name('convocatorias.index');

    // Rubros
    Route::get('/rubros', [RubrosController::class, 'index'])->name('rubros.index');
    Route::post('/rubros', [RubrosController::class, 'store'])->name('rubros.store');
    Route::delete('/rubros/{rubro}', [RubrosController::class, 'destroy'])->name('rubros.destroy');
    Route::patch('/rubros/{rubro}/toggle', [RubrosController::class, 'toggle'])->name('rubros.toggle');
    Route::get('/rubros/search', [RubrosController::class, 'search'])->name('rubros.search');

    // Settings
    Route::get('/configuracion', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/configuracion', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/configuracion/import-catalog', [SettingsController::class, 'importCatalog'])->name('settings.import-catalog');
    Route::post('/configuracion/test-connection', [SettingsController::class, 'testConnection'])->name('settings.test-connection');
    Route::post('/configuracion/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
    Route::post('/configuracion/test-telegram', [SettingsController::class, 'testTelegram'])->name('settings.test-telegram');

    // Manual poll
    Route::post('/sondeo/manual', [PollController::class, 'manual'])->name('poll.manual');
    Route::get('/sondeo/progreso', [PollProgressController::class, 'show'])->name('poll.progress');
    Route::get('/sondeo/status', [PollProgressController::class, 'status'])->name('poll.status');

    // Logs
    Route::get('/registros', [LogsController::class, 'index'])->name('logs.index');

    // Empresa (M1)
    Route::get('/empresa', [EmpresaController::class, 'index'])->name('empresa.index');
    Route::post('/empresa', [EmpresaController::class, 'update'])->name('empresa.update');

    // Documentos / Vault (M2)
    Route::get('/documentos', [DocumentosController::class, 'index'])->name('documentos.index');
    Route::post('/documentos', [DocumentosController::class, 'store'])->name('documentos.store');
    Route::get('/documentos/{documento}/download', [DocumentosController::class, 'download'])->name('documentos.download');
    Route::post('/documentos/{documento}/replace', [DocumentosController::class, 'replace'])->name('documentos.replace');
    Route::get('/documentos/{documento}/versions', [DocumentosController::class, 'versions'])->name('documentos.versions');

    // Personal / HR Vault (M3)
    Route::get('/personal', [PersonalController::class, 'index'])->name('personal.index');
    Route::post('/personal', [PersonalController::class, 'store'])->name('personal.store');
    Route::get('/personal/{personal}', [PersonalController::class, 'show'])->name('personal.show');
    Route::post('/personal/{personal}', [PersonalController::class, 'update'])->name('personal.update');
    Route::patch('/personal/{personal}/toggle', [PersonalController::class, 'toggle'])->name('personal.toggle');
    Route::post('/personal/{personal}/experience', [PersonalController::class, 'storeExperience'])->name('personal.experience.store');
    Route::post('/personal/{personal}/experience/{experience}', [PersonalController::class, 'updateExperience'])->name('personal.experience.update');
    Route::delete('/personal/{personal}/experience/{experience}', [PersonalController::class, 'destroyExperience'])->name('personal.experience.destroy');

    // Proyectos / Project Portfolio (M4)
    Route::get('/proyectos', [ProyectosController::class, 'index'])->name('proyectos.index');
    Route::post('/proyectos', [ProyectosController::class, 'store'])->name('proyectos.store');
    Route::get('/proyectos/{proyecto}', [ProyectosController::class, 'show'])->name('proyectos.show');
    Route::post('/proyectos/{proyecto}', [ProyectosController::class, 'update'])->name('proyectos.update');
    Route::delete('/proyectos/{proyecto}', [ProyectosController::class, 'destroy'])->name('proyectos.destroy');
    Route::post('/proyectos/{proyecto}/documentos', [ProyectosController::class, 'storeDocument'])->name('proyectos.documents.store');
    Route::get('/proyectos/{proyecto}/documentos/{documento}/download', [ProyectosController::class, 'downloadDocument'])->name('proyectos.documents.download');
    Route::delete('/proyectos/{proyecto}/documentos/{documento}', [ProyectosController::class, 'destroyDocument'])->name('proyectos.documents.destroy');

    // Ofertas / Bid Preparation (M8)
    Route::get('/ofertas', [OfertasController::class, 'index'])->name('ofertas.index');
    Route::get('/ofertas/create', [OfertasController::class, 'create'])->name('ofertas.create');
    Route::post('/ofertas', [OfertasController::class, 'store'])->name('ofertas.store');
    Route::get('/ofertas/{oferta}', [OfertasController::class, 'show'])->name('ofertas.show');
    Route::delete('/ofertas/{oferta}', [OfertasController::class, 'destroy'])->name('ofertas.destroy');

    // State transitions
    Route::patch('/ofertas/{oferta}/mark-listo', [OfertasController::class, 'markListo'])->name('ofertas.markListo');
    Route::patch('/ofertas/{oferta}/mark-enviado', [OfertasController::class, 'markEnviado'])->name('ofertas.markEnviado');
    Route::patch('/ofertas/{oferta}/reabrir', [OfertasController::class, 'reabrir'])->name('ofertas.reabrir');

    // Parse
    Route::post('/ofertas/{oferta}/parse', [OfertasController::class, 'triggerParse'])->name('ofertas.parse');
    Route::post('/ofertas/{oferta}/parse/{attempt}/verify', [OfertasController::class, 'verifyParse'])->name('ofertas.parse.verify');
    Route::post('/ofertas/{oferta}/pliego', [OfertasController::class, 'uploadPliego'])->name('ofertas.pliego.upload');

    // Requirements
    Route::post('/ofertas/{oferta}/requirements', [OfertasController::class, 'storeRequirement'])->name('ofertas.requirements.store');
    Route::patch('/ofertas/{oferta}/requirements/{req}', [OfertasController::class, 'updateRequirement'])->name('ofertas.requirements.update');
    Route::delete('/ofertas/{oferta}/requirements/{req}', [OfertasController::class, 'destroyRequirement'])->name('ofertas.requirements.destroy');
    Route::post('/ofertas/{oferta}/requirements/{req}/items', [OfertasController::class, 'storeRequirementItem'])->name('ofertas.requirements.items.store');
    Route::delete('/ofertas/{oferta}/requirements/{req}/items/{item}', [OfertasController::class, 'destroyRequirementItem'])->name('ofertas.requirements.items.destroy');

    // Composition (personnel/projects/equipment/financials)
    Route::post('/ofertas/{oferta}/personnel', [OfertasController::class, 'addPersonnel'])->name('ofertas.personnel.add');
    Route::delete('/ofertas/{oferta}/personnel/{op}', [OfertasController::class, 'removePersonnel'])->name('ofertas.personnel.remove');
    Route::post('/ofertas/{oferta}/projects', [OfertasController::class, 'addProject'])->name('ofertas.projects.add');
    Route::delete('/ofertas/{oferta}/projects/{op}', [OfertasController::class, 'removeProject'])->name('ofertas.projects.remove');
    Route::post('/ofertas/{oferta}/equipment', [OfertasController::class, 'addEquipment'])->name('ofertas.equipment.add');
    Route::delete('/ofertas/{oferta}/equipment/{oe}', [OfertasController::class, 'removeEquipment'])->name('ofertas.equipment.remove');
    Route::post('/ofertas/{oferta}/financials', [OfertasController::class, 'addFinancial'])->name('ofertas.financials.add');
    Route::delete('/ofertas/{oferta}/financials/{of}', [OfertasController::class, 'removeFinancial'])->name('ofertas.financials.remove');

    // Events / timeline
    Route::post('/ofertas/{oferta}/events', [OfertasController::class, 'storeEvent'])->name('ofertas.events.store');
    Route::patch('/ofertas/{oferta}/events/{event}', [OfertasController::class, 'updateEvent'])->name('ofertas.events.update');
    Route::delete('/ofertas/{oferta}/events/{event}', [OfertasController::class, 'destroyEvent'])->name('ofertas.events.destroy');

    // Assembly
    Route::post('/ofertas/{oferta}/assemble', [OfertasController::class, 'assemble'])->name('ofertas.assemble');
    Route::get('/ofertas/{oferta}/snapshots/{snapshot}/download', [OfertasController::class, 'downloadSnapshot'])->name('ofertas.snapshots.download');

    // Form generation within offer context
    Route::post('/ofertas/{oferta}/generate-form', [OfertasController::class, 'generateForm'])->name('ofertas.generate.form');
    Route::get('/ofertas/{oferta}/generated/{file}/download', [OfertasController::class, 'downloadGeneratedFile'])->name('ofertas.generated.download');

    // Formularios / Form Generator (M7)
    Route::get('/formularios', [FormulariosController::class, 'index'])->name('formularios.index');
    Route::post('/formularios/generar', [FormulariosController::class, 'generate'])->name('formularios.generate');
    Route::get('/formularios/{formulario}/download', [FormulariosController::class, 'download'])->name('formularios.download');

    // Financiero / Financial Vault (M6)
    Route::get('/financiero', [FinancieroController::class, 'index'])->name('financiero.index');
    Route::get('/financiero/nuevo', [FinancieroController::class, 'create'])->name('financiero.create');
    Route::post('/financiero', [FinancieroController::class, 'store'])->name('financiero.store');
    Route::get('/financiero/{financiero}', [FinancieroController::class, 'show'])->name('financiero.show');
    Route::post('/financiero/{financiero}', [FinancieroController::class, 'update'])->name('financiero.update');
    Route::post('/financiero/{financiero}/documentos', [FinancieroController::class, 'uploadDocument'])->name('financiero.documents.upload');
    Route::get('/financiero/{financiero}/documentos/{tipo}/download', [FinancieroController::class, 'downloadDocument'])->name('financiero.documents.download');
    Route::delete('/financiero/{financiero}', [FinancieroController::class, 'destroy'])->name('financiero.destroy');

    // Equipos / Equipment Inventory (M5)
    Route::get('/equipos', [EquiposController::class, 'index'])->name('equipos.index');
    Route::post('/equipos', [EquiposController::class, 'store'])->name('equipos.store');
    Route::post('/equipos/{equipo}', [EquiposController::class, 'update'])->name('equipos.update');
    Route::patch('/equipos/{equipo}/toggle', [EquiposController::class, 'toggle'])->name('equipos.toggle');
    Route::delete('/equipos/{equipo}', [EquiposController::class, 'destroy'])->name('equipos.destroy');

    // Users
    Route::get('/usuarios', [UsersController::class, 'index'])->name('users.index');
    Route::post('/usuarios', [UsersController::class, 'store'])->name('users.store');
    Route::delete('/usuarios/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
    Route::patch('/usuarios/{user}/password', [UsersController::class, 'updatePassword'])->name('users.password');
});
