# 02 — BUSINESS RULES · RentCar E-Commerce

> Reglas de negocio. Cambiar cualquier regla aquí **obliga** a registrar la
> decisión en `14_DECISIONS_LOG.md`. Las dudas abiertas viven en `20_OPEN_QUESTIONS.md`.

---

## 1. Clientes

- **BR-C01** Un cliente debe tener una cuenta para reservar.
- **BR-C02** El cliente debe completar su perfil (datos personales mínimos) antes de pagar una reserva.
- **BR-C03** El cliente debe poder subir documentos (licencia de conducir, identificación).
- **BR-C04** El cliente tiene un estado de verificación: `unverified`, `pending`, `verified`, `rejected`.
- **BR-C05** El cliente **solo** puede calificar reservas en estado `completed` que le pertenezcan.
- **BR-C06** El cliente puede consultar su historial de reservas y de pagos.
- **BR-C07** El cliente tiene exactamente **una** wallet interna (creada al registrarse o al primer uso).
- **BR-C08** **Edad mínima para rentar: 18 años.** Se valida contra `birthdate` en el momento de crear/pagar la reserva (la edad cumplida debe ser ≥ 18 a la fecha de inicio). *Nota de mercado: la norma de la industria en RD es 21 años; por eso se contempla un recargo "conductor joven" para 18–24 (BR-X04), configurable y desactivable.*
- **BR-C09** **Licencia de conducir obligatoria y verificada antes de pagar.** El cliente debe haber subido su licencia y el admin haberla aprobado (`customer_documents.type = license`, `status = approved`) **antes** de poder completar el pago de una reserva. Sin licencia aprobada → checkout bloqueado.
- **BR-C10** La verificación de licencia/identidad es **manual** por parte del admin/staff en el MVP (KYC automatizado queda como alcance futuro).

## 2. Vehículos

- **BR-V01** La disponibilidad se determina por **rango de fechas**, no solo por un estado global.
- **BR-V02** Estados operativos del vehículo: `available`, `reserved`, `rented`, `maintenance`, `blocked`, `out_of_service`.
- **BR-V03** Cada vehículo tiene un **precio diario base** (`daily_price`) y opcionalmente reglas de precio por temporada/estancia (`vehicle_price_rules`).
- **BR-V04** Cada vehículo puede definir su **depósito propio** (`deposit_amount`); si no, se usa el depósito por defecto de `settings`.
- **BR-V05** Cada vehículo puede tener **reglas propias** (kilometraje, combustible, política de fumar/mascotas, edad mínima).
- **BR-V06** Un vehículo en `maintenance`, `blocked` u `out_of_service` no aparece como rentable aunque no tenga reservas.
- **BR-V07** Las fotos del vehículo se ordenan; debe existir una imagen principal (`is_primary`).

## 3. Reservas

- **BR-R01** Toda reserva tiene `start_datetime` y `end_datetime` (fecha **y** hora).
- **BR-R02** El sistema **debe evitar la doble reserva** del mismo vehículo en rangos solapados.
- **BR-R03** **Antes** de cobrar, se valida disponibilidad.
- **BR-R04** **Después** del pago exitoso, se bloquean las fechas (la reserva pasa a un estado bloqueante).
- **BR-R05** Una reserva tiene estados claros y transiciones controladas (ver `10_RESERVATIONS_FLOW.md`).
- **BR-R06** La validación final de disponibilidad ocurre **dentro de una transacción de BD** con bloqueo de fila, justo antes de marcar la reserva como pagada/confirmada.
- **BR-R07** Regla de solape: dos rangos chocan cuando `new_start < existing_end AND new_end > existing_start`.
- **BR-R08** Estados que **bloquean** disponibilidad: `paid, confirmed, in_preparation, contract_signed, delivery_assigned, delivered, active, return_pending`.
- **BR-R09** Estados que **no bloquean**: `cancelled, refunded, failed, expired, no_show`.
- **BR-R10** Una reserva `draft`/`pending_payment` puede expirar tras un tiempo configurable y liberar el cupo.

## 4. Pagos

- **BR-P01** **Nunca** se almacenan datos reales de tarjeta. Solo tokens/IDs del proveedor.
- **BR-P02** Se usan tokens de Stripe / PayPal (`payment_methods`, customer/vault IDs).
- **BR-P03** Se registra **cada intento** de pago (`payment_attempts`).
- **BR-P04** Se registra cada pago exitoso (`payments`, status `paid`).
- **BR-P05** Se registra cada pago fallido (status `failed`, con motivo).
- **BR-P06** Se registra cada reembolso (`refunds`).
- **BR-P07** Se registra siempre el **proveedor** usado (`provider`) y sus IDs externos.
- **BR-P08** Los montos se manejan en `decimal(12,2)` o centavos; **nunca float** en cálculos. Ver `09_PAYMENTS_WALLET.md`.
- **BR-P09** Los webhooks deben validar firma, ser idempotentes y no duplicar pagos.
- **BR-P10** **Métodos aceptados (estándar RD):** tarjeta de crédito/débito (Stripe), PayPal, **efectivo en oficina** y **transferencia bancaria**. Efectivo y transferencia se registran como `provider = manual` (subtipo `cash` / `bank_transfer`) y requieren **verificación/conciliación manual del admin** antes de marcar la reserva como pagada.
- **BR-P11** **El depósito de seguridad SIEMPRE requiere tarjeta de crédito (hold).** No se acepta depósito en efectivo (norma de las rentadoras en RD). Aunque la renta se pague en efectivo/transferencia, el cliente debe presentar una tarjeta válida para el hold del depósito.
- **BR-P12** **Impuesto: ITBIS 18%** sobre el servicio de alquiler (configurable en `settings.tax_rate = 0.18`). El alquiler de vehículos está gravado con ITBIS según DGII. La factura debe desglosar subtotal + ITBIS.
- **BR-P13** **Moneda base: DOP (peso dominicano).** Mostrar/operar en DOP por defecto; soporte USD opcional a futuro (multi-moneda no incluido en MVP).

## 5. Depósitos de seguridad

- **BR-D00** **Modo por defecto: depósito AUTORIZADO (hold), no cobrado.** Al confirmar la reserva se crea una autorización (hold) por el monto del depósito; solo se captura (total/parcial) si hay daños, retrasos o penalidades; en caso normal se libera al cierre.
- **BR-D01** El sistema soporta depósito **cobrado** (capturado) como modo alternativo/fallback (p. ej. si el proveedor no permite hold).
- **BR-D02** El sistema soporta depósito **autorizado** (hold) cuando el proveedor lo permita (Stripe auth/capture; PayPal authorization). **Es el modo predeterminado** (BR-D00).
- **BR-D03** El sistema soporta **liberación** del depósito (`deposit_release` / void / refund).
- **BR-D04** El sistema soporta **captura parcial o total** del depósito por daños, retrasos o penalidades.
- **BR-D05** Toda operación de depósito genera una transacción trazable (`deposit_transactions` + reflejo en wallet si aplica).
- **BR-D06** Una autorización tiene vencimiento (p. ej. ~7 días en tarjeta); el sistema debe capturar o liberar antes.

## 6. Entregas

- **BR-E01** El cliente elige tipo de entrega: `pickup_point` (punto comercial), `home` (domicilio), `office` (sucursal), `airport`, `hotel`, `custom`.
- **BR-E02** La entrega puede tener **costo adicional** (`delivery_fee`).
- **BR-E00** **La entrega se gestiona por ZONAS geográficas configurables en mapa.** El admin dibuja/edita zonas (polígonos geofence) en `delivery_zones`. Cada zona define si admite entrega a domicilio, su tarifa base y parámetros de distancia. **El cliente ve en un mapa las zonas permitidas.**
- **BR-E06** **Entrega a domicilio (`home`):** disponible **solo si la dirección/coordenadas del cliente caen dentro de una zona activa que admite domicilio** (`allows_home_delivery = true`). Si no, se le ofrecen **puntos de entrega comerciales** (`delivery_pickup_points`) cercanos dentro de zonas aceptadas.
- **BR-E07** **Cobro por distancia:** la entrega a domicilio cobra un **monto extra según la distancia** desde el origen (sucursal/punto de referencia de la zona) hasta el domicilio. Modelo: `base_fee` de la zona + `price_per_km` por cada km que exceda un `free_radius_km`, hasta un `max_distance_km` (fuera de ese máximo no hay entrega a domicilio). Parámetros configurables por zona / settings.
- **BR-E08** **Puntos de entrega (zonas comerciales):** catálogo `delivery_pickup_points` con ubicación; el cliente puede recoger en el punto comercial más cercano dentro de una zona aceptada. La entrega en punto/oficina puede tener tarifa 0 o reducida.
- **BR-E09** **Ventanas horarias configurables:** el admin define rangos de hora de entrega (`delivery_time_windows`, p. ej. 09:00–12:00) por día/zona, con capacidad opcional. El cliente elige una ventana disponible al reservar la entrega.
- **BR-E10** Si el domicilio cae **fuera de toda zona**, la entrega a domicilio **no está disponible**; el cliente debe elegir un punto comercial o la oficina (o solicitar cotización manual al admin).
- **BR-E03** La entrega tiene estado: `requested`, `assigned`, `in_transit`, `delivered`, `returned`, `cancelled`.
- **BR-E04** La entrega puede tener un **responsable asignado** (staff/driver).
- **BR-E05** Misma lógica aplica a la devolución (`return_type`, `return_address`).

## 7. Inspecciones

- **BR-I01** Inspección **inicial obligatoria** antes de entregar el vehículo.
- **BR-I02** Inspección **final obligatoria** al devolver.
- **BR-I03** La inspección registra **fotos** (storage privado).
- **BR-I04** Registra nivel de **combustible**.
- **BR-I05** Registra **kilometraje**.
- **BR-I06** Registra **daños** observados.
- **BR-I07** Guarda **firma o aceptación** del cliente y/o del responsable.
- **BR-I08** Diferencias entre inspección inicial y final pueden originar penalidades o captura de depósito.

## 8. Calificaciones

- **BR-RV01** Solo clientes con una reserva `completed` propia pueden calificar **esa** reserva.
- **BR-RV02** Calificación en escala **1 a 5 estrellas**.
- **BR-RV03** Puede desglosarse en: vehículo, limpieza, servicio, entrega y experiencia general.
- **BR-RV04** Una reserva se califica **una sola vez** (editable según política).
- **BR-RV05** El admin puede moderar/ocultar calificaciones que violen políticas.

## 9. Cancelaciones y penalidades (política base RD, configurable en `settings`)

- **BR-X01** Una reserva puede cancelarse según ventana de tiempo configurable.
- **BR-X02** La penalidad por cancelación, retraso, combustible faltante o daños se registra en `penalties` y puede cobrarse vía wallet/depósito.
- **BR-X03** **Política de cancelación escalonada (default):**
  - **≥ 48 h** antes del inicio → reembolso **100%** (gratis).
  - **48 h – 24 h** antes → penalidad de **1 día** de renta (reembolso del resto).
  - **< 24 h o no-show** → penalidad de **1 día** de renta o **20%** del total (el mayor); el resto se reembolsa.
  - Valores configurables en `settings.cancellation_policy`.
- **BR-X04** **Recargo "conductor joven" (18–24 años):** cargo adicional diario configurable (`settings.young_driver_fee`), activable/desactivable. Por defecto desactivado salvo que el negocio lo habilite.

## 10. Operación de la renta (estándar RD)

- **BR-O01** **Kilometraje ILIMITADO por defecto** (estándar de las rentadoras en RD). El vehículo puede sobreescribir con un límite + tarifa por km excedente vía `vehicle.rules` / `vehicle_price_rules`.
- **BR-O02** **Combustible: política "lleno a lleno" (full-to-full).** Se entrega con el tanque lleno y debe devolverse lleno. Si se devuelve con menos, se cobra el combustible faltante (según nivel registrado en inspección final) **+ cargo por servicio de reabastecimiento** (`settings.fuel_service_fee`). Genera `penalty` tipo `fuel`.
- **BR-O03** **Retraso en la devolución:** periodo de gracia configurable (`settings.late_grace_minutes`, default 59 min). Superado el margen, se cobra **un día adicional** de renta por cada periodo iniciado. Genera `penalty` tipo `late_return`.

## 11. Seguro (estándar RD)

- **BR-S01** **Seguro de responsabilidad civil básico incluido** en toda renta (obligatorio en RD para circular).
- **BR-S02** **Coberturas opcionales** seleccionables en checkout: CDW (Colisión / reducción de deducible), cobertura ampliada/total, asistencia en carretera. Catálogo en `insurance_plans`; el monto se suma como `insurance_fee`.
- **BR-S03** El plan elegido y su deducible quedan registrados en la reserva y en el contrato.

## 12. Sucursales (locations)

- **BR-L01** El sistema soporta **múltiples sucursales/ubicaciones** (`locations`): p. ej. Santo Domingo, Aeropuerto Las Américas (SDQ), Punta Cana (PUJ), Santiago (STI).
- **BR-L02** Un vehículo pertenece a una sucursal (`vehicles.location_id`). La oficina de recogida/devolución puede ser una sucursal.
- **BR-L03** **Multiempresa (multi-tenant): NO en el MVP.** Se opera como una sola empresa con varias sucursales; el diseño no debe impedir un futuro multi-tenant (evitar supuestos que lo bloqueen).

## 13. Wallet (resumen — detalle en `09_PAYMENTS_WALLET.md`)

- **BR-W01** Cada cliente tiene una wallet con saldo en una moneda base.
- **BR-W02** Todo movimiento de saldo se registra en `wallet_transactions` con un tipo válido.
- **BR-W03** El saldo de la wallet **deriva** de la suma de transacciones (fuente de verdad = transacciones, no un campo mutable suelto sin respaldo).
