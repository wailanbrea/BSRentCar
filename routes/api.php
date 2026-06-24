<?php

use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\VehicleImageController as AdminVehicleImageController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerDocumentController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\StripePaymentController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\PayPalPaymentController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Admin\AdminContractController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Admin\AdminDeliveryController;
use App\Http\Controllers\Admin\AdminInspectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (prefijo /api/v1)
| Contratos: docs/06_API_CONTRACTS.md
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Cliente autenticado (perfil y documentos) — Fase 3.
Route::middleware(['auth:sanctum', 'role:customer'])->prefix('customer')->group(function () {
    Route::get('profile', [CustomerProfileController::class, 'show']);
    Route::put('profile', [CustomerProfileController::class, 'update']);
    Route::get('documents', [CustomerDocumentController::class, 'index']);
    Route::post('documents', [CustomerDocumentController::class, 'store']);

    // Reservas del cliente — Fase 5.
    Route::get('reservations', [ReservationController::class, 'index']);
    Route::post('reservations', [ReservationController::class, 'store']);
    Route::get('reservations/{reservation}', [ReservationController::class, 'show']);
    Route::post('reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

    // Contratos del cliente — Fase 10.
    Route::get('reservations/{reservation}/contract', [ContractController::class, 'show']);
    Route::post('reservations/{reservation}/contract/sign', [ContractController::class, 'sign']);
    Route::get('reservations/{reservation}/contract/download', [ContractController::class, 'download']);

    // Billetera del cliente — Fase 8.
    Route::get('wallet', [\App\Http\Controllers\Api\WalletController::class, 'show']);
    Route::post('wallet/topup', [\App\Http\Controllers\Api\WalletController::class, 'topup']);
});

// Catálogo público de vehículos — Fase 4.
Route::get('vehicles', [VehicleController::class, 'index']);
Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
Route::get('vehicles/{vehicle}/availability', [VehicleController::class, 'availability']);

// Logística y entregas públicas — Fase 11.
Route::get('delivery/zones', [DeliveryController::class, 'indexZones']);
Route::get('delivery/pickup-points', [DeliveryController::class, 'indexPickupPoints']);
Route::get('delivery/time-windows', [DeliveryController::class, 'indexTimeWindows']);
Route::post('delivery/quote', [DeliveryController::class, 'quote']);

// Gestión administrativa de vehículos — Fase 4 (permisos Spatie).
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('vehicles', [AdminVehicleController::class, 'index'])->middleware('permission:vehicles.view');
    Route::post('vehicles', [AdminVehicleController::class, 'store'])->middleware('permission:vehicles.create');
    Route::get('vehicles/{vehicle}', [AdminVehicleController::class, 'show'])->middleware('permission:vehicles.view');
    Route::put('vehicles/{vehicle}', [AdminVehicleController::class, 'update'])->middleware('permission:vehicles.update');
    Route::delete('vehicles/{vehicle}', [AdminVehicleController::class, 'destroy'])->middleware('permission:vehicles.delete');

    Route::post('vehicles/{vehicle}/images', [AdminVehicleImageController::class, 'store'])->middleware('permission:vehicles.update');
    Route::put('vehicles/{vehicle}/images/{image}/primary', [AdminVehicleImageController::class, 'setPrimary'])->middleware('permission:vehicles.update');
    Route::delete('vehicles/{vehicle}/images/{image}', [AdminVehicleImageController::class, 'destroy'])->middleware('permission:vehicles.update');

    // Reservas (admin) — Fase 5.
    Route::get('reservations', [AdminReservationController::class, 'index'])->middleware('permission:reservations.view');
    Route::get('reservations/{reservation}', [AdminReservationController::class, 'show'])->middleware('permission:reservations.view');
    Route::post('reservations/{reservation}/mark-paid', [AdminReservationController::class, 'markPaid'])->middleware('permission:reservations.manage');
    Route::post('reservations/{reservation}/confirm', [AdminReservationController::class, 'confirm'])->middleware('permission:reservations.manage');

    // Contratos (admin) — Fase 10.
    Route::post('reservations/{reservation}/contract', [AdminContractController::class, 'generate'])->middleware('permission:reservations.manage');
    Route::get('reservations/{reservation}/contract/download', [AdminContractController::class, 'download'])->middleware('permission:reservations.view');

    // Billetera (admin) — Fase 8.
    Route::post('customers/{id}/wallet/adjust', [\App\Http\Controllers\Admin\AdminWalletController::class, 'adjust'])->middleware('permission:wallet.manage');

    // Depósitos de seguridad (admin) — Fase 9.
    Route::post('deposits/{id}/capture', [\App\Http\Controllers\Admin\AdminDepositController::class, 'capture'])->middleware('permission:deposits.manage');
    Route::post('deposits/{id}/release', [\App\Http\Controllers\Admin\AdminDepositController::class, 'release'])->middleware('permission:deposits.manage');

    // Logística y entregas (admin) — Fase 11.
    Route::get('delivery-zones', [AdminDeliveryController::class, 'indexZones'])->middleware('permission:deliveries.manage');
    Route::post('delivery-zones', [AdminDeliveryController::class, 'storeZone'])->middleware('permission:deliveries.manage');
    Route::put('delivery-zones/{zone}', [AdminDeliveryController::class, 'updateZone'])->middleware('permission:deliveries.manage');
    Route::delete('delivery-zones/{zone}', [AdminDeliveryController::class, 'destroyZone'])->middleware('permission:deliveries.manage');

    Route::get('delivery-pickup-points', [AdminDeliveryController::class, 'indexPickupPoints'])->middleware('permission:deliveries.manage');
    Route::post('delivery-pickup-points', [AdminDeliveryController::class, 'storePickupPoint'])->middleware('permission:deliveries.manage');
    Route::put('delivery-pickup-points/{point}', [AdminDeliveryController::class, 'updatePickupPoint'])->middleware('permission:deliveries.manage');
    Route::delete('delivery-pickup-points/{point}', [AdminDeliveryController::class, 'destroyPickupPoint'])->middleware('permission:deliveries.manage');

    Route::get('delivery-time-windows', [AdminDeliveryController::class, 'indexTimeWindows'])->middleware('permission:deliveries.manage');
    Route::post('delivery-time-windows', [AdminDeliveryController::class, 'storeTimeWindow'])->middleware('permission:deliveries.manage');
    Route::put('delivery-time-windows/{window}', [AdminDeliveryController::class, 'updateTimeWindow'])->middleware('permission:deliveries.manage');
    Route::delete('delivery-time-windows/{window}', [AdminDeliveryController::class, 'destroyTimeWindow'])->middleware('permission:deliveries.manage');

    Route::post('deliveries/{deliveryRequest}/assign', [AdminDeliveryController::class, 'assignDriver'])->middleware('permission:deliveries.manage');
    Route::put('deliveries/{deliveryRequest}/status', [AdminDeliveryController::class, 'updateStatus'])->middleware('permission:deliveries.manage');

    // Inspecciones de vehículos (admin) — Fase 12.
    Route::post('reservations/{reservation}/inspections', [AdminInspectionController::class, 'store'])->middleware('permission:inspections.manage');
    Route::post('inspections/{inspection}/photos', [AdminInspectionController::class, 'uploadPhoto'])->middleware('permission:inspections.manage');
    Route::get('inspections/{inspection}', [AdminInspectionController::class, 'show'])->middleware('permission:inspections.manage');
});

// Pagos Stripe (cliente autenticado) — Fase 6.
Route::middleware(['auth:sanctum', 'role:customer'])->prefix('payments/stripe')->group(function () {
    Route::post('create-intent', [StripePaymentController::class, 'createIntent']);
    Route::post('confirm', [StripePaymentController::class, 'confirm']);
});

// Pagos PayPal (cliente autenticado) — Fase 7.
Route::middleware(['auth:sanctum', 'role:customer'])->prefix('payments/paypal')->group(function () {
    Route::post('create-intent', [PayPalPaymentController::class, 'createIntent']);
    Route::post('confirm', [PayPalPaymentController::class, 'confirm']);
    Route::get('confirm-redirect', [PayPalPaymentController::class, 'confirmRedirect'])->name('api.payments.paypal.confirm');
});

// Webhooks de proveedores de pago (sin auth, validación por firma) — Fase 6/7.
Route::prefix('payments/webhooks')->group(function () {
    Route::post('stripe', [WebhookController::class, 'handleStripe']);
    Route::post('paypal', [WebhookController::class, 'handlePaypal']);
});
