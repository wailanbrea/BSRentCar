# CHANGELOG · RentCar E-Commerce

Todos los cambios notables del proyecto se documentan aquí.
Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.1.0/);
versionado [SemVer](https://semver.org/lang/es/).

## [Unreleased]

### Added
- **Fase 0 — Documentación base** (2026-06-24):
  - Carpeta `docs/` con 21 documentos (`00`–`20`): índice maestro, contexto,
    reglas de negocio, stack, esquema de BD (29 tablas), módulos, contratos de
    API, guía frontend con design system, panel admin, pagos/wallet, flujo de
    reservas, seguridad, testing, roadmap, decisiones, work log, handoff prompt,
    payment providers, guía de despliegue, variables de entorno y preguntas abiertas.
  - `README.md`, `AI_RULES.md`, `CHANGELOG.md` en la raíz.
  - Design system derivado del mockup de referencia entregado por el usuario
    (royal blue `#2563EB`, secciones navy `#0B1437`, cards `rounded-2xl`,
    tipografía Poppins/Inter, layout mobile-first).
  - 8 decisiones iniciales registradas en `docs/14_DECISIONS_LOG.md`.

- **Decisiones de negocio/técnicas según estándares RD** (2026-06-24): ITBIS 18%, moneda DOP, efectivo/transferencia (manual), depósito siempre con tarjeta, km ilimitado, combustible lleno-a-lleno, retraso/cancelación, seguro RC+opcionales, multi-sucursal; Blade+Livewire selectivo, Stripe Payment Intents, PayPal Orders API, DomPDF, firma simple (Ley 126-02). Ver `docs/14_DECISIONS_LOG.md`.
- **Fase 1 — Proyecto Laravel base** (2026-06-24):
  - Laravel 12.62 instalado en la raíz; MySQL `rentcar` (MariaDB 10.4) configurado y migrado.
  - Sanctum (^4.3) y Spatie Permission (^6.25) instalados, publicados y cableados en `User` (`HasApiTokens`, `HasRoles`).
  - Alpine.js añadido sobre Tailwind v4 + Vite; build de producción OK.
  - Estructura modular de carpetas (`Enums`, `Services`, etc.); `storage:link`; suite base verde; guzzle parcheado.

- **Fase 2 — Autenticación y roles** (2026-06-24):
  - API `/api/v1/auth/{register,login,logout,me}` con Sanctum (Form Requests + `UserResource`).
  - Seeder de roles (admin/staff/driver/customer) y 22 permisos; admin demo `admin@rentcar.test`.
  - Middleware de roles (`role`/`permission`/`role_or_permission`) registrado.
  - Tests de auth (8) + suite total verde (10). Testing aislado en SQLite `:memory:`.

- **Fase 3 — Clientes** (2026-06-24):
  - Tablas `customers` y `customer_documents`; modelos + enums (`VerificationStatus`, `DocumentType`, `DocumentStatus`).
  - `CustomerService` + endpoints `/api/v1/customer/{profile,documents}` con storage privado y validación de archivos.
  - Perfil creado al registrarse; helper de elegibilidad (edad 18 + licencia aprobada).
  - Tests (8) + suite total verde (18).

- **Fase 4 — Vehículos y catálogo** (2026-06-24):
  - Tablas `locations`, `vehicles`, `vehicle_images`, `vehicle_features`, `vehicle_price_rules`, `vehicle_availability_blocks`; enums + modelos.
  - `AvailabilityService` (solape por rango) + `PricingService` (reglas de precio, BCMath).
  - Catálogo público `/api/v1/vehicles` con filtros (fecha/precio/categoría/transmisión/pasajeros/ubicación), detalle y `availability` con cotización.
  - CRUD admin de vehículos + fotos (`/api/v1/admin/vehicles`, permisos Spatie).
  - Tests: catálogo (9), admin (7), pricing (3). Suite total verde (37).

- **Fase 5 — Reservas** (2026-06-24):
  - Tablas `reservations` y `reservation_status_logs`; modelos y enums asociados.
  - `ReservationStateMachine` para gestionar transiciones y logs de cambio de estados.
  - `ReservationService` (crear con cotización + ITBIS 18%, cancelar, confirmar).
  - Bloqueo pesimista anti-doble-reserva (`lockForUpdate` + revalidación atómica en `markAsPaid`).
  - Endpoints cliente (`/customer/reservations`) y admin (`/admin/reservations`).
  - Tests de reservas (8) -> suite total de 45 tests verdes.

- **Fase 6 — Integración Stripe** (2026-06-24):
  - Pasarela unificada `StripePaymentGateway` implementando `PaymentGatewayInterface`.
  - Tablas `payments`, `payment_attempts`, `payment_methods` para auditoría y persistencia.
  - Endpoints de creación y confirmación de intents Stripe.
  - Webhooks de Stripe validados criptográficamente e idempotentes.
  - Tests de Stripe (8) -> suite total de 53 tests verdes.

- **Fase 7 — Integración PayPal** (2026-06-24):
  - Pasarela unificada `PayPalPaymentGateway` vía API REST v2 (directamente con el cliente HTTP nativo de Laravel).
  - Integración de webhooks PayPal con validación de firmas criptográficas.
  - Endpoints de creación y confirmación de órdenes PayPal.
  - Transición de la moneda del sistema a USD por defecto (`DEFAULT_CURRENCY=USD`).
  - Tests de PayPal (8) -> suite total de 61 tests verdes.

- **Fase 8 — Sistema de Billetera (Wallet)** (2026-06-24):
  - Tablas `wallets` y `wallet_transactions` con histórico de movimientos auditables.
  - `WalletService` con bloqueos pesimistas para concurrencia, crédito/débito y recálculo automático de balances.
  - Billetera integrada como pasarela unificada (`WalletPaymentGateway`).
  - Soporte para co-pagos y pagos parciales (ej. parte con balance de billetera y parte con Stripe/PayPal).
  - Recargas de billetera utilizando Stripe/PayPal (`wallet_topup` type).
  - Endpoints cliente (`GET /customer/wallet`, `POST /customer/wallet/topup`) y admin (`POST /admin/customers/{id}/wallet/adjust`).
  - Tests de Wallet (8) -> suite total de 69 tests verdes.

### Pendiente
- 2FA admin (con el panel web) y configurar Pint.
- Verificación de documentos por admin y descarga con URL firmada.
- Web UI (Blade) de catálogo y panel admin.
- Resolver últimas preguntas abiertas (WhatsApp, S3 en prod) — no bloquean (`docs/20_OPEN_QUESTIONS.md`).

---

> Cada release futuro debe añadir su sección con `Added / Changed / Fixed /
> Removed / Security`. No borrar entradas anteriores.
