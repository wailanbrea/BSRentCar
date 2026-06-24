# 15 — AI WORK LOG · RentCar E-Commerce

> Bitácora de trabajo de cada IA / desarrollador. **Reglas:** no borrar entradas
> anteriores; agregar al final; declarar errores, pendientes y pruebas reales.
> Formato obligatorio:

```md
## YYYY-MM-DD HH:mm - IA / Desarrollador

### Tarea

### Cambios realizados

### Archivos modificados

### Pruebas realizadas

### Pendientes
```

---

## 2026-06-24 - Claude Code (Opus 4.8)

### Tarea
Fase 0: crear toda la memoria técnica del proyecto en archivos `.md` y dejar la
documentación base lista para que múltiples IA trabajen sin perder contexto.
No programar módulos de negocio todavía.

### Cambios realizados
- Creada carpeta `docs/` con los 21 documentos (`00`–`20`).
- Creados en la raíz `README.md`, `AI_RULES.md`, `CHANGELOG.md`.
- Definidos: contexto del producto, reglas de negocio, stack, esquema de BD
  (29 tablas), módulos, contratos de API, guía frontend + design system
  (derivado del mockup de referencia entregado por el usuario), panel admin,
  lógica de pagos/wallet, flujo de reservas con anti-doble-reserva, seguridad,
  testing, roadmap por fases, decisiones iniciales, providers Stripe/PayPal,
  guía de despliegue, variables de entorno y preguntas abiertas.
- Registradas 8 decisiones iniciales en `14_DECISIONS_LOG.md`.

### Archivos modificados
```
docs/00_MASTER_INDEX.md ... docs/20_OPEN_QUESTIONS.md (21 archivos)
README.md, AI_RULES.md, CHANGELOG.md
```

### Pruebas realizadas
Ninguna (tarea de documentación; sin código ejecutable todavía).

### Pendientes
- Aprobación del usuario para iniciar Fase 1 (proyecto Laravel base).
- Resolver preguntas abiertas en `20_OPEN_QUESTIONS.md` (negocio y técnicas).
- Decidir DomPDF vs. Browsershot para contratos.
- Decidir Blade puro vs. Livewire vs. Inertia.

---

## 2026-06-24 - Claude Code (Opus 4.8)

### Tarea
Incorporar decisiones de negocio entregadas por el usuario: edad mínima 18,
licencia verificada antes de pagar, depósito autorizado (hold), entregas por zonas.

### Cambios realizados
- `02_BUSINESS_RULES.md`: BR-C08 (edad 18), BR-C09 (licencia aprobada antes de pagar),
  BR-C10 (verificación manual MVP), BR-D00 (depósito hold por defecto), BR-E00 (zonas de entrega).
- `04_DATABASE_SCHEMA.md`: nueva tabla `delivery_zones`; `delivery_requests` ahora referencia `delivery_zone_id`.
- `09_PAYMENTS_WALLET.md`: depósito autorizado como modo por defecto.
- `10_RESERVATIONS_FLOW.md`: gate de elegibilidad (edad + licencia) previo al pago; hold de depósito en happy path.
- `14_DECISIONS_LOG.md`: nueva decisión registrada.
- `20_OPEN_QUESTIONS.md`: preguntas resueltas marcadas; nueva sub-pregunta sobre asignación de zona.

### Archivos modificados
`docs/02, 04, 09, 10, 14, 15, 20`.

### Pruebas realizadas
Ninguna (documentación).

### Pendientes
- Definir método de asignación de zona de entrega (manual / código postal / geofence).
- Resto de preguntas abiertas (efectivo, transferencia, kilometraje, combustible, retraso, cancelación, seguro, sucursales, multiempresa, decisiones técnicas).
- Aprobación para iniciar Fase 1 (Laravel base).

---

## 2026-06-24 - Claude Code (Opus 4.8)

### Tarea
Detallar el sistema de entregas según requisito del usuario: zonas geofence en
mapa configurables por el admin, entrega a domicilio con cobro por distancia,
puntos comerciales cercanos y ventanas horarias configurables; cliente ve mapa de cobertura.

### Cambios realizados
- `02_BUSINESS_RULES.md`: BR-E00 ampliada + BR-E06..E10 (domicilio en zona, cobro por distancia, puntos, ventanas, fuera de cobertura).
- `04_DATABASE_SCHEMA.md`: `delivery_zones` ampliada (polygon GeoJSON, origin, allows_home_delivery, base_fee, free_radius_km, price_per_km, max_distance_km, color); nuevas `delivery_pickup_points` y `delivery_time_windows`; `delivery_requests` ampliado (zona, punto, ventana, distance_km, scheduled_window_*).
- `06_API_CONTRACTS.md`: endpoints `/delivery/zones`, `/delivery/pickup-points`, `/delivery/time-windows`, `/delivery/quote`.
- `07_FRONTEND_GUIDE.md`: componentes `x-delivery-map`, `x-time-window-picker`; checkout con selector de entrega y mapa; pantalla "Mapa de cobertura".
- `08_ADMIN_PANEL.md`: pantalla "Zonas de entrega" (editor de mapa, puntos, ventanas, tarifas por distancia).
- `14_DECISIONS_LOG.md`, `20_OPEN_QUESTIONS.md`: decisión registrada y nuevas preguntas (proveedor de mapa, método de distancia, geocoding).

### Archivos modificados
`docs/02, 04, 06, 07, 08, 14, 15, 20`.

### Pruebas realizadas
Ninguna (documentación).

### Pendientes
- Elegir proveedor de mapa (Google/Mapbox/Leaflet+OSM) y método de distancia (Haversine vs. routing).
- Confirmar geocoding vs. pin manual.
- Resto de preguntas abiertas y aprobación para Fase 1.

---

## 2026-06-24 - Claude Code (Opus 4.8)

### Tarea
Fijar proveedor de mapa y método de distancia para entregas, con diseño abierto a cambios futuros.

### Cambios realizados
- Decisión: **Leaflet + OpenStreetMap** + **Haversine**, ambos **abstraídos** (`MapProvider` / `DistanceCalculatorInterface`) para cambiar de proveedor o a routing real sin tocar negocio; pin manual + Nominatim opcional.
- `03_TECH_STACK.md`: nueva sección "Mapas y geolocalización".
- `05_MODULES.md`: módulo Deliveries ampliado con `DeliveryZoneService`, `DeliveryPricingService`, `DistanceCalculatorInterface` y pruebas (punto-en-zona, tarifa por distancia).
- `07_FRONTEND_GUIDE.md`: `x-delivery-map` con Leaflet/OSM detrás de `MapProvider`.
- `14_DECISIONS_LOG.md`: decisión registrada.
- `20_OPEN_QUESTIONS.md`: preguntas de mapa/distancia/geocoding resueltas.

### Archivos modificados
`docs/03, 05, 07, 14, 15, 20`.

### Pruebas realizadas
Ninguna (documentación).

### Pendientes
- Preguntas de negocio restantes (efectivo, transferencia, kilometraje, combustible, retraso, cancelación, seguro, sucursales, multiempresa).
- Decisiones técnicas restantes (Blade/Livewire, Stripe Checkout vs Intents, PayPal Checkout vs Orders, DomPDF vs Browsershot, firma digital, WhatsApp).
- Aprobación para iniciar Fase 1 (Laravel base) y ubicación del proyecto (raíz vs subcarpeta).

---

## 2026-06-24 - Claude Code (Opus 4.8)

### Tarea
Investigar estándares de República Dominicana y resolver las decisiones pendientes
de negocio y técnicas (efectivo, transferencia, kilometraje, combustible, retraso,
cancelación, seguro, sucursales, multiempresa, frontend, Stripe, PayPal, PDF, firma).

### Cambios realizados
- Investigación web (DGII/ITBIS 18%, requisitos de rentadoras en RD, Ley 126-02 firma electrónica).
- `02_BUSINESS_RULES.md`: BR-P10..P13 (métodos, depósito con tarjeta, ITBIS, moneda), BR-X03/X04 (cancelación escalonada, conductor joven), BR-O01..O03 (km/combustible/retraso), BR-S01..S03 (seguro), BR-L01..L03 (sucursales/multi-tenant). Renumeración de Wallet a §13.
- `03_TECH_STACK.md`: DomPDF, DOP/ITBIS, firma Ley 126-02, Blade+Livewire selectivo decididos.
- `04_DATABASE_SCHEMA.md`: nuevas tablas `locations` y `insurance_plans`; `reservations` con `pickup_location_id/return_location_id/insurance_plan_id`; `payments.provider_subtype`; settings defaults RD.
- `09_PAYMENTS_WALLET.md`: métodos aceptados (efectivo/transferencia manual), depósito siempre con tarjeta, ITBIS/DOP.
- `19_ENVIRONMENT_VARIABLES.md`: `DEFAULT_CURRENCY=DOP`, `TAX_RATE=0.18`.
- `14_DECISIONS_LOG.md`: decisión integral con fuentes.
- `20_OPEN_QUESTIONS.md`: 17 preguntas resueltas; quedan abiertas WhatsApp y S3 (no bloquean Fase 1).

### Archivos modificados
`docs/02, 03, 04, 09, 14, 15, 19, 20`.

### Pruebas realizadas
Ninguna (documentación + investigación).

### Pendientes
- Solo quedan abiertas: notificaciones WhatsApp y storage S3 en prod (no bloquean Fase 1).
- **Aprobación para iniciar Fase 1 (Laravel base)** y definir ubicación del proyecto (raíz `BSRentCar` vs subcarpeta).

---

## 2026-06-24 - Claude Code (Opus 4.8)

### Tarea
Fase 1 — Crear el proyecto Laravel base en la raíz `BSRentCar` (aprobado por el usuario) y dejar el entorno listo para desarrollo.

### Cambios realizados
- **Laravel 12.62** generado en carpeta temporal y fusionado en la raíz (preservando `docs/`, `AI_RULES.md`, `CHANGELOG.md`, `README.md`).
- `.env` y `.env.example`: `APP_NAME=RentCar`, locale es / faker es_DO, **MySQL** (`rentcar`), `DEFAULT_CURRENCY=DOP`, `TAX_RATE=0.18`.
- Base de datos `rentcar` creada en **MariaDB 10.4** (XAMPP); migraciones base aplicadas (users, cache, jobs, permission tables, personal_access_tokens).
- **Sanctum** (^4.3) y **Spatie Permission** (^6.25) instalados, configs/migraciones publicadas; `User` con traits `HasApiTokens` y `HasRoles`.
- **Alpine.js** añadido en `resources/js/app.js` sobre Tailwind v4 + Vite; `npm run build` OK (Alpine en bundle).
- Estructura modular: `app/{Enums,Services,Services/Payments,Services/Delivery,Http/Controllers/{Api,Admin,Web},Http/Requests,Http/Resources,Policies,Jobs,Events,Listeners,Notifications}` con `.gitkeep`.
- `storage:link` creado; **guzzle** actualizado (parchea CVE-2026-55767 / 55568).
- Docs actualizados: `13_TODO_ROADMAP.md` (Fase 1 ✅, Fase 2 en progreso), `CHANGELOG.md`, `03_TECH_STACK.md` (PHP 8.2 nota).

### Archivos modificados
Estructura Laravel completa + `app/Models/User.php`, `resources/js/app.js`, `.env`, `.env.example`, `package.json`, `composer.json/lock`, `docs/03,13,15`, `CHANGELOG.md`.

### Pruebas realizadas
- `php artisan migrate --force` → OK (MySQL).
- `npm run build` → OK (56 módulos, Alpine incluido).
- `php artisan test` → **2 passed** (baseline verde).
- `composer audit` → sin advisories tras actualizar guzzle.
- `php artisan about` → Laravel 12.62, PHP 8.2.12, env local, locale es.

### Pendientes
- Entorno local usa **PHP 8.2.12** (Laravel 12 lo soporta; 8.3+ recomendado en prod).
- **Fase 2:** seeder de roles/permisos, endpoints auth (register/login/logout/me), middleware de roles, 2FA admin, tests.
- Configurar Pint.

---

## 2026-06-24 - Claude Code (Opus 4.8)

### Tarea
Fase 2 — Autenticación API (Sanctum) y roles/permisos (Spatie).

### Cambios realizados
- `bootstrap/app.php`: habilitado routing API con `apiPrefix = api/v1`; alias de middleware `role`/`permission`/`role_or_permission` (Spatie).
- `routes/api.php`: rutas `auth/register|login` (throttle 6/min) y `auth/logout|me` (`auth:sanctum`).
- `app/Http/Controllers/Api/AuthController.php`: register (crea user + rol `customer` + token, 201), login (valida credenciales, 422 si falla), logout (revoca token actual, 204), me.
- `app/Http/Requests/Auth/{RegisterRequest,LoginRequest}.php`: validación (email unique, password min 8 confirmed).
- `app/Http/Resources/UserResource.php`: serializa user + `roles`.
- `database/seeders/RolesAndPermissionsSeeder.php`: 22 permisos + roles admin/staff/driver/customer.
- `database/seeders/DatabaseSeeder.php`: llama al seeder + crea admin `admin@rentcar.test` / `password`.
- `phpunit.xml`: activado SQLite `:memory:` para testing (aísla de la BD real `rentcar`).
- `tests/Feature/Auth/AuthTest.php`: 8 tests (register ok/duplicado/password corta, login ok/fallo, me sin/con auth, logout).

### Archivos modificados
`bootstrap/app.php`, `routes/api.php`, `app/Http/Controllers/Api/AuthController.php`, `app/Http/Requests/Auth/*`, `app/Http/Resources/UserResource.php`, `database/seeders/*`, `phpunit.xml`, `tests/Feature/Auth/AuthTest.php`, `docs/13`, `CHANGELOG.md`.

### Pruebas realizadas
- `php artisan db:seed` → roles/permisos + admin creados.
- `php artisan route:list --path=api` → 4 rutas `/api/v1/auth/*`.
- `php artisan test --filter=AuthTest` → **8 passed (27 asserts)**.
- `php artisan test` (suite) → **10 passed (29 asserts)**.

### Pendientes
- 2FA admin (se hará con el panel web).
- Configurar Pint.
- Fase 3 — Clientes (migraciones `customers`, `customer_documents`, perfil y documentos).

---

## 2026-06-24 - Claude Code (Opus 4.8)

### Tarea
Fase 3 — Clientes: perfil, documentos (storage privado), estado de verificación y gate de elegibilidad.

### Cambios realizados
- Migraciones `customers` (1–1 user, soft deletes, verification_status) y `customer_documents` (renombrada a 184752 para correr después de customers por la FK).
- Enums `App\Enums\{VerificationStatus, DocumentType, DocumentStatus}`.
- Modelos `Customer` (relaciones user/documents, casts, helpers `ageAt`, `hasApprovedLicense`, `rentalEligibilityErrors`) y `CustomerDocument`; `User::customer()` (hasOne).
- `CustomerService`: `createForUser` (firstOrCreate), `updateProfile`, `storeDocument` (disco privado `local`, ruta `documents/{customer_id}`).
- Form Requests `UpdateProfileRequest` (birthdate before:today) y `StoreDocumentRequest` (enum type + File pdf/jpg/png ≤5MB).
- Resources `CustomerResource` (incluye `has_approved_license`) y `CustomerDocumentResource` (NO expone `file_path`).
- Controladores `Api\CustomerProfileController` (show/update) y `Api\CustomerDocumentController` (index/store 201).
- Rutas `customer/*` con `auth:sanctum` + `role:customer`. Registro crea el cliente automáticamente.
- `CustomerFactory`; `tests/Feature/Customer/CustomerProfileTest.php` (8 tests).

### Archivos modificados
`database/migrations/2026_06_24_184751_*`, `..._184752_*`, `app/Enums/*`, `app/Models/{Customer,CustomerDocument,User}.php`, `app/Services/CustomerService.php`, `app/Http/Requests/Customer/*`, `app/Http/Resources/{CustomerResource,CustomerDocumentResource}.php`, `app/Http/Controllers/Api/{CustomerProfileController,CustomerDocumentController,AuthController}.php`, `routes/api.php`, `database/factories/CustomerFactory.php`, `tests/Feature/Customer/CustomerProfileTest.php`, `docs/13`, `CHANGELOG.md`.

### Pruebas realizadas
- `php artisan migrate` (MySQL) → customers + customer_documents OK.
- `php artisan test` → **18 passed (54 asserts)**. Incluye: perfil auto-creado, update válido/ inválido, birthdate futura rechazada, documento en disco privado (Storage::fake), tipo inválido rechazado, elegibilidad (edad/licencia).

### Pendientes
- Endpoint de descarga de documento con URL firmada temporal (con verificación admin).
- Verificación de documentos por admin (aprobar/rechazar) — se hará con el panel admin.
- Fase 4 — Vehículos y catálogo.

---

## 2026-06-24 - Claude Code (Opus 4.8)

### Tarea
Fase 4 — Vehículos y catálogo: esquema, servicios de disponibilidad/precio, catálogo público con filtros y CRUD admin.

### Cambios realizados
- Migraciones: `locations`, `vehicles` (decimal money, soft deletes), `vehicle_images`, `vehicle_features`, `vehicle_price_rules`, `vehicle_availability_blocks` (índice nombrado `vab_vehicle_range_idx` por límite de 64 chars en MySQL).
- Enums `VehicleStatus` (+`nonRentable()`), `VehicleCategory`, `Transmission`.
- Modelos `Location`, `Vehicle` (relaciones, casts, scopes `rentable`/`filter`), `VehicleImage`, `VehicleFeature`, `VehiclePriceRule`, `VehicleAvailabilityBlock`.
- `AvailabilityService` (solape `start<end AND end>start`: estado + bloqueos; reservas en Fase 5) y `PricingService` (días por 24h, reglas de precio, BCMath, escala 2).
- Catálogo público: `Api\VehicleController` (index con `CatalogFilterRequest`, show, availability), `VehicleResource`, `VehicleImageResource`.
- Admin: `Admin\VehicleController` (CRUD) + `Admin\VehicleImageController` (subir/principal/eliminar), `StoreVehicleRequest`/`UpdateVehicleRequest`, rutas con `permission:vehicles.*`.
- Factories `VehicleFactory`, `LocationFactory`.
- Tests: `Vehicle/CatalogTest` (9), `Admin/VehicleManagementTest` (7), `Unit/PricingServiceTest` (3).

### Archivos modificados
6 migraciones, `app/Enums/{VehicleStatus,VehicleCategory,Transmission}.php`, 6 modelos, `app/Services/{AvailabilityService,PricingService}.php`, `app/Http/Controllers/{Api/VehicleController,Admin/VehicleController,Admin/VehicleImageController}.php`, `app/Http/Requests/{Vehicle/CatalogFilterRequest,Admin/StoreVehicleRequest,Admin/UpdateVehicleRequest}.php`, `app/Http/Resources/{VehicleResource,VehicleImageResource}.php`, `routes/api.php`, 2 factories, 3 archivos de test, `docs/13`, `CHANGELOG.md`.

### Pruebas realizadas
- `php artisan migrate` (MySQL) → 6 tablas OK (tras corregir nombre de índice largo y tabla huérfana).
- `php artisan test` → **37 passed (104 asserts)**. Destacado: filtro por fecha excluye vehículo con bloqueo solapado e incluye el de bloqueo no solapado; quote correcta; permisos admin (customer→403, guest→401).

### Pendientes
- Web UI (Blade) de catálogo y panel admin — la API está lista.
- Ampliar `AvailabilityService` para incluir solape con reservas (Fase 5).
- Fase 5 — Reservas (máquina de estados + anti-doble-reserva en transacción).

---

## 2026-06-24 - Claude Code (Opus 4.8)

### Tarea
Fase 5 — Reservas: máquina de estados y anti-doble-reserva en transacción.

### Cambios realizados
- Migraciones `reservations` (todos los campos de docs/04, índice `res_vehicle_range_idx`) y `reservation_status_logs`.
- Enums `ReservationStatus` (+`blocking()`/`blockingValues()`), `PaymentStatus`, `ContractStatus`, `PickupType`.
- Modelos `Reservation` (casts enum/decimal/fecha, relaciones) y `ReservationStatusLog`; relaciones `Vehicle::reservations()` y `Customer::reservations()`.
- `AvailabilityService` ampliado: ahora excluye vehículos con reservas en estados bloqueantes (solape), con `exceptReservationId`.
- `ReservationStateMachine` (mapa de transiciones válidas + registro en logs).
- `ReservationService`: `createForCustomer` (gate elegibilidad + chequeo suave + cotización con ITBIS 18% + total), `markAsPaid` (TRANSACCIÓN + `lockForUpdate` + revalidación = barrera anti-doble-reserva), `confirm`, `cancel`.
- `config/rentcar.php` (tax_rate, currency, reservation_hold_minutes, deposit_mode); `PricingService::tax()`/`add()`.
- Excepciones `VehicleNotAvailableException` (409) y `CustomerNotEligibleException` (422 con razones).
- `Api\ReservationController` (index/store/show/cancel) + `Admin\ReservationController` (index/show/markPaid/confirm); `ReservationPolicy`; `AuthorizesRequests` añadido al `Controller` base; rutas registradas.
- `StoreReservationRequest`, `ReservationResource`.
- Tests `tests/Feature/Reservation/ReservationTest.php` (8 casos).

### Archivos modificados
2 migraciones, 4 enums, `app/Models/{Reservation,ReservationStatusLog,Vehicle,Customer}.php`, `app/Services/{AvailabilityService,PricingService,ReservationStateMachine,ReservationService}.php`, `config/rentcar.php`, `app/Exceptions/{VehicleNotAvailableException,CustomerNotEligibleException}.php`, `app/Http/Controllers/{Api/ReservationController,Admin/ReservationController,Controller}.php`, `app/Http/Requests/Reservation/StoreReservationRequest.php`, `app/Http/Resources/ReservationResource.php`, `app/Policies/ReservationPolicy.php`, `routes/api.php`, `tests/Feature/Reservation/ReservationTest.php`, `docs/13`.

### Pruebas realizadas
⚠️ **PENDIENTES de ejecutar.** El clasificador de seguridad del entorno que autoriza
la ejecución de comandos (PowerShell/Bash) estuvo caído al cerrar esta entrada, por
lo que NO se pudieron correr `php artisan migrate` ni `php artisan test` para Fase 5.
Los tests están escritos. **Acción requerida:** ejecutar `php artisan migrate` y
`php artisan test` cuando el entorno se restablezca; corregir lo que falle.

### Pendientes
- Ejecutar y verificar la suite de Fase 5 (migración + tests).
- Job de expiración de holds `pending_payment`.
- Registro de `Payment` real (Fase 6/7) desde `markAsPaid`/webhooks.

---

## 2026-06-24 - Antigravity (Gemini Advanced Agent)

### Tarea
Fase 6 — Integración Stripe, Fase 7 — Integración PayPal y Fase 8 — Sistema de Billetera (Wallet).

### Cambios realizados
- **Fase 6 (Stripe)**:
  - Instalado `stripe/stripe-php` e implementado `StripePaymentGateway` (cumpliendo `PaymentGatewayInterface`).
  - Creadas tablas `payments`, `payment_attempts` y `payment_methods`.
  - Creado `StripePaymentController` para creación de intents (Stripe Payment Intent) y confirmaciones.
  - Implementado `StripeWebhookHandler` para recepción, verificación de firma e idempotencia de webhooks.
  - Creada suite de pruebas en `StripePaymentTest.php` (8 casos de prueba mockeados).
- **Fase 7 (PayPal)**:
  - Implementado `PayPalPaymentGateway` consumiendo directamente la API Orders v2 REST de PayPal (Http client sin SDKs obsoletos).
  - Creado `PayPalPaymentController` para gestionar creación de intents (orden de PayPal) y redirecciones.
  - Implementado `PayPalWebhookHandler` para verificación criptográfica de firmas vía API de PayPal.
  - Modificado el sistema para operar por defecto en dólares estadounidenses (`DEFAULT_CURRENCY=USD`).
  - Corregido bug en `PaymentService::initiatePayment()` para mapeo de `clientSecret` desde el DTO.
  - Creado `InvalidPayPalSignatureException` para control preciso de respuestas HTTP 400 vs 200 en webhooks.
  - Creada suite de pruebas en `PayPalPaymentTest.php` (8 casos de prueba mockeados).
- **Fase 8 (Wallet)**:
  - Creadas tablas `wallets` y `wallet_transactions`.
  - Creado `Wallet` y `WalletTransaction` con casts de decimales de precisión en dinero.
  - Implementado `WalletService` con bloqueos pesimistas en DB (`lockForUpdate`), soporte para ajustes manuales y reconciliación de balances.
  - Adaptada la billetera como pasarela de pago (`WalletPaymentGateway`) que implementa `PaymentGatewayInterface`.
  - Refacturado `PaymentService` para soportar pagos parciales/co-pagos, recargas de billetera (`wallet_topup`), y validación de pago completo en reservas.
  - Creados `WalletController` y `AdminWalletController` para gestionar transacciones de billetera y ajustes de balance.
  - Creada suite de pruebas en `WalletTest.php` (8 casos de prueba).

### Archivos modificados
- Migraciones: `2026_06_24_193500_create_payments_tables.php`, `2026_06_24_200000_create_wallets_table.php`, `2026_06_24_200001_create_wallet_transactions_table.php`
- Modelos: `Payment`, `PaymentAttempt`, `PaymentMethod`, `Customer`, `Wallet`, `WalletTransaction`
- Enums: `PaymentStatus`, `PaymentType`, `PaymentAttemptStatus`, `PaymentProvider`
- Controladores: `StripePaymentController`, `PayPalPaymentController`, `WalletController`, `AdminWalletController`, `WebhookController`
- Servicios: `PaymentGatewayInterface`, `PaymentGatewayResponse`, `StripePaymentGateway`, `PayPalPaymentGateway`, `WalletPaymentGateway`, `PaymentGatewayFactory`, `PaymentService`, `WalletService`
- Rutas: `routes/api.php`
- Configuración: `config/rentcar.php`, `config/logging.php`
- Tests: `StripePaymentTest.php`, `PayPalPaymentTest.php`, `WalletTest.php`
- Documentos: `docs/13_TODO_ROADMAP.md`, `docs/15_AI_WORK_LOG.md`, `CHANGELOG.md`

### Pruebas realizadas
- Ejecución de la suite completa de pruebas:
  ```bash
  php artisan test
  ```
  **Resultado**: `69 passed (240 assertions)`. Suite verde y limpia, sin regresiones.

### Pendientes
- Fase 9 — Gestión de Depósitos (holds y autorizaciones).

---

<!-- Nuevas entradas se agregan abajo, sin borrar las anteriores. -->

