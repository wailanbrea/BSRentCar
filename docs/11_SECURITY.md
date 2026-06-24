# 11 — SECURITY · RentCar E-Commerce

> Lineamientos de seguridad obligatorios. Cambios obligan a registrar decisión en
> `14_DECISIONS_LOG.md`.

---

## 1. Transporte y red
- **HTTPS obligatorio** en todos los entornos públicos. Redirección 80→443, HSTS.
- Cabeceras de seguridad: `X-Content-Type-Options`, `X-Frame-Options`/CSP, `Referrer-Policy`.

## 2. Datos de pago
- **No se guardan tarjetas reales** (PAN/CVV). Solo tokens/IDs del proveedor (`payment_methods`).
- Tokenización vía Stripe Elements / SetupIntent y PayPal vault.
- Cumplir alcance PCI mínimo (SAQ-A): el navegador habla con el proveedor; el servidor nunca toca datos de tarjeta.

## 3. Archivos privados
- **Documentos** de cliente, **contratos** y **fotos de inspección** en disco **privado** (no `public`).
- Acceso solo vía URLs **firmadas temporales** y autorización por policy.
- **Validación de archivos:** tipo MIME real, extensión, tamaño máximo (p. ej. 5 MB), antivirus opcional, re-encode de imágenes para quitar metadatos/EXIF sensibles.

## 4. Validación de entrada
- Form Requests para toda entrada. Whitelist de campos.
- Prevención **SQL Injection:** Eloquent/Query Builder con bindings; nunca concatenar SQL.
- Prevención **XSS:** Blade escapa por defecto (`{{ }}`); evitar `{!! !!}` salvo contenido saneado.
- Prevención **CSRF:** token CSRF en formularios web; API con Sanctum/Bearer.
- Sanitización de uploads y de campos ricos.

## 5. Autenticación y sesión
- Sanctum para API (tokens Bearer con expiración/rotación).
- Política de contraseñas: mínimo 8, recomendado 12; bloqueo por intentos; hashing bcrypt/argon2 (default Laravel).
- **2FA obligatorio para `admin`** (TOTP). Recomendado para `staff`.
- Sesiones: expiración por inactividad, regeneración de id en login, invalidación en logout.
- Verificación de email para clientes.

## 6. Autorización
- **RBAC** con Spatie: roles `admin`, `staff`, `driver`, `customer`.
- Permisos granulares por acción (`reservations.manage`, `payments.refund`, ...). Ver `08_ADMIN_PANEL.md`.
- **Policies** en cada modelo sensible (Reservation, Payment, Document, Contract, Inspection, Wallet).
- Principio de menor privilegio; `customer` nunca accede al panel admin.

## 7. Rate limiting
- Throttling en `login`, `register`, `forgot-password`, webhooks y endpoints de pago.
- Límites por IP y por usuario. Respuestas 429 con `Retry-After`.

## 8. Webhooks (Stripe / PayPal)
- **Validar firma** (`Stripe-Signature` con `STRIPE_WEBHOOK_SECRET`; verificación de evento PayPal con `PAYPAL_WEBHOOK_ID`).
- **Idempotencia:** registrar `event_id` procesados; ignorar duplicados.
- No confiar en datos del cliente para confirmar pagos; la fuente de verdad es el webhook firmado.
- Endpoints de webhook excluidos de CSRF pero protegidos por firma.

## 9. Auditoría
- `audit_logs` para acciones sensibles (cambios de reserva, pagos, refunds, ajustes de wallet, verificación de documentos, cambios de settings).
- Registros financieros **append-only**.
- Incluir `user_id`, `ip`, `user_agent`, valores antes/después.

## 10. Backups y recuperación
- Backup diario de BD + storage, off-site, retención ≥ 7 días.
- Pruebas de restauración periódicas.
- Cifrado de backups.

## 11. Secretos y configuración
- Secretos solo en `.env` / gestor de secretos; **nunca** en el repo ni en logs.
- `APP_DEBUG=false` en producción.
- Rotación de claves de proveedores; claves de webhook distintas por entorno.

## 12. Protección de la app móvil futura
- Tokens Sanctum con expiración; refresh controlado.
- No exponer endpoints internos/admin a la app de cliente.

## 13. Checklist de release de seguridad
- [ ] HTTPS + HSTS activos
- [ ] `APP_DEBUG=false`
- [ ] Webhooks con firma validada e idempotentes
- [ ] Storage privado para documentos/contratos/inspecciones
- [ ] Rate limiting en auth y pagos
- [ ] 2FA admin activo
- [ ] Policies cubriendo entidades sensibles
- [ ] Backups verificados
- [ ] Sin secretos en el repo
