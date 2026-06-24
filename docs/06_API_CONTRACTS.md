# 06 — API CONTRACTS · RentCar E-Commerce

> Contratos REST iniciales. Base: `/api/v1`. Auth vía Sanctum (Bearer token).
> Crear/cambiar un endpoint obliga a actualizar este archivo.
> Formato de error uniforme (ver final). Dinero como string decimal `"120.00"`.

---

## Convenciones globales

- **Headers:** `Accept: application/json`, `Authorization: Bearer <token>` cuando aplica.
- **Paginación:** `?page=&per_page=` → `{ data: [], meta: { current_page, last_page, per_page, total } }`.
- **IDs:** enteros. **Fechas:** ISO 8601 UTC (`2026-06-24T14:00:00Z`).
- **Versionado:** prefijo `/api/v1`.

---

## Auth

### `POST /api/v1/auth/register`
- **Auth:** no. **Request:**
```json
{ "name":"Juan", "email":"j@x.com", "password":"secret123", "password_confirmation":"secret123" }
```
- **Validaciones:** name req; email req|unique; password req|min:8|confirmed.
- **Response 201:** `{ "user": {...}, "token":"<sanctum>" }`
- **Errores:** 422 validación, 429 rate limit.

### `POST /api/v1/auth/login`
- **Auth:** no. **Request:** `{ "email":"j@x.com", "password":"secret123" }`
- **Response 200:** `{ "user": {...}, "token":"<sanctum>" }`
- **Errores:** 422, 401 credenciales inválidas, 429.

### `POST /api/v1/auth/logout`
- **Auth:** sí. **Response 204.** Revoca el token actual.

### `GET /api/v1/auth/me`
- **Auth:** sí. **Response 200:** `{ "user": {...}, "roles":["customer"] }`

---

## Customers

### `GET /api/v1/customer/profile`
- **Auth:** customer. **Response 200:** perfil + `verification_status`.

### `PUT /api/v1/customer/profile`
- **Auth:** customer. **Request:** `{ first_name, last_name, phone, birthdate, address, city, country, license_number }`
- **Validaciones:** birthdate fecha pasada; phone formato; license opcional.
- **Response 200:** perfil actualizado. **Errores:** 422.

### `POST /api/v1/customer/documents`
- **Auth:** customer. **Request (multipart):** `type` (license|id_front|id_back|proof_address), `file` (pdf/jpg/png, max 5MB).
- **Validaciones:** mime e tamaño; type enum.
- **Response 201:** `{ document: { id, type, status:"pending" } }`
- **Errores:** 422 archivo inválido, 413 demasiado grande.

### `GET /api/v1/customer/reservations`
- **Auth:** customer. **Response 200:** lista paginada de reservas del cliente.

### `GET /api/v1/customer/wallet`
- **Auth:** customer. **Response 200:** `{ balance:"50.00", currency:"USD", status:"active" }`

---

## Vehicles / Catalog

### `GET /api/v1/vehicles`
- **Auth:** no (público). **Query:** `start_date, end_date, category, transmission, seats_min, price_min, price_max, location_id, sort, page, per_page`.
- **Comportamiento:** si vienen `start_date`/`end_date`, excluye vehículos con solape (BR-R07/R08) y bloqueos.
- **Response 200:** `{ data:[ { id, name, brand, model, category, transmission, seats, daily_price, primary_image, rating_avg, rating_count } ], meta:{...} }`

### `GET /api/v1/vehicles/{id}`
- **Auth:** no. **Response 200:** ficha completa (imágenes, features, reglas, price, rating).
- **Errores:** 404.

### `GET /api/v1/vehicles/{id}/availability`
- **Auth:** no. **Query:** `start_date, end_date`.
- **Response 200:** `{ available: true, conflicts: [], quote: { base_price:"120.00", days:2, deposit_amount:"100.00" } }`

---

## Reservations

### `POST /api/v1/reservations`
- **Auth:** customer. **Request:**
```json
{
  "vehicle_id": 12,
  "start_datetime":"2026-07-01T10:00:00Z",
  "end_datetime":"2026-07-03T10:00:00Z",
  "pickup_type":"airport", "pickup_address":"...", "pickup_latitude":..., "pickup_longitude":...,
  "return_type":"office", "return_address":"...",
  "insurance":false, "promo_code":null
}
```
- **Validaciones:** vehicle existe; end>start; rango disponible; pickup_type enum.
- **Comportamiento:** crea reserva `draft`/`pending_payment` con cotización (base, delivery, tax, deposit, total). NO bloquea aún definitivamente; la validación dura ocurre al pagar (transacción).
- **Response 201:** `{ reservation: { id, reservation_number, totals:{...}, payment_status:"pending", reservation_status:"pending_payment" } }`
- **Errores:** 409 no disponible, 422 validación.

### `GET /api/v1/reservations/{id}`
- **Auth:** customer dueño / admin. **Response 200:** detalle. **Errores:** 403, 404.

### `POST /api/v1/reservations/{id}/cancel`
- **Auth:** customer dueño / admin. **Request:** `{ reason }`.
- **Comportamiento:** aplica política de cancelación, libera fechas, dispara reembolso si corresponde.
- **Response 200:** `{ reservation_status:"cancelled", refund: {...}|null }`. **Errores:** 409 estado no cancelable.

### `POST /api/v1/reservations/{id}/confirm`
- **Auth:** admin (o sistema tras pago). **Comportamiento:** transición a `confirmed`.
- **Response 200.**

---

## Payments

### `POST /api/v1/payments/stripe/create-intent`
- **Auth:** customer. **Request:** `{ reservation_id, payment_type:"rent"|"deposit", save_method:false }`
- **Response 200:** `{ client_secret, payment_intent_id, amount, currency }`
- **Errores:** 409 reserva no pagable, 422.

### `POST /api/v1/payments/stripe/confirm`
- **Auth:** customer. **Request:** `{ payment_intent_id }`
- **Comportamiento:** confirma estado; la fuente de verdad final es el webhook.
- **Response 200:** `{ status:"paid"|"requires_action"|"processing" }`

### `POST /api/v1/payments/paypal/create-order`
- **Auth:** customer. **Request:** `{ reservation_id, payment_type }`
- **Response 200:** `{ order_id, approve_url }`

### `POST /api/v1/payments/paypal/capture-order`
- **Auth:** customer. **Request:** `{ order_id }`
- **Response 200:** `{ status:"paid", capture_id }`. **Errores:** 409, 422.

### `POST /api/v1/payments/webhooks/stripe`
- **Auth:** firma Stripe (`Stripe-Signature`). **Idempotente.**
- **Comportamiento:** valida firma, procesa `payment_intent.succeeded|payment_failed|charge.refunded`, actualiza estados, registra evento.
- **Response 200** (siempre 2xx si procesado/ignorado; 400 firma inválida).

### `POST /api/v1/payments/webhooks/paypal`
- **Auth:** verificación de firma PayPal (`PAYPAL-*` headers + verify API). **Idempotente.**
- **Comportamiento:** procesa `CHECKOUT.ORDER.APPROVED`, `PAYMENT.CAPTURE.COMPLETED|DENIED|REFUNDED`.
- **Response 200.**

---

## Delivery (zonas, puntos, ventanas y cotización)

### `GET /api/v1/delivery/zones`
- **Auth:** no (público). **Response 200:** zonas activas que verá el cliente en el mapa:
```json
{ "data": [ { "id":1, "name":"Centro", "color":"#2563EB", "allows_home_delivery":true, "polygon": { "type":"Polygon", "coordinates":[...] } } ] }
```

### `GET /api/v1/delivery/pickup-points`
- **Auth:** no. **Query:** `lat, lng` (opcional, para ordenar por cercanía).
- **Response 200:** `{ data:[ { id, name, address, latitude, longitude, fee, zone_id } ] }`

### `GET /api/v1/delivery/time-windows`
- **Auth:** no. **Query:** `zone_id, date`.
- **Response 200:** `{ data:[ { id, label, start_time, end_time, available:true } ] }` (filtra por capacidad/fecha).

### `POST /api/v1/delivery/quote`
- **Auth:** customer (o público). **Request:**
```json
{ "type":"home", "latitude":18.47, "longitude":-69.89 }
```
- **Comportamiento:** localiza la zona que contiene el punto; si `type=home` valida `allows_home_delivery` y `max_distance_km`; calcula tarifa `base_fee + max(0, distance_km - free_radius_km) * price_per_km`.
- **Response 200 (elegible):**
```json
{ "eligible":true, "zone_id":1, "distance_km":6.2, "fee":"8.00", "currency":"USD" }
```
- **Response 200 (no elegible):** `{ "eligible":false, "reason":"out_of_coverage", "suggested_pickup_points":[ ... ] }`
- **Errores:** 422 coordenadas inválidas.

---

## Wallet

### `GET /api/v1/wallet`
- **Auth:** customer. **Response 200:** `{ balance:"50.00", currency:"USD", status:"active" }`

### `GET /api/v1/wallet/transactions`
- **Auth:** customer. **Response 200:** lista paginada `{ id, type, amount, balance_after, description, created_at }`.

---

## Reviews

### `POST /api/v1/reservations/{id}/review`
- **Auth:** customer dueño de reserva `completed`. **Request:**
```json
{ "rating_vehicle":5,"rating_cleanliness":4,"rating_service":5,"rating_delivery":5,"rating_overall":5,"comment":"Excelente" }
```
- **Validaciones:** ratings 1..5; reserva completed y propia; no calificada antes.
- **Response 201:** `{ review: {...} }`. **Errores:** 403 no autorizada, 409 ya calificada, 422.

### `GET /api/v1/vehicles/{id}/reviews`
- **Auth:** no. **Response 200:** lista paginada de reviews visibles + `rating_avg`.

---

## Formato de error uniforme

```json
{
  "message": "Descripción legible",
  "errors": { "campo": ["detalle"] },
  "code": "RESERVATION_NOT_AVAILABLE"
}
```

| HTTP | Uso |
|------|-----|
| 400 | Petición malformada / firma webhook inválida |
| 401 | No autenticado |
| 403 | No autorizado (policy) |
| 404 | No encontrado |
| 409 | Conflicto (no disponible, estado inválido, ya calificado) |
| 413 | Archivo demasiado grande |
| 422 | Validación |
| 429 | Rate limit |
| 500 | Error interno |
