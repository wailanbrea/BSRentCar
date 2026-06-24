# 09 — PAYMENTS & WALLET · RentCar E-Commerce

> Lógica financiera. **Dinero: nunca `float`.** Usar `decimal(12,2)` en BD o
> enteros en centavos; cálculos en servicios con tipos exactos (BCMath / enteros).
> Cambiar pagos obliga a actualizar este archivo y `17_PAYMENT_PROVIDERS.md`.

---

## 1. Tarjeta guardada vs. Wallet

| | **Método de pago guardado** | **Wallet interna** |
|--|------------------------------|--------------------|
| Qué es | Token de Stripe/PayPal de una tarjeta o cuenta PayPal del cliente | Saldo interno de la plataforma |
| Dónde vive el dinero | En el proveedor / banco del cliente | En el ledger de RentCar (`wallet_transactions`) |
| Se usa para | Cobrar renta y depósito directamente al proveedor | Pagar/co-pagar reservas, recibir reembolsos, promo credits, ajustes |
| Datos sensibles | Solo token, nunca PAN | N/A |

El cliente puede pagar con: método del proveedor, wallet, o combinación
(wallet cubre parte, proveedor el resto).

### Métodos de pago aceptados (estándar RD)
| Método | `provider` | Notas |
|--------|-----------|-------|
| Tarjeta crédito/débito | `stripe` | Tokenizada; soporta 3DS y hold de depósito. |
| PayPal | `paypal` | Orders API. |
| Wallet interna | `wallet` | Saldo del cliente. |
| **Efectivo en oficina** | `manual` (subtype `cash`) | Pago de la **renta** en sucursal; lo confirma el admin. **No** válido para depósito. |
| **Transferencia bancaria** | `manual` (subtype `bank_transfer`) | Verificación/conciliación manual del admin antes de marcar `paid`. |

> **Regla crítica (BR-P11):** el **depósito de seguridad SIEMPRE** se hace con
> **tarjeta de crédito (hold)**, aunque la renta se pague en efectivo o
> transferencia. No se aceptan depósitos en efectivo (norma de rentadoras en RD).

> **Impuesto (BR-P12):** ITBIS **18%** sobre el servicio de alquiler; la factura
> desglosa subtotal + ITBIS. Moneda base **DOP**.

---

## 2. Reglas de wallet

- Una wallet por cliente (`wallets.customer_id` unique).
- Toda variación de saldo es un registro en `wallet_transactions` con `balance_after`.
- El saldo nunca se modifica "a mano" sin transacción correspondiente (auditable).
- La wallet no puede quedar negativa salvo `manual_adjustment` explícito de admin (auditado).
- Operaciones de wallet ocurren dentro de transacción de BD con bloqueo de la fila `wallets`.

### Tipos de `wallet_transactions`
```txt
credit              (+) ingreso (top-up, promo, reembolso a wallet)
debit               (-) uso de saldo para pagar
refund              (+) reembolso recibido
deposit_hold        (-) retención lógica de depósito (si se modela en wallet)
deposit_release     (+) liberación de retención
penalty_charge      (-) cargo por penalidad
promo_credit        (+) crédito promocional
manual_adjustment   (±) ajuste manual de admin (auditado)
```

---

## 3. Estados de pago (`payments.status` / `reservations.payment_status`)
```txt
pending              creado, sin cobrar
processing           en proceso en el proveedor
requires_action      requiere acción del cliente (3DS / aprobación PayPal)
authorized           autorizado, no capturado (hold)
paid                 capturado/cobrado con éxito
failed               fallido
cancelled            cancelado antes de cobro
refunded             reembolsado totalmente
partially_refunded   reembolsado parcialmente
```

Transiciones típicas:
`pending → processing → (requires_action) → authorized → paid → (partially_refunded|refunded)`
o `pending → processing → failed`.

---

## 4. Flujo de pago de reserva

```txt
1. Cliente confirma checkout de la reserva (pending_payment).
2. Backend abre transacción de BD:
   - SELECT ... FOR UPDATE sobre el vehículo / verificación de solape (BR-R06).
   - Si NO disponible → 409, abortar.
3. Crea PaymentAttempt (initiated) + intent/order en el proveedor (idempotency key).
4. Cliente completa pago (Stripe Intent / PayPal capture).
5. Webhook del proveedor (fuente de verdad) confirma:
   - Crea/actualiza Payment (status=paid), PaymentAttempt=succeeded.
   - Reserva → paid (estado bloqueante), bloquea fechas.
   - Dispara generación de contrato y siguiente paso.
6. Si falla → Payment/Attempt=failed, reserva sigue pending_payment, fechas libres.
```

> Idempotencia: webhooks identifican el evento por su id; si ya se procesó, se ignora.

---

## 5. Flujo de depósito (`deposit_transactions`)

**Modo por defecto del proyecto: AUTORIZADO (hold)** — ver BR-D00. El depósito se
retiene (no se cobra) al confirmar la reserva y solo se captura ante daños/retrasos/
penalidades; en caso normal se libera. El modo "cobrado" es fallback.

Dos modos (según vehículo/settings/proveedor):

**A) Autorizado (hold):**
```txt
authorize (hold) → reserva activa →
   sin incidencias: release (void/refund de la autorización)
   con incidencias: capture total o partial_capture (resto se libera)
```
**B) Cobrado (charge):**
```txt
charge (capture inmediato) → al final:
   sin incidencias: refund total a método o credit a wallet
   con incidencias: refund parcial (descontando penalidades)
```

Reglas: BR-D01..D06. Una autorización tiene `expires_at`; el Scheduler avisa/actúa
antes del vencimiento.

---

## 6. Flujo de reembolso (`refunds`)

```txt
Solicitud (cancelación dentro de política / captura parcial de depósito / ajuste)
→ RefundService crea refund (pending)
→ llama provider.refundPayment(providerPaymentId, amount)
→ webhook confirma (succeeded) → Payment pasa a refunded|partially_refunded
→ opcional: credit a wallet en lugar de reembolso al método
```

Política de reembolso por cancelación según anticipación (definir en `settings` / `20_OPEN_QUESTIONS.md`).

---

## 7. Flujo de penalidad (`penalties`)

```txt
Inspección final / retraso / combustible faltante detecta cargo
→ crea penalty (pending) con type y amount
→ cobro vía: captura de depósito (deposit) | débito de wallet | cobro a método
→ penalty=charged + transacción correspondiente (penalty_charge)
```

---

## 8. Manejo de errores

- Errores del proveedor se guardan en `payment_attempts` (`error_code`, `error_message`).
- Nunca se exponen mensajes crudos del proveedor al cliente sin mapear.
- `requires_action` (3DS / aprobación PayPal): el front guía al cliente.
- Reintentos controlados; nunca duplicar cobros (idempotency keys + webhooks idempotentes).
- Timeouts: el estado real lo determina el webhook, no la respuesta síncrona.

---

## 9. Conciliación

- Cada `payment` referencia IDs externos (`provider_payment_id`, `provider_order_id`, `provider_capture_id`).
- Job/Scheduler periódico compara estados internos vs. proveedor.
- Reporte de conciliación: pagos sin webhook, autorizaciones por vencer, refunds pendientes.

---

## 10. Auditoría financiera

- Toda operación financiera (pago, refund, depósito, penalidad, ajuste de wallet) registra `audit_logs` y/o `*_transactions`.
- Registros financieros son **append-only** (no se editan ni borran; se corrige con una transacción inversa).
- Montos siempre con `currency` explícita.

---

## 11. Reglas de dinero (recordatorio crítico)

- **Prohibido `float`** en cálculos monetarios.
- BD: `decimal(12,2)` o columnas `*_cents` (int). Elegir una convención y mantenerla; ver decisión en `14_DECISIONS_LOG.md`.
- Cálculos: BCMath (`bcadd`, `bcmul`...) o aritmética entera en centavos.
- Redondeo explícito y documentado (HALF_UP por defecto).
- Conversión de moneda (si aplica en futuro): tasa registrada en el momento de la transacción.
