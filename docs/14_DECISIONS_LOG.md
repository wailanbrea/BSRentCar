# 14 — DECISIONS LOG · RentCar E-Commerce

> Bitácora de decisiones técnicas y de arquitectura. **No borrar entradas.**
> Formato obligatorio por entrada:

```md
## YYYY-MM-DD - Título

### Decisión

### Motivo

### Impacto

### Archivos afectados

### IA / Desarrollador
```

---

## 2026-06-24 - Usar Laravel 12 + PHP 8.3

### Decisión
Backend en Laravel 12 sobre PHP 8.3+.

### Motivo
Stack requerido por el cliente; ecosistema maduro (Sanctum, Queues, Scheduler, Eloquent, Enums nativos).

### Impacto
Define convenciones de framework, estructura modular y herramientas (Pest/Pint).

### Archivos afectados
`03_TECH_STACK.md`, `05_MODULES.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - Base de datos MySQL (InnoDB)

### Decisión
Usar MySQL 8 con InnoDB.

### Motivo
Transacciones y FKs necesarias para anti-doble-reserva y consistencia financiera.

### Impacto
Migraciones con FKs e índices; locks pesimistas en reservas.

### Archivos afectados
`03_TECH_STACK.md`, `04_DATABASE_SCHEMA.md`, `10_RESERVATIONS_FLOW.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - Soportar Stripe y PayPal con abstracción de pasarela

### Decisión
Integrar Stripe y PayPal detrás de `PaymentGatewayInterface`; la lógica de negocio no llama SDKs directamente.

### Motivo
Evitar acoplamiento a un proveedor; permitir cambiar/añadir pasarelas.

### Impacto
`PaymentService`, `DepositService`, `RefundService`, `WalletService` y gateways concretos.

### Archivos afectados
`09_PAYMENTS_WALLET.md`, `17_PAYMENT_PROVIDERS.md`, `05_MODULES.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - Documentación en /docs como fuente de verdad

### Decisión
Toda la memoria técnica vive en `docs/*.md` + `README.md`, `AI_RULES.md`, `CHANGELOG.md`. Ciclo de trabajo y prohibiciones obligatorias para toda IA.

### Motivo
Permitir que múltiples IA (Claude, Codex, Gemini, ChatGPT) colaboren sin perder contexto.

### Impacto
Cada cambio de código exige actualizar la documentación correspondiente.

### Archivos afectados
Todos los `docs/*`, `AI_RULES.md`, `00_MASTER_INDEX.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - No almacenar tarjetas reales (tokenización)

### Decisión
Nunca guardar PAN/CVV. Solo tokens/IDs de Stripe/PayPal. Alcance PCI SAQ-A.

### Motivo
Seguridad y cumplimiento; reducir superficie de riesgo.

### Impacto
`payment_methods` guarda solo tokens; tokenización en cliente.

### Archivos afectados
`02_BUSINESS_RULES.md`, `11_SECURITY.md`, `04_DATABASE_SCHEMA.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - API preparada para app móvil Kotlin futura

### Decisión
API REST versionada (`/api/v1`) con Sanctum, respuestas JSON estables y errores uniformes, pensada para Kotlin/Compose/Retrofit/Room/Hilt.

### Motivo
Habilitar la app móvil sin rediseñar el backend.

### Impacto
Versionado, paginación y formato de error consistentes desde el inicio.

### Archivos afectados
`03_TECH_STACK.md`, `06_API_CONTRACTS.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - Dinero con decimal(12,2), nunca float

### Decisión
Montos en `decimal(12,2)` (o `*_cents` enteros donde convenga); cálculos con BCMath/enteros. Prohibido `float` en dinero.

### Motivo
Evitar errores de redondeo en cobros, depósitos y wallet.

### Impacto
Todas las columnas y servicios financieros.

### Archivos afectados
`09_PAYMENTS_WALLET.md`, `04_DATABASE_SCHEMA.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - Design system basado en mockup de referencia

### Decisión
Adoptar paleta royal-blue `#2563EB`, secciones navy `#0B1437`, secundario slate, cards `rounded-2xl`, tipografía Poppins/Inter, layout mobile-first, según el mockup entregado.

### Motivo
Alinear la UI con la referencia visual provista por el usuario.

### Impacto
Tokens de Tailwind y componentes Blade.

### Archivos afectados
`07_FRONTEND_GUIDE.md`, `08_ADMIN_PANEL.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - Reglas de elegibilidad, depósito y entregas

### Decisión
- **Edad mínima para rentar: 18 años** (validada a la fecha de inicio de la reserva).
- **Licencia de conducir obligatoria y verificada por el admin ANTES de pagar** (verificación manual en MVP).
- **Depósito en modo AUTORIZADO (hold) por defecto**, no cobrado; captura solo ante daños/retrasos/penalidades.
- **Entregas gestionadas por ZONAS con tarifa** (nuevo catálogo `delivery_zones`).

### Motivo
Definiciones de negocio entregadas por el usuario para desbloquear el diseño de
clientes, checkout, depósitos y logística de entrega.

### Impacto
- Gate de elegibilidad (edad + licencia aprobada) previo al pago en el flujo de reservas.
- Nueva tabla `delivery_zones`; `delivery_requests` referencia zona y deriva la tarifa.
- `DepositService` usa hold por defecto.

### Archivos afectados
`02_BUSINESS_RULES.md` (BR-C08/C09/C10, BR-D00, BR-E00), `04_DATABASE_SCHEMA.md`
(`delivery_zones`, `delivery_requests`), `09_PAYMENTS_WALLET.md`,
`10_RESERVATIONS_FLOW.md`, `20_OPEN_QUESTIONS.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - Entregas por zonas geofence en mapa, cobro por distancia y ventanas horarias

### Decisión
- Las zonas de entrega se definen como **polígonos (geofence) que el admin dibuja en un mapa**; el cliente las ve en un mapa de cobertura (`delivery_zones.polygon` GeoJSON + `color`).
- **Entrega a domicilio** solo si el domicilio cae dentro de una zona que la admite (`allows_home_delivery`).
- **Cobro extra por distancia** para domicilio: `base_fee + max(0, distance_km - free_radius_km) * price_per_km`, con tope `max_distance_km`.
- **Puntos de entrega comerciales** (`delivery_pickup_points`) como alternativa cercana.
- **Ventanas horarias configurables** por el admin (`delivery_time_windows`) con capacidad opcional.

### Motivo
Requisito del usuario: el cliente recibe en casa (si vive en zona aceptada) o en una zona comercial cercana, con costo por distancia, en rangos de hora configurables, y debe ver las zonas permitidas en un mapa que el admin configura.

### Impacto
- Nuevas tablas `delivery_zones` (con polígono/origen/parámetros de distancia), `delivery_pickup_points`, `delivery_time_windows`; `delivery_requests` ampliado.
- Nuevos endpoints `/delivery/zones|pickup-points|time-windows|quote`.
- Componentes frontend `x-delivery-map` y `x-time-window-picker`; pantalla admin "Zonas de entrega" con editor de mapa.
- Requiere proveedor de mapa y método de geolocalización/distancia (pendiente).

### Archivos afectados
`02_BUSINESS_RULES.md` (BR-E00, E06–E10), `04_DATABASE_SCHEMA.md`,
`06_API_CONTRACTS.md`, `07_FRONTEND_GUIDE.md`, `08_ADMIN_PANEL.md`,
`20_OPEN_QUESTIONS.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - Mapas con Leaflet+OSM y distancia Haversine (abstraídos)

### Decisión
- **Proveedor de mapa: Leaflet + OpenStreetMap** (gratis, sin API key) para el editor de zonas del admin y el mapa de cobertura del cliente.
- **Cálculo de distancia: Haversine** (línea recta) para el cobro de entrega a domicilio.
- Ambos **detrás de una abstracción** (`MapProvider` en frontend, `DistanceCalculatorInterface` en backend) para poder cambiar a Google/Mapbox o a distancia por ruta en el futuro **sin tocar la lógica de negocio**.
- Geocoding: **pin manual** del cliente como base; búsqueda con **Nominatim (OSM)** opcional.

### Motivo
Minimizar costo y fricción en el MVP (sin API keys ni cargos) manteniendo la puerta abierta a proveedores más robustos más adelante. El usuario lo pidió explícitamente "con opción a cambiar en futuro".

### Impacto
- Frontend: `x-delivery-map` usa Leaflet/OSM; sin dependencia de API keys.
- Backend: `DistanceCalculatorInterface` con `HaversineDistanceCalculator` (futuro: `RoutingDistanceCalculator`). `DeliveryPricingService` consume la interfaz.
- La elegibilidad de domicilio y la tarifa por distancia se calculan con Haversine entre `delivery_zones.origin_*` y el domicilio.

### Archivos afectados
`03_TECH_STACK.md`, `05_MODULES.md`, `07_FRONTEND_GUIDE.md`, `20_OPEN_QUESTIONS.md`.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

## 2026-06-24 - Reglas de negocio y técnicas según estándares de RD

### Decisión
**Negocio (fundamentado en estándares de República Dominicana):**
- **Efectivo:** aceptado para la **renta** (pago en oficina, `provider=manual/cash`, confirmado por admin). **No** para depósito.
- **Transferencia bancaria:** aceptada con **verificación/conciliación manual** (`provider=manual/bank_transfer`).
- **Depósito:** SIEMPRE con **tarjeta de crédito (hold)** — en RD no se aceptan depósitos en efectivo.
- **Impuesto:** **ITBIS 18%** sobre el alquiler (DGII); factura desglosa subtotal + ITBIS.
- **Moneda base:** **DOP**; USD multi-moneda a futuro.
- **Kilometraje:** **ilimitado** por defecto (estándar RD), con override por vehículo.
- **Combustible:** **lleno a lleno** + cargo por reabastecimiento si falta.
- **Retraso:** gracia 59 min, luego día adicional por periodo iniciado.
- **Cancelación escalonada:** ≥48h 100% reembolso; 48–24h penalidad 1 día; <24h/no-show 1 día o 20% (el mayor).
- **Seguro:** RC básico incluido (obligatorio en RD) + opcionales CDW/total/asistencia (`insurance_plans`).
- **Sucursales:** soporte multi-sucursal (`locations`: SDQ, PUJ, STI, Santo Domingo).
- **Multiempresa (multi-tenant):** NO en MVP; diseño no debe bloquearlo a futuro.
- **Recargo conductor joven (18–24):** configurable y desactivable (la norma de la industria RD es 21 años; edad mínima del proyecto sigue siendo 18 por decisión del usuario).

**Técnicas (estándares/best practices):**
- **Frontend:** Blade + Tailwind + Alpine con **Livewire selectivo** (no Inertia/SPA en MVP).
- **Stripe:** **Payment Intents custom** (necesario para holds/auth-capture del depósito, 3DS y wallet) — no Stripe Checkout hosted.
- **PayPal:** **Orders API server-side** con authorize/capture — no botones Checkout simples.
- **PDF:** **DomPDF** para contratos/facturas en MVP; Browsershot a futuro.
- **Firma digital:** **firma electrónica simple** (aceptación + hash SHA-256 del PDF + IP + user-agent + timestamp), con **validez legal bajo Ley 126-02 de RD**. Firma avanzada (ECD/INDOTEL) a futuro.

### Motivo
El usuario pidió fijar los pendientes según estándares de RD. Investigación web
confirmó: ITBIS 18% (DGII) aplica a alquiler de vehículos; las rentadoras en RD
exigen tarjeta de crédito para el depósito y no aceptan efectivo para depósito;
seguro de RC obligatorio + coberturas opcionales; Ley 126-02 da validez legal a la
firma electrónica.

### Impacto
- Nuevas tablas `locations`, `insurance_plans`; `reservations` referencia ambas; `payments.provider_subtype` (cash/bank_transfer); nuevos `settings` (tax_rate, late_grace_minutes, fuel_service_fee, young_driver_fee, cancellation_policy, mileage_unlimited, default_currency=DOP).
- `PaymentService` maneja pagos manuales (efectivo/transferencia) con verificación admin; `DepositService` exige tarjeta.
- `ContractService` (DomPDF) genera PDF y registra firma simple con hash/IP/UA.
- Frontend con Livewire selectivo; checkout integra selección de seguro y sucursal.

### Archivos afectados
`02_BUSINESS_RULES.md` (BR-P10..P13, BR-X03/X04, BR-O01..O03, BR-S01..S03, BR-L01..L03),
`03_TECH_STACK.md`, `04_DATABASE_SCHEMA.md`, `09_PAYMENTS_WALLET.md`,
`19_ENVIRONMENT_VARIABLES.md`, `20_OPEN_QUESTIONS.md`.

### Fuentes
- DGII / Pellerano & Herrera — ITBIS 18% sobre servicios de alquiler.
- Términos de rentadoras en RD (MEX, Budget/abglac, thebestofdr) — edad, tarjeta para depósito, seguro.
- INDOTEL / Ley 126-02 sobre Comercio Electrónico, Documentos y Firmas Digitales.

### IA / Desarrollador
Claude Code (Opus 4.8)

---

<!-- Próximas decisiones se agregan abajo, sin borrar las anteriores. -->
