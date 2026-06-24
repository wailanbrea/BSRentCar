# 01 — PROJECT CONTEXT · RentCar E-Commerce

## 1. ¿Qué es RentCar E-Commerce?

Una plataforma web (con API lista para una app móvil futura) que permite a una
empresa de renta de vehículos vender y operar rentas en línea de extremo a
extremo: desde que el cliente descubre un vehículo hasta que lo devuelve, firma
el contrato, paga, deja un depósito y califica la experiencia.

No es solo un catálogo: es un sistema operativo de renta que cubre pagos,
depósitos, wallet interna, entregas, inspecciones de salida/retorno, contratos
digitales, auditoría y reportes.

## 2. Problema que resuelve

Las rentadoras pequeñas y medianas suelen operar con WhatsApp, hojas de cálculo y
cobros manuales. Eso provoca:

- Dobles reservas y conflictos de disponibilidad.
- Cobros y depósitos sin trazabilidad.
- Falta de contratos firmados y de evidencia (fotos) del estado del vehículo.
- Cero historial financiero conciliable.
- Mala experiencia para el cliente (sin self-service).

RentCar centraliza todo esto con reglas claras, pagos tokenizados, evidencia
fotográfica e historial auditable.

## 3. Usuarios del sistema

| Rol | Descripción |
|-----|-------------|
| **Cliente (customer)** | Renta vehículos. Se registra, sube documentos, paga, firma, califica. |
| **Administrador (admin)** | Control total: vehículos, reservas, pagos, configuración. |
| **Operador / Staff** | (Opcional) gestiona entregas e inspecciones; permisos acotados. |
| **Agente de entrega (driver)** | (Opcional) responsable de entregar/recibir vehículos. |
| **Sistema** | Procesos automáticos: liberación de depósitos, expiración de reservas, webhooks. |

> Los roles exactos y sus permisos se definen con Spatie Permission. Ver `11_SECURITY.md`.

## 4. Flujo general del cliente

```txt
Registro / Login
  → Completar perfil + subir documentos (licencia, ID)
  → Vincular método de pago (Stripe/PayPal) y/o usar wallet
  → Explorar catálogo y filtrar (fecha, precio, categoría, transmisión, pasajeros, ubicación)
  → Ver detalle del vehículo (fotos, características, reglas, precio, calificaciones)
  → Seleccionar fechas/horas y punto de entrega/devolución
  → Checkout: pagar renta + pagar/autorizar depósito
  → Firmar contrato digital
  → Recibir vehículo (inspección inicial)
  → Usar el vehículo
  → Devolver (inspección final) → liberación/captura de depósito
  → Calificar experiencia (1–5)
```

## 5. Flujo general del administrador

```txt
Login admin (2FA)
  → Dashboard (KPIs: reservas, ingresos, ocupación)
  → Gestionar vehículos, fotos, precios, disponibilidad
  → Revisar/verificar clientes y documentos
  → Gestionar reservas (confirmar, cancelar, reprogramar)
  → Gestionar pagos, depósitos, reembolsos, penalidades, wallet
  → Asignar entregas y responsables
  → Registrar inspecciones de salida y retorno
  → Generar / archivar contratos
  → Ver reportes y auditoría
  → Gestionar calificaciones y configuración general
```

## 6. Alcance MVP

Objetivo del MVP: un cliente puede rentar un coche y pagarlo en línea, y el admin
puede operar esa renta con trazabilidad.

- Auth (registro, login, roles).
- Perfil de cliente + carga de documentos.
- Catálogo + filtros + detalle de vehículo.
- Disponibilidad por rango de fechas (anti-doble-reserva).
- Reserva con punto de entrega/devolución.
- Pago de renta con Stripe **y** PayPal (abstracción de pasarela).
- Depósito (cobrado o autorizado).
- Contrato PDF generado y "firmado" (aceptación digital).
- Inspección inicial/final con fotos.
- Wallet interna básica + historial de pagos.
- Calificaciones de reservas completadas.
- Panel admin para vehículos, reservas, pagos y clientes.
- Auditoría básica.

## 7. Alcance futuro

- App móvil nativa (Kotlin / Jetpack Compose).
- Verificación de identidad automatizada (KYC).
- Multiempresa / multisucursal.
- Zonas y tarifas de entrega dinámicas, geocoding/mapas.
- Notificaciones por WhatsApp / push.
- Seguro opcional y add-ons.
- Programa de fidelidad / promo credits avanzados.
- Reportería avanzada y BI.

## 8. Módulos principales

`Auth · Customers · Vehicles · Catalog · Reservations · Payments · Wallet ·
Deposits · Deliveries · Inspections · Contracts · Reviews · Invoices · Reports ·
Notifications · Settings · Audit · Admin`

Detalle en `05_MODULES.md`.

## 9. Riesgos principales

| Riesgo | Mitigación |
|--------|-----------|
| Doble reserva | Validación de disponibilidad por rango **dentro de transacción** + lock. Ver `10_RESERVATIONS_FLOW.md`. |
| Errores financieros (float) | Decimal/centavos, nunca float. Auditoría financiera. Ver `09_PAYMENTS_WALLET.md`. |
| Fraude / chargebacks | Tokenización, depósitos autorizados, verificación de documentos, contratos. |
| Webhooks duplicados | Idempotencia + validación de firma. Ver `17_PAYMENT_PROVIDERS.md`. |
| Fuga de datos sensibles | Storage privado para documentos/contratos/inspecciones, HTTPS, roles. Ver `11_SECURITY.md`. |
| Acoplamiento a un proveedor de pago | `PaymentGatewayInterface` con implementaciones Stripe/PayPal. |
| Pérdida de contexto entre IA | Documentación como fuente de verdad + logs obligatorios. |

## 10. Visión a largo plazo

Convertir RentCar en una plataforma SaaS multiempresa que cualquier rentadora
pueda usar: catálogo, pagos, contratos, inspecciones y operación móvil, con
trazabilidad financiera completa y experiencia de cliente de nivel e-commerce.
