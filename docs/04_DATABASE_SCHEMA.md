# 04 — DATABASE SCHEMA · RentCar E-Commerce

> Esquema inicial (MySQL/InnoDB). **Crear cualquier tabla nueva obliga a
> actualizar este archivo.** Dinero: `decimal(12,2)` o `*_cents` entero; nunca float.
> Todas las tablas llevan `created_at`/`updated_at` salvo indicación. FKs con índices.

---

## Convenciones

- PK: `id` `bigUnsignedInteger` auto-incremental.
- Timestamps UTC.
- Borrado lógico (`deleted_at`) en entidades de negocio relevantes (vehicles, customers, reservations).
- Estados → columnas `enum`/`string` respaldadas por PHP Enums.
- Moneda: columna `currency` `char(3)` (ISO 4217), default desde `settings`.

---

## 1. `users`
**Propósito:** credenciales y acceso (clientes y staff/admin comparten tabla; el rol los distingue vía Spatie).
**Campos:** `id, name, email (unique), email_verified_at, password, two_factor_secret (null), two_factor_recovery_codes (null), remember_token`.
**Relaciones:** 1–1 `customers` (si es cliente); N–N roles/permisos (Spatie).
**Índices:** `email`.

## 2. `roles` / 3. `permissions` (Spatie)
**Propósito:** RBAC. Tablas estándar de spatie/laravel-permission: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`.
**Roles base:** `admin`, `staff`, `driver`, `customer`. Detalle en `11_SECURITY.md`.

## 4. `customers`
**Propósito:** perfil de cliente.
**Campos:** `id, user_id (FK→users, unique), first_name, last_name, phone, birthdate, address, city, country, license_number, verification_status (enum: unverified|pending|verified|rejected), notes, deleted_at`.
**Relaciones:** pertenece a `users`; tiene muchos `customer_documents`, `reservations`, `payments`; 1–1 `wallets`.
**Índices:** `user_id`, `verification_status`.
**Reglas:** ver BR-C01..C08.

## 5. `customer_documents`
**Propósito:** documentos subidos (privados).
**Campos:** `id, customer_id (FK), type (enum: license|id_front|id_back|proof_address|other), file_path (privado), original_name, mime, size, status (enum: pending|approved|rejected), reviewed_by (FK→users null), reviewed_at, expires_at`.
**Índices:** `customer_id`, `type`, `status`.
**Reglas:** storage privado, validación de tipo/tamaño. Ver `11_SECURITY.md`.

## 6. `vehicles`
**Propósito:** ficha del vehículo.
**Campos:** `id, name, brand, model, year, category (enum: economy|sedan|suv|luxury|van|...), transmission (enum: manual|automatic), seats, doors, fuel_type, color, plate (unique), vin, daily_price decimal(12,2), deposit_amount decimal(12,2) null, currency, mileage, location_id null, status (enum: available|reserved|rented|maintenance|blocked|out_of_service), description, rules (text/json), rating_avg decimal(3,2) default 0, rating_count int default 0, deleted_at`.
**Relaciones:** tiene muchos `vehicle_images`, `vehicle_features`, `vehicle_price_rules`, `vehicle_availability_blocks`, `reservations`, `reviews`.
**Índices:** `status`, `category`, `transmission`, `daily_price`, `location_id`, `plate`.
**Reglas:** BR-V01..V07. Disponibilidad NO se determina solo por `status`.

## 7. `vehicle_images`
**Propósito:** fotos del vehículo.
**Campos:** `id, vehicle_id (FK), path, is_primary bool, sort_order int, alt`.
**Índices:** `vehicle_id`, `(vehicle_id, sort_order)`.

## 8. `vehicle_features`
**Propósito:** características (GPS, A/C, Bluetooth...).
**Campos:** `id, vehicle_id (FK), name, icon null`. (Alternativa: tabla `features` + pivote.)
**Índices:** `vehicle_id`.

## 9. `vehicle_price_rules`
**Propósito:** precios por temporada / duración / fin de semana.
**Campos:** `id, vehicle_id (FK), type (enum: seasonal|weekend|min_days|promo), start_date null, end_date null, min_days null, price_modifier_type (enum: fixed|percent), price_modifier_value decimal(12,2), priority int`.
**Índices:** `vehicle_id`, `(start_date,end_date)`.

## 10. `vehicle_availability_blocks`
**Propósito:** bloqueos manuales de disponibilidad (mantenimiento, uso interno) **independientes** de reservas.
**Campos:** `id, vehicle_id (FK), start_datetime, end_datetime, reason (enum: maintenance|blocked|internal|other), created_by (FK→users)`.
**Índices:** `vehicle_id`, `(vehicle_id, start_datetime, end_datetime)`.
**Reglas:** participan en el cálculo de solape junto con reservas bloqueantes.

## 10b. `locations` (sucursales)
**Propósito:** sucursales/ubicaciones físicas (BR-L01..L03).
**Campos:** `id, name, type (branch|airport|other), address, city, latitude decimal(10,7), longitude decimal(10,7), phone, opening_hours (json null), is_active bool, created_at, updated_at`.
**Relaciones:** tiene muchos `vehicles`; referenciada en recogida/devolución de reservas.
**Índices:** `is_active`, `city`.
**Ejemplos RD:** Santo Domingo, Aeropuerto Las Américas (SDQ), Punta Cana (PUJ), Santiago (STI).

## 10c. `insurance_plans`
**Propósito:** catálogo de seguros/coberturas (BR-S01..S03).
**Campos:** `id, name (RC básico|CDW|Cobertura total|Asistencia), description, is_included bool (true para el básico), daily_price decimal(12,2), deductible_amount decimal(12,2) null, currency, is_active bool, sort_order int, created_at, updated_at`.
**Índices:** `is_active`.
**Reglas:** el básico (RC) va incluido; los opcionales suman `insurance_fee` a la reserva. El plan elegido se referencia desde `reservations`.

## 11. `reservations`
**Propósito:** reserva de un vehículo por un rango.
**Campos mínimos (obligatorios):**
```txt
id
reservation_number        (único, legible: RC-YYYYMMDD-XXXX)
customer_id               (FK)
vehicle_id                (FK)
pickup_location_id        (FK→locations null)
return_location_id        (FK→locations null)
insurance_plan_id         (FK→insurance_plans null)
start_datetime
end_datetime
pickup_type               (pickup_point|home|office|airport|hotel|custom)
pickup_address
pickup_latitude
pickup_longitude
return_type
return_address
return_latitude
return_longitude
base_price                decimal(12,2)
delivery_fee              decimal(12,2)
insurance_fee             decimal(12,2)
deposit_amount            decimal(12,2)
discount_amount           decimal(12,2)
tax_amount                decimal(12,2)
total_amount              decimal(12,2)
payment_status            (ver 09_PAYMENTS_WALLET)
reservation_status        (ver 10_RESERVATIONS_FLOW)
contract_status           (none|pending|signed)
created_at
updated_at
```
**Relaciones:** pertenece a `customers`, `vehicles`; tiene muchos `payments`, `payment_attempts`, `reservation_status_logs`; 1–1 `contracts`, `delivery_requests`; tiene `vehicle_inspections` (inicial/final), `reviews`, `invoices`, `penalties`.
**Índices:** `(vehicle_id, start_datetime, end_datetime)` para solape, `customer_id`, `reservation_status`, `payment_status`, `reservation_number`.
**Reglas:** BR-R01..R10. Validación de disponibilidad en transacción.

## 12. `reservation_status_logs`
**Propósito:** auditoría de cambios de estado de la reserva.
**Campos:** `id, reservation_id (FK), from_status, to_status, changed_by (FK→users null), reason, created_at`.
**Índices:** `reservation_id`.

## 13. `payments`
**Propósito:** pago confirmado/registrado por proveedor.
**Campos (obligatorios):**
```txt
id
reservation_id            (FK, null para top-ups de wallet)
customer_id               (FK)
provider                  (stripe|paypal|wallet|manual)
provider_subtype          (null|cash|bank_transfer)   -- para provider=manual
provider_payment_id
provider_order_id
provider_capture_id
amount                    decimal(12,2)
currency
status                    (ver estados de pago en 09)
payment_type              (rent|deposit|deposit_capture|penalty|wallet_topup|refund)
metadata                  (json)
paid_at
created_at
updated_at
```
**Índices:** `reservation_id`, `customer_id`, `provider`, `status`, `provider_payment_id`.
**Reglas:** BR-P01..P09. Sin datos de tarjeta reales.

## 14. `payment_attempts`
**Propósito:** registrar cada intento (incluye fallidos) para auditoría y soporte.
**Campos:** `id, reservation_id (FK null), customer_id (FK), provider, provider_reference, amount decimal(12,2), currency, status (initiated|requires_action|succeeded|failed), error_code, error_message, request_payload (json, sin datos sensibles), response_payload (json), created_at`.
**Índices:** `reservation_id`, `customer_id`, `provider`, `status`.

## 15. `payment_methods`
**Propósito:** método de pago tokenizado del cliente.
**Campos (obligatorios):**
```txt
id
customer_id               (FK)
provider                  (stripe|paypal)
provider_customer_id
provider_payment_method_id
brand                     (visa|mastercard|paypal|...)
last_four
exp_month
exp_year
is_default                bool
status                    (active|expired|removed)
created_at
updated_at
```
**Índices:** `customer_id`, `(customer_id, is_default)`.
**Reglas:** nunca PAN real; solo tokens. Ver `11_SECURITY.md`.

## 16. `wallets`
**Propósito:** monedero interno del cliente.
**Campos:** `id, customer_id (FK, unique), currency, balance decimal(12,2) default 0, status (active|frozen)`.
**Índices:** `customer_id`.
**Regla:** `balance` se reconcilia con la suma de `wallet_transactions`.

## 17. `wallet_transactions`
**Propósito:** libro mayor del monedero.
**Campos:** `id, wallet_id (FK), type (ver lista), amount decimal(12,2), balance_after decimal(12,2), reference_type (morph: reservation|payment|deposit|penalty|manual), reference_id, description, created_by (FK→users null), created_at`.
**Tipos:** `credit, debit, refund, deposit_hold, deposit_release, penalty_charge, promo_credit, manual_adjustment`.
**Índices:** `wallet_id`, `type`, `(reference_type, reference_id)`.

## 18. `deposit_transactions`
**Propósito:** ciclo de vida del depósito de seguridad.
**Campos:** `id, reservation_id (FK), customer_id (FK), provider, provider_reference (auth/charge id), type (hold|capture|partial_capture|release|charge), amount decimal(12,2), currency, status (authorized|captured|released|failed), reason, captured_amount decimal(12,2) null, expires_at, created_at, updated_at`.
**Índices:** `reservation_id`, `status`.
**Reglas:** BR-D01..D06.

## 19. `delivery_requests`
**Propósito:** logística de entrega y devolución.
**Campos:** `id, reservation_id (FK), delivery_zone_id (FK→delivery_zones null), pickup_point_id (FK→delivery_pickup_points null), delivery_time_window_id (FK→delivery_time_windows null), direction (pickup|return), type (pickup_point|home|office|airport|hotel|custom), address, latitude, longitude, distance_km decimal(8,2) null, fee decimal(12,2), scheduled_date, scheduled_window_start time null, scheduled_window_end time null, status (requested|assigned|in_transit|delivered|returned|cancelled), assigned_to (FK→users null), notes`.
**Índices:** `reservation_id`, `status`, `assigned_to`, `delivery_zone_id`, `pickup_point_id`, `delivery_time_window_id`.
**Reglas:** la `fee` se deriva de la zona + distancia (BR-E07) o del punto/oficina; `distance_km` se calcula desde el origen de la zona/sucursal hasta el domicilio. Ventana horaria desde `delivery_time_windows`. Ver BR-E00, BR-E06..E10.

## 19b. `delivery_zones`
**Propósito:** zonas geográficas de entrega configurables en mapa (geofence) — BR-E00/E07.
**Campos:**
```txt
id
name
description
polygon                 json (GeoJSON: coordenadas del polígono que dibuja el admin en el mapa)
color                   string (para pintar la zona en el mapa del cliente)
origin_latitude         decimal(10,7)   (punto de referencia para medir distancia)
origin_longitude        decimal(10,7)
allows_home_delivery    bool
base_fee                decimal(12,2)   (tarifa base de la zona)
free_radius_km          decimal(6,2)    (km sin recargo)
price_per_km            decimal(12,2)   (recargo por km excedente)
max_distance_km         decimal(6,2)    (máximo para entrega a domicilio)
currency
is_active               bool
sort_order              int
created_at / updated_at
```
**Índices:** `is_active`, `name`.
**Reglas:** el cliente solo ve zonas `is_active`. Un domicilio es elegible para `home` si sus coordenadas caen dentro del `polygon` de una zona con `allows_home_delivery = true`. Cálculo de tarifa: `base_fee + max(0, distance_km - free_radius_km) * price_per_km`, no disponible si `distance_km > max_distance_km`.

## 19c. `delivery_pickup_points`
**Propósito:** puntos de entrega en zonas comerciales / sucursales (BR-E08).
**Campos:** `id, delivery_zone_id (FK→delivery_zones null), name, address, latitude decimal(10,7), longitude decimal(10,7), fee decimal(12,2) default 0, is_active bool, opening_hours (json null), notes, sort_order int, created_at, updated_at`.
**Índices:** `delivery_zone_id`, `is_active`.
**Reglas:** se ofrecen al cliente los puntos activos dentro de zonas aceptadas, priorizando cercanía a su ubicación/domicilio.

## 19d. `delivery_time_windows`
**Propósito:** ventanas horarias de entrega configurables por el admin (BR-E09).
**Campos:** `id, delivery_zone_id (FK→delivery_zones null = global), label, start_time time, end_time time, days_of_week (json: [1..7] null = todos), capacity int null (máx. entregas por ventana/día), is_active bool, sort_order int, created_at, updated_at`.
**Índices:** `delivery_zone_id`, `is_active`.
**Reglas:** el cliente elige una ventana activa aplicable a la zona y fecha; si `capacity` está definida, se valida disponibilidad de cupo en esa ventana/fecha.

## 20. `vehicle_inspections`
**Propósito:** inspección de salida y retorno.
**Campos:** `id, reservation_id (FK), vehicle_id (FK), type (initial|final), fuel_level (0-100 o eighths), mileage int, damages (text/json), notes, signature_path (privado, null), accepted_by_customer bool, inspector_id (FK→users null), inspected_at`.
**Índices:** `reservation_id`, `vehicle_id`, `type`.
**Reglas:** BR-I01..I08.

## 21. `inspection_photos`
**Propósito:** evidencia fotográfica (privada).
**Campos:** `id, vehicle_inspection_id (FK), path, position (front|back|left|right|interior|damage|other), note`.
**Índices:** `vehicle_inspection_id`.

## 22. `contracts`
**Propósito:** contrato digital generado (PDF privado).
**Campos:** `id, reservation_id (FK, unique), number, file_path (privado), status (draft|pending|signed|void), signed_by_customer_at, signature_meta (json: ip, ua, hash), generated_by (FK→users null), created_at`.
**Índices:** `reservation_id`, `status`.

## 23. `reviews`
**Propósito:** calificaciones (1–5).
**Campos:** `id, reservation_id (FK, unique), customer_id (FK), vehicle_id (FK), rating_vehicle tinyint, rating_cleanliness tinyint, rating_service tinyint, rating_delivery tinyint, rating_overall tinyint, comment, status (visible|hidden), created_at`.
**Índices:** `vehicle_id`, `customer_id`, `reservation_id`.
**Reglas:** BR-RV01..RV05. Solo reservas `completed`.

## 24. `invoices`
**Propósito:** comprobante de la reserva.
**Campos:** `id, reservation_id (FK), number (unique), subtotal decimal(12,2), tax_amount, discount_amount, total decimal(12,2), currency, status (draft|issued|paid|void), issued_at, file_path null`.
**Índices:** `reservation_id`, `number`, `status`.

## 25. `refunds`
**Propósito:** reembolsos.
**Campos:** `id, payment_id (FK), reservation_id (FK null), provider, provider_refund_id, amount decimal(12,2), currency, reason, status (pending|succeeded|failed), processed_at, created_at`.
**Índices:** `payment_id`, `status`.

## 26. `penalties`
**Propósito:** cargos por daños, retraso, combustible, limpieza, etc.
**Campos:** `id, reservation_id (FK), type (late_return|fuel|damage|cleaning|smoking|other), amount decimal(12,2), currency, description, status (pending|charged|waived), source (deposit|wallet|manual), created_by (FK→users), created_at`.
**Índices:** `reservation_id`, `status`.

## 27. `notifications`
**Propósito:** notificaciones a usuarios (usa también tabla nativa de Laravel si se opta por DatabaseChannel).
**Campos:** `id, user_id (FK), channel (mail|database|push|whatsapp), type, title, body, data (json), read_at, created_at`.
**Índices:** `user_id`, `read_at`.

## 28. `settings`
**Propósito:** configuración general (key/value).
**Campos:** `id, key (unique), value (json/text), group, updated_by (FK→users null)`.
**Ejemplos (defaults RD):** `default_currency = DOP`, `tax_rate = 0.18` (ITBIS), `default_deposit`, `cancellation_policy` (escalonada 48h/24h), `reservation_hold_minutes`, `late_grace_minutes = 59`, `fuel_service_fee`, `young_driver_fee` (+ `young_driver_enabled = false`), `mileage_unlimited = true`, `deposit_mode = authorized`.

## 29. `audit_logs`
**Propósito:** auditoría general de acciones sensibles.
**Campos:** `id, user_id (FK null), action, auditable_type (morph), auditable_id, old_values (json), new_values (json), ip, user_agent, created_at`.
**Índices:** `(auditable_type, auditable_id)`, `user_id`, `action`.

---

## Diagrama de relaciones (resumen textual)

```txt
users 1—1 customers 1—1 wallets 1—* wallet_transactions
customers 1—* customer_documents
customers 1—* payment_methods
customers 1—* reservations *—1 vehicles
vehicles 1—* vehicle_images / vehicle_features / vehicle_price_rules / vehicle_availability_blocks
reservations 1—* payments / payment_attempts / reservation_status_logs / penalties
reservations 1—1 contracts / delivery_requests(*) / invoices
reservations 1—* vehicle_inspections 1—* inspection_photos
reservations 1—1 reviews
payments 1—* refunds
reservations 1—* deposit_transactions
```

> Doble reserva: el índice `(vehicle_id, start_datetime, end_datetime)` + la
> consulta de solape (`new_start < existing_end AND new_end > existing_start`)
> filtrando estados bloqueantes (BR-R08) y `vehicle_availability_blocks`, todo
> dentro de una transacción con `SELECT ... FOR UPDATE`. Ver `10_RESERVATIONS_FLOW.md`.
