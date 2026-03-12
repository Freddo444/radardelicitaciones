<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PollProgressController;
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

    // Users
    Route::get('/usuarios', [UsersController::class, 'index'])->name('users.index');
    Route::post('/usuarios', [UsersController::class, 'store'])->name('users.store');
    Route::delete('/usuarios/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
    Route::patch('/usuarios/{user}/password', [UsersController::class, 'updatePassword'])->name('users.password');
});
