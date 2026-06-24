# 19 — ENVIRONMENT VARIABLES · RentCar E-Commerce

> Variables `.env`. **Nunca** commitear secretos reales; este archivo documenta
> claves y propósito. Mantener `.env.example` sincronizado.

---

## Plantilla base

```env
APP_NAME=RentCar
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Localización
APP_TIMEZONE=UTC
APP_LOCALE=es

# Base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rentcar
DB_USERNAME=root
DB_PASSWORD=

# Stripe
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# PayPal
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=
PAYPAL_CLIENT_SECRET=
PAYPAL_WEBHOOK_ID=

# Storage / Filesystem
FILESYSTEM_DISK=local
# Disco privado para documentos, contratos e inspecciones (no público)

# Colas y cache
QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Redis (recomendado en producción para colas/cache)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@rentcar.test
MAIL_FROM_NAME="${APP_NAME}"

# Sanctum / dominios SPA
SANCTUM_STATEFUL_DOMAINS=localhost
SESSION_DOMAIN=localhost

# Moneda e impuesto por defecto (también en settings) — estándar RD
DEFAULT_CURRENCY=DOP
TAX_RATE=0.18            # ITBIS 18%
```

---

## Notas por grupo

| Grupo | Notas |
|-------|-------|
| **APP** | En producción: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`. Generar `APP_KEY` con `php artisan key:generate`. |
| **DB** | MySQL/InnoDB. Usuario con privilegios mínimos necesarios. |
| **Stripe** | `STRIPE_KEY` (pk_), `STRIPE_SECRET` (sk_), `STRIPE_WEBHOOK_SECRET` (whsec_). Distintos por entorno. |
| **PayPal** | `PAYPAL_MODE=sandbox|live`. `PAYPAL_WEBHOOK_ID` para verificar firma. |
| **Filesystem** | `FILESYSTEM_DISK=local` en dev. Documentos/contratos/inspecciones SIEMPRE en disco privado. En prod considerar S3 privado. |
| **Queue** | `database` en MVP; `redis` recomendado en prod. |
| **Mail** | Proveedor SMTP real en prod (Mailgun, SES, Postmark...). |
| **Sanctum** | Configurar dominios stateful para el SPA/web; para la app móvil se usan tokens Bearer. |
| **Currency** | Fuente de verdad operativa en `settings`; esta var es default de arranque. |

---

## Variables futuras (placeholder, aún no usar)

```env
# Notificaciones (futuro)
WHATSAPP_API_TOKEN=
PUSH_FCM_SERVER_KEY=

# Almacenamiento en la nube (futuro)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# Verificación de identidad / KYC (futuro)
KYC_PROVIDER_KEY=
```

> Cualquier variable nueva debe documentarse aquí y reflejarse en `.env.example`.
