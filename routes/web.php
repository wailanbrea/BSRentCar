<?php

use App\Http\Controllers\Web\Admin\ContractController;
use App\Http\Controllers\Web\Admin\CustomerController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\DeliveryController;
use App\Http\Controllers\Web\Admin\DepositController;
use App\Http\Controllers\Web\Admin\InspectionController;
use App\Http\Controllers\Web\Admin\LoginController;
use App\Http\Controllers\Web\Admin\PaymentController;
use App\Http\Controllers\Web\Admin\ReportController;
use App\Http\Controllers\Web\Admin\ReservationController;
use App\Http\Controllers\Web\Admin\ReviewController;
use App\Http\Controllers\Web\Admin\VehicleController;
use App\Http\Controllers\Web\Client\AccountController;
use App\Http\Controllers\Web\Client\AuthController as ClientAuthController;
use App\Http\Controllers\Web\Client\BookingController;
use App\Http\Controllers\Web\Client\CatalogController;
use App\Http\Controllers\Web\Client\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Sitio público del cliente — docs/07_FRONTEND_GUIDE.md
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog');
Route::get('/vehiculos/{vehicle}', [CatalogController::class, 'show'])->name('vehicles.show');

// Autenticación del cliente (invitado).
Route::get('/login', [ClientAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [ClientAuthController::class, 'login'])->name('login.attempt');
Route::get('/registro', [ClientAuthController::class, 'showRegister'])->name('register');
Route::post('/registro', [ClientAuthController::class, 'register'])->name('register.attempt');

// Área del cliente (sesión).
Route::middleware('auth')->group(function () {
    Route::post('/logout', [ClientAuthController::class, 'logout'])->name('logout');

    Route::post('/reservar', [BookingController::class, 'store'])->name('booking.store');

    Route::prefix('mi-cuenta')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/reservas', [AccountController::class, 'reservations'])->name('reservations');
        Route::get('/reservas/{reservation}', [AccountController::class, 'showReservation'])->name('reservations.show');
        Route::get('/wallet', [AccountController::class, 'wallet'])->name('wallet');
        Route::get('/perfil', [AccountController::class, 'profile'])->name('profile');
        Route::put('/perfil', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::post('/documentos', [AccountController::class, 'uploadDocument'])->name('documents.store');
    });
});

/*
|--------------------------------------------------------------------------
| Panel administrativo (web, sesión) — docs/08_ADMIN_PANEL.md
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Autenticación (invitado).
    Route::get('login', [LoginController::class, 'show'])->name('login');
    Route::post('login', [LoginController::class, 'attempt'])->name('login.attempt');

    // Área protegida (sesión + rol admin/staff).
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('vehicles', VehicleController::class)
            ->except(['show'])
            ->parameters(['vehicles' => 'vehicle']);

        // Fotos de vehículos
        Route::post('vehicles/{vehicle}/images', [VehicleController::class, 'uploadImage'])->name('vehicles.images.upload');
        Route::post('vehicles/{vehicle}/images/{image}/primary', [VehicleController::class, 'setPrimaryImage'])->name('vehicles.images.primary');
        Route::delete('vehicles/{vehicle}/images/{image}', [VehicleController::class, 'deleteImage'])->name('vehicles.images.delete');

        // Reservas
        Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
        Route::post('reservations/{reservation}/mark-paid', [ReservationController::class, 'markPaid'])->name('reservations.mark-paid');
        Route::post('reservations/{reservation}/confirm', [ReservationController::class, 'confirm'])->name('reservations.confirm');
        Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

        // Clientes
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

        // Pagos
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');

        // Depósitos
        Route::get('deposits', [DepositController::class, 'index'])->name('deposits.index');

        // Entregas
        Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
        Route::post('deliveries/{deliveryRequest}/assign', [DeliveryController::class, 'assign'])->name('deliveries.assign');
        Route::post('deliveries/{deliveryRequest}/status', [DeliveryController::class, 'updateStatus'])->name('deliveries.status');

        // Calificaciones
        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::post('reviews/{review}/moderate', [ReviewController::class, 'moderate'])->name('reviews.moderate');

        // Contratos
        Route::get('contracts', [ContractController::class, 'index'])->name('contracts.index');
        Route::post('reservations/{reservation}/contract', [ContractController::class, 'generate'])->name('contracts.generate');
        Route::get('contracts/{contract}/download', [ContractController::class, 'download'])->name('contracts.download');

        // Inspecciones
        Route::get('inspections', [InspectionController::class, 'index'])->name('inspections.index');

        // Reportes
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    });
});
