# 17 — PAYMENT PROVIDERS · RentCar E-Commerce

> Detalle de Stripe y PayPal y la abstracción interna. Cambiar pagos obliga a
> actualizar este archivo y `09_PAYMENTS_WALLET.md`. **Dinero en centavos/decimal,
> nunca float.**

---

## 1. Stripe

### Conceptos usados
- **PaymentIntents:** cobro de renta y de depósito (modo charge). Estados:
  `requires_payment_method → requires_confirmation → requires_action (3DS) → processing → succeeded`.
- **SetupIntents:** guardar un método de pago para uso futuro sin cobrar.
- **Customer:** representa al cliente en Stripe (`provider_customer_id`).
- **PaymentMethods:** tarjetas tokenizadas asociadas al Customer.
- **Authorization / Capture:** para **depósito autorizado** usar PaymentIntent con
  `capture_method=manual` → `authorize` (hold) → `capture` total/parcial o `cancel` (release).
- **3D Secure:** manejado por `requires_action` + confirmación en cliente.
- **Refunds:** total o parcial sobre un charge/PaymentIntent.
- **Webhooks:** fuente de verdad. Eventos clave:
  `payment_intent.succeeded`, `payment_intent.payment_failed`,
  `payment_intent.amount_capturable_updated`, `charge.refunded`,
  `payment_intent.canceled`.
  Validar con `STRIPE_WEBHOOK_SECRET` (`Stripe-Signature`).
- **Metadata:** adjuntar `reservation_id`, `customer_id`, `payment_type`.
- **Idempotency keys:** en cada creación (intent/refund) para evitar duplicados.

### Mapeo a estados internos
| Stripe | Interno (`payments.status`) |
|--------|------------------------------|
| `requires_action` | `requires_action` |
| `processing` | `processing` |
| `requires_capture` | `authorized` |
| `succeeded` | `paid` |
| `canceled` | `cancelled` |
| `payment_failed` | `failed` |
| refunded (total/parcial) | `refunded` / `partially_refunded` |

### Depósito con Stripe (autorizado)
```txt
createPayment(capture_method=manual, amount=deposit) → requires_capture (authorized)
  sin incidencias: cancel() → release
  con incidencias: capture(amount_parcial) → captura parcial, resto liberado
```

---

## 2. PayPal

### Conceptos usados
- **Orders API v2:** `create order` (intent `CAPTURE` o `AUTHORIZE`) → cliente aprueba (`approve_url`) → `capture`/`authorize`.
- **Capture:** cobro de la orden aprobada.
- **Authorize + Capture:** para **depósito autorizado** usar intent `AUTHORIZE` → `authorization` → `capture` o `void` (release).
- **Vaulting:** guardar método de pago PayPal para reuso (si aplica).
- **Refunds:** sobre una capture (total/parcial).
- **Webhooks:** verificar con `PAYPAL_WEBHOOK_ID` (verify-webhook-signature API). Eventos clave:
  `CHECKOUT.ORDER.APPROVED`, `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.DENIED`,
  `PAYMENT.CAPTURE.REFUNDED`, `PAYMENT.AUTHORIZATION.CREATED/VOIDED`.
- **Metadata:** `custom_id` (reservation_id), `invoice_id` (invoice number), `description`.

### Mapeo a estados internos
| PayPal | Interno |
|--------|---------|
| order `CREATED`/`APPROVED` | `processing` / `requires_action` |
| authorization creada | `authorized` |
| capture `COMPLETED` | `paid` |
| capture `DENIED` | `failed` |
| refund | `refunded` / `partially_refunded` |
| order `VOIDED` | `cancelled` |

---

## 3. Abstracción interna

La lógica de negocio **nunca** llama a los SDKs directamente. Usa servicios:

```txt
PaymentGatewayInterface     (contrato común)
  ├─ StripePaymentGateway   (implementación Stripe)
  └─ PayPalPaymentGateway   (implementación PayPal)

PaymentService   orquesta cobros de renta (elige gateway, registra payments/attempts)
DepositService   orquesta hold/capture/release de depósitos
RefundService    orquesta reembolsos
WalletService    aplica créditos/débitos a la wallet
```

### Interfaz sugerida
```php
interface PaymentGatewayInterface
{
    public function createPayment(array $data): PaymentGatewayResponse;
    public function capturePayment(string $providerPaymentId): PaymentGatewayResponse;
    public function refundPayment(string $providerPaymentId, float $amount): PaymentGatewayResponse;
    public function createCustomer(array $data): PaymentGatewayResponse;
    public function savePaymentMethod(array $data): PaymentGatewayResponse;
}
```

> **Nota sobre el tipo `float` en la firma:** la firma anterior viene del Master
> Prompt como referencia conceptual. En la implementación real, los montos deben
> manejarse en **centavos (int)** o **decimal seguro / string**, no en `float`.
> Recomendación: `refundPayment(string $providerPaymentId, int $amountCents)` o un
> Value Object `Money`. Documentar la elección final en `14_DECISIONS_LOG.md`.

### `PaymentGatewayResponse` (DTO sugerido)
```txt
success: bool
status: string            (mapeado a estado interno)
provider: string          (stripe|paypal)
provider_payment_id: ?string
provider_order_id: ?string
provider_capture_id: ?string
amount: int|string        (centavos o decimal string)
currency: string
requires_action: bool
action_url|client_secret: ?string
raw: array                (respuesta cruda segura)
error_code: ?string
error_message: ?string
```

### Reglas comunes a ambos proveedores
- Registrar **siempre**: proveedor, IDs externos, estado externo, monto, moneda, metadata, error si falla, fecha/hora (en `payments` y `payment_attempts`).
- **Idempotencia** en creación de pagos/refunds y en el procesamiento de webhooks (`event_id` único).
- **Validar firma** de webhooks antes de procesar.
- No exponer respuestas crudas del proveedor al cliente; mapear errores.
- La confirmación final de un cobro la dicta el **webhook**, no la respuesta síncrona.

---

## 4. Selección de proveedor
- El cliente elige Stripe o PayPal en el checkout.
- `PaymentService` resuelve la implementación según `provider` (factory / container binding).
- Decisión Stripe Checkout vs. Payment Intents custom, y PayPal Checkout vs. Orders API custom: **pendiente** en `20_OPEN_QUESTIONS.md`.
