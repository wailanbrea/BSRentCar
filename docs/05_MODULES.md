# 05 — MODULES · RentCar E-Commerce

> Cada módulo: responsabilidad, entidades, servicios, controladores, vistas, APIs
> y pruebas mínimas. Crear un módulo nuevo obliga a actualizar este archivo.

Módulos:
`Auth · Customers · Vehicles · Catalog · Reservations · Payments · Wallet ·
Deposits · Deliveries · Inspections · Contracts · Reviews · Invoices · Reports ·
Notifications · Settings · Audit · Admin`

---

## Auth
- **Responsabilidad:** registro, login, logout, sesión, tokens API (Sanctum), 2FA admin.
- **Entidades:** `users`, roles/permisos (Spatie).
- **Servicios:** `AuthService`, `TwoFactorService`.
- **Controladores:** `Api/AuthController`, `Web/LoginController`, `Web/RegisterController`.
- **Vistas:** Login, Registro, Recuperar contraseña.
- **APIs:** `POST /auth/register|login|logout`, `GET /auth/me`.
- **Pruebas mínimas:** registro válido/ inválido, login ok/ fallido, logout, acceso protegido sin token.

## Customers
- **Responsabilidad:** perfil, documentos, estado de verificación, historiales.
- **Entidades:** `customers`, `customer_documents`.
- **Servicios:** `CustomerService`, `DocumentService`.
- **Controladores:** `Api/CustomerProfileController`, `Api/CustomerDocumentController`, `Web/Customer/ProfileController`.
- **Vistas:** Dashboard cliente, Perfil, Documentos.
- **APIs:** `GET/PUT /customer/profile`, `POST /customer/documents`, `GET /customer/reservations`, `GET /customer/wallet`.
- **Pruebas mínimas:** crear cliente, actualizar perfil, subir documento (tipo/tamaño), verificación de estado.

## Vehicles
- **Responsabilidad:** CRUD de vehículos, fotos, características, precios, bloqueos.
- **Entidades:** `vehicles`, `vehicle_images`, `vehicle_features`, `vehicle_price_rules`, `vehicle_availability_blocks`.
- **Servicios:** `VehicleService`, `VehicleImageService`, `PricingService`.
- **Controladores:** `Admin/VehicleController`, `Admin/VehicleImageController`, `Api/VehicleController`.
- **Vistas (admin):** lista, crear, editar, fotos, disponibilidad.
- **APIs:** `GET /vehicles`, `GET /vehicles/{id}`, `GET /vehicles/{id}/availability`.
- **Pruebas mínimas:** crear vehículo, subir foto, marca de imagen principal, cálculo de precio con reglas.

## Catalog
- **Responsabilidad:** búsqueda y filtros públicos (fecha, precio, categoría, transmisión, pasajeros, ubicación).
- **Entidades:** lee `vehicles` + disponibilidad.
- **Servicios:** `CatalogService`, `AvailabilityService`.
- **Controladores:** `Web/CatalogController`, `Api/VehicleController@index`.
- **Vistas:** Home, Catálogo, Detalle de vehículo.
- **APIs:** `GET /vehicles?filters...`.
- **Pruebas mínimas:** listar catálogo, filtrar por fecha (excluye no disponibles), filtros combinados, detalle.

## Reservations
- **Responsabilidad:** crear/gestionar reservas, máquina de estados, anti-doble-reserva.
- **Entidades:** `reservations`, `reservation_status_logs`.
- **Servicios:** `ReservationService`, `AvailabilityService`, `ReservationStateMachine`.
- **Controladores:** `Api/ReservationController`, `Admin/ReservationController`.
- **Vistas:** Mis reservas, Detalle de reserva, Checkout; (admin) reservas y detalle.
- **APIs:** `POST /reservations`, `GET /reservations/{id}`, `POST /reservations/{id}/cancel|confirm`.
- **Pruebas mínimas:** crear reserva, **evitar doble reserva**, expiración de draft, transición de estados válida/ inválida.

## Payments
- **Responsabilidad:** cobrar renta, registrar intentos/pagos, webhooks, abstracción de pasarela.
- **Entidades:** `payments`, `payment_attempts`, `payment_methods`, `refunds`.
- **Servicios:** `PaymentService`, `StripePaymentGateway`, `PayPalPaymentGateway`, `RefundService`.
- **Controladores:** `Api/StripePaymentController`, `Api/PayPalPaymentController`, `Api/WebhookController`.
- **Vistas:** Checkout, Métodos de pago, Confirmación de pago, Historial de pagos.
- **APIs:** Stripe create-intent/confirm, PayPal create-order/capture-order, webhooks. Ver `06_API_CONTRACTS.md`.
- **Pruebas mínimas:** Stripe ok/ fallido, PayPal ok/ fallido, webhook idempotente, sin tarjeta real almacenada.

## Wallet
- **Responsabilidad:** saldo interno y libro mayor de transacciones.
- **Entidades:** `wallets`, `wallet_transactions`.
- **Servicios:** `WalletService`.
- **Controladores:** `Api/WalletController`, `Admin/WalletController`.
- **Vistas:** Wallet (cliente), Wallet (admin).
- **APIs:** `GET /wallet`, `GET /wallet/transactions`.
- **Pruebas mínimas:** crear wallet, registrar credit/debit, reconciliación de `balance_after`.

## Deposits
- **Responsabilidad:** hold/capture/release del depósito.
- **Entidades:** `deposit_transactions`.
- **Servicios:** `DepositService`.
- **Controladores:** `Admin/DepositController`, parte de flujo en `PaymentService`.
- **Vistas (admin):** Depósitos.
- **APIs:** internas + admin (gestión).
- **Pruebas mínimas:** autorizar depósito, capturar parcial/total, liberar, expiración de autorización.

## Deliveries
- **Responsabilidad:** zonas de cobertura (geofence en mapa), puntos comerciales, ventanas horarias, cotización por distancia, logística de entrega/devolución y asignación.
- **Entidades:** `delivery_requests`, `delivery_zones`, `delivery_pickup_points`, `delivery_time_windows`.
- **Servicios:**
  - `DeliveryService` (solicitudes, asignación, estados).
  - `DeliveryZoneService` (alta/edición de polígonos, punto-en-polígono).
  - `DeliveryPricingService` (tarifa = base_fee + distancia; usa `DistanceCalculatorInterface`).
  - `DistanceCalculatorInterface` → `HaversineDistanceCalculator` (MVP); futuro `RoutingDistanceCalculator`.
- **Controladores:** `Admin/DeliveryController`, `Admin/DeliveryZoneController`, `Api/DeliveryController` (zones, pickup-points, time-windows, quote).
- **Vistas:** (admin) Entregas + **Zonas de entrega** (editor Leaflet); (cliente) `x-delivery-map` en checkout y mapa de cobertura.
- **Pruebas mínimas:** crear solicitud, asignar responsable, transición de estados, **punto dentro/fuera de zona**, **cálculo de tarifa por distancia (Haversine)**, tope `max_distance_km`, disponibilidad de ventana por capacidad.

> **Nota de diseño:** el cálculo de distancia y el proveedor de mapa están
> abstraídos (`DistanceCalculatorInterface` / `MapProvider`) para poder cambiar de
> Haversine a routing real o de Leaflet/OSM a Google/Mapbox sin tocar el negocio.
> Decisión en `14_DECISIONS_LOG.md`.

## Inspections
- **Responsabilidad:** inspección inicial/final con fotos, combustible, km, daños, firma.
- **Entidades:** `vehicle_inspections`, `inspection_photos`.
- **Servicios:** `InspectionService`.
- **Controladores:** `Admin/InspectionController`.
- **Vistas (admin):** Inspecciones (inicial/final).
- **Pruebas mínimas:** registrar inspección inicial, final, adjuntar fotos, derivar penalidad.

## Contracts
- **Responsabilidad:** generar PDF de contrato y registrar firma/aceptación.
- **Entidades:** `contracts`.
- **Servicios:** `ContractService` (DomPDF/Browsershot).
- **Controladores:** `Admin/ContractController`, `Api/ContractController` (firma cliente).
- **Vistas:** Contrato (cliente: ver/firmar), (admin: generar/archivar).
- **Pruebas mínimas:** generar contrato, firmar (aceptación), almacenamiento privado.

## Reviews
- **Responsabilidad:** calificaciones de reservas completadas.
- **Entidades:** `reviews`.
- **Servicios:** `ReviewService`.
- **Controladores:** `Api/ReviewController`, `Admin/ReviewController`.
- **Vistas:** Calificaciones (cliente), Reviews en detalle de vehículo, (admin) moderación.
- **APIs:** `POST /reservations/{id}/review`, `GET /vehicles/{id}/reviews`.
- **Pruebas mínimas:** calificar reserva completada, impedir calificación no autorizada, recalcular `rating_avg`.

## Invoices
- **Responsabilidad:** comprobantes de pago/reserva.
- **Entidades:** `invoices`.
- **Servicios:** `InvoiceService`.
- **Controladores:** `Admin/InvoiceController`, `Api/InvoiceController`.
- **Pruebas mínimas:** emitir factura, totales correctos, estado paid.

## Reports
- **Responsabilidad:** KPIs e informes (ingresos, ocupación, top vehículos).
- **Servicios:** `ReportService`.
- **Controladores:** `Admin/ReportController`.
- **Vistas (admin):** Reportes.
- **Pruebas mínimas:** agregación de ingresos por rango, ocupación.

## Notifications
- **Responsabilidad:** mail/database/push/whatsapp.
- **Entidades:** `notifications`.
- **Servicios:** `NotificationService` + Notifications de Laravel.
- **Pruebas mínimas:** disparo en eventos clave (pago, confirmación, recordatorio).

## Settings
- **Responsabilidad:** configuración general key/value.
- **Entidades:** `settings`.
- **Servicios:** `SettingsService` (cacheado).
- **Controladores:** `Admin/SettingController`.
- **Pruebas mínimas:** leer/escribir setting, default currency/tax.

## Audit
- **Responsabilidad:** registrar acciones sensibles.
- **Entidades:** `audit_logs`.
- **Servicios:** `AuditService` (observers/eventos).
- **Controladores:** `Admin/AuditController`.
- **Pruebas mínimas:** log en cambios de reserva/pago, inmutabilidad.

## Admin
- **Responsabilidad:** shell del panel, navegación, dashboard, control de acceso por rol.
- **Servicios:** middlewares de rol/permiso (Spatie).
- **Controladores:** `Admin/DashboardController`.
- **Vistas (admin):** todas las de `08_ADMIN_PANEL.md`.
- **Pruebas mínimas:** acceso admin vs. cliente, dashboard KPIs.

---

## Estructura de carpetas sugerida (Laravel)

```txt
app/
  Enums/                 (ReservationStatus, PaymentStatus, ...)
  Models/
  Services/              (lógica de negocio)
  Services/Payments/     (PaymentGatewayInterface, Stripe..., PayPal...)
  Http/Controllers/Api/
  Http/Controllers/Admin/
  Http/Controllers/Web/
  Http/Requests/         (Form Requests)
  Http/Resources/        (API Resources)
  Policies/
  Jobs/
  Events/  Listeners/
  Notifications/
database/migrations/ seeders/ factories/
routes/ web.php api.php
resources/views/ (blade) + admin/
tests/Feature/ Unit/
```
