# 20 — OPEN QUESTIONS · RentCar E-Commerce

> Decisiones pendientes. **No asumir respuestas**: cuando se resuelvan, registrar
> la decisión en `14_DECISIONS_LOG.md`, actualizar los docs afectados y mover la
> pregunta a "Resueltas".

---

## Negocio

- [x] ¿Cuál será la edad mínima para rentar? → **18 años** (2026-06-24).
- [x] ¿Se exigirá licencia de conducir verificada **antes** de pagar? → **Sí** (2026-06-24).
- [x] ¿Se validará identidad manualmente o con KYC automatizado? → **Manual en MVP** (2026-06-24).
- [x] ¿Se permitirá pagar sin verificación previa del cliente? → **No** (licencia aprobada obligatoria antes de pagar).
- [x] ¿El depósito será **cobrado** o **autorizado** (hold)? → **Autorizado (hold) por defecto** (2026-06-24).
- [x] ¿Se aceptará efectivo? → **Sí, solo para la renta** (pago en oficina, confirmado por admin); **no para depósito** (estándar RD) (2026-06-24).
- [x] ¿Se aceptará transferencia bancaria? → **Sí, con verificación/conciliación manual** del admin (2026-06-24).
- [x] ¿La entrega tendrá zonas y tarifas? → **Sí: zonas geofence dibujadas en mapa por el admin, entrega a domicilio si está en zona aceptada, cobro extra por distancia, puntos comerciales y ventanas horarias configurables** (2026-06-24).
- [x] **¿Qué proveedor de mapa?** → **Leaflet + OpenStreetMap** (gratis, sin API key) para MVP, **detrás de una abstracción** que permita cambiar a Google/Mapbox en el futuro (2026-06-24).
- [x] **¿Cómo se mide la distancia?** → **Línea recta (Haversine)** en MVP, **detrás de `DistanceCalculatorInterface`** para poder cambiar a distancia de ruta real (API de routing) en el futuro (2026-06-24).
- [x] **¿Geocoding o pin manual?** → **Pin manual** del cliente en el mapa como base del MVP; búsqueda de dirección con **Nominatim (OSM)** opcional. (2026-06-24).
- [x] ¿Kilometraje ilimitado o con límite? → **Ilimitado por defecto** (estándar RD), override por vehículo (2026-06-24).
- [x] ¿Cómo se cobrará el combustible faltante? → **Lleno a lleno** + cargo por reabastecimiento (`fuel_service_fee`) (2026-06-24).
- [x] ¿Cómo se cobrará el retraso? → **Gracia 59 min, luego 1 día adicional** por periodo iniciado (2026-06-24).
- [x] ¿Política de cancelación? → **Escalonada**: ≥48h 100%; 48–24h 1 día; <24h/no-show 1 día o 20% (el mayor) (2026-06-24).
- [x] ¿Seguro opcional? → **RC básico incluido + opcionales CDW/total/asistencia** (`insurance_plans`) (2026-06-24).
- [x] ¿Sucursales físicas? → **Sí, multi-sucursal** (`locations`) (2026-06-24).
- [x] ¿Multiempresa (SaaS) futuro? → **No en MVP**; diseño no debe bloquearlo (2026-06-24).

## Técnico

- [x] ¿Frontend Blade/Livewire/Inertia? → **Blade + Tailwind + Alpine con Livewire selectivo** (2026-06-24).
- [x] ¿Stripe Checkout o Payment Intents? → **Payment Intents custom** (holds/3DS/wallet) (2026-06-24).
- [x] ¿PayPal Checkout o Orders API? → **Orders API server-side** (authorize/capture) (2026-06-24).
- [x] ¿Contratos DomPDF o Browsershot? → **DomPDF** en MVP; Browsershot a futuro (2026-06-24).
- [x] ¿Firma digital simple o externa? → **Simple** (aceptación + hash SHA-256 + IP + UA + timestamp), válida bajo **Ley 126-02 (RD)**; avanzada a futuro (2026-06-24).
- [x] ¿Moneda única o multi-moneda? → **DOP única** en MVP; multi-moneda (USD) a futuro (2026-06-24).
- [x] ¿Convención monetaria? → **`decimal(12,2)`** en BD + cálculos exactos (BCMath); nunca float (2026-06-24).
- [ ] ¿Se enviarán notificaciones por WhatsApp? ¿Qué proveedor (API oficial/Twilio)?
- [ ] ¿Storage local privado o S3 privado en producción? (recomendado: local en MVP, S3 privado en prod)

## Resueltas
*(Mover aquí con fecha y enlace a la entrada de `14_DECISIONS_LOG.md`.)*

- 2026-06-24 — Stack base (Laravel 12 + MySQL + Stripe/PayPal + docs como fuente de verdad + no guardar tarjetas + dinero sin float). Ver `14_DECISIONS_LOG.md`.
- 2026-06-24 — Elegibilidad y operación: edad mínima 18, licencia verificada antes de pagar (manual), depósito autorizado (hold) por defecto, entregas por zonas con tarifa. Ver `14_DECISIONS_LOG.md`.
- 2026-06-24 — Mapas y distancia: Leaflet + OpenStreetMap y distancia Haversine en MVP, ambos detrás de abstracción para cambiar a futuro; pin manual + Nominatim opcional. Ver `14_DECISIONS_LOG.md`.
- 2026-06-24 — Estándares RD: efectivo (solo renta) + transferencia (manual), depósito siempre con tarjeta, ITBIS 18%, moneda DOP, km ilimitado, combustible lleno-a-lleno, retraso gracia 59min, cancelación escalonada, seguro RC+opcionales, multi-sucursal, no multi-tenant; técnicas: Blade+Livewire selectivo, Stripe Payment Intents, PayPal Orders API, DomPDF, firma simple (Ley 126-02), decimal(12,2). Ver `14_DECISIONS_LOG.md`.

### Quedan abiertas (no bloquean Fase 1)
- Notificaciones por WhatsApp (proveedor).
- Storage S3 privado en producción (local en MVP).
