<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Billing\AzulController;
use App\Http\Controllers\Billing\BankTransferController;
use App\Http\Controllers\Billing\PayPalController;
use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CompanySetupController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\CompanyUsersController;
use App\Http\Controllers\ConvocatoriasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\DocumentosGeneradosController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\EquiposController;
use App\Http\Controllers\FinancieroController;
use App\Http\Controllers\FormulariosController;
use App\Http\Controllers\InteligenciaController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LogsController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfertasController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PollProgressController;
use App\Http\Controllers\PrellenadoController;
use App\Http\Controllers\ProyectosController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RubrosController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TableroController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── Marketing (public) ───────────────────────────────────────────────
Route::get('/', [MarketingController::class, 'landing'])->name('landing');
Route::get('/precios', [MarketingController::class, 'pricing'])->name('pricing');
Route::get('/terminos', [MarketingController::class, 'terms'])->name('terms');
Route::get('/privacidad', [MarketingController::class, 'privacy'])->name('privacy');
Route::post('/contacto', [SupportController::class, 'contact'])->name('contact.store')->middleware('throttle:5,1');
Route::get('/sitemap.xml', [MarketingController::class, 'sitemap'])->name('sitemap');

// ── Auth (guest only) ────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::get('/registro', [RegisterController::class, 'show'])->name('register.show');
    Route::get('/registro/prueba-gratis', [RegisterController::class, 'showTrialRegister'])->name('register.trial');
    Route::post('/registro/prueba-gratis', [RegisterController::class, 'storeTrial'])->name('register.trial.store');
    Route::post('/registro/crear-orden', [RegisterController::class, 'createOrder'])->name('register.create-order');
    Route::get('/registro/paypal-return', [RegisterController::class, 'paypalReturn'])->name('register.paypal-return');
    Route::get('/registro/completar', [RegisterController::class, 'showComplete'])->name('register.complete');
    Route::post('/registro/completar', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Email verification ────────���─────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/email/verificar', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verificar/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard')->with('success', 'Correo verificado.');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/reenviar', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Enlace de verificación reenviado.');
    })->middleware('throttle:6,1')->name('verification.send');
});

// ── Invitations (no auth required — works for guests and logged-in) ──
Route::get('/invitacion/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/invitacion/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');

// ── PayPal webhook (no auth — verified by signature) ─────────────────
Route::post('/paypal/webhook', [PayPalController::class, 'webhook'])->name('paypal.webhook');

// ── Billing & company setup (auth, no tenant required) ───────────────
Route::middleware('auth')->group(function () {
    // Billing
    Route::get('/facturacion', [SubscriptionController::class, 'index'])->name('billing.index');
    Route::delete('/facturacion/cancelar', [SubscriptionController::class, 'cancel'])->name('billing.cancel');
    Route::get('/paypal/return', [PayPalController::class, 'return'])->name('paypal.return');
    Route::get('/paypal/cancel', [PayPalController::class, 'cancel'])->name('paypal.cancel');
    Route::post('/azul/pagar', [AzulController::class, 'createPayment'])->name('azul.create-payment');
    Route::get('/azul/callback', [AzulController::class, 'handleCallback'])->name('azul.callback');
    Route::post('/azul/webhook', [AzulController::class, 'handleWebhook'])->name('azul.webhook');
    Route::get('/transferencia', [BankTransferController::class, 'show'])->name('billing.bank-transfer');
    Route::post('/transferencia', [BankTransferController::class, 'uploadReceipt'])->name('billing.bank-transfer.upload');

    // Company setup wizard (post-payment)
    Route::get('/configurar-empresa', [CompanySetupController::class, 'show'])->name('company-setup.show');
    Route::post('/configurar-empresa', [CompanySetupController::class, 'store'])->name('company-setup.store');
    Route::post('/configurar-empresa/lookup-rpe', [CompanySetupController::class, 'lookupRpe'])->name('company-setup.lookup-rpe');

    // Company switcher
    Route::get('/empresas', [CompanySwitchController::class, 'index'])->name('companies.index');
    Route::post('/empresas/switch/{company}', [CompanySwitchController::class, 'switch'])->name('companies.switch');
    Route::get('/empresas/nueva', [CompanySwitchController::class, 'create'])->name('companies.create');
    Route::post('/empresas', [CompanySwitchController::class, 'store'])->name('companies.store');
});

// ── All tenant-scoped routes (auth + tenant + active subscription) ───
Route::middleware(['auth', 'verified', 'tenant', 'subscription.active'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Convocatorias
    Route::get('/convocatorias', [ConvocatoriasController::class, 'index'])->name('convocatorias.index');
    Route::get('/convocatorias/{bid}/detail', [ConvocatoriasController::class, 'detail'])->name('convocatorias.detail');
    Route::get('/convocatorias/{bid}/tab', [ConvocatoriasController::class, 'tabData'])->name('convocatorias.tab');
    Route::patch('/convocatorias/{bid}/bookmark', [ConvocatoriasController::class, 'bookmark'])->name('convocatorias.bookmark');
    Route::patch('/convocatorias/{bid}/watch', [ConvocatoriasController::class, 'watch'])->name('convocatorias.watch');
    Route::get('/convocatorias/{bid}/download-doc', [ConvocatoriasController::class, 'downloadDocument'])->name('convocatorias.download-doc');

    // Calendar .ics export
    Route::get('/calendar/bid/{bid}.ics', [CalendarController::class, 'bidIcs'])->name('calendar.bid');
    Route::get('/calendar/offer/{offer}.ics', [CalendarController::class, 'offerIcs'])->name('calendar.offer');

    // Prellenado workspace
    Route::get('/convocatorias/{bid}/prellenar', [PrellenadoController::class, 'show'])->name('prellenado.show');
    Route::post('/convocatorias/{bid}/prellenar', [PrellenadoController::class, 'generate'])->name('prellenado.generate');

    // Documentos Generados
    Route::get('/documentos-generados', [DocumentosGeneradosController::class, 'index'])->name('documentos-generados.index');
    Route::get('/documentos-generados/{package}', [DocumentosGeneradosController::class, 'show'])->name('documentos-generados.show');
    Route::get('/documentos-generados/{package}/zip', [DocumentosGeneradosController::class, 'downloadZip'])->name('documentos-generados.zip');
    Route::get('/documentos-generados/file/{file}', [DocumentosGeneradosController::class, 'downloadFile'])->name('documentos-generados.file');

    // Tablero (Kanban)
    Route::get('/tablero', [TableroController::class, 'index'])->name('tablero.index');
    Route::get('/tablero/cards', [TableroController::class, 'cards'])->name('tablero.cards');
    Route::get('/tablero/calendar', [TableroController::class, 'calendar'])->name('tablero.calendar');
    Route::patch('/tablero/{offer}/move', [TableroController::class, 'move'])->name('tablero.move');
    Route::post('/tablero/add-bid', [TableroController::class, 'addBid'])->name('tablero.add-bid');

    // Inteligencia
    Route::get('/inteligencia/adjudicados', [InteligenciaController::class, 'adjudicados'])->name('inteligencia.adjudicados');
    Route::get('/inteligencia/pacc', [InteligenciaController::class, 'pacc'])->name('inteligencia.pacc');
    Route::get('/inteligencia/contratos', [InteligenciaController::class, 'contratos'])->name('inteligencia.contratos');
    Route::get('/inteligencia/proveedores', [InteligenciaController::class, 'proveedores'])->name('inteligencia.proveedores');
    Route::get('/inteligencia/instituciones', [InteligenciaController::class, 'instituciones'])->name('inteligencia.instituciones');

    // Notifications (AJAX)
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::patch('/notifications/{inAppNotification}/read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');

    // Rubros
    Route::get('/rubros', [RubrosController::class, 'index'])->name('rubros.index');
    Route::post('/rubros', [RubrosController::class, 'store'])->name('rubros.store');
    Route::delete('/rubros/{rubro}', [RubrosController::class, 'destroy'])->name('rubros.destroy');
    Route::patch('/rubros/{rubro}/toggle', [RubrosController::class, 'toggle'])->name('rubros.toggle');
    Route::get('/rubros/search', [RubrosController::class, 'search'])->name('rubros.search');

    // Settings (per-company)
    Route::get('/configuracion', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/configuracion', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/configuracion/import-catalog', [SettingsController::class, 'importCatalog'])->name('settings.import-catalog');
    Route::post('/configuracion/test-connection', [SettingsController::class, 'testConnection'])->name('settings.test-connection');
    Route::post('/configuracion/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
    Route::post('/configuracion/test-telegram', [SettingsController::class, 'testTelegram'])->name('settings.test-telegram');

    // Manual poll + sondear
    Route::post('/sondeo/manual', [PollController::class, 'manual'])->name('poll.manual');
    Route::post('/sondeo/sondear', [PollController::class, 'sondear'])->name('poll.sondear');
    Route::get('/sondeo/progreso', [PollProgressController::class, 'show'])->name('poll.progress');
    Route::get('/sondeo/status', [PollProgressController::class, 'status'])->name('poll.status');

    // Logs
    Route::get('/registros', [LogsController::class, 'index'])->name('logs.index');

    // Empresa (M1)
    Route::get('/empresa', [EmpresaController::class, 'index'])->name('empresa.index');
    Route::post('/empresa', [EmpresaController::class, 'update'])->name('empresa.update');
    Route::post('/empresa/imagen', [EmpresaController::class, 'uploadImage'])->name('empresa.uploadImage');
    Route::delete('/empresa/imagen', [EmpresaController::class, 'deleteImage'])->name('empresa.deleteImage');

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
    Route::get('/ofertas/{oferta}/api-docs', [OfertasController::class, 'apiDocuments'])->name('ofertas.api-docs');
    Route::post('/ofertas/{oferta}/parse-from-api', [OfertasController::class, 'parseFromApi'])->name('ofertas.parse-from-api');
    Route::get('/ofertas/{oferta}/parse-status', [OfertasController::class, 'parseStatus'])->name('ofertas.parse-status');

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
    Route::get('/ofertas/{oferta}/generated/{file}/view', [OfertasController::class, 'viewGeneratedFile'])->name('ofertas.generated.view');
    Route::delete('/ofertas/{oferta}/generated/{file}', [OfertasController::class, 'deleteGeneratedFile'])->name('ofertas.generated.destroy');

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

    // Company users (per-company, owner can invite/remove)
    Route::get('/usuarios', [CompanyUsersController::class, 'index'])->name('company-users.index');
    Route::post('/usuarios/invitar', [CompanyUsersController::class, 'invite'])->name('company-users.invite');
    Route::delete('/usuarios/{user}', [CompanyUsersController::class, 'removeUser'])->name('company-users.remove');
    Route::delete('/invitaciones/{invitation}', [CompanyUsersController::class, 'cancelInvitation'])->name('company-users.cancel-invitation');

    // Support
    Route::post('/soporte', [SupportController::class, 'store'])->name('support.store');
});
