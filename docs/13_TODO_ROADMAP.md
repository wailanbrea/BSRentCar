# 13 — TODO & ROADMAP · RentCar E-Commerce

> Marca tareas `[x]` al completarlas y registra el trabajo en `15_AI_WORK_LOG.md`.
> Completar tareas obliga a actualizar este archivo.

Leyenda: `[ ]` pendiente · `[~]` en progreso · `[x]` hecho

---

## Fase 0 — Documentación y arquitectura
- [x] Crear carpeta `docs/` y los 21 archivos `.md`.
- [x] Crear `README.md`, `AI_RULES.md`, `CHANGELOG.md`.
- [x] Definir reglas de negocio, esquema de BD, módulos, API y flujos.
- [x] Definir design system (basado en mockup de referencia).
- [ ] **Aprobación del usuario para iniciar desarrollo.**

## Fase 1 — Base Laravel ✅ (2026-06-24)
- [x] Crear proyecto Laravel 12 en la raíz `BSRentCar` (PHP 8.2.12, Composer 2.8).
- [x] Configurar `.env`, MySQL (`rentcar` en MariaDB 10.4), `APP_KEY`, locale es, DOP/ITBIS.
- [x] Tailwind v4 (incluido) + **Alpine.js** añadido; `npm run build` OK.
- [x] Estructura de carpetas modular (`Enums`, `Services`, `Services/Payments`, `Services/Delivery`, `Http/Controllers/{Api,Admin,Web}`, `Requests`, `Resources`, `Policies`, `Jobs`, `Events`, `Listeners`, `Notifications`).
- [x] Migraciones base aplicadas; `storage:link`; suite base verde (2 tests); guzzle actualizado (sin advisories).
- [ ] Configurar Pint (formato) — pendiente.

## Fase 2 — Autenticación y roles ✅ (2026-06-24)
- [x] Instalar Sanctum (^4.3) + publicar config/migración + trait `HasApiTokens` en `User`.
- [x] Instalar Spatie Permission (^6.25) + publicar config/migración + trait `HasRoles` en `User`.
- [x] Seeder de roles/permisos (`RolesAndPermissionsSeeder`: admin, staff, driver, customer + 22 permisos) + admin demo (`admin@rentcar.test`).
- [x] Endpoints `register/login/logout/me` (Sanctum, prefijo `/api/v1`, Form Requests + `UserResource`).
- [x] Middleware de roles registrado (`role`, `permission`, `role_or_permission`) en `bootstrap/app.php`.
- [x] Tests de auth (8 tests, 27 asserts) + suite total verde (10).
- [ ] 2FA admin (base) — pendiente (se aborda con el panel admin web).
- [ ] Configurar Pint — pendiente.

## Fase 3 — Clientes ✅ (2026-06-24)
- [x] Migraciones `customers`, `customer_documents`.
- [x] Modelos `Customer` (1–1 `User`, soft deletes) y `CustomerDocument`; enums `VerificationStatus`, `DocumentType`, `DocumentStatus`.
- [x] `CustomerService` (crear perfil, actualizar, subir documento a disco privado).
- [x] Perfil `GET/PUT /api/v1/customer/profile`, documentos `GET/POST /api/v1/customer/documents` (storage privado, validación pdf/jpg/png ≤5MB).
- [x] Cliente creado automáticamente al registrarse.
- [x] Helper de elegibilidad (`rentalEligibilityErrors`: edad ≥18 + licencia aprobada, BR-C08/C09).
- [x] Estado de verificación (`verification_status`).
- [x] Tests (8 de cliente) + suite total verde (18).
- [ ] Endpoint de descarga de documento con URL firmada (se hará junto al panel admin / verificación).

## Fase 4 — Vehículos y catálogo ✅ (2026-06-24)
- [x] Migraciones `locations`, `vehicles`, `vehicle_images`, `vehicle_features`, `vehicle_price_rules`, `vehicle_availability_blocks`.
- [x] Enums `VehicleStatus`, `VehicleCategory`, `Transmission`; modelos + relaciones + scopes (`rentable`, `filter`).
- [x] `AvailabilityService` (solape por rango: estado + bloqueos manuales) + `PricingService` (días, reglas de precio, BCMath).
- [x] Catálogo público `GET /vehicles` (filtros fecha/precio/categoría/transmisión/pasajeros/ubicación/orden), `GET /vehicles/{id}`, `GET /vehicles/{id}/availability`.
- [x] CRUD admin de vehículos (`/admin/vehicles`, permisos Spatie) + gestión de fotos (subir/principal/eliminar, disco público).
- [x] Tests: catálogo (9, incluye **filtro por fecha que excluye bloqueados**), admin vehículos (7), `PricingService` unit (3). Suite total: **37 verde**.
- [ ] Vistas Blade del catálogo y panel admin (web UI) — API lista; UI pendiente.
- [ ] Integrar solape con **reservas** en `AvailabilityService` — se completa en Fase 5.

## Fase 5 — Reservas (código completo — ⏳ verificación de tests pendiente)
- [x] Migraciones `reservations`, `reservation_status_logs`.
- [x] Enums `ReservationStatus` (+`blocking()`), `PaymentStatus`, `ContractStatus`, `PickupType`.
- [x] Modelos `Reservation`, `ReservationStatusLog` + relaciones inversas (Vehicle, Customer).
- [x] `ReservationStateMachine` (transiciones válidas + log) y `ReservationService` (crear con cotización+ITBIS, cancelar, confirmar).
- [x] **Anti-doble-reserva en transacción** (`markAsPaid` con `lockForUpdate` + revalidación) + integración de reservas en `AvailabilityService`.
- [x] Gate de elegibilidad (edad/licencia) al crear reserva.
- [x] Endpoints cliente (crear/listar/ver/cancelar) + admin (listar/ver/mark-paid/confirmar) con permisos.
- [x] `config/rentcar.php` (ITBIS, currency, hold minutes, deposit mode); excepciones de dominio.
- [x] Tests escritos (`ReservationTest`, 8 casos: cotización ITBIS, no-elegible, conflicto, **doble reserva**, bordes, ownership, mark-paid, cancelar) — **EJECUTADOS y verificados**.
- [x] Expiración de holds `pending_payment` (`rentcar:expire-reservation-holds` + Scheduler everyFiveMinutes + `ReservationService::expireStaleHolds` + tests).

## Fase 6 — Stripe ✅ (2026-06-24)
- [x] `PaymentGatewayInterface` + `StripePaymentGateway`.
- [x] Create/confirm intent; webhook firmado e idempotente.
- [x] `payments`, `payment_attempts`, `payment_methods`.
- [x] Tests (ok/fallo/webhook).

## Fase 7 — PayPal ✅ (2026-06-24)
- [x] `PayPalPaymentGateway` (Orders API + capture).
- [x] Webhook PayPal verificado e idempotente.
- [x] Tests.

## Fase 8 — Wallet ✅ (2026-06-24)
- [x] Migraciones `wallets`, `wallet_transactions`.
- [x] `WalletService` (credit/debit, reconciliación).
- [x] Endpoints wallet.
- [x] Tests.

## Fase 9 — Depósitos ✅ (2026-06-24)
- [x] Migración `deposit_transactions`.
- [x] `DepositService` (hold/capture/release/partial).
- [x] Vencimiento de autorizaciones (Scheduler).
- [x] Tests.

## Fase 10 — Contratos ✅ (2026-06-24)
- [x] Migración `contracts`.
- [x] `ContractService` (DomPDF/Browsershot) + plantilla.
- [x] Firma/aceptación con metadatos.
- [x] Tests.

## Fase 11 — Entregas ✅ (2026-06-24)
- [x] Migración `delivery_requests`.
- [x] `DeliveryService` + asignación + estados.
- [x] Tests.

## Base 12 — Inspecciones ✅ (2026-06-24)
- [x] Migraciones `vehicle_inspections`, `inspection_photos`.
- [x] `InspectionService` (inicial/final, fotos privadas, sincronización de estados).
- [x] Derivación de penalidades.
- [x] Tests.

## Fase 13 — Calificaciones ✅ (2026-06-24)
- [x] Migración `reviews`.
- [x] `ReviewService` + recálculo de rating.
- [x] Endpoints + autorización (solo reservas completadas; moderación admin).
- [x] Tests (5).

## Fase 14 — Reportes ✅ (2026-06-24)
- [x] `ReportService` (ingresos, ocupación, top vehículos, stats de reservas).
- [x] Endpoints admin (export pendiente para web UI).
- [x] Tests (5).

## Fase 15 — Seguridad
- [ ] Policies completas, rate limiting, URLs firmadas.
- [ ] 2FA admin completo, auditoría.
- [ ] Checklist de `11_SECURITY.md`.

## Fase 16 — Testing
- [ ] Completar suite de `12_TESTING_QA.md`.
- [ ] CI con `php artisan test`.

## Fase 17 — Deploy
- [ ] Provisionar VPS (Nginx/PHP-FPM/MySQL/Redis).
- [ ] HTTPS, Scheduler, queue worker supervisado.
- [ ] Webhooks en producción.
- [ ] Backups automatizados.
- [ ] Seguir `18_DEPLOYMENT_GUIDE.md`.

## Web UI — Panel administrativo (Blade) 🟡 en progreso (2026-06-24)
- [x] Auth web de sesión (login/logout, solo admin/staff) + middleware `EnsureAdmin`.
- [x] Design system en Tailwind (colores del mockup), layout (sidebar navy + topbar), `x-admin.status-badge`.
- [x] Dashboard con KPIs (ReportService) + reservas recientes + top vehículos.
- [x] Vehículos: CRUD completo (index/create/edit).
- [x] Reservas: lista con filtros + detalle con acciones (confirmar pago, confirmar, cancelar).
- [x] Clientes: lista + detalle (perfil/documentos/reservas). Reportes: rango de fechas + KPIs.
- [x] Pagos (lista), Depósitos (lista), Entregas (lista + asignar conductor + cambiar estado), Calificaciones (lista + moderar mostrar/ocultar).
- [x] Inspecciones (lista), Contratos (lista + generar desde reserva + descargar PDF).
- [x] Subida visual de fotos de vehículos (subir / marcar principal / eliminar).
- [x] Tests web (14) — suite total **118 verde**.
- [ ] Configuración y Auditoría — requieren crear tablas `settings`/`audit_logs` (no existen aún).

## Web UI — Cliente (Blade) 🟡 en progreso (2026-06-24)
- [x] Layout público (header + footer del mockup) y componente `x-client.vehicle-card`.
- [x] Home replicando el mockup: hero con gradiente, "Explora ofertas" (grid), servicio premium (navy), proceso de reserva, por qué elegirnos, CTA, testimonios.
- [x] Catálogo con filtros (fecha/categoría/transmisión/precio/orden) + grid + paginación.
- [x] Detalle de vehículo (galería, características, reseñas, panel de precio).
- [x] Tests cliente (4: home, catálogo, filtro, detalle).
- [x] Login/registro del cliente (web) + booking (crear reserva con gate de elegibilidad) + Mi cuenta (dashboard, mis reservas + detalle, wallet, perfil + subida de documentos).
- [x] Tests cliente cuenta (6) — suite total **124 verde**.
- [x] Seeder demo (admin/cliente/driver + 8 vehículos) y servidor de prueba (`php artisan serve`).
- [ ] Pago real en checkout (Stripe Elements / PayPal buttons) — requiere credenciales sandbox; botón "Pagar" deshabilitado por ahora.

## Web UI — Cliente (Blade) — pendiente
- [ ] Home, catálogo con filtros, detalle de vehículo, login/registro, checkout, mis reservas, wallet (docs/07).

## Fase 18 — App móvil futura
- [ ] Versionar y estabilizar API.
- [ ] Documentar contratos para Kotlin/Compose/Retrofit.
- [ ] (No desarrollar aún.)
