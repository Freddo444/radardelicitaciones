<?php

use App\Http\Controllers\Admin\AdminBillingSettingsController;
use App\Http\Controllers\Admin\AdminCompanyController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminNewsletterController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

Route::get('/ajustes/facturacion', [AdminBillingSettingsController::class, 'edit'])->name('billing-settings.edit');
Route::patch('/ajustes/facturacion', [AdminBillingSettingsController::class, 'update'])->name('billing-settings.update');

// Companies
Route::get('/empresas', [AdminCompanyController::class, 'index'])->name('companies.index');
Route::get('/empresas/{company}', [AdminCompanyController::class, 'show'])->name('companies.show');
Route::post('/empresas/{company}/impersonate', [AdminCompanyController::class, 'impersonate'])->name('companies.impersonate');
Route::post('/impersonate/stop', [AdminCompanyController::class, 'stopImpersonation'])->name('impersonate.stop');

// Tenant users (subscription owners & trial)
Route::get('/usuarios', [AdminUserController::class, 'index'])->name('users.index');
Route::get('/usuarios/{user}', [AdminUserController::class, 'show'])->name('users.show');
Route::post('/usuarios/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('users.impersonate');

// Newsletter (product updates / marketing list)
Route::get('/newsletter', [AdminNewsletterController::class, 'index'])->name('newsletter.index');
Route::get('/newsletter/export', [AdminNewsletterController::class, 'export'])->name('newsletter.export');
Route::patch('/newsletter/{user}', [AdminNewsletterController::class, 'update'])->name('newsletter.update');

// Subscriptions
Route::get('/suscripciones', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
Route::patch('/suscripciones/{subscription}/status', [AdminSubscriptionController::class, 'updateStatus'])->name('subscriptions.update-status');
Route::post('/suscripciones/{subscription}/grant-trial', [AdminSubscriptionController::class, 'grantTrial'])->name('subscriptions.grant-trial');
Route::post('/suscripciones/create-trial', [AdminSubscriptionController::class, 'createTrial'])->name('subscriptions.create-trial');

// Payments
Route::get('/pagos', [AdminPaymentController::class, 'index'])->name('payments.index');
Route::patch('/pagos/{payment}/confirm', [AdminPaymentController::class, 'confirm'])->name('payments.confirm');
Route::get('/pagos/huerfanos', [AdminPaymentController::class, 'orphans'])->name('payments.orphans');
Route::patch('/pagos/huerfanos/{pendingRegistration}/refunded', [AdminPaymentController::class, 'markRefunded'])->name('payments.orphans.refunded');
