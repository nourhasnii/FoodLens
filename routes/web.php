<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BatchController;
use App\Http\Controllers\Admin\CertificationController;
use App\Http\Controllers\Admin\TraceabilityEventController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VerificationRequestController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\QualityCheckController;
use App\Http\Controllers\Admin\OcrCheckController;
use App\Http\Controllers\Admin\TransportConditionController;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\NutriMindController;
use App\Http\Controllers\Admin\VerificationDocumentController;
use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\AccountPendingController;
use App\Http\Controllers\AccountVerificationController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Front\HomeController as FrontHomeController;
use App\Http\Controllers\Front\ProductController as FrontProductController;
use App\Http\Controllers\Front\TraceabilityController as FrontTraceabilityController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FrontHomeController::class, 'index'])->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::get('/account/activate/{token}', [AccountActivationController::class, 'show'])->name('account.activation.show');
Route::post('/account/activate', [AccountActivationController::class, 'store'])->name('account.activation.store');

Route::middleware('auth')->group(function () {
    // NutriAlert Hub — centre de notifications (cloche + page dédiée).
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/account/pending', [AccountPendingController::class, 'show'])->name('account.pending');
    Route::get('/account/verification', [AccountVerificationController::class, 'edit'])->name('account.verification.edit');
    Route::post('/account/verification', [AccountVerificationController::class, 'update'])->name('account.verification.update');

    // Two-factor authentication management (profile / security page)
    Route::post('/user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])
        ->name('two-factor.enable');
    Route::post('/user/confirmed-two-factor-authentication', [TwoFactorAuthenticationController::class, 'confirm'])
        ->name('two-factor.confirm');
    Route::delete('/user/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])
        ->name('two-factor.disable');
    Route::post('/user/two-factor-recovery-codes', [TwoFactorAuthenticationController::class, 'regenerateRecoveryCodes'])
        ->name('two-factor.recovery-codes');

    // Back Office (Admin)
    // `professional` bloque le consommateur ; `can:manage_products` exige une
    // permission de gestion (producteur/distributeur/admin). Le contrôle
    // d'appartenance (producteur = ses données) est fait dans les Policies.
    Route::prefix('admin')->name('admin.')->middleware(['active.account', 'professional', 'can:manage_products'])->group(function () {
        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::get('batches/{batch}/qr', [BatchController::class, 'downloadQr'])->name('batches.qr');
        Route::post('batches/{batch}/regenerate-qr', [BatchController::class, 'regenerateQr'])->name('batches.regenerate-qr');
        Route::resource('batches', BatchController::class);
        Route::get('batches/{batch}/traceability', [TraceabilityEventController::class, 'timeline'])->name('batches.traceability');

        Route::get('certifications/intelligence', [CertificationController::class, 'intelligence'])->name('certifications.intelligence');
        Route::match(['get', 'post'], 'certifications/intelligence/analyze', [CertificationController::class, 'analyze'])->name('certifications.intelligence.analyze.store');
        Route::resource('certifications', CertificationController::class);

        Route::resource('events', TraceabilityEventController::class);
        Route::resource('transport-conditions', TransportConditionController::class);
        Route::patch('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
        Route::patch('alerts/{alert}/ignore', [AlertController::class, 'ignore'])->name('alerts.ignore');
        Route::patch('alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge'])->name('alerts.acknowledge');
        Route::resource('alerts', AlertController::class);
        Route::resource('quality-checks', QualityCheckController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::resource('ocr-checks', OcrCheckController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

        // NutriMind — moteur de décision explicable (risque, impact, simulation, recommandations).
        Route::get('nutrimind', [NutriMindController::class, 'index'])->name('nutrimind.index');
        Route::get('nutrimind/{type}/{id}', [NutriMindController::class, 'show'])
            ->whereIn('type', ['product', 'batch', 'supplier'])
            ->name('nutrimind.show');
        Route::post('nutrimind/{type}/{id}/assess', [NutriMindController::class, 'assess'])
            ->whereIn('type', ['product', 'batch', 'supplier'])
            ->name('nutrimind.assess');
        Route::post('nutrimind/{type}/{id}/actions', [NutriMindController::class, 'recordAction'])
            ->whereIn('type', ['product', 'batch', 'supplier'])
            ->name('nutrimind.actions.store');
    });

    Route::prefix('admin')->name('admin.')->middleware('can:manage_users')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status');
        Route::get('verification-requests', [VerificationRequestController::class, 'index'])->name('verification.index');
        Route::get('verification-requests/{verificationRequest}', [VerificationRequestController::class, 'show'])->name('verification.show');
        Route::post('verification-requests/{verificationRequest}/approve', [VerificationRequestController::class, 'approve'])->name('verification.approve');
        Route::post('verification-requests/{verificationRequest}/information', [VerificationRequestController::class, 'requestInformation'])->name('verification.information');
        Route::post('verification-requests/{verificationRequest}/reject', [VerificationRequestController::class, 'reject'])->name('verification.reject');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
        Route::get('verification-documents/{verificationDocument}', [VerificationDocumentController::class, 'download'])->name('verification.documents.download');
    });
});

// Front Office (public)
Route::get('/products', [FrontProductController::class, 'index'])->name('front.products.index');
Route::get('/products/{product}', [FrontProductController::class, 'show'])->name('front.products.show');

// Public traceability (no auth required)
Route::get('/traceability', [FrontTraceabilityController::class, 'index'])->name('front.traceability.index');
Route::get('/lots/{batch:lot_number}', [FrontTraceabilityController::class, 'show'])->name('front.traceability.show');

require __DIR__.'/auth.php';
require __DIR__.'/stock.php';
