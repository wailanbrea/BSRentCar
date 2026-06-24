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

### Pendiente
- 2FA admin (con el panel web) y configurar Pint.
- Verificación de documentos por admin y descarga con URL firmada.
- Web UI (Blade) de catálogo y panel admin.
- Resolver últimas preguntas abiertas (WhatsApp, S3 en prod) — no bloquean (`docs/20_OPEN_QUESTIONS.md`).

---

> Cada release futuro debe añadir su sección con `Added / Changed / Fixed /
> Removed / Security`. No borrar entradas anteriores.
