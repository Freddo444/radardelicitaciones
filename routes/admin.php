<?php

use App\Http\Controllers\Admin\AdminCompanyController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

// Companies
Route::get('/empresas', [AdminCompanyController::class, 'index'])->name('companies.index');
Route::get('/empresas/{company}', [AdminCompanyController::class, 'show'])->name('companies.show');
Route::post('/empresas/{company}/impersonate', [AdminCompanyController::class, 'impersonate'])->name('companies.impersonate');
Route::post('/impersonate/stop', [AdminCompanyController::class, 'stopImpersonation'])->name('impersonate.stop');

// Subscriptions
Route::get('/suscripciones', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
Route::patch('/suscripciones/{subscription}/status', [AdminSubscriptionController::class, 'updateStatus'])->name('subscriptions.update-status');
Route::post('/suscripciones/{subscription}/grant-trial', [AdminSubscriptionController::class, 'grantTrial'])->name('subscriptions.grant-trial');
Route::post('/suscripciones/create-trial', [AdminSubscriptionController::class, 'createTrial'])->name('subscriptions.create-trial');

// Payments
Route::get('/pagos', [AdminPaymentController::class, 'index'])->name('payments.index');
Route::patch('/pagos/{payment}/confirm', [AdminPaymentController::class, 'confirm'])->name('payments.confirm');
