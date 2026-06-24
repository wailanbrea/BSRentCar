# 12 — TESTING & QA · RentCar E-Commerce

> Pruebas mínimas obligatorias. Framework: Pest/PHPUnit + Laravel. Usar factories
> y seeders. Pagos con SDK en modo sandbox/mocks. Crear features obliga a añadir
> sus tests aquí.

---

## 1. Estrategia
- **Feature tests** para flujos de negocio (HTTP, BD real de test).
- **Unit tests** para servicios puros (pricing, disponibilidad, wallet, máquina de estados).
- **Mocks** para Stripe/PayPal en unit; sandbox real en pruebas de integración manuales.
- BD de test en transacciones (`RefreshDatabase`).
- Cobertura objetivo MVP: flujos críticos (auth, reservas, pagos, wallet, reviews).

## 2. Pruebas obligatorias (checklist)

### Auth & Customers
- [ ] Registro con datos válidos crea usuario + token.
- [ ] Registro inválido (email duplicado, password corta) → 422.
- [ ] Login correcto / incorrecto.
- [ ] Crear cliente (perfil) y actualizarlo.
- [ ] Subir documento (mime/tamaño válido / inválido).

### Vehicles & Catalog
- [ ] Crear vehículo (admin).
- [ ] Subir foto de vehículo + marcar principal.
- [ ] Listar catálogo.
- [ ] Filtrar por fecha (excluye no disponibles).
- [ ] Filtros combinados (categoría + transmisión + precio + pasajeros).
- [ ] Ver detalle de vehículo (404 si no existe).
- [ ] Cálculo de precio con `vehicle_price_rules`.

### Reservations (núcleo)
- [ ] Crear reserva válida (cotización correcta).
- [ ] **Evitar doble reserva** (rangos solapados con estado bloqueante → 409).
- [ ] Solape exacto en bordes (`new_start == existing_end` NO choca).
- [ ] Bloqueo manual (`vehicle_availability_blocks`) impide reserva.
- [ ] Expiración de `pending_payment` libera cupo.
- [ ] Transición de estado inválida es rechazada.

### Payments
- [ ] Pago Stripe exitoso → reserva `paid` + Payment `paid`.
- [ ] Pago Stripe fallido → Payment/Attempt `failed`, reserva sigue `pending_payment`.
- [ ] Pago PayPal exitoso (create-order + capture).
- [ ] Pago PayPal fallido.
- [ ] **Webhook Stripe** procesado e idempotente (mismo evento dos veces = un solo efecto).
- [ ] **Webhook PayPal** procesado e idempotente.
- [ ] Firma de webhook inválida → 400, sin efecto.
- [ ] No se almacenan datos de tarjeta reales (solo token).
- [ ] Reembolso total/parcial actualiza estado.

### Wallet & Deposits
- [ ] Crear wallet al registrar/primer uso.
- [ ] Registrar movimiento (credit/debit) con `balance_after` correcto.
- [ ] Reconciliación: suma de transacciones == balance.
- [ ] Autorizar depósito (hold).
- [ ] Capturar depósito parcial/total.
- [ ] Liberar depósito.

### Contracts, Deliveries, Inspections
- [ ] Generar contrato (PDF privado).
- [ ] Firmar/aceptar contrato (registra ip/ua/hash).
- [ ] Registrar entrega + asignar responsable + transición de estado.
- [ ] Registrar inspección inicial (fuel, km, fotos, firma).
- [ ] Registrar inspección final + derivar penalidad.

### Reviews
- [ ] Calificar reserva `completed` propia (1–5).
- [ ] **Impedir calificación no autorizada** (reserva no completed / no propia / ya calificada → 403/409).
- [ ] Recalcular `rating_avg`/`rating_count` del vehículo.

## 3. QA manual / Sandbox
- Probar 3DS en Stripe (`requires_action`).
- Probar aprobación PayPal (redirect/approve).
- Probar webhooks con CLI (`stripe listen`) y simulador PayPal.
- Probar URLs firmadas de documentos/contratos (acceso/expiración).

## 4. Datos de prueba
- Seeders: roles/permisos, admin demo, clientes demo, vehículos demo con fotos, settings base.
- Factories para todas las entidades de negocio.

## 5. CI (recomendado)
- Pipeline: `composer install` → `php artisan test` → Pint → (Larastan).
- Falla el build si fallan tests críticos de reservas/pagos.
