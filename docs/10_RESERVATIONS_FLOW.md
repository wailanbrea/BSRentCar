# 10 — RESERVATIONS FLOW · RentCar E-Commerce

> Máquina de estados y flujos de la reserva. **Regla crítica:** antes de marcar
> una reserva como pagada/confirmada, revalidar disponibilidad **dentro de una
> transacción de BD** con bloqueo. Cambios aquí obligan a actualizar
> `02_BUSINESS_RULES.md` y `04_DATABASE_SCHEMA.md`.

---

## 1. Estados (`reservations.reservation_status`)

```txt
draft                creada sin confirmar (carrito)
pending_payment      esperando pago
paid                 pago de renta confirmado (BLOQUEA)
confirmed            confirmada por sistema/admin (BLOQUEA)
in_preparation       alistando el vehículo (BLOQUEA)
contract_pending     esperando firma de contrato
contract_signed      contrato firmado (BLOQUEA)
delivery_assigned    entrega asignada (BLOQUEA)
delivered            vehículo entregado (BLOQUEA)
active               renta en curso (BLOQUEA)
return_pending       devolución pendiente (BLOQUEA)
returned             vehículo devuelto
inspection_pending   inspección final pendiente
completed            finalizada (habilita review)
cancelled            cancelada (NO bloquea)
refunded             reembolsada (NO bloquea)
no_show              cliente no se presentó (NO bloquea)
```

### Estados que **bloquean** disponibilidad (BR-R08)
`paid, confirmed, in_preparation, contract_signed, delivery_assigned, delivered, active, return_pending`

### Estados que **no bloquean**
`draft, pending_payment(*), cancelled, refunded, failed, expired, no_show`

> (*) `pending_payment` no bloquea de forma definitiva; puede sostener un *hold*
> temporal (soft hold) configurable (`settings.reservation_hold_minutes`) que
> expira y libera el cupo.

---

## 2. Transiciones válidas (resumen)

```txt
draft → pending_payment → paid → confirmed → in_preparation
      → contract_pending → contract_signed → delivery_assigned
      → delivered → active → return_pending → returned
      → inspection_pending → completed

cualquiera(no terminal) → cancelled (según política)
paid/confirmed → refunded (vía cancelación con reembolso)
delivery_assigned/delivered → no_show (si aplica)
pending_payment → expired (timeout)
```

Toda transición se registra en `reservation_status_logs` (from, to, by, reason).
La máquina de estados (`ReservationStateMachine`) rechaza transiciones inválidas.

---

## 3. Validación contra doble reserva (núcleo)

Solape de rangos (BR-R07):
```txt
conflicto  ⇔  new_start < existing_end  AND  new_end > existing_start
```

Pseudocódigo de la operación crítica (al pagar/confirmar):
```php
DB::transaction(function () use ($vehicleId, $start, $end, ...) {
    // Bloqueo pesimista sobre el vehículo
    $vehicle = Vehicle::where('id', $vehicleId)->lockForUpdate()->firstOrFail();

    // ¿Hay reservas BLOQUEANTES que solapen?
    $conflict = Reservation::where('vehicle_id', $vehicleId)
        ->whereIn('reservation_status', BLOCKING_STATES)
        ->where('start_datetime', '<', $end)
        ->where('end_datetime', '>', $start)
        ->lockForUpdate()
        ->exists();

    // ¿Hay bloqueos manuales que solapen?
    $blocked = VehicleAvailabilityBlock::where('vehicle_id', $vehicleId)
        ->where('start_datetime', '<', $end)
        ->where('end_datetime', '>', $start)
        ->exists();

    if ($conflict || $blocked) {
        throw new VehicleNotAvailableException();
    }

    // Registrar pago + pasar reserva a 'paid' (bloqueante) dentro de la MISMA transacción
    $reservation->update(['reservation_status' => 'paid', 'payment_status' => 'paid']);
});
```

Índice de apoyo: `(vehicle_id, start_datetime, end_datetime)`.

---

## 4. Flujos

### 4.0 Gate previo al pago (elegibilidad del cliente) — OBLIGATORIO
Antes de permitir el pago de una reserva, el sistema valida (si falla → 409/422, checkout bloqueado):
- **Edad ≥ 18** a la fecha de inicio de la reserva (BR-C08, contra `customers.birthdate`).
- **Licencia aprobada**: existe `customer_documents` con `type=license` y `status=approved` (BR-C09).
- (Recomendado) perfil mínimo completo (BR-C02).

### 4.1 Flujo normal (happy path)
```txt
draft → pending_payment → [gate de elegibilidad: edad≥18 + licencia aprobada]
→ [pago renta ok + validación de disponibilidad en transacción]
→ [hold del depósito (autorizado, no cobrado)] → paid
→ confirmed → in_preparation → contract_pending → contract_signed
→ delivery_assigned → delivered → active → return_pending → returned
→ inspection_pending → [depósito liberado] → completed → review habilitada
```

### 4.2 Flujo con pago fallido
```txt
pending_payment → [pago falla] → sigue pending_payment (fechas libres)
→ cliente reintenta → si expira el hold → expired
```

### 4.3 Flujo con cancelación
```txt
(estado no terminal) → cancel → aplica política →
   si hubo pago: dispara reembolso (total/parcial/nulo) → refunded|cancelled
   libera fechas
```

### 4.4 Flujo con reembolso
```txt
paid/confirmed → cancel con reembolso → RefundService → refunded
(o credit a wallet según preferencia/política)
```

### 4.5 Flujo con entrega
```txt
confirmed → delivery_assigned (asigna responsable) → delivered (inspección inicial firmada)
```

### 4.6 Flujo con inspección
```txt
delivered: inspección inicial obligatoria (fuel, km, fotos, firma)
returned: inspección final obligatoria → compara con inicial →
   sin daños: completed + libera/reembolsa depósito
   con daños/retraso: penalty + captura (parcial/total) de depósito → completed
```

### 4.7 Flujo con penalidad
```txt
inspección/retraso detecta cargo → penalty(pending) →
cobro vía depósito/wallet/método → penalty(charged) → completed
```

### 4.8 No-show
```txt
delivery_assigned/confirmed + cliente no aparece → no_show →
aplica política (retención parcial / cargo) → libera fechas
```

---

## 5. Reglas transaccionales (resumen)

- La revalidación de disponibilidad y el cambio a estado bloqueante son **atómicos** (misma transacción).
- Bloqueo pesimista (`lockForUpdate`) sobre el vehículo durante esa operación.
- Webhooks de pago son idempotentes; nunca crean doble reserva ni doble cobro.
- La expiración de `pending_payment`/`draft` la ejecuta el Scheduler (job) liberando cupos.
- Toda transición persiste en `reservation_status_logs`.

---

## 6. Diagrama de estados (texto)

```txt
[draft]
   │ checkout
   ▼
[pending_payment] ──timeout──► [expired]
   │ pago ok (tx)
   ▼
[paid] ──► [confirmed] ──► [in_preparation] ──► [contract_pending]
   │                                                   │ firma
   │ cancel+refund                                     ▼
   ▼                                            [contract_signed]
[refunded]/[cancelled]                                 │
                                                       ▼
                                     [delivery_assigned] ──► [delivered]
                                                       │ inspección inicial
                                                       ▼
                                                  [active] ──► [return_pending]
                                                                   │ devolución
                                                                   ▼
                                                              [returned] ──► [inspection_pending]
                                                                                   │ inspección final
                                                                                   ▼
                                                                              [completed]
```
